# คู่มือผู้ดูแลระบบ TAVIS

## สรุประบบ

TAVIS เป็น**ระบบอบรมพัฒนาสมรรถนะ**ด้านความปลอดภัยทางถนน ใช้งานได้ทั่วประเทศ

วงจร: **ลงทะเบียน → เรียนตามโมดูล → วัดประเมินผล → นับชั่วโมง → ผ่านหลักสูตร → เกียรติบัตรออนไลน์**

โครงสร้างหน่วยงาน: **กระทรวง → หน่วยงาน → เขตพื้นที่ → โรงเรียน**

Footer: **จัดทำโดย สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม**

## บทบาท (Role)

| บทบาท | สิทธิ์หลัก |
|---|---|
| **admin** | ดูแลทั้งระบบ ผู้ใช้ โครงสร้างหน่วยงาน ตั้งค่า |
| **trainer** | โมดูลอบรม ลงทะเบียน ประเมินผล ออกเกียรติบัตร รายงาน |
| **user** | ลงทะเบียนเรียน ดูผล นับชั่วโมง รับ/พิมพ์เกียรติบัตร |

กำหนดสิทธิ์ละเอียดใน `config/permissions.php` และ `config/roles.php`

บัญชีตัวอย่าง (รหัสผ่าน `Admin@1234`):

- `admin` — ผู้ดูแลระบบ
- `trainer` — วิทยากร
- `user01` — ผู้เข้ารับการพัฒนา

## เมนูแอดมินที่สำคัญ

- **หลักสูตร** `/admin/curriculum`
- **ลงทะเบียนหลักสูตร** `/admin/enrollments`
- **เกียรติบัตรออนไลน์** `/admin/certificates`
- **โครงสร้างหน่วยงาน** `/admin/org-units`
- **ผู้เข้ารับการพัฒนา** `/admin/members`
- **AI ผู้ช่วย** `/admin/ai` — ตั้ง `OPENAI_API_KEY` ใน `.env`
- บุคลากร / ผู้ใช้ / รายงาน / แบบฟอร์ม / Log / ตั้งค่า

## ตรวจสอบเกียรติบัตรสาธารณะ

`/certificate/verify?code=...`

## Migration (ฐานที่มีอยู่แล้ว)

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -h 127.0.0.1 -u root tavis < database/migrations/20260910_nationwide_training.sql
```

## ฐานข้อมูลใหม่

```bash
mysql -h 127.0.0.1 -u root tavis < database/schema.sql
mysql -h 127.0.0.1 -u root tavis < database/seeds/seed.sql
```

## ความปลอดภัย

CSRF, XSS escape, PDO prepared statements, password hash, session login, role permission
