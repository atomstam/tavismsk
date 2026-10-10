# คู่มือติดตั้ง TAVIS

## ความต้องการระบบ

- PHP 8.1+
- MySQL 8 / MariaDB 10.4+
- Composer
- ส่วนขยาย PHP: pdo_mysql, mbstring, gd, zip, openssl

## ขั้นตอน

1. วางโปรเจกต์ที่ `htdocs/traffic_vaccine`
2. คัดลอก `.env.example` → `.env` แล้วปรับ `APP_URL`, `DB_*`
3. `composer install`
4. สร้างฐานข้อมูลและนำเข้า:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS tavis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root tavis < database/schema.sql
mysql -u root tavis < database/seeds/seed.sql
```

5. เปิด http://localhost/traffic_vaccine/public
6. เข้าสู่ระบบ: `admin` / `Admin@1234`

## Google Drive

ค่าเริ่มต้น `GOOGLE_DRIVE_ENABLED=false`  
เปิดเมื่อมี Service Account และตั้ง `GOOGLE_DRIVE_*` ใน `.env`

## AI ผู้ช่วย

ตั้ง `OPENAI_API_KEY` ใน `.env` เมื่อพร้อมเชื่อม OpenAI  
Endpoint stub อยู่ที่ `/admin/ai`

## ข้อมูลภูมิศาสตร์ (ทางเลือก)

`db/tv.sql` เป็น dump จังหวัด/อำเภอ/ตำบล/โรงเรียน — ขนาดใหญ่  
**อย่านำเข้าแทน schema** — นำเข้าเพิ่มเมื่อต้องการฟีเจอร์เลือกพื้นที่เท่านั้น

## Production

- ตั้ง `APP_ENV=production`, `APP_DEBUG=false`
- เปลี่ยน `APP_KEY`, `JWT_SECRET`, รหัสผ่าน admin
- ชี้ DocumentRoot ไปที่ `public/`
- เปิด HTTPS และอัปเดต `APP_URL=https://tavis.spmmsk.go.th`
