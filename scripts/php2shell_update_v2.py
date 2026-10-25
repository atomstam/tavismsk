#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
php2shell_update_v2.py — อัปเดต PHP เชิงป้องกันตามคู่มือ CVE-2026-109618 / 109619

ทำตาม 4 ขั้นตอนในโพสต์ (ตรวจบน EL8 / ใช้ได้กับ RHEL, Alma, Rocky, Ubuntu, Debian, FreeBSD):
  1) เช็กเวอร์ชัน: php -v และ php-fpm -v
  2) อัปเดตแพ็กเกจ PHP (dnf / apt / pkg ตาม OS)
  3) restart + enable php-fpm และเว็บเซิร์ฟเวอร์
  4) ตรวจผล: เวอร์ชันปลอดภัย + php-fpm active + โมดูล PHP ถูกโหลด

เวอร์ชันขั้นต่ำที่ปลอดภัย (upstream PHP):
  8.5.12 / 8.4.27 / 8.3.36 / 8.2.35
  สาขาเก่ากว่า 8.2 ไม่มีแพตช์ — ต้องย้ายสาขา

สคริปต์นี้ไม่ส่ง exploit / payload / ไม่ทำ PoC

บน FreeBSD (เซิร์ฟเวอร์จริง) สคริปต์นี้จะส่งต่อไปที่
freebsd_php2shell_update_v2.py ซึ่งยึดสาขาเดิมและประเมินผลกระทบก่อนอัป

ตัวอย่าง:
  sudo python3 php2shell_update_v2.py --check
  sudo python3 php2shell_update_v2.py --all --yes
  sudo python3 php2shell_update_v2.py --all --dry-run
  sudo python3 php2shell_update_v2.py --update --restart --verify --yes
