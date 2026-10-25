#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FreeBSD PHP2Shell hardening & update helper
CVE-2026-109618 / CVE-2026-109619

อัปเดต / ตรวจ / mitigation PHP หลายเวอร์ชันบน FreeBSD (pkg)
ให้พ้นช่องโหว่ PHP2Shell ตามประกาศ PHP ฉุกเฉิน

เวอร์ชันขั้นต่ำที่ปลอดภัย (upstream PHP):
  8.5 >= 8.5.12
  8.4 >= 8.4.27
  8.3 >= 8.3.36
  8.2 >= 8.2.35
  8.1 / 7.4 และเก่ากว่า: EOL — สคริปต์จะย้ายไป 8.2.35 (ใกล้ที่สุดที่มีแพตช์)
    ไม่พยายามแพตช์ 7.4 ต่อ เพราะไม่มีแพตช์ PHP2Shell

ตัวอย่างใช้งาน:
  # ตรวจสถานะอย่างเดียว (ยังไม่อัปเดต)
  sudo python3 freebsd_php2shell_update.py --check

  # อัปเดตจริงให้พ้น PHP2Shell (แนะนำคำสั่งนี้)
  sudo python3 freebsd_php2shell_update.py --all --yes

  # อัปเดตอย่างเดียว + สลับ repo เป็น latest อัตโนมัติถ้า quarterly ยังไม่มีแพตช์
  sudo python3 freebsd_php2shell_update.py --update --restart --yes

  # จำลอง (ไม่แก้ระบบ)
  sudo python3 freebsd_php2shell_update.py --all --dry-run

  # ระบุเวอร์ชัน (flavor FreeBSD เช่น 82 83 84)
  sudo python3 freebsd_php2shell_update.py --update --php 82 --yes

  # ไม่สลับไป latest repo
  sudo python3 freebsd_php2shell_update.py --update --no-latest-repo --yes

  # โฮสต์ DirectAdmin: บังคับอัปเดตผ่าน CustomBuild
  sudo python3 freebsd_php2shell_update.py --update --custombuild --yes

  # วินิจฉัยว่า pkg พังเพราะอะไร
  sudo python3 freebsd_php2shell_update.py --diagnose

  # เมื่อ FreeBSD 13 repo ตาย/404: คอมไพล์ PHP จากซอร์สทางการ (แนะนำบนโฮสต์นี้)
  # หลัง build จะ health-check (filter_var ฯลฯ), เก็บ ext ซ้ำ, ทดสอบ apache ให้อัตโนมัติ
  sudo python3 freebsd_php2shell_update.py --from-source --yes --restart

  # ซ่อม pkg repo แล้วค่อยอัปเดต
  sudo python3 freebsd_php2shell_update.py --repair-repo --update --yes

  # กู้เว็บที่พัง (เช่น filter_var undefined) เพราะ ext ถูกปิด: คืน + รีสตาร์ท apache
  sudo python3 freebsd_php2shell_update.py --restore-ext

  # ลบคำเตือน "Module ... already loaded" จาก php -v (ปิดเฉพาะที่ซ้ำกับ builtin)
  sudo python3 freebsd_php2shell_update.py --clean-ext-dups --restart

  # ตรวจเวอร์ชัน PHP "ฝั่งเว็บ" (mod_php/FPM) — จับเคส CLI อัปแล้วแต่เว็บยังเก่า
  python3 freebsd_php2shell_update.py --web-check

  # เว็บยังเป็น mod_php เก่า แต่ php-fpm แพตช์แล้ว: สลับ Apache -> fpm (สำรอง+ย้อนได้)
  # --from-source / --all จะปิด libphp7 แล้วสลับให้อัตโนมัติเมื่อ FPM ปลอดภัยแล้ว
  sudo python3 freebsd_php2shell_update.py --use-fpm

  # บังคับสร้าง mod_php (libphp.so) ใหม่ให้ตรงเวอร์ชัน (ทางเลือกแทน --use-fpm)
  sudo python3 freebsd_php2shell_update.py --rebuild-modphp --yes --restart

  # ครบสำหรับโฮสต์ที่ CLI/FPM เป็น 8.2.35 แต่ Apache ยังเสิร์ฟ 7.4 (libphp7.so พัง)
  sudo python3 freebsd_php2shell_update.py --from-source --yes --clean-ext-dups --remove-eol

  # Apache สตาร์ทไม่ได้เพราะ php_flag ค้างหลังปิด mod_php
  sudo python3 freebsd_php2shell_update.py --fix-apache --yes

หมายเหตุสำคัญ (PHP2Shell ยิงผ่านเว็บ):
  CLI (php -v) กับ mod_php/FPM ที่เว็บเสิร์ฟ อาจคนละเวอร์ชันกัน
  ถ้าหน้าเว็บเป็น nginx จะใช้ php-fpm เป็นตัวตัดสิน (nginx มักไม่โชว์ X-Powered-By)
  และจะรีสตาร์ทเฉพาะ nginx ไม่ไปยุ่ง apache24 ที่ไม่ได้เสิร์ฟไซต์
  ถ้า Apache ยัง LoadModule libphp7.so / เสิร์ฟ PHP 7.4 ขณะที่ FPM เป็น 8.2.35
  สคริปต์จะปิดโมดูลเก่า แล้วสลับไป php-fpm ก่อนรีสตาร์ท (ไม่ติดตั้ง mod_php82 ทับ libphp7)
  ตรวจเทียบกับ php2shell_scan_v2.py ได้
  nginx/Apache ที่ซ่อนเวอร์ชัน PHP (expose_php=Off / FPM) จะขึ้น HIDDEN ไม่ใช่ UNKNOWN
  ให้ตรวจ FPM บนเครื่องด้วย --check หรือ php2shell_scan_v2.py --local

อ้างอิง:
  https://thehackernews.com/2026/10/php-issues-emergency-patches-for-two.html
  https://www.php.net/
