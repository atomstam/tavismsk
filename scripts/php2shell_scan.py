#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
php2shell_scan.py — ตัวตรวจความเสี่ยง PHP2Shell (เชิงป้องกัน / ไม่โจมตี)
CVE-2026-109618 / CVE-2026-109619

สคริปต์นี้ "ตรวจเวอร์ชัน" ของเซิร์ฟเวอร์ที่คุณดูแล เพื่อบอกว่ายังเสี่ยง
PHP2Shell อยู่หรือไม่ โดย:

  - อ่านเฉพาะ banner เวอร์ชัน PHP จาก HTTP header (Server / X-Powered-By)
  - เทียบกับเวอร์ชันขั้นต่ำที่ปลอดภัยตามประกาศ PHP
  - รายงานผล SAFE / VULNERABLE / UNKNOWN พร้อมคำแนะนำ

!!! สิ่งที่สคริปต์นี้ "ไม่ทำ" โดยเจตนา (เพื่อความปลอดภัย/ถูกกฎหมาย) !!!
  - ไม่ส่ง exploit / payload โจมตีใด ๆ
  - ไม่ทำ memory corruption / heap grooming / ไม่ทำให้ worker ล่ม
  - ไม่พยายามรันคำสั่งบนเครื่องเป้าหมาย
มันคือ "เครื่องตรวจสุขภาพ" ไม่ใช่เครื่องมือเจาะระบบ

ตัวอย่าง:
  # โหมดโต้ตอบ (เลือก/พิมพ์ IP เป้าหมาย)
  python3 php2shell_scan.py

  # ระบุเป้าหมายตรง ๆ
  python3 php2shell_scan.py 192.168.1.10
  python3 php2shell_scan.py https://reportduty.example.ac.th

  # หลายเป้าหมายจากไฟล์ (บรรทัดละ 1 host)
  python3 php2shell_scan.py --targets-file hosts.txt --json report.json

