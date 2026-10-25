#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
freebsd_php2shell_update_v2.py — อัปเดต PHP บนเซิร์ฟเวอร์ FreeBSD จริง (เชิงป้องกัน)

ใช้เอนจินจาก freebsd_php2shell_update.py แต่บังคับนโยบายที่ปลอดภัยกว่า:

  • เลือกเวอร์ชันปลอดภัยที่ใกล้สาขาปัจจุบันที่สุดเท่านั้น
    ตัวอย่าง: 8.2.30 → 8.2.35  ไม่กระโดดไป 8.3/8.4
  • ประเมินผลกระทบก่อนอัปเดต (CLI / FPM / เว็บ / ส่วนขยาย / ดาวน์ไทม์)
  • สำรอง php.ini และคอนฟิก Apache ก่อนแก้
  • ไม่สลับ FreeBSD package set เป็น latest โดยอัตโนมัติ
  • ไม่เปลี่ยนสาขา PHP เว้นแต่ใส่ --allow-branch-change
  • แพตช์ฝั่งเว็บ (FPM/mod_php) เป็นหลัก เพราะ PHP2Shell ยิงผ่านเว็บ
  • ใส่ mitigation session.upload_progress.enabled=Off ก่อนอัปเกรด

ไม่ส่ง exploit / payload / ไม่ทำ PoC

ตัวอย่างบนเซิร์ฟเวอร์จริง:
  sudo python3 freebsd_php2shell_update_v2.py --check
  sudo python3 freebsd_php2shell_update_v2.py --apply --dry-run
  sudo python3 freebsd_php2shell_update_v2.py --apply --yes
