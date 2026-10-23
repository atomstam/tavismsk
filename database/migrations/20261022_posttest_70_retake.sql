-- สอบหลัง: เกณฑ์ผ่าน 70% · สอบได้ 3 ครั้ง
UPDATE training_quizzes
SET pass_score = 70.00,
    max_attempts = 3,
    updated_at = NOW()
WHERE quiz_type = 'posttest';
