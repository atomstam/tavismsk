#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
php2shell_scan_v2.py — ตรวจความเสี่ยง PHP2Shell เชิงป้องกัน (สอดคล้อง updater v2)
CVE-2026-109618 / CVE-2026-109619

ปรับจาก php2shell_scan.py:
  - เกณฑ์เดียวกับ freebsd_php2shell_update_v2.py
  - แนะนำเวอร์ชันปลอดภัยที่ใกล้สาขาปัจจุบัน (8.2.30 → 8.2.35 ไม่กระโดด 8.3)
  - โหมด --local เทียบ CLI / FPM / โมดูลเว็บ
  - เลือก IP แบบโต้ตอบได้เหมือนเดิม
  - ไม่ส่ง exploit / payload

ตัวอย่าง:
  python3 php2shell_scan_v2.py --local
  python3 php2shell_scan_v2.py 192.168.1.10
  python3 php2shell_scan_v2.py --targets-file hosts.txt --json report.json
"""

from __future__ import annotations

import argparse
import json
import os
import re
import shutil
import socket
import ssl
import subprocess
import sys
import urllib.error
import urllib.request
from dataclasses import asdict, dataclass, field
from typing import Dict, List, Optional, Sequence, Tuple


SCRIPT_NAME = "php2shell_scan_v2.py"
SCRIPT_VERSION = "2.1.1"
UPDATER_V2 = "freebsd_php2shell_update_v2.py"

SAFE_MIN: Dict[Tuple[int, int], Tuple[int, int, int]] = {
    (8, 5): (8, 5, 12),
    (8, 4): (8, 4, 27),
    (8, 3): (8, 3, 36),
    (8, 2): (8, 2, 35),
}

PHP_VER_RE = re.compile(r"PHP/?\s*(\d+)\.(\d+)\.(\d+)", re.I)
DEFAULT_TARGETS: Tuple[str, ...] = ()


class Color:
    if sys.stdout.isatty() and os.environ.get("NO_COLOR") is None:
        RESET = "\033[0m"
        BOLD = "\033[1m"
        RED = "\033[31m"
        GREEN = "\033[32m"
        YELLOW = "\033[33m"
        CYAN = "\033[36m"
        DIM = "\033[2m"
    else:
        RESET = BOLD = RED = GREEN = YELLOW = CYAN = DIM = ""


def log(msg: str, level: str = "info") -> None:
    prefix = {
        "info": f"{Color.CYAN}[*]{Color.RESET}",
        "ok": f"{Color.GREEN}[+]{Color.RESET}",
        "warn": f"{Color.YELLOW}[!]{Color.RESET}",
        "err": f"{Color.RED}[-]{Color.RESET}",
        "step": f"{Color.BOLD}[>]{Color.RESET}",
    }.get(level, "[*]")
    print(f"{prefix} {msg}")


@dataclass
class ScanResult:
    target: str
    url: str = ""
    reachable: bool = False
    php_version: Optional[str] = None
    recommended: Optional[str] = None
    same_branch: bool = True
    server_header: str = ""
    powered_by: str = ""
    status: str = "unknown"
    min_safe: Optional[str] = None
    note: str = ""
    evidence: List[str] = field(default_factory=list)
    cli_version: Optional[str] = None
    fpm_version: Optional[str] = None
    mismatch: bool = False


def parse_tuple(text: str) -> Optional[Tuple[int, int, int]]:
    m = PHP_VER_RE.search(text or "")
    if not m:
        return None
    return int(m.group(1)), int(m.group(2)), int(m.group(3))


def closest_safe(
    version: Optional[Tuple[int, int, int]],
    server_header: str = "",
) -> Tuple[str, Optional[str], bool, str]:
    """คืน (status, recommended, same_branch, note)"""
    if version is None:
        server = (server_header or "").lower()
        if "nginx" in server or "apache" in server:
            which = "nginx" if "nginx" in server else "Apache"
            return (
                "hidden",
                None,
                True,
                f"{which} ไม่โชว์เวอร์ชัน PHP (expose_php=Off / php-fpm) — "
                f"บนเซิร์ฟเวอร์รัน {SCRIPT_NAME} --local หรือ {UPDATER_V2} --check "
                "(HIDDEN ไม่ใช่ล้ม)",
            )
        return (
            "unknown",
            None,
            True,
            "อ่านเวอร์ชันไม่ได้ — ตรวจ FPM บนเครื่องด้วย "
            f"--local หรือ {UPDATER_V2} --check",
        )
    branch = (version[0], version[1])
    vtext = ".".join(map(str, version))
    need = SAFE_MIN.get(branch)
    if need:
        rec = ".".join(map(str, need))
        if version >= need:
            return ("safe", rec, True, f"PHP {vtext} ผ่านเกณฑ์สาขาเดิม (>= {rec})")
        return (
            "vulnerable",
            rec,
            True,
            f"PHP {vtext} ต่ำกว่าเกณฑ์ — อัปในสาขาเดิมเป็น {rec} (ไม่กระโดดสาขา)",
        )
    if branch < (8, 2):
        return (
            "vulnerable",
            "8.2.35",
            False,
            f"PHP {vtext} เป็น EOL — จะอัปเป็น 8.2.35 (ใกล้ที่สุดที่มีแพตช์ ไม่แพตช์ 7.4 ต่อ)",
        )
    return ("unknown", None, True, f"ไม่รู้เกณฑ์ของสาขา {branch[0]}.{branch[1]}")


def normalize_targets(raw: str) -> List[Tuple[str, int, str]]:
    raw = raw.strip()
    if not raw:
        return []
    if raw.startswith("http://") or raw.startswith("https://"):
        m = re.match(r"^(https?)://([^/:]+)(?::(\d+))?", raw)
        if not m:
            return []
        scheme, host, port = m.group(1), m.group(2), m.group(3)
        port_i = int(port) if port else (443 if scheme == "https" else 80)
        return [(host, port_i, scheme)]
    if ":" in raw and raw.count(":") == 1:
        host, port = raw.split(":", 1)
        try:
            port_i = int(port)
        except ValueError:
            return [(raw, 443, "https"), (raw, 80, "http")]
        scheme = "https" if port_i in (443, 8443) else "http"
        return [(host, port_i, scheme)]
    return [(raw, 443, "https"), (raw, 80, "http")]


def http_get_headers(host: str, port: int, scheme: str, path: str, timeout: float) -> Optional[dict]:
    url = f"{scheme}://{host}:{port}{path}"
    ctx = None
    if scheme == "https":
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
    req = urllib.request.Request(
        url,
        method="GET",
        headers={
            "User-Agent": f"{SCRIPT_NAME}/{SCRIPT_VERSION} (defensive version check)",
            "Accept": "*/*",
            "Connection": "close",
        },
    )
    try:
        with urllib.request.urlopen(req, timeout=timeout, context=ctx) as resp:
            headers = {k.lower(): v for k, v in resp.getheaders()}
            return {"url": url, "status": resp.status, "headers": headers}
    except urllib.error.HTTPError as exc:
        headers = {k.lower(): v for k, v in (exc.headers.items() if exc.headers else [])}
        return {"url": url, "status": exc.code, "headers": headers}
    except (urllib.error.URLError, socket.timeout, ssl.SSLError, OSError):
        return None


def parse_php_from_headers(headers: dict) -> Tuple[Optional[Tuple[int, int, int]], str, str]:
    server = headers.get("server", "") or ""
    powered = headers.get("x-powered-by", "") or ""
    for src in (powered, server):
        ver = parse_tuple(src)
        if ver:
            return ver, server, powered
    return None, server, powered


def fill_eval(res: ScanResult, version: Optional[Tuple[int, int, int]], server_header: str = "") -> None:
    status, rec, same, note = closest_safe(version, server_header)
    res.status = status
    res.recommended = rec
    res.same_branch = same
    res.min_safe = rec
    res.note = note
    if version:
        res.php_version = ".".join(map(str, version))


def scan_target(target: str, paths: Sequence[str], timeout: float) -> ScanResult:
    res = ScanResult(target=target)
    for host, port, scheme in normalize_targets(target):
        for path in paths:
            got = http_get_headers(host, port, scheme, path, timeout)
            if not got:
                continue
            res.reachable = True
            res.url = got["url"]
            version, server, powered = parse_php_from_headers(got["headers"])
            res.server_header = server
            res.powered_by = powered
            if server:
                res.evidence.append(f"Server: {server}")
            if powered:
                res.evidence.append(f"X-Powered-By: {powered}")
            res.evidence.append(f"HTTP {got['status']} @ {got['url']}")
            fill_eval(res, version, server)
            if version is not None or res.status == "hidden":
                return res
    if res.reachable:
        return res
    res.status = "unreachable"
    res.note = "เชื่อมต่อไม่ได้ — ตรวจ IP/พอร์ต/ไฟร์วอลล์"
    return res


def run_out(cmd: Sequence[str]) -> str:
    try:
        proc = subprocess.run(
            list(cmd),
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            timeout=20,
            check=False,
        )
        return proc.stdout or ""
    except (OSError, subprocess.TimeoutExpired):
        return ""


def local_scan() -> ScanResult:
    res = ScanResult(target="local")
    log("โหมด --local: เช็ก php / php-fpm ตาม SAPI ที่เว็บใช้", "step")
    cli = shutil.which("php")
    fpm = (
        shutil.which("php-fpm")
        or shutil.which("php82-fpm")
        or shutil.which("php83-fpm")
        or shutil.which("php-fpm8.3")
        or shutil.which("php8.3-fpm")
        or shutil.which("php-fpm8.2")
        or shutil.which("php8.2-fpm")
    )
    judged: List[Tuple[str, Optional[Tuple[int, int, int]]]] = []
    if cli:
        out = run_out([cli, "-v"])
        ver = parse_tuple(out)
        res.cli_version = ".".join(map(str, ver)) if ver else None
        judged.append(("cli", ver))
        res.evidence.append(out.splitlines()[0] if out else f"{cli} ไม่ตอบเวอร์ชัน")
        log(f"php -v => {res.cli_version or '?'}", "info")
    else:
        res.evidence.append("ไม่พบไบนารี php")
    if fpm:
        out = run_out([fpm, "-v"])
        ver = parse_tuple(out)
        res.fpm_version = ".".join(map(str, ver)) if ver else None
        judged.append(("fpm", ver))
        res.evidence.append(out.splitlines()[0] if out else f"{fpm} ไม่ตอบเวอร์ชัน")
        log(f"php-fpm -v => {res.fpm_version or '?'}", "info")
    else:
        res.evidence.append("ไม่พบไบนารี php-fpm")

    if shutil.which("systemctl"):
        for unit in ("php-fpm", "php8.3-fpm", "php8.2-fpm", "httpd", "apache2", "nginx"):
            out = run_out(["systemctl", "is-active", unit]).strip()
            if out and out not in ("inactive", "unknown") and "not-found" not in out:
                res.evidence.append(f"{unit}={out}")
    for bin_name in ("httpd", "apachectl", "apache24", "apache2"):
        path = shutil.which(bin_name)
        if not path:
            continue
        out = run_out([path, "-M"])
        php_lines = [ln.strip() for ln in out.splitlines() if re.search(r"\bphp", ln, re.I)]
        if php_lines:
            res.evidence.append(f"{bin_name} -M: " + "; ".join(php_lines[:4]))
        break

    if not judged:
        fill_eval(res, None)
        return res
    worst = "safe"
    notes = []
    rec = None
    same = True
    pick = judged[-1][1] or judged[0][1]
    for kind, ver in judged:
        status, recommended, same_b, note = closest_safe(ver)
        notes.append(f"{kind}: {note}")
        rec = recommended or rec
        same = same and same_b
        if status == "vulnerable":
            worst = "vulnerable"
        elif status == "unknown" and worst == "safe":
            worst = "unknown"
    fill_eval(res, pick)
    res.status = worst
    res.recommended = rec
    res.same_branch = same
    res.note = " | ".join(notes)
    if res.cli_version and res.fpm_version and res.cli_version != res.fpm_version:
        res.mismatch = True
        res.note += " | CLI ≠ FPM — แพตช์ฝั่งที่เว็บใช้"
    return res


def print_result(res: ScanResult) -> None:
    color = {
        "safe": Color.GREEN,
        "vulnerable": Color.RED,
        "hidden": Color.CYAN,
        "unknown": Color.YELLOW,
        "unreachable": Color.DIM,
    }.get(res.status, "")
    label = {
        "safe": "SAFE",
        "vulnerable": "VULNERABLE",
        "hidden": "HIDDEN",
        "unknown": "UNKNOWN",
        "unreachable": "UNREACHABLE",
    }.get(res.status, res.status.upper())
    print("-" * 70)
    extra = f"  (PHP {res.php_version})" if res.php_version else ""
    print(f"{color}{Color.BOLD}{label:<12}{Color.RESET} {res.target}{extra}")
    if res.url:
        print(f"             url: {res.url}")
    if res.cli_version:
        print(f"             cli: {res.cli_version}")
    if res.fpm_version:
        print(f"             fpm: {res.fpm_version}")
    if res.recommended:
        branch_note = "สาขาเดิม" if res.same_branch else "ต้องเปลี่ยนสาขา"
        print(f"             แนะนำอัปเป็น: {res.recommended} ({branch_note})")
    if res.mismatch:
        print(f"             {Color.YELLOW}CLI ≠ FPM{Color.RESET}")
    for item in res.evidence:
        print(f"             {Color.DIM}{item}{Color.RESET}")
    if res.note:
        print(f"             {res.note}")


def interactive_pick(targets_file: Optional[str]) -> List[str]:
    menu: List[str] = list(DEFAULT_TARGETS)
    if targets_file and os.path.isfile(targets_file):
        with open(targets_file, encoding="utf-8") as fh:
            for ln in fh:
                ln = ln.strip()
                if ln and not ln.startswith("#") and ln not in menu:
                    menu.append(ln)
    print(f"{Color.BOLD}=== php2shell_scan_v2 — เลือกเป้าหมาย ==={Color.RESET}")
    print("  0) --local เครื่องนี้")
    if menu:
        for i, host in enumerate(menu, 1):
            print(f"  {i}) {host}")
        print("  a) สแกนทุกตัวในรายการ")
    print("  หรือพิมพ์ IP/โฮสต์ เช่น 192.168.1.10")
    try:
        ans = input("เลือก > ").strip()
    except EOFError:
        return []
    if not ans:
        return []
    if ans == "0":
        return ["--local--"]
    if ans.lower() == "a" and menu:
        return menu
    if ans.isdigit() and menu:
        idx = int(ans)
        if 1 <= idx <= len(menu):
            return [menu[idx - 1]]
        log("หมายเลขไม่อยู่ในช่วง", "err")
        return []
    return [part.strip() for part in ans.split(",") if part.strip()]


def build_parser() -> argparse.ArgumentParser:
    p = argparse.ArgumentParser(
        prog=SCRIPT_NAME,
        description="ตรวจความเสี่ยง PHP2Shell จากเวอร์ชัน (ไม่โจมตี) — สอดคล้อง updater v2",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__,
    )
    p.add_argument("targets", nargs="*", help="IP/โฮสต์/URL")
    p.add_argument("--local", action="store_true", help="ตรวจเครื่องนี้")
    p.add_argument("--targets-file", help="ไฟล์รายชื่อ host")
    p.add_argument("--path", default="/")
    p.add_argument("--paths", default="", help="เส้นทางเพิ่ม คั่นด้วย comma")
    p.add_argument("--timeout", type=float, default=7.0)
    p.add_argument("--json", metavar="FILE")
    p.add_argument("--version", action="version", version=f"{SCRIPT_NAME} {SCRIPT_VERSION}")
    return p


def main(argv: Optional[List[str]] = None) -> int:
    args = build_parser().parse_args(argv)
    log(f"{SCRIPT_NAME} v{SCRIPT_VERSION} — ตรวจเวอร์ชันเชิงป้องกัน ไม่ส่ง payload", "step")
    log("เกณฑ์: 8.5.12 / 8.4.27 / 8.3.36 / 8.2.35 — เลือกสาขาเดิมที่ใกล้ปัจจุบัน", "info")

    paths = [args.path]
    if args.paths:
        for part in args.paths.split(","):
            part = part.strip()
            if part and part not in paths:
                paths.append(part if part.startswith("/") else "/" + part)

    results: List[ScanResult] = []
    if args.local:
        results.append(local_scan())
        print_result(results[-1])

    targets: List[str] = list(args.targets)
    if args.targets_file and os.path.isfile(args.targets_file):
        with open(args.targets_file, encoding="utf-8") as fh:
            for ln in fh:
                ln = ln.strip()
                if ln and not ln.startswith("#"):
                    targets.append(ln)
    if not targets and not args.local:
        if sys.stdin.isatty():
            targets = interactive_pick(args.targets_file)
        if not targets:
            log("ไม่มีเป้าหมาย — ระบุ IP หรือใช้ --local", "err")
            return 1

    for target in targets:
        if target == "--local--":
            res = local_scan()
        else:
            log(f"ตรวจ {target}", "info")
            res = scan_target(target, paths, args.timeout)
        results.append(res)
        print_result(res)

    print("=" * 70)
    counts = {k: 0 for k in ("safe", "vulnerable", "hidden", "unknown", "unreachable")}
    for item in results:
        counts[item.status] = counts.get(item.status, 0) + 1
    log(
        f"สรุป: SAFE={counts['safe']} VULNERABLE={counts['vulnerable']} "
        f"HIDDEN={counts['hidden']} UNKNOWN={counts['unknown']} "
        f"UNREACHABLE={counts['unreachable']}",
        "step",
    )
    recs = sorted({item.recommended for item in results if item.recommended and item.status == "vulnerable"})
    if recs:
        log("เวอร์ชันที่แนะนำ (ใกล้สาขาปัจจุบัน): " + ", ".join(recs), "warn")
        log(f"บนเซิร์ฟเวอร์จริง: sudo python3 {UPDATER_V2} --check แล้วค่อย --apply --yes", "err")
    if counts.get("hidden"):
        log(
            "เจอเว็บที่ซ่อนเวอร์ชัน (nginx/Apache+FPM) — "
            "ตรวจ FPM บนเครื่องด้วย --local ไม่ใช่จาก header",
            "info",
        )
    if any(item.mismatch for item in results):
        log("มีเครื่องที่ CLI กับ FPM คนละเวอร์ชัน — แพตช์ฝั่งเว็บ", "warn")
    if any(not item.same_branch and item.status == "vulnerable" for item in results):
        log("บางเครื่องต้องเปลี่ยนสาขา — ใช้ --allow-branch-change บน updater v2 เท่านั้น", "warn")

    if args.json:
        payload = {
            "script": SCRIPT_NAME,
            "version": SCRIPT_VERSION,
            "safe_min": {f"{a}.{b}": f"{x}.{y}.{z}" for (a, b), (x, y, z) in SAFE_MIN.items()},
            "results": [asdict(item) for item in results],
            "summary": counts,
        }
        with open(args.json, "w", encoding="utf-8") as fh:
            json.dump(payload, fh, ensure_ascii=False, indent=2)
        log(f"เขียนรายงาน: {args.json}", "ok")

    if counts["vulnerable"]:
        return 2
    if counts["unknown"] or counts["unreachable"]:
        return 3
    return 0  # SAFE หรือ HIDDEN (nginx ซ่อนเวอร์ชัน) ไม่ถือว่าล้ม


if __name__ == "__main__":
    try:
        sys.exit(main())
    except KeyboardInterrupt:
        print()
        sys.exit(130)