"""

from __future__ import annotations

import argparse
import datetime as dt
import glob
import json
import os
import platform
import re
import shlex
import shutil
import signal
import socket
import ssl
import subprocess
import sys
import time
import urllib.error
import urllib.request
from dataclasses import asdict, dataclass, field
from pathlib import Path
from typing import Dict, Iterable, List, Optional, Sequence, Tuple


SCRIPT_NAME = "freebsd_php2shell_update.py"
SCRIPT_VERSION = "1.9.2"

LATEST_REPO_FILE = Path("/usr/local/etc/pkg/repos/FreeBSD-latest-php2shell.conf")
REPAIR_REPO_FILE = Path("/usr/local/etc/pkg/repos/FreeBSD-php2shell-repair.conf")
SYSTEM_REPO_CANDIDATES = (
    Path("/etc/pkg/FreeBSD.conf"),
    Path("/usr/local/etc/pkg/repos/FreeBSD.conf"),
)
CUSTOMBUILD_CANDIDATES = (
    Path("/usr/local/directadmin/custombuild/build"),
    Path("/usr/local/directadmin/custombuild/custombuild"),
)
CUSTOMBUILD_OPTIONS = Path("/usr/local/directadmin/custombuild/options.conf")

# Official PHP source mirrors (PHP2Shell fixed tarballs)
PHP_SOURCE_MIRRORS = (
    "https://www.php.net/distributions/php-{ver}.tar.gz",
    "https://downloads.php.net/~eric/php-{ver}.tar.gz",
    "https://museum.php.net/php8/php-{ver}.tar.gz",
)

# (major, minor) -> minimum safe (major, minor, patch)
SAFE_MIN: Dict[Tuple[int, int], Tuple[int, int, int]] = {
    (8, 5): (8, 5, 12),
    (8, 4): (8, 4, 27),
    (8, 3): (8, 3, 36),
    (8, 2): (8, 2, 35),
}

# สาขา EOL (7.4 / 8.0 / 8.1) ไม่มีแพตช์ PHP2Shell — ย้ายไปสาขาใกล้ที่สุดที่มีแพตช์
EOL_MIGRATE_TARGET: Tuple[int, int, int] = (8, 2, 35)

# FreeBSD pkg / binary naming helpers: php82, php83, ...
FLAVOR_RE = re.compile(r"^php(\d{2})$")
VERSION_RE = re.compile(
    r"(?:PHP\s+)?(\d+)\.(\d+)\.(\d+)(?:[^\d].*)?$",
    re.IGNORECASE,
)
PKG_PHP_RE = re.compile(r"^php(\d{2})(?:-|$)")

COMMON_BIN_GLOBS = (
    "/usr/local/bin/php",
    "/usr/local/bin/php??",
    "/usr/local/bin/php?.?",
)

COMMON_INI_PATHS = (
    "/usr/local/etc/php.ini",
    "/usr/local/etc/php/*/php.ini",
    "/usr/local/etc/php??/php.ini",
    "/usr/local/lib/php.ini",
)

SERVICE_CANDIDATES = (
    "php_fpm",
    "php-fpm",
    "php82_fpm",
    "php83_fpm",
    "php84_fpm",
    "php85_fpm",
    "php81_fpm",
    "php80_fpm",
    "apache24",
    "nginx",
    "httpd",
)

# Web servers to restart after PHP changes (mod_php / fpm front)
WEB_SERVICE_CANDIDATES = (
    "apache24",
    "httpd",
    "nginx",
)

NGINX_CONF_GLOBS = (
    "/usr/local/etc/nginx/nginx.conf",
    "/usr/local/etc/nginx/conf.d/*.conf",
    "/usr/local/etc/nginx/vhosts/*.conf",
    "/usr/local/etc/nginx/sites-enabled/*",
    "/usr/local/etc/nginx/sites-available/*.conf",
    "/etc/nginx/nginx.conf",
    "/etc/nginx/conf.d/*.conf",
    "/etc/nginx/sites-enabled/*",
)

# Where FreeBSD keeps per-extension .ini files (extension=NAME.so)
PHP_CONF_DIR_CANDIDATES = (
    "/usr/local/etc/php",
)

# Sub-folder used to park duplicate/broken ext ini so they can be restored
DISABLED_DUP_DIRNAME = "disabled-dup"

# Functions that must stay available or the web app breaks
# (filter_var vanished in a real incident when ext inis were disabled)
CRITICAL_PHP_FUNCS = (
    "filter_var",
    "preg_match",
    "json_encode",
    "session_start",
    "mb_strlen",
    "hash",
)

# Shared-lib / ABI breakages seen after rebuild -> targeted pkg fixes
# (only real FreeBSD package names; match on the actual shared object)
WEB_ABI_FIXES = (
    (re.compile(r"libpcre2", re.I), "pcre2"),
    (re.compile(r"libxml2\.so", re.I), "libxml2"),
    (re.compile(r"libiconv\.so", re.I), "libiconv"),
    (re.compile(r"libargon2\.so", re.I), "libargon2"),
    (re.compile(r"libsodium\.so", re.I), "libsodium"),
    (re.compile(r"libcurl\.so", re.I), "curl"),
    (re.compile(r"libonig\.so", re.I), "oniguruma"),
)

# leftover Apache PHP 7 — ห้ามซ่อมด้วย pkg install libxml2/mod_php82
LEGACY_LIBPHP_HINT_RE = re.compile(
    r"libphp[57]\.so|php7_module|php5_module|"
    r"Cannot load libexec/apache24/libphp",
    re.I,
)
LEGACY_MODPHP_LINE_RE = re.compile(
    r"^\s*(?:"
    r"LoadModule\s+php\d*_module\b"
    r"|LoadModule\s+\S+\s+\S*libphp[57](?:\.so)?\b"
    r"|LoadFile\s+\S*libphp[57]"
    r")",
    re.I | re.M,
)
LEGACY_MODPHP_COMMENT_RE = re.compile(
    r"(?m)^(\s*)("
    r"LoadModule\s+php\d*_module\b.*"
    r"|LoadModule\s+\S+\s+\S*libphp[57]\S*"
    r"|LoadFile\s+\S*libphp[57]\S*"
    r")\s*$",
    re.I,
)
# php_flag/php_value ใช้ได้เฉพาะ mod_php — หลังปิด libphp7 แล้ว Apache จะไม่ขึ้น
MODPHP_ONLY_DIR_RE = re.compile(
    r"^\s*(php_flag|php_value|php_admin_flag|php_admin_value)\b",
    re.I | re.M,
)
MODPHP_ONLY_DIR_COMMENT_RE = re.compile(
    r"(?m)^(\s*)(php_(?:flag|value|admin_flag|admin_value)\b.*)$",
    re.I,
)
MODPHP_DIR_HINT_RE = re.compile(
    r"Invalid command ['\"]php_(?:flag|value|admin_flag|admin_value)|"
    r"php_flag.*module not included",
    re.I,
)
# Zend module API ของสาขา EOL — ห้ามชี้ extension_dir ของ 8.2+ ไปที่นี่
EOL_ZEND_APIS = (
    "20131226",
    "20151012",
    "20160303",
    "20170718",
    "20180731",
    "20190902",
    "20200930",
    "20210902",
)

# Candidate PHP extension dirs (pkg vs source build often differ!)
EXTENSION_DIR_CANDIDATES = (
    "/usr/local/lib/php/20220829",
    "/usr/local/lib/php/extensions/no-debug-non-zts-20220829",
    "/usr/local/lib/php/20230831",
    "/usr/local/lib/php/20240924",
)
EXTENSION_DIR_DROPIN = "/usr/local/etc/php/00-extension-dir.ini"


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

    def __format__(self, spec: str) -> str:
        return format(str(self), spec)


@dataclass
class PhpInstall:
    binary: str
    version: Version
    flavor: Optional[str] = None  # e.g. "82"
    ini_files: List[str] = field(default_factory=list)
    pkg_names: List[str] = field(default_factory=list)
    sapi: str = ""
    status: str = "unknown"  # safe | vulnerable | eol_unknown | unknown
    note: str = ""


@dataclass
class ActionResult:
    ok: bool
    message: str
    details: List[str] = field(default_factory=list)


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


def die(msg: str, code: int = 1) -> None:
    log(msg, "err")
    sys.exit(code)


def run(
    cmd: Sequence[str],
    *,
    dry_run: bool = False,
    check: bool = False,
    capture: bool = True,
    env: Optional[dict] = None,
) -> subprocess.CompletedProcess:
    pretty = " ".join(cmd)
    if dry_run:
        log(f"DRY-RUN: {pretty}", "warn")
        return subprocess.CompletedProcess(cmd, 0, stdout="", stderr="")
    return subprocess.run(
        list(cmd),
        check=check,
        text=True,
        capture_output=capture,
        env=env,
    )


def which(name: str) -> Optional[str]:
    return shutil.which(name)


def require_root() -> None:
    if hasattr(os, "geteuid") and os.geteuid() != 0:
        die("ต้องรันด้วย root (sudo) เพราะต้องอัปเดตแพ็กเกจและแก้ php.ini")


def require_freebsd() -> None:
    sysname = platform.system().lower()
    if sysname != "freebsd":
        die(f"สคริปต์นี้รองรับ FreeBSD เท่านั้น (ตรวจพบ: {platform.system()})")
    if not which("pkg"):
        die("ไม่พบคำสั่ง pkg — ติดตั้ง/เปิดใช้ pkg ก่อน")


def parse_version(text: str) -> Optional[Version]:
    text = (text or "").strip()
    m = VERSION_RE.search(text)
    if not m:
        # also accept "8.2.35"
        m2 = re.search(r"(\d+)\.(\d+)\.(\d+)", text)
        if not m2:
            return None
        return Version(int(m2.group(1)), int(m2.group(2)), int(m2.group(3)))
    return Version(int(m.group(1)), int(m.group(2)), int(m.group(3)))


def flavor_from_version(v: Version) -> str:
    return f"{v.major}{v.minor}"


def is_eol_branch(v: Version) -> bool:
    return v.branch() < (8, 2)


def eol_migrate_version() -> Version:
    return Version(*EOL_MIGRATE_TARGET)


def assess_version(v: Version) -> Tuple[str, str]:
    """Return (status, note)."""
    branch = v.branch()
    if branch in SAFE_MIN:
        minimum = SAFE_MIN[branch]
        if v.as_tuple() >= minimum:
            return "safe", f"ผ่านเกณฑ์ขั้นต่ำ {'.'.join(map(str, minimum))}"
        return (
            "vulnerable",
            f"ต่ำกว่าเกณฑ์ — ต้องอัปเดตเป็น >= {'.'.join(map(str, minimum))}",
        )
    if is_eol_branch(v):
        dest = eol_migrate_version()
        return (
            "vulnerable",
            f"สาขา EOL — จะอัปเดตเป็น {dest} (ใกล้ที่สุดที่มีแพตช์ PHP2Shell)",
        )
    return "unknown", "ยังไม่มีตารางเกณฑ์สำหรับสาขานี้ — ตรวจประกาศ PHP ล่าสุด"


def expand_globs(patterns: Iterable[str]) -> List[str]:
    out: List[str] = []
    for pat in patterns:
        if any(ch in pat for ch in "*?[]"):
            out.extend(sorted(glob.glob(pat)))
        elif Path(pat).exists():
            out.append(pat)
    seen = set()
    uniq: List[str] = []
    for p in out:
        if p not in seen and Path(p).is_file():
            seen.add(p)
            uniq.append(p)
    return uniq


def list_pkg_php_packages() -> List[str]:
    cp = run(["pkg", "info", "-q"], dry_run=False, check=False)
    if cp.returncode != 0:
        return []
    pkgs = []
    for line in (cp.stdout or "").splitlines():
        name = line.strip().split("-")[0] if line.strip() else ""
        # FreeBSD package names look like php82-8.2.35 or php82-extensions-...
        full = line.strip()
        if not full:
            continue
        base = full.rsplit("-", 1)[0] if re.search(r"-\d", full) else full
        # Keep only phpNN and phpNN-* roots that are clearly PHP runtime/ext
        m = re.match(r"^(php\d{2})(?:$|-)", base)
        if m:
            pkgs.append(full)
    return pkgs


def pkg_origins_for_flavor(flavor: str) -> List[str]:
    """Return installed package names matching php{flavor}*."""
    cp = run(["pkg", "info", "-x", f"^php{flavor}([.-]|$)"], dry_run=False, check=False)
    if cp.returncode != 0:
        return []
    return [ln.strip() for ln in (cp.stdout or "").splitlines() if ln.strip()]


def detect_binaries(flavors: Optional[Sequence[str]] = None) -> List[str]:
    bins: List[str] = []
    # Default php
    for name in ("php",):
        p = which(name)
        if p:
            bins.append(p)
    # php82 / php8.2 style
    local_bin = Path("/usr/local/bin")
    if local_bin.is_dir():
        for p in sorted(local_bin.iterdir()):
            if not p.is_file() or not os.access(p, os.X_OK):
                continue
            name = p.name
            if name == "php" or FLAVOR_RE.match(name) or re.match(r"^php\d+\.\d+$", name):
                bins.append(str(p))
    # Filter by requested flavors
    if flavors:
        wanted = set(flavors)
        filtered = []
        for b in bins:
            name = Path(b).name
            m = FLAVOR_RE.match(name)
            if m and m.group(1) in wanted:
                filtered.append(b)
            elif name == "php":
                # keep default php; version filter later
                filtered.append(b)
            elif re.match(r"^php(\d)\.(\d+)$", name):
                mm = re.match(r"^php(\d)\.(\d+)$", name)
                if mm and f"{mm.group(1)}{mm.group(2)}" in wanted:
                    filtered.append(b)
        bins = filtered
    # Unique
    seen = set()
    out = []
    for b in bins:
        rb = str(Path(b).resolve()) if Path(b).exists() else b
        if rb not in seen:
            seen.add(rb)
            out.append(b)
    return out


def php_version_of(binary: str) -> Optional[Version]:
    cp = run([binary, "-v"], dry_run=False, check=False)
    if cp.returncode != 0:
        return None
    first = (cp.stdout or cp.stderr or "").splitlines()
    if not first:
        return None
    return parse_version(first[0])


def php_ini_files(binary: str) -> List[str]:
    files: List[str] = []
    cp = run([binary, "--ini"], dry_run=False, check=False)
    text = (cp.stdout or "") + "\n" + (cp.stderr or "")
    for line in text.splitlines():
        if "Loaded Configuration File:" in line:
            path = line.split(":", 1)[-1].strip()
            if path and path.lower() != "(none)" and Path(path).is_file():
                files.append(path)
        if "Scan for additional .ini files in:" in line:
            scan = line.split(":", 1)[-1].strip()
            if scan and scan.lower() != "(none)" and Path(scan).is_dir():
                for p in sorted(Path(scan).glob("*.ini")):
                    files.append(str(p))
    # Also common paths
    for p in expand_globs(COMMON_INI_PATHS):
        files.append(p)
    # Unique
    seen = set()
    out = []
    for f in files:
        if f not in seen and Path(f).is_file():
            seen.add(f)
            out.append(f)
    return out


def php_sapi(binary: str) -> str:
    cp = run([binary, "-r", "echo PHP_SAPI;"], dry_run=False, check=False)
    return (cp.stdout or "").strip()


def discover_installs(flavors: Optional[Sequence[str]] = None) -> List[PhpInstall]:
    installs: List[PhpInstall] = []
    for binary in detect_binaries(flavors):
        ver = php_version_of(binary)
        if not ver:
            continue
        if flavors:
            fl = flavor_from_version(ver)
            # If user asked specific flavors, skip default php of other branches
            name = Path(binary).name
            if name == "php" and fl not in set(flavors):
                continue
        flavor = flavor_from_version(ver)
        status, note = assess_version(ver)
        pkgs = pkg_origins_for_flavor(flavor)
        # Prefer runtime package phpNN as first
        runtime = [p for p in pkgs if re.match(rf"^php{flavor}-\d", p)]
        inst = PhpInstall(
            binary=binary,
            version=ver,
            flavor=flavor,
            ini_files=php_ini_files(binary),
            pkg_names=runtime or pkgs[:5],
            sapi=php_sapi(binary),
            status=status,
            note=note,
        )
        installs.append(inst)

    # Also discover flavors installed via pkg but without a distinct binary alias
    if flavors is None:
        pkg_list = list_pkg_php_packages()
        found_flavors = set()
        for full in pkg_list:
            m = re.match(r"^php(\d{2})-", full)
            if m:
                found_flavors.add(m.group(1))
        have = {i.flavor for i in installs}
        for fl in sorted(found_flavors - have):
            candidate = f"/usr/local/bin/php{fl}"
            binary = candidate if Path(candidate).exists() else (which("php") or "")
            if not binary:
                continue
            ver = php_version_of(binary)
            if not ver or flavor_from_version(ver) != fl:
                # leftover php74-openssl ฯลฯ หลัง default php ย้ายไป 8.2 แล้ว
                # อย่าสร้าง stub 7.4 จากแพ็กเกจค้าง ถ้าไม่มีไบนารี php74 จริง
                # และมีสาขาที่แพตช์แล้วอยู่แล้ว — จะทำให้ --from-source คอมไพล์ซ้ำโดยไม่จำเป็น
                real_bin = Path(f"/usr/local/bin/php{fl}")
                already_patched = any(
                    i.status == "safe" and SAFE_MIN.get(i.version.branch())
                    and i.version.as_tuple() >= SAFE_MIN[i.version.branch()]
                    for i in installs
                )
                if not real_bin.exists() and already_patched:
                    log(
                        f"พบแพ็กเกจค้าง php{fl}-* แต่ไบนารีเป็นสาขาที่แพตช์แล้ว — "
                        "จะย้าย/ลบของเก่า ไม่ถือเป็น PHP แยก",
                        "info",
                    )
                    continue
                for full in pkg_origins_for_flavor(fl):
                    vm = re.search(r"-(\d+\.\d+\.\d+)", full)
                    if vm:
                        ver = parse_version(vm.group(1))
                        break
            if not ver:
                continue
            status, note = assess_version(ver)
            installs.append(
                PhpInstall(
                    binary=binary,
                    version=ver,
                    flavor=fl,
                    ini_files=php_ini_files(binary) if Path(binary).exists() else [],
                    pkg_names=pkg_origins_for_flavor(fl),
                    sapi=php_sapi(binary) if Path(binary).exists() else "",
                    status=status,
                    note=note + " (ค้นพบจาก pkg)",
                )
            )

    # Deduplicate by flavor+version+binary
    uniq: Dict[str, PhpInstall] = {}
    for i in installs:
        key = f"{i.flavor}:{i.version}:{i.binary}"
        uniq[key] = i
    return sorted(uniq.values(), key=lambda x: (x.flavor or "", x.version.as_tuple(), x.binary))


def print_report(installs: List[PhpInstall]) -> int:
    """Print report. Return exit code: 0 safe, 2 vulnerable, 3 eol/unknown."""
    if not installs:
        log("ไม่พบ PHP บนระบบ", "warn")
        return 1

    print()
    print(f"{Color.BOLD}PHP installs{Color.RESET}")
    print("-" * 72)
    worst = 0
    for i in installs:
        color = {
            "safe": Color.GREEN,
            "vulnerable": Color.RED,
            "eol_unknown": Color.YELLOW,
            "unknown": Color.YELLOW,
        }.get(i.status, "")
        print(
            f"  {color}{i.status.upper():12}{Color.RESET}  "
            f"php{i.flavor}  {i.version}  ({i.binary})"
        )
        print(f"               SAPI: {i.sapi or '-'}  |  {i.note}")
        if i.pkg_names:
            print(f"               pkg: {', '.join(i.pkg_names[:4])}")
        if i.ini_files:
            print(f"               ini: {i.ini_files[0]}")
            for extra in i.ini_files[1:3]:
                print(f"                    {extra}")
        if i.status == "vulnerable":
            worst = max(worst, 2)
        elif i.status in ("eol_unknown", "unknown"):
            worst = max(worst, 3)
    print("-" * 72)
    print(f"{Color.DIM}ตารางนี้คือ CLI เท่านั้น — เว็บอาจยังเป็น mod_php คนละเวอร์ชัน{Color.RESET}")
    return worst


def backup_file(path: Path, dry_run: bool = False) -> Optional[Path]:
    stamp = dt.datetime.now().strftime("%Y%m%d%H%M%S")
    bak = path.with_name(path.name + f".bak-php2shell-{stamp}")
    if dry_run:
        log(f"DRY-RUN: backup {path} -> {bak}", "warn")
        return bak
    shutil.copy2(path, bak)
    return bak


def mitigate_ini(path: str, dry_run: bool = False) -> ActionResult:
    """Set session.upload_progress.enabled=Off in a php.ini-like file."""
    p = Path(path)
    if not p.is_file():
        return ActionResult(False, f"ไม่พบไฟล์ {path}")

    try:
        text = p.read_text(encoding="utf-8", errors="replace")
    except OSError as e:
        return ActionResult(False, f"อ่านไม่ได้ {path}: {e}")

    lines = text.splitlines(keepends=True)
    key_re = re.compile(r"^\s*;?\s*session\.upload_progress\.enabled\s*=")
    new_line = "session.upload_progress.enabled = Off\n"
    changed = False
    out_lines: List[str] = []
    found = False
    for line in lines:
        if key_re.match(line):
            found = True
            # Preserve newline style
            nl = "\n" if line.endswith("\n") else ""
            if line.lstrip().startswith(";"):
                # uncomment / replace
                out_lines.append(new_line if nl == "\n" else new_line.rstrip("\n"))
                changed = True
            elif re.search(r"=\s*Off\b", line, re.I):
                out_lines.append(line)
            else:
                out_lines.append(new_line if nl == "\n" else new_line.rstrip("\n"))
                changed = True
        else:
            out_lines.append(line)

    if not found:
        if out_lines and not out_lines[-1].endswith("\n"):
            out_lines[-1] = out_lines[-1] + "\n"
        out_lines.append("\n; PHP2Shell mitigation (CVE-2026-109618 / CVE-2026-109619)\n")
        out_lines.append(new_line)
        changed = True

    if not changed:
        return ActionResult(True, f"มี mitigation แล้ว: {path}")

    backup_file(p, dry_run=dry_run)
    if dry_run:
        return ActionResult(True, f"DRY-RUN จะตั้ง Off ที่ {path}", ["session.upload_progress.enabled = Off"])

    try:
        p.write_text("".join(out_lines), encoding="utf-8")
    except OSError as e:
        return ActionResult(False, f"เขียนไม่ได้ {path}: {e}")
    return ActionResult(True, f"ตั้ง session.upload_progress.enabled=Off ที่ {path}")


def should_mitigate_ini(path: str) -> bool:
    """Only touch main php.ini / session-related conf — not every extension .ini."""
    name = Path(path).name.lower()
    if name == "php.ini":
        return True
    if "session" in name:
        return True
    if name in {"zzz-php2shell.ini", "php2shell.ini", "99-php2shell.ini"}:
        return True
    return False


def mitigate_all(installs: List[PhpInstall], dry_run: bool = False) -> List[ActionResult]:
    targets: List[str] = []
    for i in installs:
        targets.extend(i.ini_files)
    targets.extend(expand_globs(COMMON_INI_PATHS))
    # Preferred dedicated drop-in (cleanest)
    drop_ins = [
        "/usr/local/etc/php/99-php2shell.ini",
        "/usr/local/etc/php/zzz-php2shell.ini",
    ]
    for d in drop_ins:
        parent = Path(d).parent
        if parent.is_dir():
            targets.append(d)

    seen = set()
    results = []
    for t in targets:
        if t in seen:
            continue
        seen.add(t)
        if not should_mitigate_ini(t) and not t.endswith("99-php2shell.ini") and not t.endswith("zzz-php2shell.ini"):
            continue
        # Create drop-in if missing
        if t.endswith("php2shell.ini") and not Path(t).exists():
            if dry_run:
                results.append(ActionResult(True, f"DRY-RUN สร้าง {t}"))
                log(results[-1].message, "warn")
                continue
            Path(t).parent.mkdir(parents=True, exist_ok=True)
            Path(t).write_text(
                "; PHP2Shell mitigation CVE-2026-109618 / CVE-2026-109619\n"
                "session.upload_progress.enabled = Off\n",
                encoding="utf-8",
            )
            results.append(ActionResult(True, f"สร้าง drop-in mitigation: {t}"))
            log(results[-1].message, "ok")
            continue
        if not Path(t).exists():
            continue
        res = mitigate_ini(t, dry_run=dry_run)
        results.append(res)
        log(res.message, "ok" if res.ok else "err")
    if not results:
        log("ไม่พบ php.ini ที่จะใส่ mitigation", "warn")
    log(
        "หมายเหตุ: mitigation ลดความเสี่ยงบางเส้นทางเท่านั้น — ต้องอัปเดต PHP จึงปลอดภัยจริง",
        "warn",
    )
    return results


def pkg_update(dry_run: bool = False) -> ActionResult:
    cp = run(["pkg", "update", "-f"], dry_run=dry_run, check=False, capture=True)
    if dry_run:
        return ActionResult(True, "DRY-RUN pkg update -f")
    out = ((cp.stderr or "") + "\n" + (cp.stdout or "")).strip()
    if cp.returncode != 0:
        return ActionResult(False, "pkg update ล้มเหลว", [out[-4000:] if out else f"exit={cp.returncode}"])
    return ActionResult(True, "pkg update สำเร็จ", [out[-1000:]] if out else [])


def find_custombuild() -> Optional[Path]:
    for p in CUSTOMBUILD_CANDIDATES:
        if p.is_file() and os.access(p, os.X_OK):
            return p
    # also accept non-executable script
    for p in CUSTOMBUILD_CANDIDATES:
        if p.is_file():
            return p
    return None


def diagnose_environment() -> List[str]:
    lines: List[str] = []
    lines.append(f"platform: {platform.platform()}")
    lines.append(f"ABI: {freebsd_abi()}")
    lines.append(f"uid: {os.geteuid() if hasattr(os, 'geteuid') else 'n/a'}")
    lines.append(f"pkg: {which('pkg') or '-'}")
    lines.append(f"php: {which('php') or '-'}")
    if which("php"):
        v = php_version_of(which("php") or "")
        lines.append(f"php -v: {v or '-'}")
    cb = find_custombuild()
    lines.append(f"directadmin custombuild: {cb or 'ไม่พบ'}")
    if CUSTOMBUILD_OPTIONS.is_file():
        try:
            opt = CUSTOMBUILD_OPTIONS.read_text(encoding="utf-8", errors="replace")
            for key in ("php1_release", "php1_mode", "php2_release", "php3_release"):
                m = re.search(rf"^{key}=(.*)$", opt, re.M)
                if m:
                    lines.append(f"custombuild {key}={m.group(1).strip()}")
        except OSError as e:
            lines.append(f"อ่าน options.conf ไม่ได้: {e}")
    urls = current_repo_urls()
    lines.append("pkg urls: " + (", ".join(urls) if urls else "-"))
    for overlay in (LATEST_REPO_FILE, REPAIR_REPO_FILE):
        lines.append(f"overlay {overlay.name}: {'มี' if overlay.is_file() else 'ไม่มี'}")
    # network probes
    for host in ("pkg.freebsd.org", "github.com", "www.php.net"):
        cp = run(["/usr/bin/host", host], dry_run=False, check=False, capture=True)
        if cp.returncode != 0:
            cp = run(["ping", "-c", "1", "-W", "2000", host], dry_run=False, check=False, capture=True)
        lines.append(
            f"dns/net {host}: {'ok' if cp.returncode == 0 else 'FAIL'} "
            f"{((cp.stdout or cp.stderr or '')[:120]).replace(chr(10), ' ')}"
        )
    # Probe package sets (FreeBSD 13 latest often 404 / EOL)
    lines.append("pkg set probes:")
    any_ok = False
    for name, url, ok in probe_pkg_sets():
        lines.append(f"  {name}: {'OK' if ok else '404/FAIL'} -> {url}")
        any_ok = any_ok or ok
    if not any_ok:
        lines.append(
            "สรุป: ไม่มี pkg set ที่ใช้ได้ — FreeBSD นี้น่าจะ EOL; "
            "อัปเดต PHP ด้วย: sudo python3 freebsd_php2shell_update.py --from-source --yes"
        )
    cp = run(["pkg", "update", "-f"], dry_run=False, check=False, capture=True)
    out = ((cp.stderr or "") + "\n" + (cp.stdout or "")).strip()
    lines.append(f"pkg update -f: exit={cp.returncode}")
    if out:
        lines.append(out[-1500:])
    for fl in ("82", "83", "84"):
        remote = pkg_remote_php_version(fl)
        lines.append(f"remote php{fl}: {remote or '-'}")
    return lines


def custombuild_update_php(dry_run: bool = False) -> ActionResult:
    """Update/rebuild PHP via DirectAdmin CustomBuild (common on FreeBSD hosting)."""
    build = find_custombuild()
    if not build:
        return ActionResult(False, "ไม่พบ DirectAdmin CustomBuild")

    details = [f"build={build}"]
    cwd = str(build.parent)

    def cb(*args: str) -> subprocess.CompletedProcess:
        cmd = [str(build), *args]
        if dry_run:
            log("DRY-RUN: " + " ".join(cmd), "warn")
            return subprocess.CompletedProcess(cmd, 0, stdout="", stderr="")
        log("CustomBuild: " + " ".join(cmd), "info")
        return subprocess.run(cmd, cwd=cwd, text=True, capture_output=True)

    # Refresh custombuild scripts/options
    r = cb("update")
    details.append(f"./build update exit={r.returncode}")
    if (r.stdout or r.stderr) and not dry_run:
        details.append(((r.stdout or "") + (r.stderr or ""))[-1200:])
    if r.returncode != 0 and not dry_run:
        return ActionResult(False, "CustomBuild ./build update ล้มเหลว", details)

    # Ensure PHP 8.2 stays selected if already 8.2 (don't change major unexpectedly)
    if CUSTOMBUILD_OPTIONS.is_file() and not dry_run:
        opt = CUSTOMBUILD_OPTIONS.read_text(encoding="utf-8", errors="replace")
        m = re.search(r"^php1_release=(.*)$", opt, re.M)
        cur = (m.group(1).strip() if m else "")
        details.append(f"php1_release={cur or '-'}")
        if cur.startswith("8.2") or cur == "8.2":
            pass
        elif not cur:
            rset = cb("set", "php1_release", "8.2")
            details.append(f"set php1_release=8.2 exit={rset.returncode}")

    # Rebuild PHP (non-interactive style used by DA)
    # 'php n' = rebuild php without asking; some versions use './build php'
    for args in (("php", "n"), ("php",)):
        r = cb(*args)
        details.append(f"./build {' '.join(args)} exit={r.returncode}")
        tail = ((r.stdout or "") + (r.stderr or "")).strip()
        if tail and not dry_run:
            details.append(tail[-2000:])
        if dry_run or r.returncode == 0:
            # verify
            ver = php_version_of(which("php") or "/usr/local/bin/php")
            details.append(f"php after custombuild: {ver or '-'}")
            need = SAFE_MIN.get((8, 2))
            if ver and need and ver.as_tuple() >= need:
                return ActionResult(True, f"CustomBuild อัปเดต PHP สำเร็จ -> {ver}", details)
            if ver and need and ver.as_tuple() < need:
                return ActionResult(
                    False,
                    f"CustomBuild รันแล้วแต่ PHP ยังเป็น {ver} (ต้องการ >= {Version(*need)})",
                    details
                    + [
                        "CustomBuild อาจยังดึงทarball 8.2.30 — รอ DA/อัปเดต versions หรือบังคับเวอร์ชันใน custom files",
                        "ตรวจ: /usr/local/directadmin/custombuild/versions.txt | grep php",
                    ],
                )
            if r.returncode == 0:
                return ActionResult(True, "CustomBuild รัน php สำเร็จ (ตรวจเวอร์ชันเอง)", details)
    return ActionResult(False, "CustomBuild rebuild PHP ล้มเหลว", details)


def pkg_remote_php_version(flavor: str) -> Optional[Version]:
    """Best available remote version for runtime package phpNN."""
    pkgname = f"php{flavor}"
    # Prefer exact package query
    for cmd in (
        ["pkg", "rquery", "%v", pkgname],
        ["pkg", "search", "-Q", "version", "-e", pkgname],
        ["pkg", "search", "-f", pkgname],
    ):
        cp = run(cmd, dry_run=False, check=False, capture=True)
        text = ((cp.stdout or "") + "\n" + (cp.stderr or "")).strip()
        if cp.returncode != 0 or not text:
            continue
        # rquery may return one line version
        versions: List[Version] = []
        for line in text.splitlines():
            line = line.strip()
            if not line:
                continue
            # formats: "8.2.35" or "php82-8.2.35" or "php82-8.2.35         PHP ..."
            m = re.search(rf"(?:^|php{flavor}-)(\d+\.\d+\.\d+)\b", line)
            if not m:
                m = re.search(r"\b(\d+\.\d+\.\d+)\b", line)
            if m:
                v = parse_version(m.group(1))
                if v and flavor_from_version(v) == flavor:
                    versions.append(v)
        if versions:
            return sorted(versions, key=lambda v: v.as_tuple())[-1]
    return None


def pkg_is_locked(pkgbase: str) -> bool:
    cp = run(["pkg", "lock", "-l"], dry_run=False, check=False, capture=True)
    text = cp.stdout or ""
    return bool(re.search(rf"(^|\s){re.escape(pkgbase)}(-\d|\s|$)", text))


def pkg_unlock(pkgbase: str, dry_run: bool = False) -> None:
    if dry_run:
        log(f"DRY-RUN: pkg unlock -y {pkgbase}", "warn")
        return
    if pkg_is_locked(pkgbase):
        run(["pkg", "unlock", "-y", pkgbase], dry_run=False, check=False, capture=True)
        log(f"ปลด lock แพ็กเกจ {pkgbase}", "ok")


def current_repo_urls() -> List[str]:
    urls: List[str] = []
    cp = run(["pkg", "-vv"], dry_run=False, check=False, capture=True)
    text = cp.stdout or ""
    for m in re.finditer(r"url\s*:\s*\"([^\"]+)\"", text):
        urls.append(m.group(1))
    for conf in SYSTEM_REPO_CANDIDATES:
        if conf.is_file():
            try:
                body = conf.read_text(encoding="utf-8", errors="replace")
            except OSError:
                continue
            for m in re.finditer(r"url\s*:\s*\"([^\"]+)\"", body):
                urls.append(m.group(1))
    # unique
    seen = set()
    out = []
    for u in urls:
        if u not in seen:
            seen.add(u)
            out.append(u)
    return out


def using_quarterly_repo() -> bool:
    return any("/quarterly" in u for u in current_repo_urls())


def freebsd_abi() -> str:
    cp = run(["uname", "-p"], dry_run=False, check=False, capture=True)
    arch = (cp.stdout or "amd64").strip() or "amd64"
    # FreeBSD major from platform.release e.g. 13.4-RELEASE
    rel = platform.release().split("-")[0]
    major = rel.split(".")[0] if rel else "13"
    return f"FreeBSD:{major}:{arch}"


def http_ok(url: str) -> bool:
    """Return True if URL looks fetchable (fetch/curl)."""
    for cmd in (
        ["fetch", "-qo", "/dev/null", url],
        ["curl", "-fsI", url],
        ["wget", "-q", "--spider", url],
    ):
        if not which(cmd[0]):
            continue
        cp = run(cmd, dry_run=False, check=False, capture=True)
        if cp.returncode == 0:
            return True
    return False


def probe_pkg_sets() -> List[Tuple[str, str, bool]]:
    """
    Probe FreeBSD package sets for current ABI.
    Returns list of (name, url_template, ok).
    """
    abi = freebsd_abi()
    # Note: EOL FreeBSD 13 often has empty/404 latest; quarterly/archive may remain.
    candidates = [
        ("quarterly", f"https://pkg.freebsd.org/{abi}/quarterly/meta.conf"),
        ("latest", f"https://pkg.freebsd.org/{abi}/latest/meta.conf"),
        ("release_4", f"https://pkg.freebsd.org/{abi}/release_4/meta.conf"),
        ("release_3", f"https://pkg.freebsd.org/{abi}/release_3/meta.conf"),
        (
            "archive-quarterly",
            f"https://pkg-archive.freebsd.org/FreeBSD/{abi.split(':')[1]}-RELEASE/{abi.split(':')[2]}/quarterly/meta.conf",
        ),
    ]
    out = []
    for name, url in candidates:
        ok = http_ok(url)
        # also try packagesite
        if not ok:
            alt = url.replace("meta.conf", "packagesite.pkg")
            ok = http_ok(alt)
        out.append((name, url, ok))
    return out


def write_repo_overlay(set_name: str, dry_run: bool = False) -> ActionResult:
    """Write /usr/local/etc/pkg/repos overlay forcing a working package set."""
    # Map set name to url path suffix
    suffix = {
        "quarterly": "quarterly",
        "latest": "latest",
        "release_4": "release_4",
        "release_3": "release_3",
    }.get(set_name, set_name)
    if set_name.startswith("archive-"):
        return ActionResult(False, "archive URL ต้องตั้งมือ — ใช้ --from-source แทนบน FreeBSD EOL")

    content = (
        f"# Auto-generated by {SCRIPT_NAME} v{SCRIPT_VERSION}\n"
        f"# Package set: {suffix}\n"
        "FreeBSD: {\n"
        f'  url: "pkg+https://pkg.FreeBSD.org/${{ABI}}/{suffix}",\n'
        '  mirror_type: "srv",\n'
        '  signature_type: "fingerprints",\n'
        '  fingerprints: "/usr/share/keys/pkg",\n'
        "  enabled: yes\n"
        "}\n"
    )
    details = [f"set={suffix}", str(REPAIR_REPO_FILE)]
    if dry_run:
        return ActionResult(True, f"DRY-RUN เขียน repo overlay -> {suffix}", details)

    REPAIR_REPO_FILE.parent.mkdir(parents=True, exist_ok=True)
    # Remove broken latest overlay created by older script versions
    if LATEST_REPO_FILE.exists():
        backup_file(LATEST_REPO_FILE, dry_run=False)
        LATEST_REPO_FILE.unlink()
        details.append(f"removed broken {LATEST_REPO_FILE}")
    if REPAIR_REPO_FILE.exists():
        backup_file(REPAIR_REPO_FILE, dry_run=False)
    REPAIR_REPO_FILE.write_text(content, encoding="utf-8")

    cp = run(["pkg", "update", "-f"], dry_run=False, check=False, capture=True)
    out = ((cp.stderr or "") + "\n" + (cp.stdout or "")).strip()
    details.append(out[-1500:] if out else f"exit={cp.returncode}")
    if cp.returncode != 0:
        return ActionResult(False, f"ตั้ง repo {suffix} แล้วแต่ pkg update ยังพัง", details)
    return ActionResult(True, f"ซ่อม pkg repo เป็นชุด {suffix} สำเร็จ", details)


def repair_pkg_repo(dry_run: bool = False) -> ActionResult:
    """
    FreeBSD 13 EOL: /latest often 404.
    Probe working sets and switch overlay automatically.
    """
    details: List[str] = [f"ABI={freebsd_abi()}"]
    probes = probe_pkg_sets()
    for name, url, ok in probes:
        details.append(f"probe {name}: {'OK' if ok else 'FAIL'} ({url})")
        log(details[-1], "ok" if ok else "warn")

    # Prefer quarterly then release_* then latest
    order = ["quarterly", "release_4", "release_3", "latest"]
    for name in order:
        hit = next((p for p in probes if p[0] == name and p[2]), None)
        if not hit:
            continue
        r = write_repo_overlay(name, dry_run=dry_run)
        details.extend(r.details)
        if r.ok or dry_run:
            return ActionResult(True, r.message, details)
        details.append(r.message)

    return ActionResult(
        False,
        "ไม่พบ pkg set ที่ใช้ได้สำหรับ ABI นี้ (FreeBSD อาจ EOL แล้ว) — ใช้ --from-source",
        details,
    )


def ensure_latest_repo(dry_run: bool = False) -> ActionResult:
    """
    Try to use a working package set. On FreeBSD 13, 'latest' often 404 —
    fall back via repair_pkg_repo().
    """
    # If latest works, use it; else repair.
    abi = freebsd_abi()
    latest_meta = f"https://pkg.freebsd.org/{abi}/latest/meta.conf"
    if http_ok(latest_meta) or http_ok(latest_meta.replace("meta.conf", "packagesite.pkg")):
        content = (
            f"# Auto-generated by {SCRIPT_NAME}\n"
            "FreeBSD: {\n"
            '  url: "pkg+https://pkg.FreeBSD.org/${ABI}/latest",\n'
            '  mirror_type: "srv",\n'
            '  signature_type: "fingerprints",\n'
            '  fingerprints: "/usr/share/keys/pkg",\n'
            "  enabled: yes\n"
            "}\n"
        )
        if dry_run:
            return ActionResult(True, "DRY-RUN เปิด latest repo", [latest_meta])
        LATEST_REPO_FILE.parent.mkdir(parents=True, exist_ok=True)
        if LATEST_REPO_FILE.exists():
            backup_file(LATEST_REPO_FILE, dry_run=False)
        LATEST_REPO_FILE.write_text(content, encoding="utf-8")
        cp = run(["pkg", "update", "-f"], dry_run=False, check=False, capture=True)
        if cp.returncode == 0:
            return ActionResult(True, f"เปิด FreeBSD latest repo แล้ว ({LATEST_REPO_FILE})")
    log("latest repo ใช้ไม่ได้ (มักเกิดบน FreeBSD 13 EOL) — ซ่อม repo อัตโนมัติ", "warn")
    return repair_pkg_repo(dry_run=dry_run)


def target_php_version_for_install(install: PhpInstall) -> Version:
    need = SAFE_MIN.get(install.version.branch())
    if need:
        return Version(*need)
    if is_eol_branch(install.version):
        return eol_migrate_version()
    return eol_migrate_version()


def find_patched_php_binary() -> Optional[str]:
    dest = eol_migrate_version()
    candidates = [
        "/usr/local/bin/php82",
        "/usr/local/bin/php8.2",
        which("php82") or "",
        which("php") or "",
        "/usr/local/bin/php",
    ]
    for path in candidates:
        if not path or not Path(path).exists():
            continue
        ver = php_version_of(path)
        if not ver:
            continue
        need = SAFE_MIN.get(ver.branch())
        if need and ver.as_tuple() >= need:
            return path
        if ver.as_tuple() >= dest.as_tuple():
            return path
    return None


def migrate_eol_to_safe(dry_run: bool = False, remove_eol: bool = False) -> ActionResult:
    """
    ย้ายค่าเริ่มต้นจาก PHP 7.4/8.0/8.1 ไป 8.2.35
    ถ้ามี php82 อยู่แล้วจะไม่คอมไพล์ซ้ำ — ชี้ php/php-fpm ไปสาขาที่แพตช์
    """
    details: List[str] = []
    dest = eol_migrate_version()
    patched = find_patched_php_binary()
    if not patched and which("pkg"):
        log(f"ยังไม่มีไบนารีที่แพตช์ — ลอง pkg install php82 เพื่อย้ายจาก 7.4", "info")
        if dry_run:
            details.append("DRY-RUN: pkg install -y php82")
        else:
            cp = run(["pkg", "install", "-y", "php82"], dry_run=False, check=False, capture=True)
            details.append(f"pkg install php82 exit={cp.returncode}")
            patched = find_patched_php_binary()
    if not patched:
        return ActionResult(
            False,
            f"ยังไม่มี PHP {dest} ให้ย้ายจาก 7.4 — ต้อง --from-source หรือ pkg install php82",
            details,
        )

    patched_ver = php_version_of(patched)
    details.append(f"ไบนารีที่แพตช์: {patched} ({patched_ver})")
    default_php = Path("/usr/local/bin/php")
    if default_php.exists():
        cur = php_version_of(str(default_php))
        details.append(f"php ปัจจุบัน: {default_php} ({cur})")
        if cur and is_eol_branch(cur) and Path(patched).resolve() != default_php.resolve():
            bak = Path(f"/usr/local/bin/php{cur.major}{cur.minor}.bak")
            if dry_run:
                details.append(f"DRY-RUN: {default_php} -> {bak}; ln -s {patched} {default_php}")
            else:
                try:
                    if not bak.exists():
                        shutil.copy2(default_php, bak)
                    default_php.unlink()
                    default_php.symlink_to(patched)
                    details.append(f"ชี้ {default_php} -> {patched} (สำรองที่ {bak})")
                except OSError as exc:
                    details.append(f"ย้าย {default_php} ไม่ได้: {exc}")

    leftover: List[str] = []
    for fl in ("70", "71", "72", "73", "74", "80", "81"):
        leftover.extend(pkg_origins_for_flavor(fl))
    if leftover:
        details.append("แพ็กเกจ EOL ที่ค้าง: " + ", ".join(leftover[:12]))
        if remove_eol:
            if dry_run:
                details.append("DRY-RUN: pkg delete -y " + " ".join(leftover[:8]))
            elif which("pkg"):
                cp = run(["pkg", "delete", "-y", *leftover], dry_run=False, check=False, capture=True)
                details.append(f"pkg delete EOL exit={cp.returncode}")
        else:
            details.append("เก็บแพ็กเกจ 7.4 ไว้ — ลบด้วย --remove-eol ถ้าไม่มีเว็บไหนใช้แล้ว")

    after = php_version_of(which("php") or "/usr/local/bin/php")
    ok = bool(after and not is_eol_branch(after))
    if ok and detect_http_front() != "nginx":
        cut = complete_eol_web_cutover(dry_run=dry_run)
        details.append(cut.message)
        details.extend(cut.details[:8])
        if not cut.ok:
            ok = False
    return ActionResult(
        ok,
        f"ย้ายจาก PHP 7.4/EOL ไป {after or dest}" + (" สำเร็จ" if ok else " ยังไม่ครบ"),
        details,
    )


def php_config_bin(flavor: Optional[str] = None) -> Optional[str]:
    candidates = []
    if flavor:
        candidates.append(f"php-config{flavor}")
        candidates.append(f"/usr/local/bin/php-config{flavor}")
    candidates.extend(["php-config", "/usr/local/bin/php-config"])
    for c in candidates:
        p = which(c) if "/" not in c else (c if Path(c).exists() else None)
        if p:
            return p
    return None


def parse_php_configure_options(raw: str) -> List[str]:
    """
    php-config --configure-options often mixes real ./configure flags with
    compiler leftovers (CFLAGS=-O2 -pipe ...). Keep only --* tokens.
    """
    raw = (raw or "").strip()
    if not raw:
        return []
    try:
        tokens = shlex.split(raw)
    except ValueError:
        tokens = raw.split()

    out: List[str] = []
    skip_prefixes = (
        "CFLAGS=",
        "CXXFLAGS=",
        "CPPFLAGS=",
        "LDFLAGS=",
        "CC=",
        "CXX=",
        "LIBS=",
    )
    for tok in tokens:
        if not tok:
            continue
        # Drop env-style assignments and bare compiler flags (-O2, -pipe, -I..., -L...)
        if tok.startswith(skip_prefixes):
            continue
        if tok.startswith("-") and not tok.startswith("--"):
            continue
        if not tok.startswith("--"):
            continue
        # Obsolete / often-breaking rebuild options
        if tok.startswith("--with-libdir=") or tok == "--disable-all":
            continue
        # mhash merged into hash in modern PHP; old php-config still emits it
        if tok.startswith("--with-mhash"):
            continue
        out.append(tok)
    return out


def freebsd_build_env() -> dict:
    """Env so configure finds libs under /usr/local on FreeBSD."""
    env = os.environ.copy()
    inc = "/usr/local/include"
    lib = "/usr/local/lib"
    pc = "/usr/local/libdata/pkgconfig:/usr/local/lib/pkgconfig"
    cpp = env.get("CPPFLAGS", "").strip()
    ld = env.get("LDFLAGS", "").strip()
    if inc not in cpp:
        env["CPPFLAGS"] = (cpp + f" -I{inc}").strip()
    if lib not in ld:
        env["LDFLAGS"] = (ld + f" -L{lib}").strip()
    env["PKG_CONFIG_PATH"] = pc + (
        (":" + env["PKG_CONFIG_PATH"]) if env.get("PKG_CONFIG_PATH") else ""
    )
    # Prefer clang on FreeBSD
    if which("clang") and not env.get("CC"):
        env["CC"] = which("clang") or "clang"
    return env


def ensure_iconv_flag(args: List[str], prefix: str) -> List[str]:
    out = [a for a in args if not a.startswith("--with-iconv")]
    out.append(f"--with-iconv={prefix}")
    return out


def default_php_configure_args() -> List[str]:
    """FreeBSD-friendly configure (enough for typical web LMS + FPM)."""
    return [
        "--prefix=/usr/local",
        "--with-config-file-path=/usr/local/etc",
        "--with-config-file-scan-dir=/usr/local/etc/php",
        "--enable-fpm",
        "--enable-cli",
        "--with-zlib",
        "--with-curl",
        "--with-openssl",
        "--with-mysqli=mysqlnd",
        "--with-pdo-mysql=mysqlnd",
        "--enable-mbstring",
        "--enable-opcache",
        "--enable-gd",
        "--with-jpeg",
        "--with-freetype",
        "--enable-bcmath",
        "--enable-exif",
        "--enable-ftp",
        "--enable-soap",
        "--enable-sockets",
        "--enable-session",
        "--with-pear",
    ]


def bare_php_configure_args() -> List[str]:
    """Last-resort configure: core only (extensions from /usr/local/etc/php still load if ABI matches)."""
    return [
        "--prefix=/usr/local",
        "--with-config-file-path=/usr/local/etc",
        "--with-config-file-scan-dir=/usr/local/etc/php",
        "--enable-cli",
        "--enable-fpm",
        "--enable-session",
        "--enable-opcache",
        "--enable-mbstring",
        "--with-zlib",
        "--with-openssl",
        "--with-curl",
        "--with-mysqli=mysqlnd",
        "--with-pdo-mysql=mysqlnd",
    ]


def write_build_log(path: Path, text: str) -> None:
    try:
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(text or "", encoding="utf-8", errors="replace")
    except OSError:
        pass


def run_php_configure(
    srcdir: Path,
    configure_args: List[str],
    log_path: Path,
    env: Optional[dict] = None,
) -> subprocess.CompletedProcess:
    cmd = ["./configure", *configure_args]
    write_build_log(log_path.with_suffix(".cmd.txt"), " ".join(shlex.quote(c) for c in cmd))
    cp = subprocess.run(
        cmd,
        cwd=str(srcdir),
        text=True,
        capture_output=True,
        env=env or freebsd_build_env(),
    )
    blob = (cp.stdout or "") + "\n" + (cp.stderr or "")
    write_build_log(log_path, blob)
    return cp


def dump_log_tail(path: Path, n: int = 80) -> List[str]:
    lines_out = [f"---- tail {n} {path} ----"]
    try:
        text = path.read_text(encoding="utf-8", errors="replace")
        lines = text.splitlines()
        for ln in lines[-n:]:
            lines_out.append(ln)
            print(Color.DIM + ln + Color.RESET)
    except OSError as e:
        lines_out.append(f"(อ่าน log ไม่ได้: {e})")
    lines_out.append("---- end log ----")
    return lines_out


def configure_php_resilient(
    srcdir: Path,
    initial_args: List[str],
    work_root: Path,
) -> Tuple[bool, List[str], List[str]]:
    """
    Run ./configure, stripping unrecognized / missing-lib options until it works.
    FreeBSD: also retries iconv prefixes (/usr/local, /usr).
    Returns (ok, final_args, detail_lines).
    """
    details: List[str] = []
    log_path = work_root / "last-configure.log"
    env = freebsd_build_env()
    details.append(f"CPPFLAGS={env.get('CPPFLAGS', '')}")
    details.append(f"LDFLAGS={env.get('LDFLAGS', '')}")

    # Seed FreeBSD iconv (base or libiconv in /usr/local)
    args = list(initial_args)
    if not any(a.startswith("--with-iconv") for a in args):
        if Path("/usr/local/include/iconv.h").exists() or Path("/usr/local/lib/libiconv.so").exists():
            args = ensure_iconv_flag(args, "/usr/local")
        else:
            args = ensure_iconv_flag(args, "/usr")

    iconv_tried: List[str] = []

    def try_configure(label: str, cur: List[str]) -> Tuple[bool, List[str], str]:
        log(f"./configure ({label}, {len(cur)} opts) ...", "info")
        cp = run_php_configure(srcdir, cur, log_path, env=env)
        err = ((cp.stderr or "") + "\n" + (cp.stdout or "")).strip()
        if cp.returncode == 0:
            return True, cur, err
        return False, cur, err

    # Iterative strip / iconv fix on primary set
    for round_i in range(50):
        ok, args, err = try_configure(f"strip-round {round_i + 1}", args)
        if ok:
            details.append(f"configure OK after strip-round {round_i + 1}")
            details.append(f"log: {log_path}")
            return True, args, details
        details.append(f"configure fail round {round_i + 1}: {err[-400:]}")

        # FreeBSD iconv errno / libiconv issues
        if re.search(r"iconv", err, re.I) and (
            re.search(r"errno|libiconv|Please install|cannot find iconv|not found", err, re.I)
            or "iconv" in err.lower()
        ):
            for prefix in ("/usr/local", "/usr"):
                if prefix in iconv_tried:
                    continue
                iconv_tried.append(prefix)
                log(f"ลอง --with-iconv={prefix}", "warn")
                args = ensure_iconv_flag(args, prefix)
                ok, args, err = try_configure(f"iconv={prefix}", args)
                if ok:
                    details.append(f"configure OK with --with-iconv={prefix}")
                    return True, args, details
            # last resort: drop iconv (app may still work via mbstring)
            if any(a.startswith("--with-iconv") for a in args):
                log("ตัด --with-iconv ชั่วคราวเพื่อให้คอมไพล์ผ่าน", "warn")
                args = [a for a in args if not a.startswith("--with-iconv")]
                continue

        m = re.search(r"unrecognized option:\s*'([^']+)'", err)
        if not m:
            m = re.search(r"unrecognized option:\s*(\S+)", err)
        if m:
            bad = m.group(1).strip()
            before = len(args)
            args = [a for a in args if a != bad and not a.startswith(bad + "=")]
            if len(args) == before:
                args = [a for a in args if bad not in a]
            if len(args) < before:
                log(f"ตัด option ที่ไม่รู้จัก: {bad}", "warn")
                continue

        drop_key = None
        for key in (
            "curl",
            "openssl",
            "zlib",
            "jpeg",
            "freetype",
            "png",
            "webp",
            "xpm",
            "avif",
            "sqlite",
            "pgsql",
            "imap",
            "ldap",
            "tidy",
            "xsl",
            "gettext",
            "gmp",
            "enchant",
            "ffi",
            "sodium",
            "argon2",
            "password-argon2",
            "zip",
            "pear",
            "apxs2",
            "apxs",
            "litespeed",
            "snmp",
            "pspell",
            "aspell",
            "readline",
            "libedit",
            "icu",
            "onig",
            "external-pcre",
            "pcre-jit",
            "gd",
            "mhash",
            "imap",
        ):
            if re.search(rf"\b{re.escape(key)}\b", err, re.I) and re.search(
                r"error:|cannot|not found|unable|Please reinstall|Package",
                err,
                re.I,
            ):
                drop_key = key
                break
        if drop_key:
            before = len(args)
            args = [
                a
                for a in args
                if drop_key not in a.lower()
                or a.startswith("--prefix")
                or a.startswith("--with-config-file")
                or a in ("--enable-cli", "--enable-session")
            ]
            if len(args) < before:
                log(f"ตัด option ที่เกี่ยวกับ dependency: {drop_key}", "warn")
                continue

        break

    # Alternate presets with FreeBSD iconv
    for name, preset in (
        ("default", default_php_configure_args()),
        ("bare", bare_php_configure_args()),
    ):
        for prefix in ("/usr/local", "/usr"):
            args = ensure_iconv_flag(list(preset), prefix)
            ok, args, err = try_configure(f"{name}+iconv={prefix}", args)
            if ok:
                details.append(f"configure OK with preset={name} iconv={prefix}")
                details.append(f"log: {log_path}")
                return True, args, details
            details.append(f"configure preset {name} iconv={prefix} fail: {err[-300:]}")

            # strip unrecognized inside preset
            for _ in range(15):
                m = re.search(r"unrecognized option:\s*'([^']+)'", err)
                if not m:
                    break
                bad = m.group(1).strip()
                args = [a for a in args if a != bad and not a.startswith(bad + "=")]
                ok, args, err = try_configure(f"{name} strip {bad}", args)
                if ok:
                    details.append(f"configure OK with preset={name} after strips")
                    return True, args, details

    details.append(f"configure FAILED — ดูเต็ม: {log_path}")
    details.append(f"คำสั่ง: tail -100 {log_path}")
    details.extend(dump_log_tail(log_path, 80))
    return False, args, details


def web_vulnerable_branches() -> set:
    """Branches (maj,min) whose WEB-facing SAPI is still below safe minimum."""
    bad = set()
    for sapi, v, _ in detect_web_php_versions():
        if v is None:
            continue
        if not (sapi.startswith("web:") or sapi.startswith("fpm:")):
            continue
        need = SAFE_MIN.get(v.branch())
        if need and v.as_tuple() < need:
            bad.add(v.branch())
    return bad


def build_php_from_source(
    installs: List[PhpInstall],
    flavors: Optional[Sequence[str]] = None,
    dry_run: bool = False,
    force: bool = False,
) -> ActionResult:
    """
    Compile/install patched PHP from official tarball when pkg repos are dead.
    Reuses existing php-config --configure-options when available.

    force=True (or a still-vulnerable web SAPI) rebuilds even when the CLI is
    already patched — needed to regenerate mod_php (libphp.so) for Apache.
    """
    wanted = [i for i in installs if not flavors or i.flavor in set(flavors)]
    if not wanted:
        wanted = installs
    if not wanted:
        return ActionResult(False, "ไม่พบ PHP สำหรับคอมไพล์จากซอร์ส")

    details: List[str] = []
    work_root = Path("/usr/local/src/php2shell-build")
    success_any = False

    # Detect which branches are still vulnerable on the WEB (mod_php/FPM),
    # so we rebuild them even if the CLI binary is already patched.
    web_bad = web_vulnerable_branches()
    apxs_present = apache_apxs_path() is not None
    if web_bad:
        details.append(
            "web ยังเสี่ยงที่สาขา: "
            + ", ".join(f"{b[0]}.{b[1]}" for b in sorted(web_bad))
        )

    for inst in wanted:
        target = target_php_version_for_install(inst)
        branch = inst.version.branch()
        if is_eol_branch(inst.version):
            already = next(
                (
                    i
                    for i in installs
                    if i.status == "safe" and i.version.as_tuple() >= target.as_tuple()
                ),
                None,
            )
            if already:
                log(
                    f"php{inst.flavor} {inst.version} เป็น EOL — "
                    f"มี {already.version} แล้ว จะย้ายค่าเริ่มต้นไป 8.2.35 ไม่คอมไพล์ซ้ำ",
                    "step",
                )
                mr = migrate_eol_to_safe(dry_run=dry_run, remove_eol=False)
                details.append(mr.message)
                details.extend(mr.details[:10])
                success_any = success_any or mr.ok
                continue
            log(
                f"php{inst.flavor} {inst.version} เป็น EOL — จะติดตั้ง {target} แทนการแพตช์ 7.4",
                "step",
            )
        needs_web_rebuild = branch in web_bad  # mod_php/FPM behind
        if (
            inst.version.as_tuple() >= target.as_tuple()
            and not force
            and not needs_web_rebuild
        ):
            details.append(f"php{inst.flavor}: ปลอดภัยแล้ว ({inst.version})")
            success_any = True
            continue
        if inst.version.as_tuple() >= target.as_tuple() and (force or needs_web_rebuild):
            reason = "force" if force else "เว็บ (mod_php/FPM) ยังเก่า"
            log(
                f"php{inst.flavor}: CLI ปลอดภัยแล้วแต่ต้อง build ใหม่ ({reason}) "
                "เพื่อสร้าง mod_php/libphp.so ให้เว็บ",
                "warn",
            )
            details.append(f"php{inst.flavor}: rebuild เพราะ {reason}")
            if needs_web_rebuild and not apxs_present:
                details.append(
                    "เตือน: ไม่พบ apxs — จะ build CLI/FPM แต่สร้าง mod_php ไม่ได้ "
                    "(พิจารณาสลับ Apache ไปใช้ php-fpm)"
                )

        ver = str(target)
        details.append(f"build php{inst.flavor}: {inst.version} -> {ver}")
        log(f"คอมไพล์ PHP {ver} จากซอร์ส (แทน pkg ที่ 404)", "step")
        log(f"script {SCRIPT_VERSION} — ต้องเห็นเวอร์ชันนี้บนจอ", "info")

        if dry_run:
            details.append(f"DRY-RUN: fetch/configure/make install php-{ver}")
            success_any = True
            continue

        # tools — PHP on FreeBSD needs GNU make (gmake), not BSD make
        if not which("fetch") and not which("curl"):
            return ActionResult(False, "ต้องมี fetch หรือ curl เพื่อดาวน์โหลดซอร์ส", details)
        if not which("cc") and not which("clang") and not which("gcc"):
            return ActionResult(False, "ไม่พบ C compiler (cc/clang)", details)
        if not which("tar"):
            return ActionResult(False, "ไม่พบ tar", details)

        # Best-effort: install common build deps (may fail if repo dead — ignore)
        # libiconv: FreeBSD PHP configure มักพังที่ "iconv ... errno" ถ้าไม่ชี้ path ถูก
        build_pkgs = [
            "autoconf",
            "automake",
            "libtool",
            "re2c",
            "pkgconf",
            "bison",
            "gmake",
            "libiconv",
            "openssl",
            "curl",
            "libxml2",
            "oniguruma",
        ]
        if which("pkg") and not dry_run:
            log("พยายามติดตั้ง dependency สำหรับคอมไพล์ (ถ้า pkg repo ใช้ได้)", "info")
            run(["pkg", "install", "-y", *build_pkgs], dry_run=False, check=False, capture=True)

        make_bin = which("gmake") or which("make")
        if not make_bin:
            return ActionResult(
                False,
                "ไม่พบ gmake/make — ติดตั้ง: pkg install gmake",
                details,
            )
        if "gmake" not in Path(make_bin).name:
            log("ไม่พบ gmake — ใช้ make (อาจพังบน FreeBSD; แนะนำ pkg install gmake)", "warn")
        details.append(f"make_bin={make_bin}")

        work_root.mkdir(parents=True, exist_ok=True)
        tarball = work_root / f"php-{ver}.tar.gz"
        srcdir = work_root / f"php-{ver}"
        make_log = work_root / "last-make.log"

        # download
        downloaded = False
        if tarball.is_file() and tarball.stat().st_size > 1_000_000:
            downloaded = True
            details.append(f"ใช้ทarballที่มีอยู่: {tarball}")
        else:
            for tmpl in PHP_SOURCE_MIRRORS:
                url = tmpl.format(ver=ver)
                log(f"ดาวน์โหลด {url}", "info")
                if which("fetch"):
                    cp = run(["fetch", "-o", str(tarball), url], dry_run=False, check=False, capture=True)
                else:
                    cp = run(["curl", "-fL", "-o", str(tarball), url], dry_run=False, check=False, capture=True)
                if cp.returncode == 0 and tarball.is_file() and tarball.stat().st_size > 1_000_000:
                    downloaded = True
                    details.append(f"downloaded: {url}")
                    break
                details.append(f"download fail: {url} exit={cp.returncode}")
        if not downloaded:
            return ActionResult(
                False,
                f"ดาวน์โหลด php-{ver}.tar.gz ไม่สำเร็จ",
                details
                + [
                    "ตรวจเน็ตออกนอก/ไฟร์วอลล์ หรือดาวน์โหลดมือแล้ววางที่ "
                    f"{tarball}",
                ],
            )

        # extract
        if srcdir.exists():
            shutil.rmtree(srcdir)
        cp = run(["tar", "-xzf", str(tarball), "-C", str(work_root)], dry_run=False, check=False, capture=True)
        if cp.returncode != 0 or not srcdir.is_dir():
            return ActionResult(False, "แตกทarball ไม่สำเร็จ", details + [(cp.stderr or cp.stdout or "")[-800:]])

        # configure options from current build (strip compiler junk like -pipe)
        cfg = php_config_bin(inst.flavor)
        configure_args: List[str] = []
        if cfg:
            cp = run([cfg, "--configure-options"], dry_run=False, check=False, capture=True)
            raw_opts = (cp.stdout or "").strip()
            details.append(f"php-config: {cfg}")
            if raw_opts:
                details.append(f"php-config raw (trunc): {raw_opts[:400]}")
                configure_args = parse_php_configure_options(raw_opts)
                details.append(f"configure args filtered: {len(configure_args)} flags")
        if not configure_args:
            configure_args = default_php_configure_args()
            details.append("ใช้ default configure args (ไม่มี/ใช้ php-config ไม่ได้)")
        if not any(a.startswith("--prefix=") for a in configure_args):
            configure_args.insert(0, "--prefix=/usr/local")
        for flag in (
            "--enable-fpm",
            "--enable-cli",
            "--with-config-file-scan-dir=/usr/local/etc/php",
        ):
            key = flag.split("=")[0]
            if not any(a == flag or a.startswith(key + "=") for a in configure_args):
                configure_args.append(flag)

        # Rebuild Apache module (libphp.so) too, so the WEB serves the patched
        # version — not just the CLI. This was the gap that left mod_php at 8.2.30.
        apxs = apache_apxs_path()
        if apxs and not any(a.startswith("--with-apxs2") for a in configure_args):
            configure_args.append(f"--with-apxs2={apxs}")
            details.append(f"เพิ่ม --with-apxs2={apxs} เพื่อสร้าง mod_php (libphp.so) ใหม่ให้เว็บ")
            log("พบ Apache (apxs) — จะคอมไพล์ mod_php ใหม่ให้ตรงเวอร์ชันด้วย", "info")

        ok_cfg, configure_args, cfg_details = configure_php_resilient(
            srcdir, configure_args, work_root
        )
        details.extend(cfg_details)
        if not ok_cfg:
            return ActionResult(
                False,
                "configure PHP ล้มเหลว — ส่ง output ของ: "
                f"tail -100 {work_root / 'last-configure.log'}",
                details,
            )

        ncpu = (run(["sysctl", "-n", "hw.ncpu"], dry_run=False, check=False, capture=True).stdout or "2").strip()
        try:
            jobs = max(1, min(8, int(ncpu)))
        except ValueError:
            jobs = 2
        build_env = freebsd_build_env()
        log(f"{Path(make_bin).name} -j{jobs}", "info")
        cp = subprocess.run(
            [make_bin, f"-j{jobs}"],
            cwd=str(srcdir),
            text=True,
            capture_output=True,
            env=build_env,
        )
        write_build_log(make_log, (cp.stdout or "") + "\n" + (cp.stderr or ""))
        details.append(f"make exit={cp.returncode} log={make_log}")
        if cp.returncode != 0:
            details.extend(dump_log_tail(make_log, 60))
            return ActionResult(
                False,
                f"make PHP ล้มเหลว — ดู: tail -100 {make_log}",
                details + [((cp.stderr or "") + (cp.stdout or ""))[-2000:]],
            )

        log(f"{Path(make_bin).name} install", "info")
        cp = subprocess.run(
            [make_bin, "install"],
            cwd=str(srcdir),
            text=True,
            capture_output=True,
            env=build_env,
        )
        write_build_log(work_root / "last-make-install.log", (cp.stdout or "") + "\n" + (cp.stderr or ""))
        details.append(f"make install exit={cp.returncode}")
        if cp.returncode != 0:
            return ActionResult(
                False,
                f"make install ล้มเหลว — ดู: tail -100 {work_root / 'last-make-install.log'}",
                details + [((cp.stderr or "") + (cp.stdout or ""))[-2000:]],
            )

        # Ensure mitigation drop-in remains
        drop = Path("/usr/local/etc/php/99-php2shell.ini")
        drop.parent.mkdir(parents=True, exist_ok=True)
        drop.write_text(
            "; PHP2Shell mitigation CVE-2026-109618 / CVE-2026-109619\n"
            "session.upload_progress.enabled = Off\n",
            encoding="utf-8",
        )

        new_ver = php_version_of(inst.binary if Path(inst.binary).exists() else (which("php") or "/usr/local/bin/php"))
        details.append(f"php after source build: {new_ver or '-'}")
        if new_ver and new_ver.as_tuple() >= target.as_tuple():
            success_any = True
            log(f"คอมไพล์สำเร็จ: PHP {new_ver}", "ok")
        else:
            return ActionResult(
                False,
                f"ติดตั้งจากซอร์สแล้ว แต่เวอร์ชันยังเป็น {new_ver or '-'} (ต้องการ >= {target})",
                details,
            )

    if success_any:
        return ActionResult(True, "คอมไพล์/ติดตั้ง PHP จากซอร์สสำเร็จ", details)
    return ActionResult(False, "คอมไพล์จากซอร์สไม่สำเร็จ", details)


def min_safe_for_flavor(flavor: str) -> Optional[Version]:
    if len(flavor) != 2 or not flavor.isdigit():
        return None
    major = int(flavor[0])
    minor = int(flavor[1])
    key = (major, minor)
    if key not in SAFE_MIN:
        return None
    m = SAFE_MIN[key]
    return Version(*m)


def pkg_upgrade_php(
    installs: List[PhpInstall],
    flavors: Optional[Sequence[str]] = None,
    dry_run: bool = False,
    use_latest_repo: bool = True,
) -> ActionResult:
    wanted_flavors = set(flavors) if flavors else {i.flavor for i in installs if i.flavor}
    if not wanted_flavors:
        return ActionResult(False, "ไม่มี flavor PHP ที่จะอัปเดต")

    details: List[str] = []
    # 1) Prefer latest repo when quarterly cannot satisfy safe version
    need_latest = False
    remote_map: Dict[str, Optional[Version]] = {}
    for fl in sorted(wanted_flavors):
        remote = pkg_remote_php_version(fl)
        remote_map[fl] = remote
        need = min_safe_for_flavor(fl)
        details.append(
            f"php{fl}: remote={remote or '-'}  need>={need or '-'}"
        )
        if need and (remote is None or remote.as_tuple() < need.as_tuple()):
            need_latest = True

    if use_latest_repo and (need_latest or using_quarterly_repo()):
        log("แพ็กเกจใน repo ปัจจุบันยังไม่ถึงเกณฑ์ปลอดภัย — สลับไปชุด latest", "warn")
        r = ensure_latest_repo(dry_run=dry_run)
        details.extend(r.details)
        details.append(r.message)
        log(r.message, "ok" if r.ok else "err")
        if not r.ok and not dry_run:
            return ActionResult(False, "เปิด latest repo ไม่สำเร็จ", details)
        # refresh remote versions
        for fl in sorted(wanted_flavors):
            remote_map[fl] = pkg_remote_php_version(fl)
            details.append(f"php{fl}: remote(after latest)={remote_map[fl] or '-'}")

    # 2) Unlock + upgrade/install each runtime and related packages
    to_touch: List[str] = []
    for fl in sorted(wanted_flavors):
        pkgs = pkg_origins_for_flavor(fl)
        bases = sorted({re.sub(r"-\d.*$", "", p) for p in pkgs}) or [f"php{fl}"]
        # Ensure runtime package is first
        runtime = f"php{fl}"
        if runtime not in bases:
            bases.insert(0, runtime)
        else:
            bases = [runtime] + [b for b in bases if b != runtime]
        to_touch.extend(bases)

    seen = set()
    pkgs_unique = []
    for p in to_touch:
        if p not in seen:
            seen.add(p)
            pkgs_unique.append(p)
    details.append("packages: " + ", ".join(pkgs_unique))

    for base in pkgs_unique:
        pkg_unlock(base, dry_run=dry_run)

    # Force reinstall/upgrade of runtime packages first
    runtime_pkgs = [f"php{fl}" for fl in sorted(wanted_flavors)]
    cmds = [
        ["pkg", "upgrade", "-y", "-f", *runtime_pkgs],
        ["pkg", "install", "-y", "-f", *runtime_pkgs],
        ["pkg", "upgrade", "-y", *pkgs_unique],
    ]
    last_out = ""
    if dry_run:
        for c in cmds:
            details.append("DRY-RUN: " + " ".join(c))
        return ActionResult(True, "DRY-RUN pkg upgrade/install PHP packages", details)

    ok_any = False
    for cmd in cmds:
        log("รัน: " + " ".join(cmd), "info")
        cp = run(cmd, dry_run=False, check=False, capture=True)
        last_out = ((cp.stdout or "") + (cp.stderr or "")).strip()
        details.append(f"$ {' '.join(cmd)} -> exit {cp.returncode}")
        if cp.returncode == 0:
            ok_any = True
            # Don't stop early; still try full related package upgrade once runtime ok
            if cmd[1] == "upgrade" and "-f" in cmd:
                continue
            if cmd[1] == "install":
                break

    # Final related packages upgrade
    cp = run(["pkg", "upgrade", "-y", *pkgs_unique], dry_run=False, check=False, capture=True)
    last_out = ((cp.stdout or "") + (cp.stderr or "")).strip() or last_out
    if cp.returncode == 0:
        ok_any = True

    # 3) Verify remote/local now meet safe minimums
    still_bad: List[str] = []
    for fl in sorted(wanted_flavors):
        need = min_safe_for_flavor(fl)
        remote = pkg_remote_php_version(fl)
        # local from php binary if possible
        local_ver = None
        bin_candidates = [f"/usr/local/bin/php{fl}", "/usr/local/bin/php"]
        for b in bin_candidates:
            if Path(b).exists():
                local_ver = php_version_of(b)
                if local_ver and flavor_from_version(local_ver) == fl:
                    break
                local_ver = None
        # also from pkg info
        if local_ver is None:
            cpinfo = run(["pkg", "query", "%v", f"php{fl}"], dry_run=False, check=False, capture=True)
            local_ver = parse_version((cpinfo.stdout or "").strip())
        details.append(f"php{fl}: local={local_ver or '-'} remote={remote or '-'}")
        if need and (local_ver is None or local_ver.as_tuple() < need.as_tuple()):
            still_bad.append(
                f"php{fl} ยังเป็น {local_ver or '-'} (ต้องการ >= {need}; remote={remote or 'ไม่มีใน repo'})"
            )

    if still_bad:
        tip = [
            "repo ยังไม่มีแพ็กเกจที่แพตช์ หรือ host ล็อกเวอร์ชัน PHP ไว้",
            "ตรวจ: pkg rquery %n-%v php82",
            "ถ้าใช้ DirectAdmin/custom repo ต้องกดอัปเดตจากแผงควบคุม หรือรอ vendor ปล่อยแพตช์",
            "ทางเลือก: สร้างจาก ports — cd /usr/ports/lang/php82 && make BATCH=yes deinstall install clean",
        ]
        return ActionResult(False, "อัปเดตแล้วยังไม่ถึงเกณฑ์ปลอดภัย", details + still_bad + tip)

    if not ok_any:
        return ActionResult(False, "pkg upgrade/install ล้มเหลว", details + [last_out[-4000:]])
    return ActionResult(True, "pkg upgrade/install PHP สำเร็จและถึงเกณฑ์ปลอดภัย", details)


def pkg_audit_php(dry_run: bool = False) -> ActionResult:
    if dry_run:
        return ActionResult(True, "DRY-RUN pkg audit")
    if not which("pkg"):
        return ActionResult(False, "ไม่มี pkg")
    # Refresh vuln db if possible
    run(["pkg", "audit", "-F"], dry_run=False, check=False, capture=True)
    cp = run(["pkg", "audit", "-q"], dry_run=False, check=False, capture=True)
    text = (cp.stdout or "") + (cp.stderr or "")
    php_hits = [ln for ln in text.splitlines() if re.search(r"php\d{2}|PHP", ln, re.I)]
    if php_hits:
        return ActionResult(False, "pkg audit พบรายการเกี่ยวกับ PHP", php_hits[:50])
    if cp.returncode == 0:
        return ActionResult(True, "pkg audit ไม่พบช่องโหว่ที่รายงาน (หรือไม่มีข้อมูล)")
    return ActionResult(True, "pkg audit เสร็จ (ตรวจผลเพิ่มในรายละเอียด)", [text[-1500:]])


def service_exists(name: str) -> bool:
    # FreeBSD service(8)
    cp = run(["service", "-e"], dry_run=False, check=False)
    enabled = {ln.strip().split("/")[-1] for ln in (cp.stdout or "").splitlines() if ln.strip()}
    if name in enabled:
        return True
    # Also check rc.d script presence
    for base in ("/usr/local/etc/rc.d", "/etc/rc.d"):
        if Path(base, name).exists():
            return True
    return False


def find_php_conf_dir() -> Optional[Path]:
    """Locate the dir holding ext-*.ini (FreeBSD: /usr/local/etc/php)."""
    for c in PHP_CONF_DIR_CANDIDATES:
        p = Path(c)
        if p.is_dir():
            return p
    # Derive from php -i (Scan this dir)
    php = which("php")
    if php:
        cp = run([php, "-i"], dry_run=False, check=False, capture=True)
        m = re.search(r"Scan this dir for additional \.ini files\s*=>\s*(\S+)", cp.stdout or "")
        if m and Path(m.group(1)).is_dir():
            return Path(m.group(1))
    return None


def _php_module_set(php_bin: str, extra_args: Optional[Sequence[str]] = None) -> set:
    """Return lowercased set of loaded modules from `php [extra] -m`."""
    cmd = [php_bin]
    if extra_args:
        cmd.extend(extra_args)
    cmd.append("-m")
    cp = run(cmd, dry_run=False, check=False, capture=True)
    mods = set()
    for ln in (cp.stdout or "").splitlines():
        ln = ln.strip()
        if not ln or ln.startswith("[") or ln.startswith("Warning") or "already loaded" in ln:
            continue
        mods.add(ln.lower())
    return mods


def php_builtin_modules(php_bin: str) -> set:
    """Modules statically compiled into PHP (php -n -m ignores all ini files)."""
    return _php_module_set(php_bin, ["-n"])


def php_loaded_modules(php_bin: str) -> set:
    """Modules currently active with normal ini loading."""
    return _php_module_set(php_bin)


def php_startup_warnings(php_bin: str) -> List[str]:
    """Capture 'Unable to load' / 'already loaded' startup warnings."""
    cp = run([php_bin, "-v"], dry_run=False, check=False, capture=True)
    blob = (cp.stderr or "") + "\n" + (cp.stdout or "")
    out = []
    for ln in blob.splitlines():
        if "Unable to load dynamic library" in ln or "already loaded" in ln:
            out.append(ln.strip())
    return out


def classify_extension_warnings(php_bin: str) -> Tuple[List[str], List[str]]:
    """Split startup warnings into (already_loaded, unable_to_load)."""
    already: List[str] = []
    missing: List[str] = []
    for ln in php_startup_warnings(php_bin):
        m = re.search(r'Module "([^"]+)" is already loaded', ln)
        if m:
            already.append(m.group(1))
            continue
        m2 = re.search(r"Unable to load dynamic library '([^']+)'", ln)
        if m2:
            missing.append(Path(m2.group(1)).name.replace(".so", ""))
            continue
    return already, missing


def report_extension_status(php_bin: str) -> None:
    """Print a readable extension report (ไม่ใช่ dump คำเตือนยาว ๆ)."""
    already, missing = classify_extension_warnings(php_bin)
    builtin = sorted(php_builtin_modules(php_bin))
    loaded = sorted(php_loaded_modules(php_bin))
    log("สถานะ extension (CLI):", "step")
    print(f"    builtin (php -n): {len(builtin)} โมดูล")
    print(f"    โหลดจริง:         {len(loaded)} โมดูล")
    if already:
        log(
            f"โหลดซ้ำ {len(already)} โมดูล (builtin + ext-*.ini) — ไม่ทำให้เว็บพัง: "
            + ", ".join(already[:12])
            + ("…" if len(already) > 12 else ""),
            "warn",
        )
        log("เก็บกวาดได้หลังเว็บกับ CLI เป็นเวอร์ชันเดียวกัน: --clean-ext-dups", "info")
    if missing:
        log(
            "โหลด .so ไม่ได้ (ปัญหาจริง): " + ", ".join(missing),
            "err",
        )
        log("ลอง: --align-ext-dir แล้ว --restore-ext", "info")
    if not already and not missing:
        log("ไม่มีคำเตือนโหลดซ้ำ / หา .so ไม่เจอ", "ok")


def extension_module_name(ini_path: Path) -> Optional[str]:
    """Parse `extension=NAME(.so)` from an ini file; ignore zend_extension."""
    try:
        text = ini_path.read_text(encoding="utf-8", errors="replace")
    except OSError:
        return None
    for ln in text.splitlines():
        s = ln.strip()
        if s.startswith(";") or s.startswith("#"):
            continue
        m = re.match(r"^extension\s*=\s*(.+)$", s, re.I)
        if not m:
            continue
        val = m.group(1).strip().strip('"').strip("'")
        val = Path(val).name  # drop any path
        if val.endswith(".so"):
            val = val[:-3]
        return val
    return None


def php_compiled_extension_dir(php_bin: str) -> Optional[str]:
    """PHP_EXTENSION_DIR ที่คอมไพล์มากับไบนารี (ไม่ดู ini)."""
    if not php_bin or not Path(php_bin).exists():
        return None
    cp = run(
        [php_bin, "-n", "-r", "echo PHP_EXTENSION_DIR;"],
        dry_run=False,
        check=False,
        capture=True,
    )
    val = (cp.stdout or "").strip()
    if val and "PHP_EXTENSION_DIR" not in val and val.startswith("/"):
        return val
    return None


def best_extension_dir(php_bin: Optional[str] = None) -> Optional[str]:
    """Pick the 8.2+ extension dir. Never point a patched PHP at leftover 7.4 ABI."""
    compiled = php_compiled_extension_dir(php_bin) if php_bin else None
    candidates: List[str] = []
    if compiled:
        candidates.append(compiled)
    candidates.extend(EXTENSION_DIR_CANDIDATES)
    best = None
    best_count = -1
    for d in candidates:
        if any(api in d for api in EOL_ZEND_APIS):
            continue
        p = Path(d)
        if not p.is_dir():
            continue
        if compiled and os.path.normpath(d) == os.path.normpath(compiled):
            return d
        count = len(list(p.glob("*.so")))
        if count > best_count:
            best_count = count
            best = d
    return best or compiled


def current_extension_dir(php_bin: str) -> Optional[str]:
    cp = run([php_bin, "-i"], dry_run=False, check=False, capture=True)
    for ln in (cp.stdout or "").splitlines():
        m = re.match(r"^extension_dir\s*=>\s*(\S+)", ln)
        if m:
            return m.group(1)
    return None


def align_extension_dir(php_bin: str, dry_run: bool = False) -> ActionResult:
    """
    Point extension_dir at the dir that holds the real *.so files, so a
    source-built PHP (8.2.x) can load the pkg-built extensions (same ABI
    20220829). Fixes 'Unable to load pdo_pgsql.so / xmlreader.so' on FPM/CLI.
    """
    if not Path(php_bin).exists():
        php_bin = which("php") or php_bin
    best = best_extension_dir(php_bin)
    if not best:
        return ActionResult(False, "ไม่พบ extension dir ของสาขาที่แพตช์")
    cur = current_extension_dir(php_bin)
    cnt = len(list(Path(best).glob("*.so")))
    details = [f"extension_dir ปัจจุบัน: {cur or '-'}", f"ที่มี .so มากสุด: {best} ({cnt} ไฟล์)"]
    if cur and os.path.normpath(cur) == os.path.normpath(best):
        return ActionResult(True, "extension_dir ตรงแล้ว", details)
    if dry_run:
        return ActionResult(True, f"DRY-RUN จะตั้ง extension_dir={best}", details)
    Path(EXTENSION_DIR_DROPIN).parent.mkdir(parents=True, exist_ok=True)
    Path(EXTENSION_DIR_DROPIN).write_text(
        "; php2shell: ให้ทุก SAPI (CLI/FPM/mod_php) ใช้ extension dir เดียวกัน\n"
        f"extension_dir = {best}\n",
        encoding="utf-8",
    )
    details.append(f"เขียน {EXTENSION_DIR_DROPIN}")
    new = current_extension_dir(php_bin)
    details.append(f"extension_dir ใหม่: {new or '-'}")
    return ActionResult(True, f"ตั้ง extension_dir -> {best}", details)


def check_critical_php_health(php_bin: str) -> ActionResult:
    """Ensure PHP runs and critical functions exist (e.g. filter_var)."""
    if not Path(php_bin).exists():
        php_bin = which("php") or php_bin
    checks = ";".join(
        f"echo function_exists('{fn}')?'{fn}=1 ':'{fn}=0 ';" for fn in CRITICAL_PHP_FUNCS
    )
    cp = run(
        [php_bin, "-d", "error_reporting=0", "-d", "display_errors=0", "-r", checks],
        dry_run=False,
        check=False,
        capture=True,
    )
    out = (cp.stdout or "").strip()
    missing = [fn for fn in CRITICAL_PHP_FUNCS if f"{fn}=0" in out]
    # filter_var & preg_match & json_encode are the real blockers for most apps
    hard_missing = [fn for fn in ("filter_var", "preg_match", "json_encode") if fn in missing]
    if cp.returncode != 0:
        return ActionResult(False, "PHP รันไม่ผ่าน (health check)", [out[-400:]])
    if hard_missing:
        return ActionResult(
            False,
            "ฟังก์ชันสำคัญหาย: " + ", ".join(hard_missing),
            [out, "คืน extension ด้วย: --restore-ext"],
        )
    return ActionResult(True, "PHP health OK", [out])


def restore_extension_inis(dry_run: bool = False) -> ActionResult:
    """Move every ext ini back from disabled-dup/ (the proven recovery step)."""
    conf_dir = find_php_conf_dir()
    if not conf_dir:
        return ActionResult(False, "ไม่พบไดเรกทอรี .ini ของ PHP")
    disabled = conf_dir / DISABLED_DUP_DIRNAME
    if not disabled.is_dir():
        return ActionResult(True, "ไม่มีโฟลเดอร์ disabled-dup — ไม่มีอะไรต้องคืน", [str(disabled)])
    files = sorted(disabled.glob("*.ini"))
    if not files:
        return ActionResult(True, "disabled-dup ว่าง — ไม่มี ext ini ให้คืน")
    if dry_run:
        return ActionResult(True, f"DRY-RUN คืน {len(files)} ext ini", [f.name for f in files])
    restored = []
    for f in files:
        dest = conf_dir / f.name
        try:
            shutil.move(str(f), str(dest))
            restored.append(f.name)
        except OSError as e:
            return ActionResult(False, f"คืน {f.name} ไม่ได้: {e}", restored)
    return ActionResult(
        True,
        f"คืน extension {len(restored)} ไฟล์จาก disabled-dup -> {conf_dir}",
        restored,
    )


def comment_builtin_extensions_in_file(path: Path, builtin: set, dry_run: bool = False) -> List[str]:
    """Comment active extension= lines whose module is already compiled in."""
    if not path.is_file():
        return []
    try:
        text = path.read_text(encoding="utf-8", errors="replace")
    except OSError:
        return []
    changed = []
    out_lines = []
    for ln in text.splitlines(keepends=True):
        raw = ln.rstrip("\n")
        s = raw.strip()
        if s.startswith(";") or s.startswith("#") or not re.match(r"^extension\s*=", s, re.I):
            out_lines.append(ln)
            continue
        m = re.match(r"^extension\s*=\s*(.+)$", s, re.I)
        val = (m.group(1).strip().strip("\"'") if m else "")
        val = Path(val).name
        if val.endswith(".so"):
            val = val[:-3]
        if val.lower() in builtin:
            if not dry_run:
                out_lines.append("; disabled-dup (builtin) " + raw + "\n")
            else:
                out_lines.append(ln)
            changed.append(f"{path.name}:{val}")
        else:
            out_lines.append(ln)
    if changed and not dry_run:
        backup_file(path, dry_run=False)
        path.write_text("".join(out_lines), encoding="utf-8")
    return changed


def disable_missing_extension_inis(php_bin: str, dry_run: bool = False) -> ActionResult:
    """
    ปิด extension= ที่โหลด .so ไม่ได้ (เช่น leftover php74-openssl หลังย้ายไป 8.2
    ที่คอมไพล์ --with-openssl แล้วไม่มี openssl.so ใน 20220829)
    """
    if not Path(php_bin).exists():
        php_bin = which("php") or php_bin
    _, missing = classify_extension_warnings(php_bin)
    if not missing:
        return ActionResult(True, "ไม่มี .so ที่โหลดไม่สำเร็จ")
    conf_dir = find_php_conf_dir()
    if not conf_dir:
        return ActionResult(False, "ไม่พบไดเรกทอรี .ini ของ PHP")
    want = {m.lower() for m in missing}
    changed: List[str] = []
    inis = sorted(conf_dir.glob("*.ini")) + [
        Path("/usr/local/etc/php.ini"),
        Path("/usr/local/etc/php/php.ini"),
    ]
    seen = set()
    for path in inis:
        if not path.is_file():
            continue
        try:
            key = str(path.resolve())
        except OSError:
            key = str(path)
        if key in seen:
            continue
        seen.add(key)
        if path.name.endswith("php2shell.ini") or path.name == Path(EXTENSION_DIR_DROPIN).name:
            continue
        try:
            text = path.read_text(encoding="utf-8", errors="replace")
        except OSError:
            continue
        out_lines = []
        file_changed = False
        for ln in text.splitlines(keepends=True):
            raw = ln.rstrip("\n")
            s = raw.strip()
            if s.startswith(";") or s.startswith("#") or not re.match(r"^extension\s*=", s, re.I):
                out_lines.append(ln)
                continue
            m = re.match(r"^extension\s*=\s*(.+)$", s, re.I)
            val = (m.group(1).strip().strip("\"'") if m else "")
            val = Path(val).name
            if val.endswith(".so"):
                val = val[:-3]
            if val.lower() in want:
                if not dry_run:
                    out_lines.append("; php2shell: .so หายหรือ ABI ไม่ตรง " + raw + "\n")
                else:
                    out_lines.append(ln)
                file_changed = True
                changed.append(f"{path.name}:{val}")
            else:
                out_lines.append(ln)
        if file_changed and not dry_run:
            backup_file(path, dry_run=False)
            path.write_text("".join(out_lines), encoding="utf-8")
    if changed:
        return ActionResult(
            True,
            f"ปิด extension ที่โหลด .so ไม่ได้ {len(changed)} รายการ",
            changed,
        )
    return ActionResult(
        True,
        "พบ .so หายแต่ไม่เจอ ini ที่ตรงกัน — ตรวจ " + ", ".join(sorted(want)),
        sorted(want),
    )


def clean_duplicate_extension_inis(php_bin: str, dry_run: bool = False) -> ActionResult:
    """
    Safely silence 'already loaded' by disabling ONLY ext inis whose module is
    already compiled into the binary (php -n -m). Verifies that the live module
    set does not shrink and critical funcs survive; otherwise auto-restores.
    """
    conf_dir = find_php_conf_dir()
    if not conf_dir:
        return ActionResult(False, "ไม่พบไดเรกทอรี .ini ของ PHP (/usr/local/etc/php)")
    if not Path(php_bin).exists():
        php_bin = which("php") or php_bin

    # Mixed-SAPI guard: if the WEB SAPI (mod_php) runs a DIFFERENT PHP version
    # than the CLI we inspect, builtin detection via CLI is unreliable and can
    # break the web (e.g. pdo_mysql disappears). Skip dedupe entirely then.
    cli_ver = php_version_of(php_bin)
    web_vers = [v for s, v, _ in detect_web_php_versions() if s.startswith("web:") and v]
    if cli_ver and web_vers and any(wv.as_tuple() != cli_ver.as_tuple() for wv in web_vers):
        return ActionResult(
            False,
            "SAPI เว็บ (mod_php) กับ CLI คนละเวอร์ชัน — ข้าม dedupe เพื่อกันเว็บพัง "
            f"(CLI={cli_ver}, web={', '.join(str(v) for v in web_vers)}) "
            "ถ้าเว็บมีปัญหาให้ใช้ --restore-ext",
        )

    builtin = php_builtin_modules(php_bin)
    if not builtin:
        return ActionResult(
            False,
            "อ่าน builtin modules (php -n -m) ไม่ได้ — ข้าม dedupe เพื่อความปลอดภัย",
        )

    before_modules = php_loaded_modules(php_bin)
    before_warn = php_startup_warnings(php_bin)

    ext_inis = sorted(conf_dir.glob("ext-*.ini")) + sorted(conf_dir.glob("*.ini"))
    # unique preserve order
    seen_p = set()
    uniq_inis: List[Path] = []
    for p in ext_inis:
        if p.name in seen_p:
            continue
        # never park our own drop-ins
        if p.name.endswith("php2shell.ini") or p.name == Path(EXTENSION_DIR_DROPIN).name:
            continue
        seen_p.add(p.name)
        uniq_inis.append(p)

    candidates: List[Tuple[Path, str]] = []
    for p in uniq_inis:
        if not p.name.startswith("ext-"):
            continue
        mod = extension_module_name(p)
        if mod and mod.lower() in builtin:
            candidates.append((p, mod))

    php_ini_candidates = [
        Path("/usr/local/etc/php.ini"),
        Path("/usr/local/etc/php/php.ini"),
    ]
    # leftover scan-dir inis that are not ext-* (may still have extension=)
    extra_inis = [p for p in uniq_inis if not p.name.startswith("ext-")]

    if dry_run:
        return ActionResult(
            True,
            f"DRY-RUN จะปิด ext ini ซ้ำ {len(candidates)} ไฟล์ + comment php.ini",
            [f"{p.name} (module {mod})" for p, mod in candidates],
        )

    disabled = conf_dir / DISABLED_DUP_DIRNAME
    disabled.mkdir(parents=True, exist_ok=True)
    moved: List[Tuple[Path, Path]] = []
    commented: List[str] = []
    for p, _mod in candidates:
        dest = disabled / p.name
        try:
            shutil.move(str(p), str(dest))
            moved.append((p, dest))
        except OSError as e:
            for orig, d in moved:
                shutil.move(str(d), str(orig))
            return ActionResult(False, f"ย้าย {p.name} ไม่ได้: {e}")

    for ini in php_ini_candidates + extra_inis:
        commented.extend(comment_builtin_extensions_in_file(ini, builtin, dry_run=False))

    after_modules = php_loaded_modules(php_bin)
    health = check_critical_php_health(php_bin)
    missing = before_modules - after_modules

    if missing or not health.ok:
        for orig, dest in moved:
            try:
                shutil.move(str(dest), str(orig))
            except OSError:
                pass
        return ActionResult(
            False,
            "ปิด ext ini แล้วโมดูลหาย/health ไม่ผ่าน — คืนค่าไฟล์ที่ย้ายแล้ว "
            "(ถ้า comment ใน php.ini แล้ว ให้คืนจาก .bak)",
            [
                f"module หาย: {', '.join(sorted(missing)) or '-'}",
                health.message,
            ],
        )

    after_already, after_missing = classify_extension_warnings(php_bin)
    return ActionResult(
        True,
        f"ปิดโหลดซ้ำ: ย้าย {len(moved)} ext ini, comment {len(commented)} บรรทัดใน php.ini",
        [
            f"already-loaded ก่อน {len(before_warn)} → หลัง {len(after_already)}",
            f"โมดูลคงเดิม {len(after_modules)} ตัว",
            f"commented: {', '.join(commented) or '-'}",
            "คืน ext ini: sudo python3 "
            f"{SCRIPT_NAME} --restore-ext",
            f"ยังโหลด .so ไม่ได้: {', '.join(after_missing) or '-'}",
        ],
    )


def apache_apxs_path() -> Optional[str]:
    for c in ("/usr/local/sbin/apxs", "apxs"):
        p = which(c) if "/" not in c else (c if Path(c).exists() else None)
        if p:
            return p
    return None


def _http_server_header(
    host: str, port: int, scheme: str, timeout: float = 5.0
) -> Optional[dict]:
    """Benign local GET to read Server / X-Powered-By (no payload)."""
    url = f"{scheme}://{host}:{port}/"
    ctx = None
    if scheme == "https":
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
    req = urllib.request.Request(
        url,
        method="GET",
        headers={"User-Agent": f"{SCRIPT_NAME}/{SCRIPT_VERSION} selfcheck", "Connection": "close"},
    )
    try:
        with urllib.request.urlopen(req, timeout=timeout, context=ctx) as r:
            return {k.lower(): v for k, v in r.getheaders()}
    except urllib.error.HTTPError as e:
        return {k.lower(): v for k, v in (e.headers.items() if e.headers else [])}
    except (urllib.error.URLError, ssl.SSLError, OSError):
        return None


def detect_fpm_listen() -> Tuple[str, str]:
    """
    Read php-fpm pool listen= (tcp host:port or unix socket).
    Returns (kind, address) where kind is 'tcp' or 'unix'.
    """
    paths: List[Path] = []
    for pat in (
        "/usr/local/etc/php-fpm.d/*.conf",
        "/usr/local/etc/php-fpm.conf",
        "/usr/local/etc/php/*-fpm.d/*.conf",
        "/usr/local/etc/php82-fpm.d/*.conf",
        "/usr/local/etc/php-fpm.d/www.conf",
    ):
        paths.extend(Path(p) for p in sorted(glob.glob(pat)))
    seen = set()
    for p in paths:
        if str(p) in seen or not p.is_file():
            continue
        seen.add(str(p))
        try:
            text = p.read_text(encoding="utf-8", errors="replace")
        except OSError:
            continue
        for ln in text.splitlines():
            s = ln.split(";", 1)[0].strip()
            if not s.startswith("listen"):
                continue
            m = re.match(r"listen\s*=\s*(\S+)", s)
            if not m:
                continue
            val = m.group(1).strip().strip("\"'")
            if val.startswith("/"):
                return "unix", val
            return "tcp", val
    return "tcp", "127.0.0.1:9000"


def fpm_proxy_handler(kind: str, addr: str) -> str:
    if kind == "unix":
        return f'proxy:unix:{addr}|fcgi://localhost/'
    if "://" in addr:
        return f"proxy:fcgi://{addr.split('://', 1)[-1]}"
    return f"proxy:fcgi://{addr}"


def fpm_is_listening(kind: str, addr: str) -> bool:
    if kind == "unix":
        return Path(addr).exists()
    host, _, port_s = addr.partition(":")
    try:
        port = int(port_s or "9000")
    except ValueError:
        return False
    return _tcp_open(host or "127.0.0.1", port)


def fpm_listener_pids(kind: str, addr: str) -> List[int]:
    """PID ที่ฟัง listen ของ FPM (FreeBSD sockstat) — ใช้ตอน pidfile ค้าง."""
    pids: List[int] = []
    if kind == "unix":
        cp = run(["sockstat", "-u"], dry_run=False, check=False, capture=True)
        needle = addr
    else:
        _host, _, port_s = addr.partition(":")
        port_s = port_s or "9000"
        cp = run(["sockstat", "-4", "-l", "-P", "tcp"], dry_run=False, check=False, capture=True)
        needle = ":" + port_s
    for ln in (cp.stdout or "").splitlines():
        if needle not in ln:
            continue
        parts = ln.split()
        if len(parts) < 3:
            continue
        try:
            pids.append(int(parts[2]))
        except ValueError:
            continue
    return list(dict.fromkeys(pids))


def _pid_alive(pid: int) -> bool:
    if pid <= 0:
        return False
    try:
        os.kill(pid, 0)
        return True
    except OSError:
        return False


def _pid_command(pid: int) -> str:
    cp = run(["ps", "-p", str(pid), "-o", "comm="], dry_run=False, check=False, capture=True)
    return (cp.stdout or "").strip()


def restart_php_fpm_graceful() -> ActionResult:
    """
    รีสตาร์ท php-fpm โดยไม่พังเมื่อ pidfile ค้างแต่พอร์ต 9000 ถูกใช้อยู่แล้ว
    (service restart จะขึ้น Address already in use ทั้งที่ FPM ทำงานปกติ)
    """
    kind, addr = detect_fpm_listen()
    details = [f"listen={kind}:{addr}"]
    if fpm_is_listening(kind, addr):
        pids = fpm_listener_pids(kind, addr)
        details.append("ฟังอยู่แล้ว pids=" + (",".join(map(str, pids)) or "-"))
        return ActionResult(True, f"php-fpm ทำงานอยู่แล้วที่ {addr}", details)

    for name in ("php-fpm", "php_fpm"):
        run(["service", name, "stop"], dry_run=False, check=False, capture=True)
    time.sleep(0.4)
    if fpm_is_listening(kind, addr):
        for pid in fpm_listener_pids(kind, addr):
            cmd = _pid_command(pid).lower()
            if "php-fpm" in cmd or "php_fpm" in cmd:
                try:
                    os.kill(pid, signal.SIGTERM)
                    details.append(f"ส่ง TERM ให้ leftover {pid} ({cmd})")
                except OSError:
                    pass
        time.sleep(0.5)
        if fpm_is_listening(kind, addr):
            for pid in fpm_listener_pids(kind, addr):
                cmd = _pid_command(pid).lower()
                if "php-fpm" in cmd or "php_fpm" in cmd:
                    try:
                        os.kill(pid, signal.SIGKILL)
                        details.append(f"ส่ง KILL ให้ leftover {pid}")
                    except OSError:
                        pass
    for pf in (
        "/var/run/php-fpm.pid",
        "/var/run/php_fpm.pid",
        "/usr/local/var/run/php-fpm.pid",
    ):
        p = Path(pf)
        if not p.is_file():
            continue
        try:
            old = int(p.read_text(encoding="utf-8", errors="replace").strip() or "0")
        except ValueError:
            old = 0
        if not _pid_alive(old):
            try:
                p.unlink()
                details.append(f"ลบ pidfile ค้าง {pf}")
            except OSError:
                pass

    started = False
    for name in ("php-fpm", "php_fpm"):
        if not service_exists(name):
            continue
        run(["service", name, "enable"], dry_run=False, check=False, capture=True)
        cp = run(["service", name, "start"], dry_run=False, check=False, capture=True)
        details.append(f"service {name} start exit={cp.returncode}")
        started = True
        break
    if fpm_is_listening(kind, addr):
        return ActionResult(True, f"php-fpm พร้อมที่ {addr}", details)
    if not started:
        return ActionResult(False, "ไม่พบบริการ php-fpm ที่จะสตาร์ท", details)
    return ActionResult(False, f"php-fpm ยังไม่ฟังที่ {addr} หลังซ่อม pid/bind", details)


def apache_routes_to_fpm() -> bool:
    """Apache ส่ง *.php ไป php-fpm แล้วหรือยัง (SetHandler proxy:fcgi)."""
    base = Path("/usr/local/etc/apache24")
    if (base / "Includes" / "zz-php2shell-fpm.conf").is_file():
        return True
    rx = re.compile(r"SetHandler\s+[\"']?proxy:(?:fcgi|unix)", re.I)
    paths: List[Path] = []
    if base.is_dir():
        paths.extend(base.glob("*.conf"))
        for sub in ("Includes", "extra", "conf.d"):
            d = base / sub
            if d.is_dir():
                paths.extend(d.glob("*.conf"))
    for f in paths:
        try:
            if rx.search(f.read_text(encoding="utf-8", errors="replace")):
                return True
        except OSError:
            continue
    return False


def nginx_conf_paths() -> List[Path]:
    files: List[Path] = []
    seen = set()
    for pat in NGINX_CONF_GLOBS:
        for raw in glob.glob(pat):
            p = Path(raw)
            if not p.is_file() or str(p) in seen:
                continue
            seen.add(str(p))
            files.append(p)
    return files


def detect_nginx_fastcgi() -> List[str]:
    """อ่าน fastcgi_pass จากคอนฟิก nginx (ไม่ยิง payload)"""
    found: List[str] = []
    rx = re.compile(r"fastcgi_pass\s+([^;#]+)", re.I)
    for path in nginx_conf_paths():
        try:
            text = path.read_text(encoding="utf-8", errors="replace")
        except OSError:
            continue
        for m in rx.finditer(text):
            val = m.group(1).strip().strip("\"';")
            if val and val not in found:
                found.append(val)
    return found


def detect_http_front(timeout: float = 3.0) -> str:
    """ใครตอบพอร์ต 80/443 จริง: nginx | apache | both | unknown"""
    nginx = apache = False
    for scheme, port in (("http", 80), ("https", 443)):
        hdr = _http_server_header("127.0.0.1", port, scheme, timeout)
        if not hdr:
            continue
        server = (hdr.get("server") or "").lower()
        if "nginx" in server:
            nginx = True
        if "apache" in server:
            apache = True
    if nginx and apache:
        return "both"
    if nginx:
        return "nginx"
    if apache:
        return "apache"
    if nginx_conf_paths() and service_exists("nginx"):
        return "nginx"
    if _apache_base() and (service_exists("apache24") or service_exists("httpd")):
        return "apache"
    return "unknown"


def default_web_restart_services() -> List[str]:
    front = detect_http_front()
    if front == "nginx":
        return ["nginx"]
    if front == "apache":
        return ["apache24", "httpd"]
    if front == "both":
        return ["nginx", "apache24", "httpd"]
    names = [n for n in ("nginx", "apache24", "httpd") if service_exists(n)]
    return names or list(WEB_SERVICE_CANDIDATES)


def resolve_web_services(extra: Optional[Sequence[str]]) -> List[str]:
    """ถ้าไม่ได้ระบุเอง จะรีสตาร์ทเฉพาะหน้าเว็บที่กำลังเสิร์ฟ (nginx หรือ apache)"""
    if extra is None or set(extra) == set(WEB_SERVICE_CANDIDATES):
        return default_web_restart_services()
    return [s for s in extra if s]


def nginx_config_test() -> Optional[subprocess.CompletedProcess]:
    for exe in ("nginx", "/usr/local/sbin/nginx", "/usr/sbin/nginx"):
        path = which(exe) if "/" not in exe else (exe if Path(exe).exists() else None)
        if path:
            return run([path, "-t"], dry_run=False, check=False, capture=True)
    return None


def detect_web_php_versions(timeout: float = 5.0) -> List[Tuple[str, Optional[Version], str]]:
    """
    Detect the PHP version that the WEB actually serves (the real attack surface
    for PHP2Shell), separate from the CLI binary.
    Returns list of (sapi_label, Version|None, detail).
    """
    results: List[Tuple[str, Optional[Version], str]] = []

    # mod_php / proxied fpm as seen over local HTTP(S)
    for scheme, port in (("http", 80), ("https", 443)):
        hdr = _http_server_header("127.0.0.1", port, scheme, timeout)
        if hdr is None:
            continue
        server = hdr.get("server", "") or ""
        powered = hdr.get("x-powered-by", "") or ""
        v = None
        for s in (powered, server):
            m = re.search(r"PHP/(\d+)\.(\d+)\.(\d+)", s, re.I)
            if m:
                v = Version(int(m.group(1)), int(m.group(2)), int(m.group(3)))
                break
        if not v and ("nginx" in server.lower() or "apache" in server.lower()):
            detail = f"{server} (ไม่โชว์เวอร์ชัน PHP — ใช้ FPM เป็นตัวตัดสิน)"
        else:
            detail = (server or powered or "ไม่มี PHP banner (อาจ expose_php=Off)").strip()
        results.append((f"web:{scheme}", v, detail[:140]))

    # php-fpm binary version
    for fpm in ("php-fpm", "php82-fpm", "php83-fpm", "php84-fpm", "php85-fpm"):
        exe = which(fpm)
        if not exe:
            continue
        cp = run([exe, "-v"], dry_run=False, check=False, capture=True)
        v = parse_version((cp.stdout or "").splitlines()[0] if cp.stdout else "")
        first = (cp.stdout or "").splitlines()[0] if cp.stdout else "-"
        results.append((f"fpm:{fpm}", v, first[:140]))
        break

    # mod_php shared object on disk (version not directly readable)
    for so in (
        "/usr/local/libexec/apache24/libphp.so",
        "/usr/local/libexec/apache24/libphp8.so",
        "/usr/local/libexec/apache24/libphp7.so",
    ):
        if Path(so).exists():
            results.append(("mod_php:file", None, so))

    upstreams = detect_nginx_fastcgi()
    if upstreams:
        kind, addr = detect_fpm_listen()
        listening = fpm_is_listening(kind, addr)
        match = any(addr in u or u.replace("unix:", "") in addr for u in upstreams)
        state = "listening" if listening else "ไม่พบ listen"
        wired = "ตรงกับ FPM" if match else "ตรวจ mapping ด้วยมือ"
        results.append(
            (
                "nginx:fpm",
                None,
                f"fastcgi_pass {', '.join(upstreams[:3])}; fpm {addr} ({wired}, {state})"[:140],
            )
        )

    return results


def _front_from_web_results(results: List[Tuple[str, Optional[Version], str]]) -> str:
    nginx = apache = False
    for sapi, _v, detail in results:
        if not sapi.startswith("web:"):
            continue
        d = (detail or "").lower()
        if "nginx" in d:
            nginx = True
        if "apache" in d:
            apache = True
    if nginx and apache:
        return "both"
    if nginx:
        return "nginx"
    if apache:
        return "apache"
    if any(s.startswith("nginx:") for s, _v, _d in results):
        return "nginx"
    return "unknown"


def assess_web_sapi(results: List[Tuple[str, Optional[Version], str]]) -> Tuple[int, List[str]]:
    """Return (worst_code, lines). 2=vulnerable web, 3=unknown, 0=ok.

    nginx + php-fpm: header มักไม่มี PHP/x.y.z — ตัดสินจาก FPM
    Apache + FPM หลังปิด mod_php: header ก็ไม่โชว์ PHP — ตัดสินจาก FPM เช่นกัน
    Apache/mod_php: ตัดสินจาก web:* ถ้ามีเวอร์ชัน
    """
    worst = 0
    lines: List[str] = []
    saw_web = False
    front = _front_from_web_results(results)
    fpm_ok = False
    fpm_bad = False
    for sapi, v, _detail in results:
        if not sapi.startswith("fpm:") or v is None:
            continue
        need = SAFE_MIN.get(v.branch())
        if need and v.as_tuple() >= need:
            fpm_ok = True
        elif need and v.as_tuple() < need:
            fpm_bad = True

    for sapi, v, detail in results:
        if sapi == "mod_php:file":
            if front == "nginx":
                lines.append(f"  {sapi}: ไฟล์ค้างจาก Apache — nginx ไม่ใช้ ({detail})")
            else:
                lines.append(f"  {sapi}: มีไฟล์ค้างอยู่ {detail} (ไม่ใช้ถ้าปิด LoadModule แล้ว)")
            continue
        if sapi.startswith("nginx:"):
            color = Color.GREEN if fpm_ok and not fpm_bad else Color.YELLOW
            lines.append(f"  {color}{sapi}: {detail}{Color.RESET}")
            continue
        if v is None:
            hidden_front = (
                front in ("nginx", "apache")
                or "nginx" in (detail or "").lower()
                or "apache" in (detail or "").lower()
            )
            if sapi.startswith("web:") and hidden_front:
                saw_web = True
                lines.append(
                    f"  {sapi}: ไม่โชว์เวอร์ชัน PHP ({detail}) — ใช้แถว fpm: เป็นตัวตัดสิน"
                )
                continue
            lines.append(f"  {sapi}: เวอร์ชันอ่านไม่ได้ ({detail})")
            if sapi.startswith("web:"):
                saw_web = True
                worst = max(worst, 3)
            continue
        need = SAFE_MIN.get(v.branch())
        label = "ปลอดภัย" if (need and v.as_tuple() >= need) or not need else f"ยังเสี่ยง (ต้อง >= {Version(*need)})"
        if need and v.as_tuple() < need:
            color = Color.RED
            if sapi.startswith("web:"):
                saw_web = True
                if front != "nginx":
                    worst = max(worst, 2)
            elif sapi.startswith("fpm:"):
                worst = max(worst, 2)
        else:
            color = Color.GREEN
            if sapi.startswith("web:"):
                saw_web = True
        lines.append(f"  {color}{sapi}: PHP {v} {label}{Color.RESET} [{detail}]")

    if front in ("nginx", "apache"):
        if fpm_bad:
            worst = max(worst, 2)
        elif fpm_ok:
            if worst == 3:
                worst = 0
            if front == "apache" and apache_has_legacy_modphp() and not apache_routes_to_fpm():
                worst = max(worst, 2)
        elif not fpm_ok and not fpm_bad:
            worst = max(worst, 3)
        saw_web = True
    elif not saw_web:
        worst = max(worst, 3)
    return worst, lines


def print_sapi_matrix(installs: Optional[List[PhpInstall]] = None) -> int:
    """Print a single clear table: CLI / FPM / WEB. Return web worst-code."""
    log("สรุปเวอร์ชันแยกตาม SAPI (อย่าดูแค่ php -v)", "step")
    print("   " + "-" * 68)
    if installs:
        for i in installs:
            st = i.status.upper()
            print(f"   CLI     php{i.flavor or '?'}  {i.version}  {st:12}  {i.binary}")
    else:
        php = which("php")
        if php:
            v = php_version_of(php)
            print(f"   CLI     {v or '-'}  {php}")
    results = detect_web_php_versions()
    for sapi, v, detail in results:
        ver = str(v) if v else "-"
        print(f"   {sapi:<16} {ver:<10} {detail[:48]}")
    print("   " + "-" * 68)
    worst, _ = assess_web_sapi(results)
    front = _front_from_web_results(results)
    if worst == 2:
        if front == "nginx":
            log("nginx ใช้ php-fpm ที่ยังต่ำกว่าเกณฑ์ — อัป FPM ไม่ต้องสลับ Apache", "err")
        else:
            log("เว็บ (mod_php) ยังเก่า — CLI/FPM ที่แพตช์แล้วยังไม่ถูก Apache ใช้", "err")
            log("สคริปต์จะสลับ Apache → php-fpm ให้อัตโนมัติ (หรือรัน --use-fpm)", "info")
    elif worst == 0:
        if front == "nginx":
            log("nginx + php-fpm ผ่านเกณฑ์แล้ว (nginx ไม่โชว์เวอร์ชันใน header เป็นเรื่องปกติ)", "ok")
        elif front == "apache":
            log(
                "Apache + php-fpm ผ่านเกณฑ์แล้ว "
                "(ไม่โชว์ PHP ใน header เป็นเรื่องปกติหลังสลับ FPM / expose_php=Off)",
                "ok",
            )
        else:
            log("เว็บเสิร์ฟ PHP ผ่านเกณฑ์แล้ว — ตรงกับเครื่องสแกนภายนอกได้", "ok")
    elif worst == 3 and front in ("nginx", "apache"):
        log(
            "เว็บซ่อนเวอร์ชัน PHP — ตรวจแถว fpm: บนเครื่องนี้ "
            "หรือรัน php2shell_scan_v2.py --local (HIDDEN ไม่ใช่ล้ม)",
            "warn",
        )
    return worst


def report_web_sapi(report: Optional[dict] = None) -> int:
    """Print web-facing SAPI versions and return worst code (0/2/3)."""
    results = detect_web_php_versions()
    if not results:
        log("ตรวจเว็บไม่ได้ (ไม่มีเว็บเซิร์ฟเวอร์ตอบที่ 127.0.0.1) — ข้าม", "warn")
        return 0
    worst, lines = assess_web_sapi(results)
    log("เวอร์ชัน PHP ฝั่งเว็บ (พื้นผิวโจมตีจริงของ PHP2Shell):", "step")
    for ln in lines:
        print("   ", ln)
    if worst == 2:
        front = _front_from_web_results(results)
        fpm_ok = any(
            s.startswith("fpm:") and v and SAFE_MIN.get(v.branch()) and v.as_tuple() >= SAFE_MIN[v.branch()]
            for s, v, _ in results
        )
        if front == "nginx":
            log("nginx ส่ง PHP ผ่าน php-fpm ที่ยังต่ำกว่าเกณฑ์ — อัป FPM ในสาขาเดิม", "err")
        elif fpm_ok:
            log(
                "php-fpm ปลอดภัยแล้ว แต่ Apache ยังผูก mod_php เก่า — "
                "ต้องสลับไป FPM (--use-fpm) ไม่ใช่ดูแค่ CLI",
                "err",
            )
        else:
            log(
                "เว็บยังเสิร์ฟ PHP เวอร์ชันเก่า — CLI อาจอัปแล้วแต่ mod_php/FPM ยังไม่อัป",
                "err",
            )
    if report is not None:
        report.setdefault("actions", []).append(
            {
                "action": "web_sapi_check",
                "ok": worst == 0,
                "message": "web sapi check",
                "details": [re.sub(r"\033\[[0-9;]*m", "", x).strip() for x in lines],
            }
        )
    return worst


def _apache_base() -> Optional[Path]:
    for c in ("/usr/local/etc/apache24", "/usr/local/etc/apache2", "/etc/apache24"):
        if Path(c, "httpd.conf").exists():
            return Path(c)
    return None


def _tcp_open(host: str, port: int, timeout: float = 2.0) -> bool:
    try:
        with socket.create_connection((host, port), timeout=timeout):
            return True
    except OSError:
        return False


def _apache_conf_files(base: Path) -> List[Path]:
    files: List[Path] = []
    if (base / "httpd.conf").is_file():
        files.append(base / "httpd.conf")
    for sub in ("modules.d", "Includes", "extra", "conf.d", "modules", "vhosts", "sites-enabled"):
        d = base / sub
        if d.is_dir():
            files.extend(sorted(d.glob("*.conf")))
    for extra in list(base.glob("*.conf")) + list(base.glob("**/*php*.conf")):
        if extra.is_file() and extra not in files:
            files.append(extra)
    return files


def sanitize_modphp_directives(dry_run: bool = False) -> ActionResult:
    """
    ปิด php_flag / php_value / php_admin_* ที่เหลือจาก mod_php
    (เช่น extra/httpd-ssl.conf) เพื่อให้ Apache ขึ้นได้หลังสลับไป php-fpm
    ค่าเหล่านี้ย้ายไปใส่ใน php.ini หรือ php-fpm pool
    """
    base = _apache_base()
    if not base:
        return ActionResult(True, "ไม่พบ Apache — ไม่มี php_flag ให้ปิด")
    details: List[str] = []
    changed: List[str] = []
    for f in _apache_conf_files(base):
        try:
            txt = f.read_text(encoding="utf-8", errors="replace")
        except OSError:
            continue
        if not MODPHP_ONLY_DIR_RE.search(txt):
            continue
        if dry_run:
            details.append(f"DRY-RUN จะปิด php_flag/php_value ใน {f}")
            changed.append(str(f))
            continue
        backup_file(f, dry_run=False)
        txt2 = MODPHP_ONLY_DIR_COMMENT_RE.sub(
            r"\1#\2  # disabled by php2shell (mod_php only; ใช้ php.ini / FPM)",
            txt,
        )
        if txt2 != txt:
            f.write_text(txt2, encoding="utf-8")
            changed.append(str(f))
            details.append(f"ปิด php_flag/php_value ใน {f}")
    if not changed:
        return ActionResult(True, "ไม่มี php_flag/php_value ของ mod_php ค้าง", details)
    return ActionResult(True, f"ปิดคำสั่ง mod_php ใน {len(changed)} ไฟล์", details)


def fpm_is_safe() -> bool:
    for s, v, _ in detect_web_php_versions():
        if s.startswith("fpm:") and v:
            need = SAFE_MIN.get(v.branch())
            if need and v.as_tuple() >= need:
                return True
    patched = find_patched_php_binary()
    if not patched:
        return False
    ver = php_version_of(patched)
    if not ver:
        return False
    need = SAFE_MIN.get(ver.branch())
    if not (need and ver.as_tuple() >= need):
        return False
    return bool(which("php-fpm") or which("php82-fpm") or Path("/usr/local/sbin/php-fpm").exists())


def web_serves_eol() -> bool:
    for s, v, _ in detect_web_php_versions():
        if s.startswith("web:") and v and is_eol_branch(v):
            return True
    return False


def apache_has_legacy_modphp(ct_text: str = "") -> bool:
    blob = ct_text or ""
    if LEGACY_LIBPHP_HINT_RE.search(blob) or MODPHP_DIR_HINT_RE.search(blob):
        return True
    base = _apache_base()
    if not base:
        return False
    for f in _apache_conf_files(base):
        try:
            txt = f.read_text(encoding="utf-8", errors="replace")
        except OSError:
            continue
        if LEGACY_MODPHP_LINE_RE.search(txt) or MODPHP_ONLY_DIR_RE.search(txt):
            return True
    return False


def disable_legacy_modphp_loads(dry_run: bool = False) -> ActionResult:
    """Comment leftover PHP 7 LoadModule/LoadFile so Apache can start without libphp7.so."""
    base = _apache_base()
    if not base:
        return ActionResult(True, "ไม่พบ Apache — ไม่มี libphp7 ให้ปิด")
    details: List[str] = []
    changed: List[str] = []
    for f in _apache_conf_files(base):
        try:
            txt = f.read_text(encoding="utf-8", errors="replace")
        except OSError:
            continue
        if not LEGACY_MODPHP_LINE_RE.search(txt):
            continue
        if dry_run:
            details.append(f"DRY-RUN จะปิด leftover PHP 7 ใน {f}")
            changed.append(str(f))
            continue
        backup_file(f, dry_run=False)
        txt2 = LEGACY_MODPHP_COMMENT_RE.sub(
            r"\1#\2  # disabled by php2shell (leftover PHP 7)",
            txt,
        )
        if txt2 != txt:
            f.write_text(txt2, encoding="utf-8")
            changed.append(str(f))
            details.append(f"ปิด LoadModule PHP 7 ใน {f}")
    leftover = []
    for f in _apache_conf_files(base):
        try:
            if LEGACY_MODPHP_LINE_RE.search(f.read_text(encoding="utf-8", errors="replace")):
                leftover.append(str(f))
        except OSError:
            continue
    if leftover and not dry_run:
        details.append("ยังมีบรรทัด PHP 7 ค้าง: " + ", ".join(leftover))
    rs = sanitize_modphp_directives(dry_run=dry_run)
    details.append(rs.message)
    details.extend(rs.details)
    if not changed and not rs.details:
        return ActionResult(True, "ไม่มี LoadModule PHP 7 / php_flag ค้าง", details)
    return ActionResult(True, f"ปิด leftover mod_php ใน {len(changed)} ไฟล์", details)


def complete_eol_web_cutover(
    dry_run: bool = False, fpm_addr: Optional[str] = None
) -> ActionResult:
    """
    เมื่อ CLI/FPM เป็น 8.2.35 แล้วแต่ Apache ยังโหลด libphp7.so หรือเสิร์ฟ 7.4:
    ปิดโมดูลเก่าแล้วสลับไป php-fpm — ไม่ย้อนกลับไป libphp7 ที่พังอยู่แล้ว
    """
    details: List[str] = []
    if detect_http_front() == "nginx":
        return ActionResult(True, "หน้าเว็บเป็น nginx — ไม่แตะ Apache leftover", details)
    ct = apache_config_test()
    ct_text = ((ct.stderr or "") + "\n" + (ct.stdout or "")) if ct else ""
    if not (apache_has_legacy_modphp(ct_text) or web_serves_eol()):
        return ActionResult(True, "Apache ไม่ได้โหลด PHP 7 ค้าง", details)
    if not fpm_is_safe():
        return ActionResult(
            False,
            "ยังมี leftover PHP 7 บน Apache แต่ php-fpm ยังไม่ปลอดภัย — อัป FPM ก่อน",
            details,
        )
    log("พบ leftover PHP 7 บน Apache (libphp7 / php_flag / เว็บ 7.4) — ปิดแล้วสลับไป php-fpm", "step")
    rd = disable_legacy_modphp_loads(dry_run=dry_run)
    details.append(rd.message)
    details.extend(rd.details)
    rs = sanitize_modphp_directives(dry_run=dry_run)
    details.append(rs.message)
    details.extend(rs.details)
    rf = switch_apache_to_fpm(dry_run=dry_run, fpm_addr=fpm_addr)
    details.append(rf.message)
    details.extend(rf.details[:12])
    return ActionResult(
        rf.ok,
        rf.message if rf.ok else "ปิด libphp7 แล้ว แต่สลับ FPM ยังไม่ครบ — ดูรายละเอียด",
        details,
    )


def switch_apache_to_fpm(dry_run: bool = False, fpm_addr: Optional[str] = None) -> ActionResult:
    """
    Route Apache's PHP to php-fpm (already patched) instead of mod_php.
    Fully backed up; auto-rollback on configtest/restart/verify failure.
    Detects FPM listen (tcp or unix socket) automatically when fpm_addr is None.
    """
    base = _apache_base()
    if not base:
        return ActionResult(False, "ไม่พบ Apache config (apache24)")
    httpd = base / "httpd.conf"
    includes = base / "Includes"
    new_conf = includes / "zz-php2shell-fpm.conf"

    if fpm_addr:
        kind, addr = ("unix", fpm_addr) if fpm_addr.startswith("/") else ("tcp", fpm_addr)
    else:
        kind, addr = detect_fpm_listen()
    handler = fpm_proxy_handler(kind, addr)
    details: List[str] = [f"apache base={base}", f"fpm listen={kind}:{addr}", f"handler={handler}"]

    php_mod_files = []
    for f in _apache_conf_files(base):
        try:
            txt = f.read_text(encoding="utf-8", errors="replace")
        except OSError:
            continue
        if LEGACY_MODPHP_LINE_RE.search(txt):
            php_mod_files.append(f)
    details.append("mod_php พบใน: " + (", ".join(str(x) for x in php_mod_files) or "-"))
    prev_ct = apache_config_test()
    prev_ct_text = ((prev_ct.stderr or "") + "\n" + (prev_ct.stdout or "")) if prev_ct else ""
    prev_broken = bool(LEGACY_LIBPHP_HINT_RE.search(prev_ct_text) or web_serves_eol())

    if dry_run:
        return ActionResult(True, "DRY-RUN สลับ Apache -> php-fpm", details)

    # Ensure php-fpm is enabled & running (อย่า restart ซ้ำถ้าพอร์ตถูกใช้อยู่แล้ว)
    run(["service", "php-fpm", "enable"], dry_run=False, check=False, capture=True)
    run(["service", "php_fpm", "enable"], dry_run=False, check=False, capture=True)
    if not fpm_is_listening(kind, addr):
        rg = restart_php_fpm_graceful()
        details.append(rg.message)
        details.extend(rg.details[:6])
    if not fpm_is_listening(kind, addr):
        return ActionResult(
            False,
            f"php-fpm ไม่ฟังที่ {kind}:{addr} — ตรวจ listen ใน /usr/local/etc/php-fpm.d/www.conf "
            "แล้ว service php-fpm restart หรือระบุ --fpm-addr",
            details,
        )

    backups: List[Tuple[Path, Path]] = []

    def backup(f: Path) -> None:
        b = backup_file(f, dry_run=False)
        if b:
            backups.append((f, b))

    def rollback() -> None:
        for f, b in backups:
            try:
                shutil.copy2(str(b), str(f))
            except OSError:
                pass
        if new_conf.exists():
            try:
                new_conf.unlink()
            except OSError:
                pass

    # 1) disable leftover mod_php / libphp7.so (ทุกแบบ LoadModule)
    for f in php_mod_files:
        backup(f)
        txt = f.read_text(encoding="utf-8", errors="replace")
        txt2 = LEGACY_MODPHP_COMMENT_RE.sub(
            r"\1#\2  # disabled by php2shell --use-fpm",
            txt,
        )
        f.write_text(txt2, encoding="utf-8")

    # 2) enable proxy + proxy_fcgi modules in httpd.conf
    backup(httpd)
    htxt = httpd.read_text(encoding="utf-8", errors="replace")
    for mod, sofile in (
        ("proxy_module", "libexec/apache24/mod_proxy.so"),
        ("proxy_fcgi_module", "libexec/apache24/mod_proxy_fcgi.so"),
    ):
        uncommented = re.sub(
            rf"(?m)^\s*#\s*(LoadModule\s+{mod}\s+\S+)\s*$", r"\1", htxt
        )
        if uncommented != htxt:
            htxt = uncommented
        elif not re.search(rf"(?m)^\s*LoadModule\s+{mod}\b", htxt):
            htxt += f"\nLoadModule {mod} {sofile}\n"
    httpd.write_text(htxt, encoding="utf-8")

    # 3) handler: send *.php to fpm
    includes.mkdir(parents=True, exist_ok=True)
    new_conf.write_text(
        "# php2shell: route PHP to php-fpm (patched), do not use leftover mod_php\n"
        "<IfModule proxy_fcgi_module>\n"
        '  <FilesMatch "\\.php$">\n'
        f'    SetHandler "{handler}"\n'
        "  </FilesMatch>\n"
        "  DirectoryIndex index.php index.html\n"
        "</IfModule>\n",
        encoding="utf-8",
    )
    details.append(f"เขียน handler: {new_conf}")

    # 3b) ปิด php_flag/php_value ที่เหลือใน httpd-ssl.conf ฯลฯ
    rs = sanitize_modphp_directives(dry_run=False)
    details.append(rs.message)
    details.extend(rs.details[:8])

    # 4) configtest — ถ้ายังมี libphp7 / php_flag ให้ปิดซ้ำก่อนย้อนค่า
    ct = apache_config_test()
    if ct is not None and ct.returncode != 0:
        err = (ct.stderr or "") + "\n" + (ct.stdout or "")
        if LEGACY_LIBPHP_HINT_RE.search(err) or MODPHP_DIR_HINT_RE.search(err):
            disable_legacy_modphp_loads(dry_run=False)
            sanitize_modphp_directives(dry_run=False)
            ct = apache_config_test()
            err = ((ct.stderr or "") + "\n" + (ct.stdout or "")) if ct else err
        if ct is not None and ct.returncode != 0:
            if prev_broken:
                details.append(
                    "configtest ยังไม่ผ่าน แต่สถานะเดิมก็พังจาก libphp7 — ไม่ย้อนไปโมดูลเก่า"
                )
                details.append(err[-700:])
            else:
                rollback()
                return ActionResult(
                    False,
                    "apache configtest ไม่ผ่านหลังสลับ — ย้อนค่าทั้งหมดแล้ว",
                    details + [err[-700:]],
                )

    # 5) restart apache
    cp = run(["service", "apache24", "restart"], dry_run=False, check=False, capture=True)
    if cp.returncode != 0:
        sc = Path("/usr/local/etc/rc.d/apache24")
        if sc.exists():
            cp = run([str(sc), "restart"], dry_run=False, check=False, capture=True)
    if cp.returncode != 0:
        rst_err = (cp.stderr or "") + "\n" + (cp.stdout or "")
        if LEGACY_LIBPHP_HINT_RE.search(rst_err) or prev_broken:
            disable_legacy_modphp_loads(dry_run=False)
            cp = run(["service", "apache24", "restart"], dry_run=False, check=False, capture=True)
        if cp.returncode != 0:
            if prev_broken:
                details.append("restart ยังไม่ผ่าน แต่ไม่ย้อนไป libphp7 ที่พังอยู่แล้ว")
                details.append((cp.stderr or cp.stdout or "")[-400:])
            else:
                rollback()
                run(["service", "apache24", "restart"], dry_run=False, check=False, capture=True)
                return ActionResult(
                    False,
                    "restart apache หลังสลับไม่ผ่าน — ย้อนค่าแล้ว",
                    details + [(cp.stderr or cp.stdout or "")[-400:]],
                )

    # 6) verify: Server header must no longer advertise the old mod_php version.
    web = detect_web_php_versions()
    ww, lines = assess_web_sapi(web)
    details += [re.sub(r"\033\[[0-9;]*m", "", x).strip() for x in lines]
    leftover = []
    for f in _apache_conf_files(base):
        try:
            if LEGACY_MODPHP_LINE_RE.search(f.read_text(encoding="utf-8", errors="replace")):
                leftover.append(f)
        except OSError:
            continue
    if leftover:
        details.append("ยังมี LoadModule php ค้าง: " + ", ".join(str(x) for x in leftover))
        disable_legacy_modphp_loads(dry_run=False)
        run(["service", "apache24", "restart"], dry_run=False, check=False, capture=True)
        web = detect_web_php_versions()
        ww, lines = assess_web_sapi(web)
        details += [re.sub(r"\033\[[0-9;]*m", "", x).strip() for x in lines]
    if ww == 2:
        # อย่าย้อนกลับไป libphp7 ที่พัง (libxml2.so.2 / PHP 7.4) — คง FPM ไว้
        details.append("เว็บยังโชว์ PHP เก่าหลังสลับ — ไม่ย้อนไป libphp7")
        return ActionResult(
            False,
            "สลับ Apache -> php-fpm แล้วแต่เว็บยังเสิร์ฟ PHP เก่า "
            "(ตรวจ VirtualHost / SetHandler; ไม่ย้อน libphp7)",
            details,
        )
    return ActionResult(
        True,
        "สลับ Apache -> php-fpm สำเร็จ — เว็บใช้ PHP ที่แพตช์แล้ว "
        f"(สำรองไว้ {len(backups)} ไฟล์, ย้อนได้จาก *.bak)",
        details,
    )


def web_abi_fix_packages(text: str) -> List[str]:
    out: List[str] = []
    for rx, pkgname in WEB_ABI_FIXES:
        if rx.search(text) and pkgname not in out:
            out.append(pkgname)
    return out


def apache_config_test() -> Optional[subprocess.CompletedProcess]:
    for ctl in (
        "/usr/local/sbin/apachectl",
        "/usr/local/sbin/httpd",
        "apachectl",
        "httpd",
    ):
        exe = which(ctl) if "/" not in ctl else (ctl if Path(ctl).exists() else None)
        if not exe:
            continue
        flag = "configtest" if "apachectl" in exe else "-t"
        return run([exe, flag], dry_run=False, check=False, capture=True)
    return None


def fix_apache_runtime(dry_run: bool = False) -> ActionResult:
    """
    ซ่อม Apache ที่สตาร์ทไม่ได้หลังปิด libphp7:
    ปิด php_flag/php_value ค้าง แล้ว start apache24
    """
    details: List[str] = []
    rd = disable_legacy_modphp_loads(dry_run=dry_run)
    details.append(rd.message)
    details.extend(rd.details[:8])
    rs = sanitize_modphp_directives(dry_run=dry_run)
    details.append(rs.message)
    details.extend(rs.details[:12])
    if dry_run:
        return ActionResult(True, "DRY-RUN ซ่อม Apache leftover mod_php", details)
    ct = apache_config_test()
    if ct is not None and ct.returncode != 0:
        err = (ct.stderr or "") + "\n" + (ct.stdout or "")
        if MODPHP_DIR_HINT_RE.search(err) or LEGACY_LIBPHP_HINT_RE.search(err):
            sanitize_modphp_directives(dry_run=False)
            disable_legacy_modphp_loads(dry_run=False)
            ct = apache_config_test()
            err = ((ct.stderr or "") + "\n" + (ct.stdout or "")) if ct else err
        if ct is not None and ct.returncode != 0:
            return ActionResult(
                False,
                "apache configtest ยังไม่ผ่าน — ตรวจไฟล์ที่รายงาน",
                details + [err[-800:]],
            )
    details.append("apache configtest: ผ่าน")
    cp = run(["service", "apache24", "start"], dry_run=False, check=False, capture=True)
    if cp.returncode != 0:
        sc = Path("/usr/local/etc/rc.d/apache24")
        if sc.exists():
            cp = run([str(sc), "start"], dry_run=False, check=False, capture=True)
    if cp.returncode != 0:
        cp = run(["service", "apache24", "restart"], dry_run=False, check=False, capture=True)
    if cp.returncode != 0:
        return ActionResult(
            False,
            "config ผ่านแล้วแต่ start apache24 ไม่สำเร็จ",
            details + [((cp.stderr or cp.stdout or "")[-400:])],
        )
    return ActionResult(True, "Apache ขึ้นแล้วหลังปิด leftover php_flag/libphp7", details)


def restart_services(
    flavors: Optional[Sequence[str]] = None,
    dry_run: bool = False,
    extra_services: Optional[Sequence[str]] = None,
) -> List[ActionResult]:
    names: List[str] = []
    if extra_services:
        names.extend(resolve_web_services(extra_services))
    else:
        names.extend(default_web_restart_services())
    if flavors:
        for fl in flavors:
            names.append(f"php{fl}_fpm")
    fpm_only = [n for n in SERVICE_CANDIDATES if "fpm" in n]
    names.extend(fpm_only)
    results = []
    seen = set()

    def do_restart(name: str) -> subprocess.CompletedProcess:
        cp = run(["service", name, "restart"], dry_run=dry_run, check=False, capture=True)
        if cp.returncode != 0 and not dry_run:
            # Fall back to the rc.d script directly (some boxes need this)
            for base in ("/usr/local/etc/rc.d", "/etc/rc.d"):
                script = Path(base, name)
                if script.exists():
                    cp = run([str(script), "restart"], dry_run=False, check=False, capture=True)
                    break
        return cp

    for name in names:
        if name in seen:
            continue
        seen.add(name)
        if not service_exists(name):
            continue
        cp = do_restart(name)
        if dry_run:
            results.append(ActionResult(True, f"DRY-RUN service {name} restart"))
            log(results[-1].message, "warn")
            continue
        if cp.returncode == 0:
            results.append(ActionResult(True, f"restart {name} สำเร็จ"))
            log(results[-1].message, "ok")
            continue

        msg = (cp.stderr or cp.stdout or "").strip()
        ct_preview = apache_config_test() if ("apache" in name or name == "httpd") else None
        ct_text = (
            ((ct_preview.stderr or "") + "\n" + (ct_preview.stdout or ""))
            if ct_preview
            else ""
        )
        combined = msg + "\n" + ct_text
        # leftover php_flag/php_value หลังปิด mod_php — ปิดคำสั่งแล้วลองใหม่
        if ("apache" in name or name == "httpd") and MODPHP_DIR_HINT_RE.search(combined):
            log(
                "Apache พังเพราะ php_flag/php_value (คำสั่งของ mod_php) — ปิดแล้วลองใหม่",
                "warn",
            )
            sanitize_modphp_directives(dry_run=False)
            disable_legacy_modphp_loads(dry_run=False)
            cp2 = do_restart(name)
            if cp2.returncode == 0:
                results.append(
                    ActionResult(
                        True,
                        f"restart {name} สำเร็จ (หลังปิด php_flag ของ mod_php)",
                        [combined[-500:]],
                    )
                )
                log(results[-1].message, "ok")
                continue
            results.append(
                ActionResult(
                    False,
                    f"restart {name} ยังไม่ขึ้นหลังปิด php_flag",
                    [combined[-800:], ((cp2.stderr or cp2.stdout or "")[-400:])],
                )
            )
            log(results[-1].message, "err")
            continue
        # leftover libphp7.so + missing libxml2.so.2: ปิดโมดูลเก่า ห้าม pkg install mod_php82
        if ("apache" in name or name == "httpd") and LEGACY_LIBPHP_HINT_RE.search(combined):
            log(
                "Apache พังเพราะ leftover libphp7.so — ปิดโมดูล PHP 7 "
                "ไม่ติดตั้ง libxml2/mod_php82 ทับ",
                "warn",
            )
            disable_legacy_modphp_loads(dry_run=False)
            cp2 = do_restart(name)
            if cp2.returncode == 0:
                results.append(
                    ActionResult(
                        True,
                        f"restart {name} สำเร็จ (หลังปิด leftover libphp7)",
                        [combined[-500:]],
                    )
                )
                log(results[-1].message, "ok")
                continue
            results.append(
                ActionResult(
                    False,
                    f"restart {name} ยังไม่ขึ้นหลังปิด libphp7 — จะสลับไป FPM ในขั้นถัดไป",
                    [combined[-800:], ((cp2.stderr or cp2.stdout or "")[-400:])],
                )
            )
            log(results[-1].message, "warn")
            continue
        # php-fpm: pidfile ค้าง + พอร์ต 9000 ถูกใช้แล้ว → service restart ล้มแต่ FPM ทำงานอยู่
        if "fpm" in name:
            kind, addr = detect_fpm_listen()
            eaddr = "already in use" in msg.lower()
            stale = "not running" in msg.lower()
            if eaddr or stale or "already loaded" in msg:
                if fpm_is_listening(kind, addr):
                    results.append(
                        ActionResult(
                            True,
                            f"restart {name} สำเร็จ (FPM ฟังอยู่แล้วที่ {addr} — pidfile ค้างไม่เป็นไร)",
                            [msg[-400:]],
                        )
                    )
                    log(results[-1].message, "ok")
                    continue
                rg = restart_php_fpm_graceful()
                log(rg.message, "ok" if rg.ok else "err")
                results.append(rg)
                continue
        # php-fpm rc script often exits non-zero because of "already loaded"
        # warnings on stderr even though the daemon is up.
        if "fpm" in name and "already loaded" in msg:
            kind, addr = detect_fpm_listen()
            if fpm_is_listening(kind, addr):
                results.append(
                    ActionResult(
                        True,
                        f"restart {name} สำเร็จ (มีคำเตือน already loaded — บริการทำงานอยู่)",
                        [msg[-400:]],
                    )
                )
                log(results[-1].message, "ok")
                continue
        # Detect ABI / shared-lib breakage and try to self-heal via pkg
        fixes = web_abi_fix_packages(msg)
        detail = [msg[-800:]]
        healed = False
        if fixes and which("pkg"):
            log(f"{name} พังเพราะ shared lib: {', '.join(fixes)} — ลองซ่อมด้วย pkg", "warn")
            run(["pkg", "install", "-yf", *fixes], dry_run=False, check=False, capture=True)
            # mod_php may also need reinstall to match new libs
            run(["pkg", "install", "-yf", "mod_php82"], dry_run=False, check=False, capture=True)
            cp2 = do_restart(name)
            if cp2.returncode == 0:
                healed = True
                detail.append(f"ซ่อมด้วย pkg install -yf {' '.join(fixes)} แล้วรีสตาร์ทผ่าน")
        if healed:
            results.append(ActionResult(True, f"restart {name} สำเร็จ (หลังซ่อม lib)", detail))
            log(results[-1].message, "ok")
            continue

        # Give actionable guidance for the common cases we hit
        if fixes:
            detail.append("แก้มือ: pkg update -f && pkg install -yf " + " ".join(fixes) + " mod_php82")
        ct = apache_config_test()
        if ct is not None and ct.returncode != 0:
            detail.append("apache configtest: " + ((ct.stderr or ct.stdout or "")[-400:]))
        results.append(ActionResult(False, f"restart {name} ล้มเหลว", detail))
        log(results[-1].message, "err")
        for d in detail:
            print(Color.DIM + str(d)[:400] + Color.RESET)

    if not results:
        log("ไม่พบบริการ php-fpm/apache/nginx ที่จะรีสตาร์ท — รีสตาร์ทเองตามสแตกของคุณ", "warn")
    return results


def finalize_php_runtime(
    installs: List[PhpInstall],
    *,
    do_clean_dups: bool,
    web_services: Optional[Sequence[str]],
    dry_run: bool,
    report: Optional[dict] = None,
) -> bool:
    """
    After PHP is updated/compiled: make the stack actually usable.
    1) health-check critical funcs (filter_var ...) and auto-restore if broken
    2) optionally silence 'already loaded' by safe dedupe (with auto-rollback)
    3) report web readiness (apache configtest)
    Returns True if runtime is healthy.
    """
    php_bin = which("php") or (installs[0].binary if installs else "/usr/local/bin/php")

    def record(action: str, r: ActionResult) -> None:
        if report is not None:
            report["actions"].append({"action": action, **asdict(r)})

    # 1) health check; if critical funcs missing, restore ext inis and recheck
    health = check_critical_php_health(php_bin)
    log(health.message, "ok" if health.ok else "err")
    record("health_check", health)
    if not health.ok and not dry_run:
        log("พยายามกู้คืน extension จาก disabled-dup", "warn")
        rr = restore_extension_inis(dry_run=False)
        log(rr.message, "ok" if rr.ok else "err")
        record("restore_ext", rr)
        health = check_critical_php_health(php_bin)
        log(health.message, "ok" if health.ok else "err")
        record("health_recheck", health)

    # 1b) align extension_dir so source-built PHP finds the pkg .so extensions
    log("ตรวจ/จัด extension_dir ให้ทุก SAPI ใช้ที่เดียวกัน", "step")
    ra = align_extension_dir(php_bin, dry_run=dry_run)
    log(ra.message, "ok" if ra.ok else "warn")
    for d in ra.details:
        print(Color.DIM + str(d)[:300] + Color.RESET)
    record("align_extension_dir", ra)

    # 1c) leftover php74-openssl ฯลฯ — ปิด .so ที่โหลดไม่ได้ (openssl builtin ใน 8.2)
    rm = disable_missing_extension_inis(php_bin, dry_run=dry_run)
    log(rm.message, "ok" if rm.ok else "warn")
    for d in rm.details[:8]:
        print(Color.DIM + str(d)[:300] + Color.RESET)
    record("disable_missing_ext", rm)

    # 1d) ปิด libphp7 / สลับ FPM ก่อนรีสตาร์ท — กัน configtest พังแล้ว Apache เก่ายังเสิร์ฟ 7.4
    if not dry_run and detect_http_front() != "nginx":
        if apache_has_legacy_modphp() or web_serves_eol():
            log("ตัด leftover PHP 7 ออกจาก Apache ก่อนรีสตาร์ท", "step")
            cut = complete_eol_web_cutover(dry_run=False)
            log(cut.message, "ok" if cut.ok else "warn")
            for d in cut.details[:12]:
                print(Color.DIM + str(d)[:500] + Color.RESET)
            record("eol_web_cutover", cut)

    # 2) safe dedupe of 'already loaded' duplicates (never removes needed ones)
    if do_clean_dups:
        log("เก็บกวาด ext ini ซ้ำ (already loaded) แบบปลอดภัย", "step")
        rc = clean_duplicate_extension_inis(php_bin, dry_run=dry_run)
        log(rc.message, "ok" if rc.ok else "warn")
        for d in rc.details:
            print(Color.DIM + str(d)[:400] + Color.RESET)
        record("clean_ext_dups", rc)

    # 3) web readiness + extension report (ไม่สับสนกับ already loaded)
    report_extension_status(php_bin)
    front = detect_http_front()
    if front in ("apache", "both", "unknown"):
        ct = apache_config_test()
        if ct is not None:
            if ct.returncode == 0:
                log("apache configtest: ผ่าน", "ok")
            else:
                err = (ct.stderr or "") + "\n" + (ct.stdout or "")
                log("apache configtest: ไม่ผ่าน — ดูรายละเอียดด้านล่าง", "warn")
                print(Color.DIM + err[-500:] + Color.RESET)
                if not dry_run and MODPHP_DIR_HINT_RE.search(err):
                    log("พบ php_flag ค้างจาก mod_php — ปิดแล้วตรวจใหม่", "step")
                    rs = sanitize_modphp_directives(dry_run=False)
                    log(rs.message, "ok" if rs.ok else "warn")
                    record("sanitize_modphp_directives", rs)
                    ct = apache_config_test()
                    if ct is not None and ct.returncode == 0:
                        log("apache configtest: ผ่านหลังปิด php_flag", "ok")
    if front in ("nginx", "both", "unknown"):
        nt = nginx_config_test()
        if nt is not None:
            if nt.returncode == 0:
                log("nginx configtest: ผ่าน", "ok")
            else:
                log("nginx configtest: ไม่ผ่าน — ดูรายละเอียดด้านล่าง", "warn")
                print(Color.DIM + ((nt.stderr or nt.stdout or "")[-500:]) + Color.RESET)

    # Restart web first so a freshly-built libphp.so / FPM is actually loaded
    # before we judge the web-facing version.
    if not dry_run:
        log("รีสตาร์ทเว็บ/FPM ก่อนตรวจเวอร์ชันฝั่งเว็บ", "step")
        for r in restart_services(
            [i.flavor for i in installs if i.flavor],
            dry_run=False,
            extra_services=resolve_web_services(web_services),
        ):
            record("restart_pre_webcheck", r)

    # 4) web-facing SAPI version (the real PHP2Shell surface)
    web_worst = report_web_sapi(report)

    # 5) If FPM is patched but Apache still serves old mod_php — switch automatically
    #    nginx already uses FPM: do not touch Apache.
    if web_worst == 2 and not dry_run and detect_http_front() == "nginx":
        log("หน้าเว็บเป็น nginx — ไม่สลับ Apache; ต้องให้ php-fpm ผ่านเกณฑ์ในสาขาเดิม", "warn")
    elif web_worst == 2 and not dry_run:
        fpm_safe = False
        for s, v, _ in detect_web_php_versions():
            if s.startswith("fpm:") and v:
                need = SAFE_MIN.get(v.branch())
                if need and v.as_tuple() >= need:
                    fpm_safe = True
        if fpm_safe:
            log("php-fpm ปลอดภัยแล้ว — สลับ Apache จาก mod_php เก่าไป FPM ให้อัตโนมัติ", "step")
            rf = complete_eol_web_cutover(dry_run=False)
            log(rf.message, "ok" if rf.ok else "err")
            for d in rf.details[:16]:
                print(Color.DIM + str(d)[:500] + Color.RESET)
            record("auto_use_fpm", rf)
            if rf.ok:
                web_worst = report_web_sapi(report)
                # now SAPIs match — safe to silence already-loaded
                if web_worst != 2:
                    log("เว็บตรงกับ FPM แล้ว — เก็บกวาด ext ini ซ้ำ", "step")
                    rc = clean_duplicate_extension_inis(php_bin, dry_run=False)
                    log(rc.message, "ok" if rc.ok else "warn")
                    record("clean_ext_dups_after_fpm", rc)
                    rm2 = disable_missing_extension_inis(php_bin, dry_run=False)
                    log(rm2.message, "ok" if rm2.ok else "warn")
                    record("disable_missing_ext_after_fpm", rm2)
                    report_extension_status(php_bin)
        else:
            log(
                "แม้ CLI ปลอดภัยแล้ว แต่เว็บยังเสี่ยง และ FPM ก็ยังไม่ผ่านเกณฑ์ — "
                "ต้อง --from-source / --rebuild-modphp",
                "err",
            )

    print_sapi_matrix(installs)

    # Always silence php -v "already loaded" once WEB matches CLI
    if web_worst != 2 and not dry_run:
        already, _ = classify_extension_warnings(php_bin)
        if already:
            log("เก็บกวาด extension ที่โหลดซ้ำ (php -v already loaded)", "step")
            rc = clean_duplicate_extension_inis(php_bin, dry_run=False)
            log(rc.message, "ok" if rc.ok else "warn")
            for d in rc.details:
                print(Color.DIM + str(d)[:400] + Color.RESET)
            record("clean_ext_dups_final", rc)
            rm3 = disable_missing_extension_inis(php_bin, dry_run=False)
            if rm3.details:
                log(rm3.message, "ok" if rm3.ok else "warn")
                record("disable_missing_ext_final", rm3)
            report_extension_status(php_bin)

    return health.ok and web_worst != 2


def verify_mitigation(installs: List[PhpInstall]) -> List[ActionResult]:
    results = []
    for i in installs:
        if not Path(i.binary).exists():
            continue
        cp = run(
            [i.binary, "-i"],
            dry_run=False,
            check=False,
            capture=True,
        )
        text = cp.stdout or ""
        m = re.search(
            r"session\.upload_progress\.enabled\s*=>\s*(\S+)\s*=>\s*(\S+)",
            text,
            re.I,
        )
        if not m:
            # fallback parse
            on = "session.upload_progress.enabled => On" in text
            off = "session.upload_progress.enabled => Off" in text
            if off and not on:
                results.append(ActionResult(True, f"{i.binary}: upload_progress=Off"))
            else:
                results.append(ActionResult(False, f"{i.binary}: อ่านค่า upload_progress ไม่ได้"))
            continue
        local, master = m.group(1), m.group(2)
        ok = local.lower() == "off" and master.lower() == "off"
        results.append(
            ActionResult(
                ok,
                f"{i.binary}: session.upload_progress.enabled => {local} => {master}",
            )
        )
    for r in results:
        log(r.message, "ok" if r.ok else "warn")
    return results


def write_json_report(path: str, payload: dict, dry_run: bool = False) -> None:
    if dry_run:
        log(f"DRY-RUN เขียนรายงาน JSON: {path}", "warn")
        return
    Path(path).write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    log(f"เขียนรายงาน: {path}", "ok")


def parse_flavors(text: Optional[str]) -> Optional[List[str]]:
    if not text:
        return None
    parts = [p.strip() for p in text.replace(" ", "").split(",") if p.strip()]
    out = []
    for p in parts:
        if re.fullmatch(r"\d{2}", p):
            out.append(p)
        elif re.fullmatch(r"\d+\.\d+", p):
            maj, min_ = p.split(".")
            out.append(f"{maj}{min_}")
        elif re.fullmatch(r"php\d{2}", p, re.I):
            out.append(p[-2:])
        else:
            die(f"รูปแบบ --php ไม่รู้จัก: {p} (ใช้เช่น 82,83 หรือ 8.2,8.3)")
    return out


def build_arg_parser() -> argparse.ArgumentParser:
    p = argparse.ArgumentParser(
        prog=SCRIPT_NAME,
        description="FreeBSD: ตรวจ / mitigation / อัปเดต PHP หลายเวอร์ชัน ให้พ้น PHP2Shell",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__,
    )
    p.add_argument("--check", action="store_true", help="ตรวจเวอร์ชันและความเสี่ยง (ยังไม่อัปเดต)")
    p.add_argument("--mitigate", action="store_true", help="ตั้ง session.upload_progress.enabled=Off")
    p.add_argument("--update", action="store_true", help="pkg update + สลับ latest ถ้าจำเป็น + upgrade/install PHP")
    p.add_argument("--restart", action="store_true", help="รีสตาร์ท php-fpm/apache/nginx")
    p.add_argument("--verify", action="store_true", help="ตรวจค่า mitigation หลังแก้")
    p.add_argument("--audit", action="store_true", help="รัน pkg audit ที่เกี่ยวกับ PHP")
    p.add_argument("--all", action="store_true", help="mitigate + update + restart + verify + audit")
    p.add_argument("--php", dest="php_flavors", help="จำกัดเวอร์ชัน เช่น 82,83 หรือ 8.2,8.3")
    p.add_argument(
        "--no-latest-repo",
        action="store_true",
        help="ไม่สลับ FreeBSD package set เป็น latest อัตโนมัติ",
    )
    p.add_argument(
        "--custombuild",
        action="store_true",
        help="อัปเดต PHP ผ่าน DirectAdmin CustomBuild (แนะนำเมื่อ pkg update พัง)",
    )
    p.add_argument(
        "--diagnose",
        action="store_true",
        help="วินิจฉัย pkg/repo/custombuild/network แล้วจบ",
    )
    p.add_argument(
        "--repair-repo",
        action="store_true",
        help="ซ่อม pkg repo (สลับไป quarterly/release เมื่อ latest 404)",
    )
    p.add_argument(
        "--from-source",
        action="store_true",
        help="คอมไพล์ PHP จากทarball ทางการ แล้วปิด leftover libphp7 / สลับ Apache ไป php-fpm 8.2.35",
    )
    p.add_argument(
        "--clean-ext-dups",
        action="store_true",
        help="ปิด ext-*.ini ที่ซ้ำกับโมดูล builtin เพื่อลบคำเตือน 'already loaded' (ปลอดภัย+กู้คืนอัตโนมัติ)",
    )
    p.add_argument(
        "--restore-ext",
        action="store_true",
        help="คืน extension ทั้งหมดจาก disabled-dup/ แล้วรีสตาร์ทเว็บ (กู้เว็บที่พังเพราะ ext ถูกปิด)",
    )
    p.add_argument(
        "--web-service",
        dest="web_services",
        help="ระบุบริการเว็บที่จะรีสตาร์ท เช่น apache24,nginx (คั่นด้วย ,)",
    )
    p.add_argument(
        "--web-check",
        action="store_true",
        help="ตรวจเวอร์ชัน PHP ฝั่งเว็บ (mod_php/FPM) ที่ 127.0.0.1 — จับเคส CLI อัปแต่เว็บยังเก่า",
    )
    p.add_argument(
        "--rebuild-modphp",
        action="store_true",
        help="บังคับ build PHP จากซอร์สใหม่ (แม้ CLI ปลอดภัยแล้ว) เพื่อสร้าง mod_php/libphp.so ให้เว็บ",
    )
    p.add_argument(
        "--use-fpm",
        action="store_true",
        help="ปิด leftover libphp7 แล้วสลับ Apache ไป php-fpm ที่แพตช์แล้ว (ไม่ย้อนกลับไปโมดูล 7.4 ที่พัง)",
    )
    p.add_argument(
        "--fix-apache",
        action="store_true",
        help="ซ่อม Apache ที่สตาร์ทไม่ได้: ปิด libphp7 + php_flag/php_value แล้ว start apache24",
    )
    p.add_argument(
        "--align-ext-dir",
        action="store_true",
        help="ตั้ง extension_dir ให้ทุก SAPI ชี้ที่เดียวกัน (แก้ source build หา .so ของ pkg ไม่เจอ)",
    )
    p.add_argument(
        "--fpm-addr",
        default="",
        help="ที่อยู่ php-fpm สำหรับ --use-fpm (ว่าง = อ่าน listen จาก php-fpm.d อัตโนมัติ; tcp หรือ unix socket)",
    )
    p.add_argument(
        "--remove-eol",
        action="store_true",
        help="หลังย้าย 7.4/8.0/8.1 ไป 8.2.35 แล้ว ลบแพ็กเกจ EOL ที่ค้าง (php74-* เป็นต้น)",
    )
    p.add_argument("--dry-run", action="store_true", help="จำลอง ไม่แก้ระบบ")
    p.add_argument("--json-report", metavar="FILE", help="เขียนรายงาน JSON")
    p.add_argument("--yes", action="store_true", help="ไม่ถามยืนยันตอนอัปเดต")
    p.add_argument("--version", action="version", version=f"%(prog)s {SCRIPT_VERSION}")
    return p


def confirm_update(yes: bool) -> None:
    if yes or not sys.stdin.isatty():
        return
    ans = input("ยืนยันอัปเดตแพ็กเกจ PHP บนระบบนี้? [y/N]: ").strip().lower()
    if ans not in ("y", "yes"):
        die("ยกเลิกโดยผู้ใช้", 0)


def main(argv: Optional[Sequence[str]] = None) -> int:
    args = build_arg_parser().parse_args(argv)

    # --rebuild-modphp is "from source, but force even if CLI is already safe"
    if args.rebuild_modphp:
        args.from_source = True

    # Default action
    if not any(
        [
            args.check,
            args.mitigate,
            args.update,
            args.restart,
            args.verify,
            args.audit,
            args.all,
            args.diagnose,
            args.custombuild,
            args.repair_repo,
            args.from_source,
            args.clean_ext_dups,
            args.restore_ext,
            args.web_check,
            args.rebuild_modphp,
            args.use_fpm,
            args.fix_apache,
            args.align_ext_dir,
            args.remove_eol,
        ]
    ):
        args.check = True

    if args.all:
        args.mitigate = args.update = args.restart = args.verify = args.audit = True
        args.check = True
        # After updating PHP, tidy duplicate ext inis safely by default
        args.clean_ext_dups = True
        # Auto-use CustomBuild when present (typical FreeBSD + DirectAdmin)
        if find_custombuild():
            args.custombuild = True

    web_services: Optional[List[str]] = None
    if args.web_services:
        web_services = [s.strip() for s in args.web_services.split(",") if s.strip()]

    require_freebsd()
    if (
        args.mitigate
        or args.update
        or args.restart
        or args.custombuild
        or args.repair_repo
        or args.from_source
        or args.clean_ext_dups
        or args.restore_ext
        or args.use_fpm
        or args.fix_apache
        or args.align_ext_dir
        or args.remove_eol
    ):
        if not args.dry_run:
            require_root()

    flavors = parse_flavors(args.php_flavors)
    log(f"{SCRIPT_NAME} v{SCRIPT_VERSION} on {platform.platform()}", "step")
    if flavors:
        log("จำกัด flavor: " + ", ".join(f"php{f}" for f in flavors), "info")
    if args.dry_run:
        log("โหมด DRY-RUN — ไม่แก้ระบบจริง", "warn")

    if args.diagnose:
        log("วินิจฉัยสภาพแวดล้อม", "step")
        for line in diagnose_environment():
            print("   ", line)
        return 0

    if args.web_check and not any(
        [args.update, args.from_source, args.custombuild, args.all, args.mitigate, args.restart]
    ):
        php_bin = which("php") or "/usr/local/bin/php"
        report_extension_status(php_bin)
        worst = print_sapi_matrix()
        return 2 if worst == 2 else (3 if worst == 3 else 0)

    installs = discover_installs(flavors)
    code = print_report(installs)
    if find_custombuild():
        log("พบ DirectAdmin CustomBuild — แนะนำอัปเดตด้วย --custombuild หรือ --all --yes", "info")

    check_only = args.check and not any(
        [
            args.mitigate,
            args.update,
            args.restart,
            args.verify,
            args.audit,
            args.all,
            args.repair_repo,
            args.from_source,
            args.clean_ext_dups,
            args.restore_ext,
            args.use_fpm,
            args.fix_apache,
            args.rebuild_modphp,
        ]
    )
    if check_only:
        log(
            "โหมดตรวจอย่างเดียว — ยังไม่ได้อัปเดต PHP",
            "warn",
        )
        log(
            "อัปเดตจริงให้รัน:  sudo python3 freebsd_php2shell_update.py --all --yes",
            "step",
        )
        log(
            "ถ้า pkg 404 (FreeBSD 13 EOL): sudo python3 freebsd_php2shell_update.py --from-source --yes",
            "step",
        )
        for fl in sorted({i.flavor for i in installs if i.flavor}):
            remote = pkg_remote_php_version(fl or "")
            need = min_safe_for_flavor(fl or "")
            log(
                f"repo ตอนนี้ php{fl}: remote={remote or '-'} | ต้องการ >= {need or '-'}",
                "info",
            )

    report = {
        "script": SCRIPT_NAME,
        "script_version": SCRIPT_VERSION,
        "host": platform.node(),
        "freebsd": platform.release(),
        "time": dt.datetime.now().isoformat(timespec="seconds"),
        "dry_run": bool(args.dry_run),
        "installs": [asdict(i) for i in installs],
        "actions": [],
    }

    if args.mitigate:
        log("ใส่ mitigation session.upload_progress.enabled=Off", "step")
        for r in mitigate_all(installs, dry_run=args.dry_run):
            report["actions"].append({"action": "mitigate", **asdict(r)})

    # Recovery: restore extensions that were disabled (proven fix for broken web)
    if args.restore_ext:
        log("คืน extension จาก disabled-dup", "step")
        rr = restore_extension_inis(dry_run=args.dry_run)
        log(rr.message, "ok" if rr.ok else "err")
        for d in rr.details:
            print(Color.DIM + str(d)[:400] + Color.RESET)
        report["actions"].append({"action": "restore_ext", **asdict(rr)})
        if not args.dry_run:
            h = check_critical_php_health(which("php") or "/usr/local/bin/php")
            log(h.message, "ok" if h.ok else "err")
            report["actions"].append({"action": "health_after_restore", **asdict(h)})
        # Restart web so the restored extensions take effect
        log("รีสตาร์ทเว็บหลังคืน extension", "step")
        for r in restart_services(
            flavors or [i.flavor for i in installs if i.flavor],
            dry_run=args.dry_run,
            extra_services=web_services or list(WEB_SERVICE_CANDIDATES),
        ):
            report["actions"].append({"action": "restart", **asdict(r)})
        # If this is the only requested action, finish here
        if not any(
            [args.update, args.from_source, args.custombuild, args.mitigate, args.clean_ext_dups]
        ):
            if args.json_report:
                write_json_report(args.json_report, report, dry_run=args.dry_run)
            return 0

    if args.repair_repo and not (args.update or args.from_source or args.custombuild):
        log("ซ่อม FreeBSD pkg repository", "step")
        rr = repair_pkg_repo(dry_run=args.dry_run)
        log(rr.message, "ok" if rr.ok else "err")
        for d in rr.details:
            print(Color.DIM + str(d)[:500] + Color.RESET)
        report["actions"].append({"action": "repair_repo", **asdict(rr)})
        if not rr.ok and not args.dry_run:
            die("ซ่อม repo ไม่สำเร็จ — ใช้ --from-source แทน")

    update_ok = False
    if args.from_source and not (args.update or args.custombuild):
        if not installs and not flavors:
            die("ไม่พบ PHP ให้คอมไพล์จากซอร์ส")
        confirm_update(args.yes or args.dry_run)
        log("คอมไพล์ PHP จากซอร์สทางการ (pkg repo ใช้ไม่ได้)", "step")
        rs = build_php_from_source(
            installs, flavors=flavors, dry_run=args.dry_run, force=args.rebuild_modphp
        )
        log(rs.message, "ok" if rs.ok else "err")
        for d in rs.details:
            print(Color.DIM + str(d)[:500] + Color.RESET)
        report["actions"].append({"action": "from_source", **asdict(rs)})
        update_ok = rs.ok
        installs = discover_installs(flavors)
        code = print_report(installs)
        report["installs_after_update"] = [asdict(i) for i in installs]
        if not update_ok and not args.dry_run:
            if not args.mitigate:
                for r in mitigate_all(installs, dry_run=False):
                    report["actions"].append({"action": "mitigate_fallback", **asdict(r)})
            die("คอมไพล์ PHP จากซอร์สไม่สำเร็จ")
        if update_ok:
            # Make the freshly-built PHP actually usable (health + ext tidy)
            finalize_php_runtime(
                installs,
                do_clean_dups=args.clean_ext_dups,
                web_services=web_services,
                dry_run=args.dry_run,
                report=report,
            )
            mr = migrate_eol_to_safe(dry_run=args.dry_run, remove_eol=args.remove_eol)
            log(mr.message, "ok" if mr.ok else "warn")
            report["actions"].append({"action": "migrate_eol", **asdict(mr)})
            installs = discover_installs(flavors)

    if args.update or args.custombuild:
        if not installs and not flavors and not args.custombuild:
            die("ไม่พบ PHP ให้อัปเดต")
        confirm_update(args.yes or args.dry_run)

        # Path A: DirectAdmin CustomBuild (preferred when present / requested)
        cb_path = find_custombuild()
        use_cb = bool(args.custombuild or (args.all and cb_path))
        if use_cb and cb_path:
            log("อัปเดต PHP ผ่าน DirectAdmin CustomBuild", "step")
            rcb = custombuild_update_php(dry_run=args.dry_run)
            log(rcb.message, "ok" if rcb.ok else "err")
            for d in rcb.details:
                print(Color.DIM + str(d)[:500] + Color.RESET)
            report["actions"].append({"action": "custombuild", **asdict(rcb)})
            update_ok = rcb.ok
            installs = discover_installs(flavors)
            code = print_report(installs)
            report["installs_after_update"] = [asdict(i) for i in installs]
            if rcb.ok:
                log("อัปเดตผ่าน CustomBuild สำเร็จ", "ok")

        # Path B: repair repo + pkg
        if not update_ok:
            if args.repair_repo or not args.no_latest_repo:
                log("ซ่อม/ปรับ pkg repository ก่อนอัปเดต", "step")
                rr = repair_pkg_repo(dry_run=args.dry_run) if args.repair_repo else ensure_latest_repo(
                    dry_run=args.dry_run
                )
                log(rr.message, "ok" if rr.ok else "warn")
                for d in rr.details[:12]:
                    print(Color.DIM + str(d)[:500] + Color.RESET)
                report["actions"].append({"action": "repo_prepare", **asdict(rr)})

            log("อัปเดตดัชนีแพ็กเกจ (pkg)", "step")
            r1 = pkg_update(dry_run=args.dry_run)
            log(r1.message, "ok" if r1.ok else "err")
            for d in r1.details:
                print(Color.RED + str(d)[:800] + Color.RESET)
            report["actions"].append({"action": "pkg_update", **asdict(r1)})
            if not r1.ok:
                log(
                    "pkg update พัง — จะลอง upgrade/install ต่อ, CustomBuild, หรือจากซอร์ส",
                    "warn",
                )

            if not use_cb and cb_path:
                log("พบ CustomBuild — ลองอัปเดตทางนี้เพราะ pkg มีปัญหา", "warn")
                rcb = custombuild_update_php(dry_run=args.dry_run)
                log(rcb.message, "ok" if rcb.ok else "err")
                for d in rcb.details:
                    print(Color.DIM + str(d)[:500] + Color.RESET)
                report["actions"].append({"action": "custombuild_fallback", **asdict(rcb)})
                update_ok = rcb.ok

            if not update_ok:
                log("อัปเกรดแพ็กเกจ PHP ผ่าน pkg", "step")
                r2 = pkg_upgrade_php(
                    installs,
                    flavors=flavors,
                    dry_run=args.dry_run,
                    use_latest_repo=False,  # already prepared above
                )
                log(r2.message, "ok" if r2.ok else "err")
                for d in r2.details:
                    print(Color.DIM + str(d)[:500] + Color.RESET)
                report["actions"].append({"action": "pkg_upgrade", **asdict(r2)})
                update_ok = r2.ok

            # Path C: compile from official source when repos are dead (FreeBSD 13 EOL)
            if not update_ok:
                log("pkg/CustomBuild ไม่ได้ — คอมไพล์ PHP จากซอร์สทางการ", "warn")
                rs = build_php_from_source(
                    installs, flavors=flavors, dry_run=args.dry_run, force=args.rebuild_modphp
                )
                log(rs.message, "ok" if rs.ok else "err")
                for d in rs.details:
                    print(Color.DIM + str(d)[:500] + Color.RESET)
                report["actions"].append({"action": "from_source_fallback", **asdict(rs)})
                update_ok = rs.ok

            installs = discover_installs(flavors)
            code = print_report(installs)
            report["installs_after_update"] = [asdict(i) for i in installs]

        if update_ok:
            # Make the updated/compiled PHP actually usable (health + ext tidy)
            finalize_php_runtime(
                installs,
                do_clean_dups=args.clean_ext_dups,
                web_services=web_services,
                dry_run=args.dry_run,
                report=report,
            )
            mr = migrate_eol_to_safe(dry_run=args.dry_run, remove_eol=args.remove_eol)
            log(mr.message, "ok" if mr.ok else "warn")
            report["actions"].append({"action": "migrate_eol", **asdict(mr)})
            installs = discover_installs(flavors)

        if not update_ok and not args.dry_run:
            if not args.mitigate:
                log("อัปเดตไม่ถึงเกณฑ์ — คง mitigation ชั่วคราวไว้", "warn")
                for r in mitigate_all(installs, dry_run=False):
                    report["actions"].append({"action": "mitigate_fallback", **asdict(r)})
            log("==== สรุปปัญหา / ทางแก้ ====", "err")
            log("สาเหตุจาก diagnose: FreeBSD 13 pkg set /latest ได้ 404 (OS EOL)", "err")
            log("1) รัน: sudo python3 freebsd_php2shell_update.py --from-source --yes", "info")
            log("2) หรือซ่อม repo แล้วอัปเดต: sudo python3 freebsd_php2shell_update.py --repair-repo --update --yes", "info")
            log("3) ทางยาว: อัปเกรด OS เป็น FreeBSD 14/15 แล้วใช้ pkg ปกติ", "info")
            die("อัปเดต PHP ไม่สำเร็จถึงเวอร์ชันปลอดภัย")

    # Align extension_dir (fix: source PHP can't find pkg .so extensions)
    if args.align_ext_dir and not (args.update or args.from_source or args.custombuild):
        log("จัด extension_dir ให้ทุก SAPI ใช้ที่เดียวกัน", "step")
        php_bin = which("php") or (installs[0].binary if installs else "/usr/local/bin/php")
        ra = align_extension_dir(php_bin, dry_run=args.dry_run)
        log(ra.message, "ok" if ra.ok else "warn")
        for d in ra.details:
            print(Color.DIM + str(d)[:300] + Color.RESET)
        report["actions"].append({"action": "align_extension_dir", **asdict(ra)})

    if args.fix_apache:
        log("ซ่อม Apache ที่สตาร์ทไม่ได้ (ปิด leftover libphp7 + php_flag)", "step")
        rf = fix_apache_runtime(dry_run=args.dry_run)
        log(rf.message, "ok" if rf.ok else "err")
        for d in rf.details:
            print(Color.DIM + str(d)[:500] + Color.RESET)
        report["actions"].append({"action": "fix_apache", **asdict(rf)})
        if not rf.ok and not args.dry_run:
            die("ซ่อม Apache ไม่สำเร็จ — ดู apache configtest ด้านบน")

    # Switch Apache to php-fpm (patched) — the clean fix when mod_php stays old
    if args.use_fpm:
        log("สลับ Apache ให้ใช้ php-fpm (เวอร์ชันที่แพตช์แล้ว) แทน leftover libphp7/mod_php", "step")
        rf = complete_eol_web_cutover(dry_run=args.dry_run, fpm_addr=args.fpm_addr or None)
        if rf.ok and "ไม่ได้โหลด PHP 7" in rf.message:
            rf = switch_apache_to_fpm(dry_run=args.dry_run, fpm_addr=args.fpm_addr or None)
        log(rf.message, "ok" if rf.ok else "err")
        for d in rf.details:
            print(Color.DIM + str(d)[:500] + Color.RESET)
        report["actions"].append({"action": "use_fpm", **asdict(rf)})
        if not rf.ok and not args.dry_run:
            log("สลับ fpm ไม่สำเร็จ — ไม่ย้อนไป libphp7; ทางเลือก: --rebuild-modphp", "warn")
        elif rf.ok:
            # เว็บกับ CLI เป็นเวอร์ชันเดียวกันแล้ว — เก็บกวาด already loaded ได้
            args.clean_ext_dups = True
            php_bin = which("php") or (installs[0].binary if installs else "/usr/local/bin/php")
            rm = disable_missing_extension_inis(php_bin, dry_run=args.dry_run)
            log(rm.message, "ok" if rm.ok else "warn")
            report["actions"].append({"action": "disable_missing_ext", **asdict(rm)})

    # Standalone dedupe (when not already done inside an update run)
    if args.clean_ext_dups and not (args.update or args.custombuild or args.from_source):
        log("เก็บกวาด ext ini ซ้ำ (already loaded) แบบปลอดภัย", "step")
        php_bin = which("php") or (installs[0].binary if installs else "/usr/local/bin/php")
        rc = clean_duplicate_extension_inis(php_bin, dry_run=args.dry_run)
        log(rc.message, "ok" if rc.ok else "warn")
        for d in rc.details:
            print(Color.DIM + str(d)[:400] + Color.RESET)
        report["actions"].append({"action": "clean_ext_dups", **asdict(rc)})
        rm = disable_missing_extension_inis(php_bin, dry_run=args.dry_run)
        log(rm.message, "ok" if rm.ok else "warn")
        report["actions"].append({"action": "disable_missing_ext", **asdict(rm)})

    if args.restart:
        log("รีสตาร์ทบริการเว็บ/PHP", "step")
        fl = flavors or [i.flavor for i in installs if i.flavor]
        for r in restart_services(
            fl,
            dry_run=args.dry_run,
            extra_services=web_services or list(WEB_SERVICE_CANDIDATES),
        ):
            report["actions"].append({"action": "restart", **asdict(r)})

    if args.verify:
        log("ตรวจสอบ mitigation", "step")
        # refresh installs for current binaries
        installs = discover_installs(flavors)
        for r in verify_mitigation(installs):
            report["actions"].append({"action": "verify", **asdict(r)})

    if args.audit:
        log("pkg audit", "step")
        r = pkg_audit_php(dry_run=args.dry_run)
        log(r.message, "ok" if r.ok else "warn")
        for d in r.details[:20]:
            print("   ", d)
        report["actions"].append({"action": "audit", **asdict(r)})

    if args.json_report:
        write_json_report(args.json_report, report, dry_run=args.dry_run)

    # Final guidance
    vulns = [i for i in installs if i.status == "vulnerable"]
    if vulns:
        log(
            "ยังมี PHP ที่เสี่ยงอยู่ — ตรวจว่า repository FreeBSD มีแพ็กเกจที่แพตช์แล้วหรือยัง "
            "(pkg search php82 / pkg version -v | grep php)",
            "err",
        )
        log(
            "เกณฑ์ปลอดภัย: 8.2.35+ / 8.3.36+ / 8.4.27+ / 8.5.12+",
            "warn",
        )
        return 2

    # Honest verdict: the web-facing SAPI matters more than CLI for PHP2Shell
    if args.update or args.from_source or args.custombuild or args.all or args.use_fpm:
        web_results = detect_web_php_versions()
        if web_results:
            ww, _ = assess_web_sapi(web_results)
            if ww == 2:
                if detect_http_front() == "nginx":
                    log(
                        "สรุป: nginx ใช้ php-fpm ที่ยังต่ำกว่าเกณฑ์ — "
                        f"อัป FPM ด้วย sudo python3 {SCRIPT_NAME} --all --yes",
                        "err",
                    )
                else:
                    log(
                        "สรุป: CLI อาจปลอดภัย แต่ Apache ยังเสิร์ฟ mod_php เก่า — "
                        "ยังไม่ปลอดภัยจริง รัน: sudo python3 "
                        f"{SCRIPT_NAME} --use-fpm",
                        "err",
                    )
                return 2

    if args.remove_eol and not (args.update or args.from_source or args.custombuild):
        mr = migrate_eol_to_safe(dry_run=args.dry_run, remove_eol=True)
        log(mr.message, "ok" if mr.ok else "warn")
        for d in mr.details[:12]:
            print("   ", d)
        installs = discover_installs(flavors)

    eol = [i for i in installs if is_eol_branch(i.version)]
    if eol:
        log(
            "ยังมี PHP 7.4/EOL — จะย้ายไป 8.2.35 "
            f"(sudo python3 {SCRIPT_NAME} --from-source --yes --remove-eol)",
            "warn",
        )
        return 3

    if installs:
        log("ไม่พบสาขาที่ต่ำกว่าเกณฑ์ PHP2Shell จากตาราง upstream", "ok")
    return code if args.check and not (args.update or args.mitigate) else 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    except KeyboardInterrupt:
        print()
        die("ยกเลิก", 130)