หมายเหตุ: ถ้าเซิร์ฟเวอร์ตั้ง expose_php=Off / ใช้ php-fpm จะได้ผล HIDDEN
ไม่ใช่ UNKNOWN — ให้ไปตรวจ FPM บนเครื่องด้วย php2shell_scan_v2.py --local
หรือ freebsd_php2shell_update.py --check
"""

from __future__ import annotations

import argparse
import json
import os
import re
import socket
import ssl
import sys
import urllib.error
import urllib.request
from dataclasses import dataclass, field, asdict
from typing import Dict, List, Optional, Tuple

SCRIPT_NAME = "php2shell_scan.py"
SCRIPT_VERSION = "1.1.1"

# (major, minor) -> minimum safe (major, minor, patch)  [upstream PHP]
SAFE_MIN: Dict[Tuple[int, int], Tuple[int, int, int]] = {
    (8, 5): (8, 5, 12),
    (8, 4): (8, 4, 27),
    (8, 3): (8, 3, 36),
    (8, 2): (8, 2, 35),
}

PHP_VER_RE = re.compile(r"PHP/(\d+)\.(\d+)\.(\d+)", re.I)
# Default hosts shown in the interactive menu (edit to your estate)
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
    server_header: str = ""
    powered_by: str = ""
    status: str = "unknown"  # safe | vulnerable | unknown | unreachable
    min_safe: Optional[str] = None
    note: str = ""
    evidence: List[str] = field(default_factory=list)


def normalize_targets(raw: str) -> List[Tuple[str, int, str]]:
    """
    Turn a user string into (host, port, scheme) candidates.
    Accepts: '1.2.3.4', 'host:8443', 'https://host/path', 'http://host'.
    When scheme/port unknown, we try https:443 then http:80.
    """
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
    # host[:port]
    if ":" in raw and raw.count(":") == 1:
        host, port = raw.split(":", 1)
        try:
            port_i = int(port)
        except ValueError:
            return [(raw, 443, "https"), (raw, 80, "http")]
        scheme = "https" if port_i in (443, 8443) else "http"
        return [(host, port_i, scheme)]
    # bare host/ip: try https first, then http
    return [(raw, 443, "https"), (raw, 80, "http")]


def http_head_version(host: str, port: int, scheme: str, path: str, timeout: float) -> Optional[dict]:
    """
    Make a single benign GET and return headers. No payload, no exploitation.
    Returns dict(headers=..., status=...) or None if unreachable.
    """
    url = f"{scheme}://{host}:{port}{path}"
    ctx = None
    if scheme == "https":
        ctx = ssl.create_default_context()
        # We only read a banner; don't fail the scan on cert issues.
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
    except urllib.error.HTTPError as e:
        # Even error responses carry Server / X-Powered-By headers
        headers = {k.lower(): v for k, v in (e.headers.items() if e.headers else [])}
        return {"url": url, "status": e.code, "headers": headers}
    except (urllib.error.URLError, socket.timeout, ssl.SSLError, OSError):
        return None


def parse_php_version(headers: dict) -> Tuple[Optional[Tuple[int, int, int]], str, str]:
    server = headers.get("server", "") or ""
    powered = headers.get("x-powered-by", "") or ""
    for src in (powered, server):
        m = PHP_VER_RE.search(src)
        if m:
            return (int(m.group(1)), int(m.group(2)), int(m.group(3))), server, powered
    return None, server, powered


def evaluate(
    version: Optional[Tuple[int, int, int]],
    server_header: str = "",
) -> Tuple[str, Optional[str], str]:
    if version is None:
        server = (server_header or "").lower()
        if "nginx" in server or "apache" in server:
            which = "nginx" if "nginx" in server else "Apache"
            return (
                "hidden",
                None,
                f"{which} ไม่โชว์เวอร์ชัน PHP ใน header (expose_php=Off / php-fpm) — "
                "บนเซิร์ฟเวอร์รัน php2shell_scan_v2.py --local หรือ "
                "freebsd_php2shell_update.py --check เพื่อดูเวอร์ชัน FPM (HIDDEN ไม่ใช่ล้ม)",
            )
        return (
            "unknown",
            None,
            "อ่านเวอร์ชันจาก header ไม่ได้ (อาจตั้ง expose_php=Off) — "
            "ตรวจบนเครื่องด้วย freebsd_php2shell_update.py --check",
        )
    branch = (version[0], version[1])
    need = SAFE_MIN.get(branch)
    vtext = ".".join(map(str, version))
    if need is None:
        if branch < (8, 2):
            return (
                "vulnerable",
                "8.2.35",
                f"PHP {vtext} เป็นสาขา EOL — จะอัปเป็น 8.2.35 (ใกล้ที่สุดที่มีแพตช์)",
            )
        return ("unknown", None, f"ไม่รู้เกณฑ์ของสาขา {branch[0]}.{branch[1]}")
    need_text = ".".join(map(str, need))
    if version >= need:
        return ("safe", need_text, f"PHP {vtext} ผ่านเกณฑ์ (>= {need_text})")
    return ("vulnerable", need_text, f"PHP {vtext} ต่ำกว่าเกณฑ์ — ต้องอัปเดตเป็น >= {need_text}")


def scan_target(target: str, path: str, timeout: float) -> ScanResult:
    res = ScanResult(target=target)
    for host, port, scheme in normalize_targets(target):
        got = http_head_version(host, port, scheme, path, timeout)
        if not got:
            continue
        res.reachable = True
        res.url = got["url"]
        version, server, powered = parse_php_version(got["headers"])
        res.server_header = server
        res.powered_by = powered
        if server:
            res.evidence.append(f"Server: {server}")
        if powered:
            res.evidence.append(f"X-Powered-By: {powered}")
        res.evidence.append(f"HTTP {got['status']} @ {got['url']}")
        if version is not None:
            res.php_version = ".".join(map(str, version))
        status, need_text, note = evaluate(version, server)
        res.status = status
        res.min_safe = need_text
        res.note = note
        return res  # first reachable endpoint wins
    res.status = "unreachable"
    res.note = "เชื่อมต่อไม่ได้ (ลองทั้ง https:443 และ http:80) — ตรวจ IP/พอร์ต/ไฟร์วอลล์"
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
    print(f"{color}{Color.BOLD}{label:<12}{Color.RESET} {res.target}"
          + (f"  (PHP {res.php_version})" if res.php_version else ""))
    if res.url:
        print(f"             url: {res.url}")
    for e in res.evidence:
        print(f"             {Color.DIM}{e}{Color.RESET}")
    if res.note:
        print(f"             {res.note}")


def interactive_pick(targets_file: Optional[str]) -> List[str]:
    menu: List[str] = list(DEFAULT_TARGETS)
    if targets_file and os.path.isfile(targets_file):
        with open(targets_file, encoding="utf-8") as f:
            for ln in f:
                ln = ln.strip()
                if ln and not ln.startswith("#") and ln not in menu:
                    menu.append(ln)

    print(f"{Color.BOLD}=== php2shell_scan — เลือกเป้าหมาย ==={Color.RESET}")
    if menu:
        for i, h in enumerate(menu, 1):
            print(f"  {i}) {h}")
        print("  a) สแกนทุกตัวในรายการ")
    print("  หรือพิมพ์ IP/โฮสต์เอง เช่น 192.168.1.10 หรือ https://host")
    try:
        ans = input("เลือก > ").strip()
    except EOFError:
        return []
    if not ans:
        return []
    if ans.lower() == "a" and menu:
        return menu
    if ans.isdigit() and menu:
        idx = int(ans)
        if 1 <= idx <= len(menu):
            return [menu[idx - 1]]
        log("หมายเลขไม่อยู่ในช่วง", "err")
        return []
    # treat as a typed host (allow comma-separated)
    return [t.strip() for t in ans.split(",") if t.strip()]


def build_parser() -> argparse.ArgumentParser:
    p = argparse.ArgumentParser(
        prog=SCRIPT_NAME,
        description="ตรวจความเสี่ยง PHP2Shell จาก banner เวอร์ชัน (ไม่โจมตี)",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__,
    )
    p.add_argument("targets", nargs="*", help="IP/โฮสต์/URL เป้าหมาย (เว้นว่าง = โหมดโต้ตอบ)")
    p.add_argument("--targets-file", help="ไฟล์รายชื่อ host (บรรทัดละ 1)")
    p.add_argument("--path", default="/", help="เส้นทางที่จะ GET (ค่าเริ่มต้น /)")
    p.add_argument("--timeout", type=float, default=7.0, help="วินาที timeout ต่อการเชื่อมต่อ")
    p.add_argument("--json", metavar="FILE", help="เขียนผลเป็น JSON")
    p.add_argument("--version", action="version", version=f"%(prog)s {SCRIPT_VERSION}")
    return p


def main(argv: Optional[List[str]] = None) -> int:
    args = build_parser().parse_args(argv)

    log(f"{SCRIPT_NAME} v{SCRIPT_VERSION} — ตรวจเวอร์ชันเชิงป้องกัน (ไม่ส่ง payload โจมตี)", "step")

    targets: List[str] = list(args.targets)
    if args.targets_file and os.path.isfile(args.targets_file):
        with open(args.targets_file, encoding="utf-8") as f:
            for ln in f:
                ln = ln.strip()
                if ln and not ln.startswith("#"):
                    targets.append(ln)

    if not targets:
        if sys.stdin.isatty():
            targets = interactive_pick(args.targets_file)
        if not targets:
            log("ไม่มีเป้าหมาย — ระบุ IP/โฮสต์ หรือใช้ --targets-file", "err")
            return 1

    results: List[ScanResult] = []
    for t in targets:
        log(f"ตรวจ {t}", "info")
        res = scan_target(t, args.path, args.timeout)
        results.append(res)
        print_result(res)

    # summary
    print("=" * 70)
    counts = {k: 0 for k in ("safe", "vulnerable", "hidden", "unknown", "unreachable")}
    for r in results:
        counts[r.status] = counts.get(r.status, 0) + 1
    log(
        f"สรุป: SAFE={counts['safe']} VULNERABLE={counts['vulnerable']} "
        f"HIDDEN={counts['hidden']} UNKNOWN={counts['unknown']} "
        f"UNREACHABLE={counts['unreachable']}",
        "step",
    )
    if counts["vulnerable"]:
        log("พบเครื่องที่ยังเสี่ยง — อัปเดต PHP ด้วย freebsd_php2shell_update_v2.py --apply --yes", "err")
    if counts["hidden"]:
        log(
            "เจอเว็บที่ซ่อนเวอร์ชัน PHP (nginx/Apache+FPM) — "
            "ตรวจ FPM บนเครื่องด้วย php2shell_scan_v2.py --local หรือ updater --check",
            "info",
        )
    if counts["unknown"]:
        log("บางเครื่องซ่อนเวอร์ชัน — ตรวจบนเครื่องด้วย freebsd_php2shell_update_v2.py --check", "warn")

    if args.json:
        payload = {
            "script": SCRIPT_NAME,
            "version": SCRIPT_VERSION,
            "results": [asdict(r) for r in results],
            "summary": counts,
        }
        with open(args.json, "w", encoding="utf-8") as f:
            json.dump(payload, f, ensure_ascii=False, indent=2)
        log(f"เขียนรายงาน: {args.json}", "ok")

    if counts["vulnerable"]:
        return 2
    if counts["unknown"] or counts["unreachable"]:
        return 3
    return 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    except KeyboardInterrupt:
        print()
        sys.exit(130)
