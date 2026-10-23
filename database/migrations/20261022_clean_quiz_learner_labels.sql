-- ล้างชื่อแบบทดสอบเดโม่ และคำนำ "แผนที่ X ข้อ Y:" ในคลังข้อสอบ
-- ผู้เรียนจะเห็นเฉพาะคำถาม ไม่บอกว่าสุ่มมาจากข้อไหน

UPDATE training_quizzes
SET title = CASE quiz_type
  WHEN 'pretest' THEN 'ทดสอบก่อนเรียน'
  WHEN 'posttest' THEN 'สอบหลังเรียน'
  WHEN 'satisfaction' THEN 'แบบประเมินความพึงพอใจ'
  ELSE title
END,
description = CASE quiz_type
  WHEN 'pretest' THEN 'ทดสอบความรู้ก่อนเรียน'
  WHEN 'posttest' THEN 'ทดสอบความรู้หลังเรียน'
  WHEN 'satisfaction' THEN 'ประเมินความพึงพอใจต่อการเรียน'
  ELSE description
END,
updated_at = NOW()
WHERE title LIKE '%โมดูลนำร่อง%'
   OR title IN ('Pretest', 'Posttest', 'PRETEST', 'POSTTEST')
   OR title LIKE 'Pretest %'
   OR title LIKE 'Posttest %';

UPDATE training_quiz_questions
SET question_text = TRIM(REGEXP_REPLACE(question_text, '^แผนที่[[:space:]]*[0-9]+[[:space:]]*ข้อ[[:space:]]*[0-9]+[[:space:]]*:[[:space:]]*', '')),
    updated_at = NOW()
WHERE question_text REGEXP '^แผนที่[[:space:]]*[0-9]+[[:space:]]*ข้อ[[:space:]]*[0-9]+[[:space:]]*:';