"""

from __future__ import annotations

import argparse
import os
import platform
import re
import shutil
import subprocess
import sys
from dataclasses import dataclass, field
from pathlib import Path
from typing import Dict, List, Optional, Sequence, Tuple


SCRIPT_NAME = "php2shell_update_v2.py"
SCRIPT_VERSION = "2.0.1"

SAFE_MIN: Dict[Tuple[int, int], Tuple[int, int, int]] = {
    (8, 5): (8, 5, 12),
    (8, 4): (8, 4, 27),
    (8, 3): (8, 3, 36),
    (8, 2): (8, 2, 35),
}

VERSION_RE = re.compile(r"(?:PHP\s+)?(\d+)\.(\d+)\.(\d+)", re.I)
OS_RELEASE = Path("/etc/os-release")


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
class Version:
    major: int
    minor: int
    patch: int

    def as_tuple(self) -> Tuple[int, int, int]:
        return (self.major, self.minor, self.patch)

    def branch(self) -> Tuple[int, int]:
        return (self.major, self.minor)

    def __str__(self) -> str:
        return f"{self.major}.{self.minor}.{self.patch}"


@dataclass
class OsInfo:
    family: str  # rhel | debian | freebsd | macos | other
    id: str
    pretty: str
    pkg: str  # dnf | apt | pkg | unknown


@dataclass
class BinVersion:
    kind: str
    path: str
    version: Optional[Version]
    raw: str
    status: str  # safe | vulnerable | eol | unknown | missing
    need: str = ""


@dataclass
class Plan:
    dry_run: bool = False
    yes: bool = False
    actions: List[str] = field(default_factory=list)


def parse_version(text: str) -> Optional[Version]:
    m = VERSION_RE.search(text or "")
    if not m:
        return None
    return Version(int(m.group(1)), int(m.group(2)), int(m.group(3)))


def classify(ver: Optional[Version]) -> Tuple[str, str]:
    if ver is None:
        return "unknown", ""
    need = SAFE_MIN.get(ver.branch())
    if need is None:
        if ver.branch() < (8, 2):
            return "eol", "8.2.35+"
        return "unknown", ""
    need_text = ".".join(map(str, need))
    if ver.as_tuple() >= need:
        return "safe", need_text
    return "vulnerable", need_text


def run(cmd: Sequence[str], dry_run: bool = False, timeout: int = 300) -> Tuple[int, str]:
    shown = " ".join(cmd)
    if dry_run:
        log(f"DRY-RUN: {shown}", "warn")
        return 0, ""
    try:
        proc = subprocess.run(
            list(cmd),
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            timeout=timeout,
            check=False,
        )
        return proc.returncode, proc.stdout or ""
    except (OSError, subprocess.TimeoutExpired) as exc:
        return 1, str(exc)


def which_all(*names: str) -> List[str]:
    found: List[str] = []
    for name in names:
        path = shutil.which(name)
        if path and path not in found:
            found.append(path)
    return found


def read_os_release() -> Dict[str, str]:
    data: Dict[str, str] = {}
    if not OS_RELEASE.is_file():
        return data
    for line in OS_RELEASE.read_text(encoding="utf-8", errors="replace").splitlines():
        if "=" not in line or line.startswith("#"):
            continue
        key, val = line.split("=", 1)
        data[key.strip()] = val.strip().strip('"')
    return data


def detect_os() -> OsInfo:
    sysname = platform.system().lower()
    rel = read_os_release()
    os_id = (rel.get("ID") or "").lower()
    like = (rel.get("ID_LIKE") or "").lower()
    pretty = rel.get("PRETTY_NAME") or platform.platform()

    if sysname == "darwin":
        pkg = "brew" if shutil.which("brew") else "unknown"
        return OsInfo("macos", "darwin", pretty, pkg)
    if sysname == "freebsd" or os_id == "freebsd":
        return OsInfo("freebsd", "freebsd", pretty, "pkg")
    rhelish = {"rhel", "centos", "almalinux", "rocky", "ol", "fedora", "amzn"}
    if os_id in rhelish or "rhel" in like or "fedora" in like or "centos" in like:
        pkg = "dnf" if shutil.which("dnf") else ("yum" if shutil.which("yum") else "unknown")
        return OsInfo("rhel", os_id or "rhel", pretty, pkg)
    debianish = {"ubuntu", "debian", "linuxmint", "pop"}
    if os_id in debianish or "debian" in like or "ubuntu" in like:
        return OsInfo("debian", os_id or "debian", pretty, "apt")
    if shutil.which("dnf") or shutil.which("yum"):
        return OsInfo("rhel", os_id or "unknown", pretty, "dnf" if shutil.which("dnf") else "yum")
    if shutil.which("apt-get"):
        return OsInfo("debian", os_id or "unknown", pretty, "apt")
    if shutil.which("pkg"):
        return OsInfo("freebsd", os_id or "freebsd", pretty, "pkg")
    return OsInfo("other", os_id or sysname, pretty, "unknown")


def version_of(binary: str) -> Tuple[Optional[Version], str]:
    code, out = run([binary, "-v"], timeout=20)
    text = (out or "").strip()
    if code != 0 and not text:
        code, out = run([binary, "--version"], timeout=20)
        text = (out or "").strip()
    return parse_version(text), text.splitlines()[0] if text else ""


def collect_bins() -> List[BinVersion]:
    rows: List[BinVersion] = []
    cli_candidates = which_all("php", "php8.5", "php8.4", "php8.3", "php8.2", "php82", "php83", "php84", "php85")
    fpm_candidates = which_all(
        "php-fpm",
        "php-fpm8.5",
        "php-fpm8.4",
        "php-fpm8.3",
        "php-fpm8.2",
        "php8.5-fpm",
        "php8.4-fpm",
        "php8.3-fpm",
        "php8.2-fpm",
        "php82-fpm",
        "php83-fpm",
        "php84-fpm",
        "php85-fpm",
    )
    extra_cli = [
        "/Applications/XAMPP/xamppfiles/bin/php",
        "/usr/local/opt/php@8.2/bin/php",
        "/usr/local/opt/php@8.3/bin/php",
        "/usr/local/opt/php@8.4/bin/php",
        "/opt/homebrew/opt/php@8.2/bin/php",
        str(Path.home() / ".local/php-override/php"),
        str(Path.home() / ".local/php-8.2.35/bin/php"),
    ]
    extra_globs = [
        "/usr/sbin/php-fpm",
        "/usr/sbin/php-fpm8.*",
        "/usr/local/sbin/php-fpm",
        "/usr/local/opt/php@8.*/sbin/php-fpm",
        "/opt/homebrew/opt/php@8.*/sbin/php-fpm",
        str(Path.home() / ".local/php-8.*/sbin/php-fpm"),
        "/opt/remi/php*/root/usr/sbin/php-fpm",
    ]
    for path in extra_cli:
        if os.path.isfile(path) and os.access(path, os.X_OK) and path not in cli_candidates:
            cli_candidates.append(path)
    import glob as _glob

    for pattern in extra_globs:
        for path in _glob.glob(pattern):
            if os.path.isfile(path) and os.access(path, os.X_OK) and path not in fpm_candidates:
                fpm_candidates.append(path)

    seen = set()
    for kind, paths in (("cli", cli_candidates), ("fpm", fpm_candidates)):
        for path in paths:
            if path in seen:
                continue
            seen.add(path)
            ver, raw = version_of(path)
            status, need = classify(ver)
            rows.append(BinVersion(kind, path, ver, raw, status, need))
    if not rows:
        rows.append(BinVersion("cli", "php", None, "", "missing"))
        rows.append(BinVersion("fpm", "php-fpm", None, "", "missing"))
    return rows


def print_bin_table(rows: List[BinVersion]) -> None:
    log("ขั้นตอน 1 — เช็กเวอร์ชัน CLI และ FPM", "step")
    if not rows:
        log("ไม่พบไบนารี php / php-fpm", "err")
        return
    for row in rows:
        color = {
            "safe": Color.GREEN,
            "vulnerable": Color.RED,
            "eol": Color.RED,
            "unknown": Color.YELLOW,
            "missing": Color.DIM,
        }.get(row.status, "")
        ver = str(row.version) if row.version else "-"
        extra = f"  ต้อง >= {row.need}" if row.need and row.status in ("vulnerable", "eol") else ""
        log(f"{row.kind:<4} {row.path}  PHP {ver}  [{row.status}]{extra}", "info")
        if row.raw:
            print(f"      {Color.DIM}{row.raw}{Color.RESET}")
        if color:
            pass


def confirm(plan: Plan, question: str) -> bool:
    if plan.yes or plan.dry_run:
        return True
    if not sys.stdin.isatty():
        log("ไม่มี --yes และไม่ใช่ TTY — หยุดไว้", "err")
        return False
    try:
        ans = input(f"{question} [y/N] ").strip().lower()
    except EOFError:
        return False
    return ans in ("y", "yes")


def update_commands(osinfo: OsInfo, rows: List[BinVersion]) -> List[List[str]]:
    cmds: List[List[str]] = []
    if osinfo.pkg == "dnf":
        cmds.append(["dnf", "update", "php*", "-y"])
    elif osinfo.pkg == "yum":
        cmds.append(["yum", "update", "php*", "-y"])
    elif osinfo.pkg == "apt":
        cmds.append(["apt-get", "update"])
        branches = sorted({f"{r.version.major}.{r.version.minor}" for r in rows if r.version})
        if branches:
            for branch in branches:
                cmds.append(["apt-get", "install", "--only-upgrade", "-y", f"php{branch}*"])
        else:
            cmds.append(["apt-get", "install", "--only-upgrade", "-y", "php*"])
    elif osinfo.pkg == "pkg":
        cmds.append(["pkg", "update"])
        cmds.append(["pkg", "upgrade", "-y", "php*"])
    elif osinfo.pkg == "brew":
        branches = sorted({r.version.branch() for r in rows if r.version})
        if not branches or any(b < (8, 2) for b in branches):
            cmds.append(["brew", "install", "php@8.2"])
        else:
            # คงสาขาเดิมที่ใกล้ที่สุดที่มีแพตช์ (8.2.x → php@8.2)
            want = max(b for b in branches if b in SAFE_MIN) if any(b in SAFE_MIN for b in branches) else (8, 2)
            formula = f"php@{want[0]}.{want[1]}"
            cmds.append(["brew", "install", formula])
            cmds.append(["brew", "upgrade", formula])
    return cmds


def list_unit_names() -> List[str]:
    code, out = run(["systemctl", "list-unit-files", "--type=service", "--no-legend", "--no-pager"], timeout=30)
    if code != 0:
        return []
    names = []
    for line in out.splitlines():
        name = line.split()[0] if line.strip() else ""
        if name.endswith(".service"):
            names.append(name[:-8])
        elif name:
            names.append(name)
    return names


def pick_services() -> Tuple[List[str], List[str]]:
    """คืน (fpm_units, web_units) ที่น่าจะรีสตาร์ท"""
    units = list_unit_names()
    fpm_prefer = [
        "php-fpm",
        "php8.5-fpm",
        "php8.4-fpm",
        "php8.3-fpm",
        "php8.2-fpm",
        "php85-php-fpm",
        "php84-php-fpm",
        "php83-php-fpm",
        "php82-php-fpm",
        "php_fpm",
    ]
    web_prefer = ["httpd", "apache2", "nginx"]
    fpm = [u for u in fpm_prefer if u in units]
    web = [u for u in web_prefer if u in units]
    if not fpm:
        fpm = [u for u in units if "php" in u and "fpm" in u]
    if not web and shutil.which("service"):
        for cand in web_prefer + ["apache24"]:
            web.append(cand)
    return fpm, web


def freebsd_services() -> Tuple[List[str], List[str]]:
    fpm = ["php_fpm", "php-fpm", "php82_fpm", "php83_fpm", "php84_fpm", "php85_fpm"]
    web = ["apache24", "httpd", "nginx"]
    return fpm, web


def service_cmd(osinfo: OsInfo, action: str, name: str, dry_run: bool) -> Tuple[int, str]:
    if osinfo.family == "freebsd":
        return run(["service", name, action], dry_run=dry_run, timeout=60)
    if shutil.which("systemctl"):
        return run(["systemctl", action, name], dry_run=dry_run, timeout=60)
    return run(["service", name, action], dry_run=dry_run, timeout=60)


def enable_service(osinfo: OsInfo, name: str, dry_run: bool) -> Tuple[int, str]:
    if osinfo.family == "freebsd":
        # sysrc php_fpm_enable=YES when possible
        if shutil.which("sysrc"):
            key = f"{name}_enable"
            return run(["sysrc", f"{key}=YES"], dry_run=dry_run, timeout=30)
        return 0, ""
    if shutil.which("systemctl"):
        return run(["systemctl", "enable", name], dry_run=dry_run, timeout=30)
    return 0, ""


def is_active(osinfo: OsInfo, name: str) -> bool:
    if shutil.which("systemctl") and osinfo.family != "freebsd":
        code, out = run(["systemctl", "is-active", name], timeout=15)
        return code == 0 and "active" in (out or "")
    code, out = run(["service", name, "status"], timeout=15)
    text = (out or "").lower()
    return code == 0 or "running" in text or "active" in text


def httpd_php_loaded() -> Tuple[bool, str]:
    for bin_name in ("httpd", "apache2", "apachectl", "apache2ctl"):
        path = shutil.which(bin_name)
        if not path:
            continue
        for args in ([path, "-M"], [path, "-t", "-D", "DUMP_MODULES"]):
            code, out = run(args, timeout=20)
            if code != 0:
                continue
            hit = [ln.strip() for ln in out.splitlines() if re.search(r"\bphp", ln, re.I)]
            if hit:
                return True, "; ".join(hit[:6])
            return False, f"{bin_name} ไม่รายงานโมดูล php"
    return False, "ไม่พบ httpd/apachectl — ข้ามการตรวจโมดูล (อาจใช้ nginx + php-fpm)"


def apply_mitigation(dry_run: bool) -> List[str]:
    candidates = [
        "/etc/php.ini",
        "/etc/php.d",
        "/etc/php/*/php.ini",
        "/etc/php/*/fpm/php.ini",
        "/etc/php/*/apache2/php.ini",
        "/etc/php/*/cli/php.ini",
        "/usr/local/etc/php.ini",
        "/usr/local/etc/php/*/php.ini",
        "/usr/local/etc/php/*/conf.d",
        "/Applications/XAMPP/xamppfiles/etc/php.ini",
        "/opt/remi/php*/root/etc/php.ini",
    ]
    import glob as _glob

    files: List[Path] = []
    for pattern in candidates:
        p = Path(pattern)
        if p.is_file():
            files.append(p)
        else:
            files.extend(Path(x) for x in _glob.glob(pattern) if Path(x).is_file())

    dropins = [
        Path("/etc/php.d/99-php2shell-mitigation.ini"),
        Path("/usr/local/etc/php/99-php2shell-mitigation.ini"),
        Path("/usr/local/etc/php/8.2/conf.d/99-php2shell-mitigation.ini"),
    ]
    for d in (
        "/etc/php.d",
        "/etc/php/conf.d",
        "/usr/local/etc/php",
        "/usr/local/etc/php/8.2/conf.d",
        "/usr/local/etc/php/8.3/conf.d",
        "/opt/homebrew/etc/php/8.2/conf.d",
    ):
        if Path(d).is_dir():
            dropins.append(Path(d) / "99-php2shell-mitigation.ini")

    changed: List[str] = []
    line = "session.upload_progress.enabled=Off\n"
    target = next((p for p in dropins if p.parent.is_dir()), None)
    if target is None and files:
        target = files[0]
    if target is None:
        log("ไม่พบ php.ini ที่จะใส่ mitigation", "warn")
        return changed
    if dry_run:
        log(f"DRY-RUN จะเขียน {target}", "warn")
        return [str(target)]
    if target.suffix == ".ini" and target.name.startswith("99-"):
        target.write_text(line, encoding="utf-8")
        changed.append(str(target))
        log(f"ตั้ง session.upload_progress.enabled=Off ที่ {target}", "ok")
        return changed
    text = target.read_text(encoding="utf-8", errors="replace")
    if re.search(r"^\s*session\.upload_progress\.enabled\s*=", text, re.M):
        text = re.sub(
            r"^\s*session\.upload_progress\.enabled\s*=.*$",
            "session.upload_progress.enabled=Off",
            text,
            flags=re.M,
        )
    else:
        text += "\n" + line
    target.write_text(text, encoding="utf-8")
    changed.append(str(target))
    log(f"ตั้ง session.upload_progress.enabled=Off ที่ {target}", "ok")
    return changed


def step_update(osinfo: OsInfo, rows: List[BinVersion], plan: Plan) -> int:
    log("ขั้นตอน 2 — อัปเดตแพ็กเกจ PHP", "step")
    if osinfo.pkg == "unknown":
        log(f"ไม่รู้จักตัวจัดการแพ็กเกจของ {osinfo.pretty}", "err")
        return 1
    if os.geteuid() != 0 and osinfo.pkg != "brew" and not plan.dry_run:
        log("ต้องรันด้วย sudo/root เพื่ออัปเดตแพ็กเกจ", "err")
        return 1
    cmds = update_commands(osinfo, rows)
    if not cmds:
        log("ไม่มีคำสั่งอัปเดตสำหรับ OS นี้", "err")
        return 1
    for cmd in cmds:
        log("รัน: " + " ".join(cmd), "info")
        code, out = run(cmd, dry_run=plan.dry_run, timeout=900)
        if out.strip():
            print(out[-4000:] if len(out) > 4000 else out)
        if code != 0:
            log(f"คำสั่งล้มเหลว ({code}): {' '.join(cmd)}", "err")
            if osinfo.family == "freebsd":
                log(
                    "FreeBSD repo อาจไม่มีแพตช์ — ใช้ scripts/freebsd_php2shell_update.py --from-source --yes",
                    "warn",
                )
            return code
    log("อัปเดตแพ็กเกจเสร็จ", "ok")
    return 0


def macos_point_cli_to_brew(dry_run: bool) -> List[str]:
    """ชี้ ~/.local/php-override และ XAMPP wrapper ไป Homebrew php@8.2"""
    brew_php = None
    for cand in (
        "/usr/local/opt/php@8.2/bin/php",
        "/opt/homebrew/opt/php@8.2/bin/php",
    ):
        if Path(cand).is_file():
            brew_php = cand
            break
    if not brew_php:
        return []
    changed: List[str] = []
    shim = f"#!/bin/sh\nexec {brew_php} \"$@\"\n"
    for dest in (
        Path.home() / ".local/php-override/php",
        Path.home() / ".local/bin/php",
    ):
        if dry_run:
            log(f"DRY-RUN จะชี้ {dest} -> {brew_php}", "warn")
            changed.append(str(dest))
            continue
        dest.parent.mkdir(parents=True, exist_ok=True)
        dest.write_text(shim, encoding="utf-8")
        dest.chmod(0o755)
        changed.append(str(dest))
        log(f"ชี้ {dest} -> {brew_php}", "ok")
    zshenv = Path.home() / ".zshenv"
    if zshenv.is_file():
        text = zshenv.read_text(encoding="utf-8", errors="replace")
        if "xampp-php-force" in text and brew_php not in text:
            new = text.replace(
                "php() { /Applications/XAMPP/xamppfiles/bin/php \"$@\"; }",
                f"php() {{ {brew_php} \"$@\"; }}",
            )
            new = new.replace(
                "compdef php=/Applications/XAMPP/xamppfiles/bin/php",
                f"compdef php={brew_php}",
            )
            if new != text and not dry_run:
                zshenv.write_text(new, encoding="utf-8")
                changed.append(str(zshenv))
                log(f"ปรับฟังก์ชัน php() ใน {zshenv}", "ok")
    return changed


def macos_switch_xampp_to_fpm(dry_run: bool) -> int:
    """ปิด XAMPP libphp 8.2.4 แล้วส่ง *.php ไป brew php-fpm 8.2.35"""
    conf = Path("/Applications/XAMPP/xamppfiles/etc/extra/httpd-xampp.conf")
    if not conf.is_file():
        log("ไม่พบ httpd-xampp.conf — ข้ามการสลับ Apache", "warn")
        return 0
    fpm_bin = next(
        (
            p
            for p in (
                "/usr/local/opt/php@8.2/sbin/php-fpm",
                "/opt/homebrew/opt/php@8.2/sbin/php-fpm",
            )
            if Path(p).is_file()
        ),
        None,
    )
    if not fpm_bin:
        log("ยังไม่มี brew php-fpm — ข้ามการสลับ XAMPP Apache", "warn")
        return 0
    if dry_run:
        log("DRY-RUN จะปิด XAMPP libphp แล้วชี้ไป php-fpm", "warn")
        return 0
    text = conf.read_text(encoding="utf-8", errors="replace")
    if "zz-php2shell-fpm" not in text:
        backup = conf.with_suffix(conf.suffix + ".bak-php2shell")
        if not backup.exists():
            shutil.copy2(conf, backup)
        text = re.sub(
            r"^(\s*LoadModule\s+php_module\s+modules/libphp\.so\s*)$",
            r"#\1  # disabled by php2shell — ใช้ brew php-fpm 8.2.35",
            text,
            flags=re.M,
        )
        text += (
            "\n# zz-php2shell-fpm: XAMPP Apache -> Homebrew php@8.2 FPM\n"
            "<IfModule proxy_fcgi_module>\n"
            '  <FilesMatch "\\.php$">\n'
            '    SetHandler "proxy:fcgi://127.0.0.1:9000"\n'
            "  </FilesMatch>\n"
            "</IfModule>\n"
        )
        conf.write_text(text, encoding="utf-8")
        log(f"สลับ XAMPP Apache ไป php-fpm ใน {conf}", "ok")
    run(["brew", "services", "start", "php@8.2"], timeout=60)
    apachectl = "/Applications/XAMPP/xamppfiles/bin/apachectl"
    if Path(apachectl).is_file():
        code, out = run([apachectl, "configtest"], timeout=30)
        if code != 0:
            log("apache configtest ไม่ผ่าน: " + (out or "")[-300:], "err")
            return 1
        code, out = run(["sudo", apachectl, "restart"], timeout=60)
        if code != 0:
            log("restart XAMPP Apache ไม่สำเร็จ (อาจต้องใส่รหัสผ่าน): " + (out or "")[-200:], "warn")
    return 0


def step_restart(osinfo: OsInfo, plan: Plan) -> int:
    log("ขั้นตอน 3 — restart + enable php-fpm และเว็บเซิร์ฟเวอร์", "step")
    if osinfo.family == "macos":
        macos_point_cli_to_brew(plan.dry_run)
        if not plan.dry_run:
            run(["brew", "services", "start", "php@8.2"], timeout=60)
        return macos_switch_xampp_to_fpm(plan.dry_run)
    if osinfo.family == "freebsd":
        fpm, web = freebsd_services()
    else:
        fpm, web = pick_services()
    if not fpm:
        log("ไม่พบบริการ php-fpm — จะรีสตาร์ทเฉพาะเว็บเซิร์ฟเวอร์", "warn")
    names = fpm + [w for w in web if w not in fpm]
    if not names:
        log("ไม่พบบริการที่จะรีสตาร์ท", "warn")
        return 0
    failed = 0
    for name in fpm:
        code, out = enable_service(osinfo, name, plan.dry_run)
        if code == 0:
            log(f"enable {name}", "ok")
        else:
            log(f"enable {name} ไม่สำเร็จ: {(out or '').strip()[:200]}", "warn")
    for name in names:
        code, out = service_cmd(osinfo, "restart", name, plan.dry_run)
        if code == 0:
            log(f"restart {name}", "ok")
        else:
            # บางชื่อเป็นแค่ตัวเลือก — ไม่ถือว่าล้มทั้งงานถ้ามีตัวอื่นสำเร็จ
            log(f"restart {name} ไม่สำเร็จ: {(out or '').strip()[:200]}", "warn")
            failed += 1
    if fpm and not plan.dry_run:
        if any(is_active(osinfo, n) for n in fpm):
            log("php-fpm ทำงานอยู่ (active)", "ok")
        else:
            log("php-fpm ยังไม่ active — เว็บอาจหลุด ตรวจ systemctl status php-fpm", "err")
            return 1
    return 0 if failed < len(names) else 1


def step_verify(osinfo: OsInfo, after: List[BinVersion]) -> int:
    log("ขั้นตอน 4 — ตรวจผลเวอร์ชัน / สถานะบริการ / โมดูล PHP", "step")
    bad = 0
    saw_php = False
    for row in after:
        if row.status == "missing":
            continue
        saw_php = True
        if row.status in ("vulnerable", "eol"):
            log(f"{row.kind} {row.path} ยังเป็น PHP {row.version} — ยังไม่ผ่านเกณฑ์", "err")
            bad += 1
        elif row.status == "safe":
            log(f"{row.kind} {row.path} PHP {row.version} ผ่านเกณฑ์ (>= {row.need})", "ok")
        else:
            log(f"{row.kind} {row.path} อ่านเวอร์ชันไม่ได้", "warn")
    if not saw_php:
        log("ไม่พบไบนารี PHP หลังอัปเดต", "err")
        bad += 1

    if osinfo.family == "freebsd":
        fpm, _web = freebsd_services()
    else:
        fpm, _web = pick_services()
    if fpm:
        if any(is_active(osinfo, n) for n in fpm):
            log("php-fpm = active", "ok")
        else:
            log("php-fpm ยังไม่ active", "err")
            bad += 1

    loaded, detail = httpd_php_loaded()
    if loaded:
        log(f"เว็บโหลด PHP แล้ว: {detail}", "ok")
    else:
        log(detail, "warn")
        log("ถ้าใช้ nginx/apache + php-fpm การไม่มี mod_php ไม่ถือว่าพัง — สำคัญที่ FPM ต้องแพตช์แล้ว", "info")

    if bad:
        log("ยังไม่ครบเกณฑ์ — อย่าเพิ่งถือว่าพ้น PHP2Shell", "err")
        return 2
    log("ผ่านเกณฑ์เวอร์ชันปลอดภัยของ PHP2Shell (CLI/FPM ที่ตรวจได้)", "ok")
    return 0


def needed(rows: List[BinVersion]) -> bool:
    return any(r.status in ("vulnerable", "eol") for r in rows)


def build_parser() -> argparse.ArgumentParser:
    p = argparse.ArgumentParser(
        prog=SCRIPT_NAME,
        description="อัปเดต PHP ให้พ้น PHP2Shell ตาม 4 ขั้นตอนในคู่มือ (เชิงป้องกัน)",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__,
    )
    p.add_argument("--check", action="store_true", help="ขั้นตอน 1: เช็กเวอร์ชันอย่างเดียว")
    p.add_argument("--update", action="store_true", help="ขั้นตอน 2: อัปเดตแพ็กเกจ PHP")
    p.add_argument("--restart", action="store_true", help="ขั้นตอน 3: restart + enable")
    p.add_argument("--verify", action="store_true", help="ขั้นตอน 4: ตรวจผล")
    p.add_argument("--all", action="store_true", help="ทำครบ 1–4 ตามรูป")
    p.add_argument("--mitigate", action="store_true", help="ตั้ง session.upload_progress.enabled=Off")
    p.add_argument("--yes", action="store_true", help="ไม่ถามยืนยัน")
    p.add_argument("--dry-run", action="store_true", help="จำลอง ไม่แก้ระบบ")
    p.add_argument("--version", action="version", version=f"%(prog)s {SCRIPT_VERSION}")
    return p


def main(argv: Optional[Sequence[str]] = None) -> int:
    args = build_parser().parse_args(argv)
    # เซิร์ฟเวอร์จริงที่เป็น FreeBSD ใช้ตัว v2 ที่ประเมินผลกระทบและยึดสาขาเดิม
    if platform.system().lower() == "freebsd" or Path("/bin/freebsd-version").exists():
        sibling = Path(__file__).resolve().parent / "freebsd_php2shell_update_v2.py"
        forwarded: List[str] = [sys.executable, str(sibling)]
        if args.all or args.update:
            forwarded.append("--apply")
        elif args.check or not any((args.update, args.restart, args.verify)):
            forwarded.append("--check")
        if args.yes:
            forwarded.append("--yes")
        if args.dry_run:
            forwarded.append("--dry-run")
        os.execv(sys.executable, forwarded)
    plan = Plan(dry_run=args.dry_run, yes=args.yes)
    osinfo = detect_os()

    do_check = args.check or args.all or not any((args.update, args.restart, args.verify, args.mitigate))
    do_update = args.update or args.all
    do_restart = args.restart or args.all
    do_verify = args.verify or args.all

    log(f"{SCRIPT_NAME} v{SCRIPT_VERSION} — เชิงป้องกัน ไม่ส่ง exploit", "step")
    log(f"OS: {osinfo.pretty}  family={osinfo.family}  pkg={osinfo.pkg}", "info")
    log("เกณฑ์ปลอดภัย: 8.5.12 / 8.4.27 / 8.3.36 / 8.2.35", "info")

    rows = collect_bins()
    if do_check:
        print_bin_table(rows)

    if not needed(rows) and do_update and not args.all:
        log("เวอร์ชันที่ตรวจได้ผ่านเกณฑ์แล้ว — ไม่บังคับอัปเดต เว้นแต่เว็บ SAPI คนละตัวกับ CLI", "ok")
    if needed(rows):
        log("จำเป็นต้องอัปเดต: มี CLI หรือ FPM ต่ำกว่าเกณฑ์ในรูป", "warn")
        log("PHP2Shell ยิงผ่านเว็บ — ต้องแพตช์ FPM/mod_php ไม่ใช่แค่ php -v", "warn")
    elif do_check and not do_update:
        log("จากไบนารีบนเครื่องนี้ ยังไม่จำเป็นต้องอัปเดต — ตรวจฝั่งเว็บด้วย php2shell_scan_v2.py ด้วย", "ok")
        if args.mitigate:
            apply_mitigation(plan.dry_run)
        return 0

    if do_update:
        if not confirm(plan, "จะอัปเดตแพ็กเกจ PHP บนเครื่องนี้?"):
            return 1
        code = step_update(osinfo, rows, plan)
        if code != 0:
            return code

    if args.mitigate or args.all:
        apply_mitigation(plan.dry_run)

    if do_restart:
        code = step_restart(osinfo, plan)
        if code != 0:
            return code

    if do_verify or do_update:
        after = collect_bins()
        print_bin_table(after)
        return step_verify(osinfo, after)

    return 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    except KeyboardInterrupt:
        print()
        sys.exit(130)
