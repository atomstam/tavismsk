<?php

declare(strict_types=1);

namespace App\Services;

/**
 * ลงทะเบียนอบรม · ประเมินผล · นับชั่วโมง · ออกเกียรติบัตร
 */
class TrainingService
{
    public function nextEnrollmentNo(): string
    {
        $year = current_academic_year();
        $seq = (int) (db()->fetch(
            'SELECT COUNT(*) AS c FROM course_enrollments WHERE academic_year = ?',
            [$year]
        )['c'] ?? 0) + 1;
        return sprintf('EN-%d-%05d', $year, $seq);
    }

    public function nextCertNo(): string
    {
        $prefix = (string) (setting('certificate_prefix') ?: config('app', 'training.certificate_prefix', 'TAVIS'));
        $year = current_academic_year();
        $seq = (int) (db()->fetch(
            'SELECT COUNT(*) AS c FROM certificates WHERE YEAR(issued_at) = YEAR(CURDATE())'
        )['c'] ?? 0) + 1;
        return sprintf('%s-%d-%06d', $prefix, $year, $seq);
    }

    /**
     * @param array{user_id:int,org_unit_id?:int|null,member_id?:int|null,academic_year?:int|null,note?:string|null} $data
     */
    public function enroll(array $data): int
    {
        $required = (float) (setting('required_hours') ?: config('app', 'training.total_hours', 14));
        $pass = (float) (setting('pass_score') ?: config('app', 'training.pass_score', 60));

        return (int) db()->insert('course_enrollments', [
            'enrollment_no' => $this->nextEnrollmentNo(),
            'user_id' => (int) $data['user_id'],
            'org_unit_id' => $data['org_unit_id'] ?? null,
            'member_id' => $data['member_id'] ?? null,
            'academic_year' => $data['academic_year'] ?? current_academic_year(),
            'status' => 'pending',
            'registered_at' => date('Y-m-d H:i:s'),
            'required_hours' => $required,
            'pass_score' => $pass,
            'note' => $data['note'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function approve(int $enrollmentId, int $approverId): bool
    {
        $row = db()->fetch('SELECT * FROM course_enrollments WHERE id = ?', [$enrollmentId]);
        if (!$row || !in_array($row['status'], ['pending', 'cancelled'], true)) {
            return false;
        }
        db()->update('course_enrollments', [
            'status' => 'approved',
            'approved_at' => date('Y-m-d H:i:s'),
            'approved_by' => $approverId,
            'started_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$enrollmentId]);
        return true;
    }

    /**
     * @param array{score?:float|null,max_score?:float,hours_earned?:float,note?:string|null} $data
     */
    public function saveAssessment(int $enrollmentId, int $planId, array $data, int $assessorId): bool
    {
        $enroll = db()->fetch('SELECT * FROM course_enrollments WHERE id = ?', [$enrollmentId]);
        $plan = db()->fetch('SELECT * FROM learning_plans WHERE id = ?', [$planId]);
        if (!$enroll || !$plan) {
            return false;
        }

        $max = (float) ($data['max_score'] ?? 100);
        $score = isset($data['score']) ? (float) $data['score'] : null;
        $passScore = (float) ($enroll['pass_score'] ?? 60);
        $passed = $score !== null && $score >= $passScore ? 1 : 0;
        $hours = (float) ($data['hours_earned'] ?? $plan['hours'] ?? 1);
        if (!$passed) {
            $hours = 0;
        }

        $existing = db()->fetch(
            'SELECT id FROM module_assessments WHERE enrollment_id = ? AND plan_id = ?',
            [$enrollmentId, $planId]
        );

        $payload = [
            'score' => $score,
            'max_score' => $max,
            'is_passed' => $passed,
            'hours_earned' => $hours,
            'assessed_at' => date('Y-m-d H:i:s'),
            'assessed_by' => $assessorId,
            'note' => $data['note'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            db()->update('module_assessments', $payload, 'id = ?', [(int) $existing['id']]);
        } else {
            $payload['enrollment_id'] = $enrollmentId;
            $payload['plan_id'] = $planId;
            $payload['created_at'] = date('Y-m-d H:i:s');
            db()->insert('module_assessments', $payload);
        }

        if ($passed && $hours > 0) {
            db()->insert('training_hour_logs', [
                'user_id' => (int) $enroll['user_id'],
                'enrollment_id' => $enrollmentId,
                'plan_id' => $planId,
                'hours' => $hours,
                'source' => 'module',
                'description' => 'ผ่านโมดูล: ' . ($plan['title'] ?? ''),
                'recorded_by' => $assessorId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->recalculateEnrollment($enrollmentId);
        return true;
    }

    public function recalculateEnrollment(int $enrollmentId): void
    {
        $enroll = db()->fetch('SELECT * FROM course_enrollments WHERE id = ?', [$enrollmentId]);
        if (!$enroll) {
            return;
        }

        $sum = db()->fetch(
            'SELECT COALESCE(SUM(hours_earned),0) AS h, COALESCE(AVG(score),0) AS avg_score,
                    SUM(CASE WHEN is_passed=1 THEN 1 ELSE 0 END) AS passed_modules,
                    COUNT(*) AS assessed
             FROM module_assessments WHERE enrollment_id = ?',
            [$enrollmentId]
        );

        $totalHours = (float) ($sum['h'] ?? 0);
        $required = (float) ($enroll['required_hours'] ?? 14);
        $avg = (float) ($sum['avg_score'] ?? 0);
        $passScore = (float) ($enroll['pass_score'] ?? 60);
        $requiredIds = db()->fetchAll('SELECT id FROM learning_plans WHERE status = 1 AND is_required = 1');
        $requiredTotal = count($requiredIds);
        $requiredPassed = 0;
        foreach ($requiredIds as $plan) {
            $row = db()->fetch(
                'SELECT is_passed FROM training_module_progress WHERE enrollment_id = ? AND plan_id = ?',
                [$enrollmentId, (int) $plan['id']]
            );
            if ((int) ($row['is_passed'] ?? 0) === 1) {
                $requiredPassed++;
            }
        }
        $attendance = $requiredTotal > 0 ? round(($requiredPassed / $requiredTotal) * 100, 2) : 0;
        $isPassed = ($requiredTotal > 0 && $requiredPassed === $requiredTotal) ? 1 : 0;
        $status = $enroll['status'];
        if ($isPassed) {
            $status = 'completed';
        } elseif (in_array($status, ['approved', 'pending'], true) && $passedModules > 0) {
            $status = 'in_progress';
        }

        db()->update('course_enrollments', [
            'total_hours' => $totalHours,
            'final_score' => round($avg, 2),
            'attendance_percent' => $attendance,
            'is_passed' => $isPassed,
            'status' => $status,
            'completed_at' => $isPassed ? date('Y-m-d H:i:s') : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$enrollmentId]);
    }

    public function issueCertificate(int $enrollmentId, int $issuerId): ?array
    {
        $enroll = db()->fetch(
            'SELECT e.*, u.title, u.first_name, u.last_name, o.name AS org_name
             FROM course_enrollments e
             INNER JOIN users u ON u.id = e.user_id
             LEFT JOIN org_units o ON o.id = e.org_unit_id
             WHERE e.id = ?',
            [$enrollmentId]
        );
        if (!$enroll) {
            return null;
        }
        $this->recalculateEnrollment($enrollmentId);
        $enroll = db()->fetch(
            'SELECT e.*, u.title, u.first_name, u.last_name, o.name AS org_name
             FROM course_enrollments e
             INNER JOIN users u ON u.id = e.user_id
             LEFT JOIN org_units o ON o.id = e.org_unit_id
             WHERE e.id = ?',
            [$enrollmentId]
        );
        if (!$enroll || !$this->requiredPlansComplete($enrollmentId)) {
            return null;
        }

        $existing = db()->fetch(
            "SELECT * FROM certificates WHERE enrollment_id = ? AND plan_id IS NULL AND status = 'active'",
            [$enrollmentId]
        );
        if ($existing) {
            return $existing;
        }

        $name = trim(($enroll['title'] ?? '') . ($enroll['first_name'] ?? '') . ' ' . ($enroll['last_name'] ?? ''));
        $id = (int) db()->insert('certificates', [
            'cert_no' => $this->nextCertNo(),
            'verify_code' => bin2hex(random_bytes(16)),
            'enrollment_id' => $enrollmentId,
            'user_id' => (int) $enroll['user_id'],
            'org_unit_id' => $enroll['org_unit_id'] ?? null,
            'title' => 'วุฒิบัตรผ่านหลักสูตร ' . app_brand('full_name') . ' (' . app_brand('short_name') . ')',
            'recipient_name' => $name,
            'org_name' => $enroll['org_name'] ?? null,
            'hours' => (float) $enroll['total_hours'],
            'final_score' => $enroll['final_score'] ?? null,
            'issued_at' => date('Y-m-d H:i:s'),
            'issued_by' => $issuerId,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return db()->fetch('SELECT * FROM certificates WHERE id = ?', [$id]) ?: null;
    }

    /** เกียรติบัตรของแผนเดียว ออกได้เมื่อแผนนั้นผ่านเกณฑ์ */
    public function issuePlanCertificate(int $enrollmentId, int $planId, int $issuerId): ?array
    {
        $enroll = db()->fetch(
            'SELECT e.*, u.title, u.first_name, u.last_name, o.name AS org_name
             FROM course_enrollments e
             INNER JOIN users u ON u.id = e.user_id
             LEFT JOIN org_units o ON o.id = e.org_unit_id
             WHERE e.id = ?',
            [$enrollmentId]
        );
        $plan = db()->fetch('SELECT * FROM learning_plans WHERE id = ? AND status = 1', [$planId]);
        $progress = db()->fetch(
            'SELECT is_passed, overall_percent FROM training_module_progress WHERE enrollment_id = ? AND plan_id = ?',
            [$enrollmentId, $planId]
        );
        if (!$enroll || !$plan || !(int) ($progress['is_passed'] ?? 0)) {
            return null;
        }
        $existing = db()->fetch(
            "SELECT * FROM certificates WHERE enrollment_id = ? AND plan_id = ? AND status = 'active'",
            [$enrollmentId, $planId]
        );
        if ($existing) {
            return $existing;
        }
        $name = trim(($enroll['title'] ?? '') . ($enroll['first_name'] ?? '') . ' ' . ($enroll['last_name'] ?? ''));
        $id = (int) db()->insert('certificates', [
            'cert_no' => $this->nextCertNo(),
            'verify_code' => bin2hex(random_bytes(16)),
            'enrollment_id' => $enrollmentId,
            'plan_id' => $planId,
            'user_id' => (int) $enroll['user_id'],
            'org_unit_id' => $enroll['org_unit_id'] ?? null,
            'title' => 'เกียรติบัตรแผนที่ ' . (int) $plan['plan_no'] . ' ' . $plan['title'],
            'recipient_name' => $name,
            'org_name' => $enroll['org_name'] ?? null,
            'hours' => (float) ($plan['hours'] ?? 1),
            'final_score' => $progress['overall_percent'] ?? null,
            'issued_at' => date('Y-m-d H:i:s'),
            'issued_by' => $issuerId,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return db()->fetch('SELECT * FROM certificates WHERE id = ?', [$id]) ?: null;
    }

    /** @return array{total:int,passed:int,ready:bool} */
    public function requiredPlanStanding(int $enrollmentId): array
    {
        $plans = db()->fetchAll(
            'SELECT id FROM learning_plans WHERE status = 1 AND is_required = 1'
        );
        $passed = 0;
        foreach ($plans as $plan) {
            $row = db()->fetch(
                'SELECT is_passed FROM training_module_progress WHERE enrollment_id = ? AND plan_id = ?',
                [$enrollmentId, (int) $plan['id']]
            );
            if ((int) ($row['is_passed'] ?? 0) === 1) {
                $passed++;
            }
        }
        $total = count($plans);
        return [
            'total' => $total,
            'passed' => $passed,
            'ready' => $total > 0 && $passed === $total,
        ];
    }

    private function requiredPlansComplete(int $enrollmentId): bool
    {
        return $this->requiredPlanStanding($enrollmentId)['ready'];
    }
}
