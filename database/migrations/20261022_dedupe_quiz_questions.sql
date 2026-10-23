-- ปิดข้อสอบที่ข้อความซ้ำในคลังเดียวกัน (เก็บ id น้อยสุด) และล้างชุดสุ่มเดิมให้สร้างใหม่
UPDATE training_quiz_questions q
INNER JOIN (
  SELECT quiz_id, question_text, MIN(id) AS keep_id
  FROM training_quiz_questions
  WHERE status = 1
  GROUP BY quiz_id, question_text
  HAVING COUNT(*) > 1
) d ON d.quiz_id = q.quiz_id
   AND d.question_text = q.question_text
   AND q.id <> d.keep_id
SET q.status = 0,
    q.updated_at = NOW()
WHERE q.status = 1;

-- ชุดสุ่มเดิมอาจมี id ที่ข้อความซ้ำ — ให้สร้างใหม่ตอนผู้เรียนเข้าสอบครั้งถัดไป
DELETE FROM training_quiz_papers;