"""

from __future__ import annotations

import argparse
import datetime as dt
import importlib.util
import os
import platform
import re
import shutil
import sys
from dataclasses import asdict, dataclass, field
from pathlib import Path
from typing import Any, Dict, List, Optional, Sequence, Tuple


SCRIPT_NAME = "freebsd_php2shell_update_v2.py"
SCRIPT_VERSION = "2.1.3"
V1_NAME = "freebsd_php2shell_update.py"
BACKUP_ROOT = Path("/var/backups/php2shell-v2")

SAFE_MIN: Dict[Tuple[int, int], Tuple[int, int, int]] = {
    (8, 5): (8, 5, 12),
    (8, 4): (8, 4, 27),
    (8, 3): (8, 3, 36),
    (8, 2): (8, 2, 35),
}


@dataclass
class TargetPlan:
    flavor: str
    current: str
    target: str
    status: str
    same_branch: bool
    needs_update: bool
    note: str


@dataclass
class Impact:
    plans: List[TargetPlan] = field(default_factory=list)
    extensions: List[str] = field(default_factory=list)
    web_lines: List[str] = field(default_factory=list)
    risks: List[str] = field(default_factory=list)
    backup_dir: str = ""
    needs_apply: bool = False
    needs_branch_change: bool = False
    web_vulnerable: bool = False


def load_v1() -> Any:
    path = Path(__file__).resolve().parent / V1_NAME
    if not path.is_file():
        print(f"[-] ไม่พบ {path} — ต้องมีสคริปต์ต้นฉบับคู่กัน", file=sys.stderr)
        sys.exit(1)
    spec = importlib.util.spec_from_file_location("freebsd_php2shell_update_v1", path)
    if spec is None or spec.loader is None:
        print(f"[-] โหลด {V1_NAME} ไม่ได้", file=sys.stderr)
        sys.exit(1)
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


def closest_safe(ver: Any, v1: Any) -> Tuple[Optional[Any], str, bool]:
    """คืน (target Version, หมายเหตุ, อยู่ในสาขาเดิมหรือไม่)"""
    branch = ver.branch()
    need = SAFE_MIN.get(branch)
    if need:
        target = v1.Version(*need)
        if ver.as_tuple() >= need:
            return target, f"สาขา {ver.major}.{ver.minor} ผ่านเกณฑ์แล้ว", True
        return target, f"อัปในสาขาเดิม {ver} → {target}", True
    if branch < (8, 2):
        target = v1.Version(8, 2, 35)
        return (
            target,
            f"สาขา {ver} เป็น EOL — จะอัปเป็น {target} (ใกล้ที่สุดที่มีแพตช์ ไม่แพตช์ 7.4 ต่อ)",
            False,
        )
    return None, f"ยังไม่มีเกณฑ์สำหรับสาขา {ver.major}.{ver.minor}", True


def collect_extensions(v1: Any, binary: str) -> List[str]:
    if not binary or not Path(binary).exists():
        return []
    cp = v1.run([binary, "-m"], dry_run=False, check=False)
    mods = []
    for line in (cp.stdout or "").splitlines():
        name = line.strip()
        if not name or name.startswith("[") or name.lower() == "module":
            continue
        mods.append(name)
    return sorted(set(mods))


def build_impact(v1: Any, installs: list, allow_branch_change: bool) -> Impact:
    impact = Impact()
    flavors = []
    for inst in installs:
        target, note, same = closest_safe(inst.version, v1)
        needs = bool(target and inst.version.as_tuple() < target.as_tuple())
        if not same:
            impact.needs_branch_change = True
            # PHP 7.4 / 8.0 / 8.1 → 8.2.35 เป็นเส้นทางมาตรฐาน ไม่ต้องรอ --allow-branch-change
            if inst.version.branch() < (8, 2):
                needs = True
                note += " — อนุญาตอัตโนมัติเพราะสาขาเดิมไม่มีแพตช์"
            elif not allow_branch_change:
                note += " — ถูกบล็อกจนกว่าจะใส่ --allow-branch-change"
                needs = False
        plan = TargetPlan(
            flavor=inst.flavor or "??",
            current=str(inst.version),
            target=str(target) if target else "-",
            status=inst.status,
            same_branch=same,
            needs_update=needs,
            note=note,
        )
        impact.plans.append(plan)
        if needs:
            impact.needs_apply = True
        flavors.append(inst.flavor)

    web = v1.detect_web_php_versions()
    worst, lines = v1.assess_web_sapi(web)
    impact.web_lines = [re.sub(r"\033\[[0-9;]*m", "", x).strip() for x in lines]
    impact.web_vulnerable = worst == 2
    if impact.web_vulnerable:
        impact.needs_apply = True

    php_bin = v1.which("php") or (installs[0].binary if installs else "")
    impact.extensions = collect_extensions(v1, php_bin)

    impact.risks.append("รีสตาร์ท php-fpm/apache ทำให้เว็บหยุดสั้น ๆ (โดยปกติไม่กี่วินาที)")
    impact.risks.append("อัปเดตอยู่ในสาขาเดิม จึงเสี่ยงเข้ากันไม่ได้กับแอปน้อยกว่าการกระโดด 8.2→8.3")
    if any(not p.same_branch for p in impact.plans):
        impact.risks.append("การเปลี่ยนสาขาอาจทำให้ส่วนขยาย/แอปที่ผูก API เก่าพัง — ต้องอนุญาตแยก")
    impact.risks.append("ถ้า pkg ของ FreeBSD 13 ไม่มีแพตช์ จะคอมไพล์เฉพาะเวอร์ชันเป้าหมายในสาขาเดิมจากซอร์สทางการ")
    if impact.web_vulnerable:
        if v1.detect_http_front() == "nginx":
            impact.risks.append("หน้าเว็บเป็น nginx — ต้องแพตช์ php-fpm ไม่ต้องสลับ Apache/mod_php")
        else:
            impact.risks.append("เว็บยังเสิร์ฟ PHP เก่า — หลังแพตช์ CLI/FPM อาจต้องสลับ Apache ไป php-fpm")
    impact.risks.append("จะใส่ mitigation ก่อนอัปเกรด เพื่อลดความเสี่ยงระหว่างรีสตาร์ท")
    return impact


def print_impact(impact: Impact) -> None:
    print()
    print("======== แผนอัปเดต (เลือกเวอร์ชันใกล้สาขาปัจจุบัน) ========")
    if not impact.plans:
        print("  ไม่พบ PHP บนเครื่อง")
    for p in impact.plans:
        mark = "อัปเดต" if p.needs_update else ("เปลี่ยนสาขา" if not p.same_branch else "คงไว้")
        print(f"  php{p.flavor}: {p.current} → {p.target}  [{p.status}]  {mark}")
        print(f"           {p.note}")
    print("-------- ฝั่งเว็บ (พื้นผิวโจมตีจริง) --------")
    if impact.web_lines:
        for line in impact.web_lines:
            print(f"  {line}")
    else:
        print("  อ่าน banner เว็บไม่ได้ (อาจซ่อนเวอร์ชัน)")
    print(f"-------- ส่วนขยายที่โหลดอยู่ ({len(impact.extensions)}) --------")
    if impact.extensions:
        print("  " + ", ".join(impact.extensions[:40]) + (" …" if len(impact.extensions) > 40 else ""))
    print("-------- ผลกระทบที่คาด --------")
    for risk in impact.risks:
        print(f"  • {risk}")
    print("======================================================")
    print()


def backup_configs(v1: Any, installs: list, dry_run: bool) -> Path:
    stamp = dt.datetime.now().strftime("%Y%m%d-%H%M%S")
    dest = BACKUP_ROOT / stamp
    if dry_run:
        v1.log(f"DRY-RUN จะสำรองคอนฟิกไป {dest}", "warn")
        return dest
    dest.mkdir(parents=True, exist_ok=True)
    copied = 0
    candidates = [
        Path("/usr/local/etc/php.ini"),
        Path("/usr/local/etc/php"),
        Path("/usr/local/etc/php-fpm.conf"),
        Path("/usr/local/etc/php-fpm.d"),
        Path("/usr/local/etc/apache24/httpd.conf"),
        Path("/usr/local/etc/apache24/Includes"),
        Path("/usr/local/etc/apache24/extra"),
        Path("/usr/local/etc/nginx/nginx.conf"),
        Path("/usr/local/etc/nginx"),
        Path("/usr/local/etc/nginx/conf.d"),
        Path("/usr/local/etc/nginx/vhosts"),
        Path("/usr/local/etc/nginx/sites-enabled"),
    ]
    for inst in installs:
        for ini in inst.ini_files:
            candidates.append(Path(ini))
    seen = set()
    for src in candidates:
        key = str(src)
        if key in seen or not src.exists():
            continue
        seen.add(key)
        target = dest / src.relative_to("/")
        try:
            if src.is_dir():
                shutil.copytree(src, target, dirs_exist_ok=True)
            else:
                target.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(src, target)
            copied += 1
        except OSError as exc:
            v1.log(f"สำรอง {src} ไม่ได้: {exc}", "warn")
    (dest / "README.txt").write_text(
        f"สำรองโดย {SCRIPT_NAME} v{SCRIPT_VERSION}\n"
        f"เวลา {stamp}\n"
        f"คืนค่าด้วยการคัดลอกกลับจากโฟลเดอร์นี้\n",
        encoding="utf-8",
    )
    v1.log(f"สำรองคอนฟิก {copied} รายการที่ {dest}", "ok")
    return dest


def confirm(yes: bool, question: str) -> bool:
    if yes:
        return True
    if not sys.stdin.isatty():
        print("[-] ไม่ใช่ TTY และไม่มี --yes")
        return False
    try:
        ans = input(f"{question} [y/N] ").strip().lower()
    except EOFError:
        return False
    return ans in ("y", "yes")


def flavors_from_plans(plans: List[TargetPlan], allow_branch_change: bool) -> List[str]:
    out = []
    for p in plans:
        if p.needs_update and not p.same_branch:
            out.append("82")
        elif p.needs_update or (not p.same_branch and allow_branch_change):
            if p.flavor.isdigit():
                out.append(p.flavor)
        elif p.flavor.isdigit() and p.flavor not in out:
            # ยังส่ง flavor ปัจจุบันให้ v1 เพื่อไม่ไปยุ่งสาขาอื่น
            out.append(p.flavor)
    # unique preserve
    seen = set()
    uniq = []
    for f in out:
        if f not in seen:
            seen.add(f)
            uniq.append(f)
    return uniq


def apply_update(v1: Any, installs: list, impact: Impact, args: argparse.Namespace) -> int:
    if impact.needs_branch_change and not args.allow_branch_change:
        blocked = [p for p in impact.plans if not p.same_branch]
        if blocked and not any(p.needs_update for p in impact.plans) and not impact.web_vulnerable:
            v1.log("ต้องเปลี่ยนสาขาจึงจะมีแพตช์ — ใส่ --allow-branch-change ถ้ายอมรับผลกระทบ", "err")
            return 3

    if os.geteuid() != 0 and not args.dry_run:
        v1.log("ต้องรันด้วย sudo/root", "err")
        return 1

    if not confirm(args.yes or args.dry_run, "ยืนยันอัปเดต PHP ตามแผนสาขาเดิมบนเซิร์ฟเวอร์นี้?"):
        v1.log("ยกเลิกโดยผู้ใช้", "warn")
        return 0

    report: Dict[str, Any] = {
        "script": SCRIPT_NAME,
        "script_version": SCRIPT_VERSION,
        "engine": V1_NAME,
        "host": platform.node(),
        "time": dt.datetime.now().isoformat(timespec="seconds"),
        "dry_run": bool(args.dry_run),
        "plans": [asdict(p) for p in impact.plans],
        "actions": [],
    }

    backup = backup_configs(v1, installs, args.dry_run)
    report["backup_dir"] = str(backup)

    v1.log("ใส่ mitigation ก่อนอัปเกรด", "step")
    for r in v1.mitigate_all(installs, dry_run=args.dry_run):
        report["actions"].append({"action": "mitigate", **asdict(r)})

    flavors = flavors_from_plans(impact.plans, args.allow_branch_change)
    update_ok = False

    if any(p.needs_update for p in impact.plans) or impact.web_vulnerable:
        v1.log("อัปเกรดในสาขาเดิมผ่าน pkg (ไม่สลับ latest repo)", "step")
        r2 = v1.pkg_upgrade_php(
            installs,
            flavors=flavors or None,
            dry_run=args.dry_run,
            use_latest_repo=False,
        )
        v1.log(r2.message, "ok" if r2.ok else "warn")
        report["actions"].append({"action": "pkg_upgrade_same_branch", **asdict(r2)})
        update_ok = r2.ok

        if not update_ok and args.custombuild and v1.find_custombuild():
            v1.log("ลอง DirectAdmin CustomBuild (ยังอยู่สาขาที่ตั้งไว้ใน options.conf)", "step")
            rcb = v1.custombuild_update_php(dry_run=args.dry_run)
            v1.log(rcb.message, "ok" if rcb.ok else "warn")
            report["actions"].append({"action": "custombuild", **asdict(rcb)})
            update_ok = rcb.ok

        if not update_ok:
            v1.log("pkg ไม่ถึงเกณฑ์ — คอมไพล์เฉพาะเวอร์ชันเป้าหมายในสาขาเดิมจากซอร์สทางการ", "warn")
            rs = v1.build_php_from_source(
                installs, flavors=flavors or None, dry_run=args.dry_run, force=False
            )
            v1.log(rs.message, "ok" if rs.ok else "err")
            report["actions"].append({"action": "from_source_same_branch", **asdict(rs)})
            update_ok = rs.ok

        installs = v1.discover_installs(flavors or None)
        v1.print_report(installs)
        if update_ok:
            v1.finalize_php_runtime(
                installs,
                do_clean_dups=True,
                web_services=None,
                dry_run=args.dry_run,
                report=report,
            )
            mr = v1.migrate_eol_to_safe(dry_run=args.dry_run, remove_eol=args.remove_eol)
            v1.log(mr.message, "ok" if mr.ok else "warn")
            report["actions"].append({"action": "migrate_eol", **asdict(mr)})
            installs = v1.discover_installs(flavors or None)
            cut = v1.complete_eol_web_cutover(
                dry_run=args.dry_run, fpm_addr=args.fpm_addr or None
            )
            v1.log(cut.message, "ok" if cut.ok else "warn")
            report["actions"].append({"action": "eol_web_cutover", **asdict(cut)})
        elif not args.dry_run:
            v1.log("อัปเดตไม่ถึงเกณฑ์ — คง mitigation ไว้ และไม่สลับคอนฟิกเว็บ", "err")
            if args.json_report:
                v1.write_json_report(args.json_report, report, dry_run=args.dry_run)
            return 2

    web_worst = v1.print_sapi_matrix(installs)
    if web_worst == 2 and v1.detect_http_front() == "nginx":
        v1.log("หน้าเว็บเป็น nginx — ไม่สลับ Apache; ตรวจว่า php-fpm ผ่านเกณฑ์ในสาขาเดิม", "warn")
    elif web_worst == 2 or (
        hasattr(v1, "apache_has_legacy_modphp") and v1.apache_has_legacy_modphp()
    ):
        v1.log("เว็บยังเสิร์ฟของเก่า / มี leftover libphp7 — สลับ Apache ไป php-fpm", "step")
        rf = v1.complete_eol_web_cutover(dry_run=args.dry_run, fpm_addr=args.fpm_addr or None)
        if rf.ok and "ไม่ได้โหลด PHP 7" in rf.message:
            rf = v1.switch_apache_to_fpm(dry_run=args.dry_run, fpm_addr=args.fpm_addr or None)
        v1.log(rf.message, "ok" if rf.ok else "err")
        report["actions"].append({"action": "use_fpm", **asdict(rf)})
        if rf.ok and not args.dry_run:
            php_bin = v1.which("php") or (installs[0].binary if installs else "/usr/local/bin/php")
            rc = v1.clean_duplicate_extension_inis(php_bin, dry_run=False)
            v1.log(rc.message, "ok" if rc.ok else "warn")
            report["actions"].append({"action": "clean_ext_dups_after_fpm", **asdict(rc)})
            rm = v1.disable_missing_extension_inis(php_bin, dry_run=False)
            v1.log(rm.message, "ok" if rm.ok else "warn")
            report["actions"].append({"action": "disable_missing_ext", **asdict(rm)})

    v1.log("รีสตาร์ทบริการเว็บ/PHP", "step")
    for r in v1.restart_services(
        flavors or [i.flavor for i in installs if i.flavor],
        dry_run=args.dry_run,
        extra_services=list(v1.WEB_SERVICE_CANDIDATES),
    ):
        report["actions"].append({"action": "restart", **asdict(r)})

    php_bin = v1.which("php") or (installs[0].binary if installs else "/usr/local/bin/php")
    health = v1.check_critical_php_health(php_bin)
    v1.log(health.message, "ok" if health.ok else "err")
    report["actions"].append({"action": "health", **asdict(health)})
    if not health.ok and not args.dry_run:
        v1.log("ฟังก์ชันสำคัญหาย — คืน extension จาก disabled-dup", "err")
        rr = v1.restore_extension_inis(dry_run=False)
        report["actions"].append({"action": "restore_ext", **asdict(rr)})
        v1.log(f"คอนฟิกสำรองอยู่ที่ {backup}", "warn")

    installs = v1.discover_installs(flavors or None)
    after = build_impact(v1, installs, args.allow_branch_change)
    print_impact(after)
    if args.json_report:
        report["installs_after"] = [asdict(i) for i in installs]
        v1.write_json_report(args.json_report, report, dry_run=args.dry_run)

    still = [p for p in after.plans if p.status == "vulnerable"]
    if still or after.web_vulnerable:
        v1.log("ยังไม่ผ่านเกณฑ์ฝั่งเว็บ — อย่าเพิ่งปิด mitigation", "err")
        return 2
    v1.log("อัปเดตในสาขาเดิมเสร็จ และฝั่งที่ตรวจได้ผ่านเกณฑ์ PHP2Shell", "ok")
    return 0


def build_parser() -> argparse.ArgumentParser:
    p = argparse.ArgumentParser(
        prog=SCRIPT_NAME,
        description="FreeBSD v2: อัปเดต PHP ในสาขาเดิม พร้อมประเมินผลกระทบ (เชิงป้องกัน)",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__,
    )
    p.add_argument("--check", action="store_true", help="ตรวจและแสดงแผน/ผลกระทบ (ค่าเริ่มต้น)")
    p.add_argument("--apply", action="store_true", help="ทำตามแผน: สำรอง → mitigate → อัปในสาขาเดิม → ตรวจเว็บ")
    p.add_argument("--all", action="store_true", help="เหมือน --apply (นโยบาย v2 ไม่สลับ latest repo)")
    p.add_argument("--custombuild", action="store_true", help="อนุญาตให้ใช้ DirectAdmin CustomBuild ถ้า pkg ไม่ถึงเกณฑ์")
    p.add_argument(
        "--allow-branch-change",
        action="store_true",
        help="ยอมให้ย้ายสาขาอื่นนอกเหนือจาก 7.4/8.0/8.1 → 8.2.35",
    )
    p.add_argument(
        "--remove-eol",
        action="store_true",
        help="หลังย้าย 7.4 ไป 8.2.35 แล้ว ลบแพ็กเกจ php74-* ที่ค้าง",
    )
    p.add_argument("--fpm-addr", default="", help="ที่อยู่ php-fpm ถ้าต้องสลับจาก mod_php")
    p.add_argument("--json-report", metavar="FILE", help="เขียนรายงาน JSON")
    p.add_argument("--dry-run", action="store_true", help="จำลอง ไม่แก้ระบบ")
    p.add_argument("--yes", action="store_true", help="ไม่ถามยืนยัน")
    p.add_argument("--version", action="version", version=f"{SCRIPT_NAME} {SCRIPT_VERSION}")
    return p


def main(argv: Optional[Sequence[str]] = None) -> int:
    args = build_parser().parse_args(argv)
    if args.all:
        args.apply = True
    if not args.apply:
        args.check = True

    v1 = load_v1()
    v1.log(f"{SCRIPT_NAME} v{SCRIPT_VERSION} ใช้เอนจิน {V1_NAME}", "step")
    v1.log("นโยบาย: เวอร์ชันปลอดภัยที่ใกล้สาขาปัจจุบัน + ประเมินผลกระทบก่อนอัป", "info")
    v1.require_freebsd()

    installs = v1.discover_installs(None)
    v1.print_report(installs)
    v1.print_sapi_matrix(installs)
    impact = build_impact(v1, installs, args.allow_branch_change)
    print_impact(impact)

    if not args.apply:
        v1.log("โหมดตรวจอย่างเดียว — ยังไม่ได้อัปเดต", "warn")
        if impact.needs_apply:
            v1.log("จำเป็นต้องอัปเดต: sudo python3 freebsd_php2shell_update_v2.py --apply --yes", "step")
        else:
            v1.log("สาขาปัจจุบันผ่านเกณฑ์แล้ว — ตรวจฝั่งเว็บด้วย php2shell_scan_v2.py อีกครั้ง", "ok")
        return 2 if impact.needs_apply or impact.web_vulnerable else 0

    return apply_update(v1, installs, impact, args)


if __name__ == "__main__":
    try:
        sys.exit(main())
    except KeyboardInterrupt:
        print()
        sys.exit(130)
