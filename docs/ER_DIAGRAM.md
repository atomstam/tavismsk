# ER Diagram — TAVIS

## ภาพรวม

ฐานข้อมูลหลัก: **`tavis`**

โมดูลเดิมจาก E-Portfolio ยังคงอยู่ (users, personnel, portfolio_*, forms, tickets, CMS, logs, gallery)  
เพิ่มโมดูลหลักสูตรชมรมวัคซีนจราจร

## เอนทิตีหลัก (หลักสูตร)

```
learning_plans 1───* learning_resources
learning_plans 1───* worksheets
club_members   1───* learning_progress *───1 learning_plans
learning_objectives (master วัตถุประสงค์หลักสูตร)
```

### learning_plans
หลักสูตร 14 ชั่วโมง (`plan_no` UNIQUE)

### learning_resources
สื่อ/ลิงก์/วิดีโอ/PDF ของแต่ละหลักสูตร

### worksheets
ใบงานของแต่ละหลักสูตร

### club_members
สมาชิกชมรม (นักเรียน) — เชื่อม `personnel_id` ได้ (optional)

### learning_progress
สถานะเรียนของสมาชิกต่อหลักสูตร — UNIQUE(`member_id`,`plan_id`)  
status: pending | in_progress | completed | absent

### learning_objectives
วัตถุประสงค์การเรียนรู้ระดับหลักสูตร

## เอนทิตีที่สืบทอด

- users ↔ personnel ↔ departments / positions / academic_ranks
- portfolio_items / portfolio_files (กิจกรรมชมรม)
- forms / form_fields / form_submissions
- tickets / ticket_replies / ticket_files
- pages, news, faqs, downloads, announcements, contact_messages
- notifications, login_logs, activity_logs, settings
- api_tokens, visitors, backups
- gallery_albums / gallery_media
- prefixes, academic_years

## ข้อมูลภูมิศาสตร์ (แยก)

ไฟล์ `db/tv.sql` มีตารางอ้างอิง province / amphur / tumbon / school  
**ไม่รวมใน schema.sql** — นำเข้าทางเลือกเมื่อต้องการ
