# TAVIS – วัคซีนจราจร

**วัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน**  
หน่วยงาน: สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม  
ที่อยู่: 134 ถนนเลี่ยงเมืองมหาสารคาม-ร้อยเอ็ด ตำบลแวงน่าง อำเภอเมือง จังหวัดมหาสารคาม 44000  
โทรศัพท์: 085-537-4109  
เว็บไซต์: https://tavis.thaiedu.in.th  
อีเมล: tavis@spmmsk.go.th

ระบบอบรมพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน ตามโมดูล 14 ชั่วโมง มีวัดประเมินผล ออกเกียรติบัตรออนไลน์ และนับชั่วโมง — สถาปัตยกรรม MVC (PHP 8.1 + MySQL)

## เริ่มต้นอย่างรวดเร็ว (XAMPP)

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/traffic_vaccine
/Applications/XAMPP/xamppfiles/bin/php /usr/local/bin/composer install --no-interaction
/Applications/XAMPP/xamppfiles/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS tavis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
/Applications/XAMPP/xamppfiles/bin/mysql -u root tavis < database/schema.sql
/Applications/XAMPP/xamppfiles/bin/mysql -u root tavis < database/seeds/seed.sql
```

เปิด: http://localhost/traffic_vaccine/public

คัดลอก `.env.example` เป็น `.env` (สร้างไว้แล้วในเครื่องพัฒนา)

## บัญชีเข้าสู่ระบบ

| บทบาท | ชื่อผู้ใช้ | รหัสผ่าน |
|---|---|---|
| Admin | `admin` | `Admin@1234` |
| Trainer | `trainer` | `Admin@1234` |
| User | `user01` | `Admin@1234` |

## โมดูลหลัก

- ลงทะเบียนอบรม → โมดูล 14 ชม. → ประเมินผล → นับชั่วโมง → เกียรติบัตรออนไลน์
- โครงสร้างหน่วยงาน: กระทรวง / หน่วยงาน / เขตพื้นที่ / โรงเรียน
- บทบาท: **admin · trainer · user**
- สารสนเทศ / รายงาน / AI ผู้ช่วย
- เว็บไซต์สาธารณะ + ตรวจสอบเกียรติบัตร

**จัดทำโดย สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม**

## ข้อมูลภูมิศาสตร์ (ทางเลือก)

ไฟล์ `db/tv.sql` มี master geo (จังหวัด/อำเภอ/ตำบล/โรงเรียน) — **ไม่รวมใน schema.sql**  
นำเข้าแยกเมื่อต้องการเท่านั้น

ดูคู่มือเต็มในโฟลเดอร์ `docs/`
# tavismsk
