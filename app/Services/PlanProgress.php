<?php

declare(strict_types=1);

namespace App\Services;

/**
 * ความก้าวหน้า (overall) = ดำเนินการครบองค์ประกอบ
 * คะแนนรวม (score_percent) = คะแนนถ่วงน้ำหนัก ใช้ตัดสินผ่านและแสดงในเกียรติบัตร
 */
final class PlanProgress
{
    /**
     * คำนวณคะแนนรวมจากแถว progress / คะแนนรายขั้น
     *
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $progress
     */
    public static function scorePercent(array $settings, array $progress): float
    {
        if (isset($progress['score_percent']) && $progress['score_percent'] !== '' && $progress['score_percent'] !== null) {
            return round((float) $progress['score_percent'], 2);
        }
        $scores = [
            'pretest' => (float) ($progress['pretest'] ?? $progress['pretest_percent'] ?? 0),
            'content' => (float) ($progress['content'] ?? $progress['content_percent'] ?? 0),
            'task' => (float) ($progress['task'] ?? $progress['task_percent'] ?? 0),
            'posttest' => (float) ($progress['posttest'] ?? $progress['posttest_percent'] ?? 0),
            'satisfaction' => (float) ($progress['satisfaction'] ?? $progress['satisfaction_percent'] ?? 0),
        ];
        $decision = self::decide($settings, $scores, [
            'pretest_done' => (int) ($progress['pretest_done'] ?? 0),
            'content_done' => (int) ($progress['content_done'] ?? 0),
            'task_done' => (int) ($progress['task_done'] ?? 0),
            'posttest_done' => (int) ($progress['posttest_done'] ?? 0),
            'satisfaction_done' => (int) ($progress['satisfaction_done'] ?? 0),
        ]);

        return (float) $decision['score_percent'];
    }

    /** แผนที่ 1–12 บังคับ แผนที่ 13 เป็นต้นไปเลือกเรียนตามความสนใจ */
    public static function isRequired(array $plan): bool
    {
        if (array_key_exists('is_required', $plan)) {
            return (int) $plan['is_required'] === 1;
        }
        return (int) ($plan['plan_no'] ?? 0) > 0 && (int) $plan['plan_no'] <= 12;
    }

    public static function trackLabel(array $plan): string
    {
        return self::isRequired($plan) ? 'บังคับ' : 'ทั่วไป';
    }

    /**
     * ความก้าวหน้า (overall) = การดำเนินการครบองค์ประกอบ ตามน้ำหนักขั้น
     * คะแนนรวม (score_percent) = คะแนนจริงถ่วงน้ำหนัก ใช้ตัดสินผ่าน/แสดงในเกียรติบัตร
     *
     * @param array<string, mixed> $settings
     * @param array<string, float|int> $scorePercents pretest|content|task|posttest|satisfaction เป็น 0–100 (คะแนน)
     * @param array<string, int> $done คีย์ *_done
     * @param array<string, float|int>|null $completionPercents ร้อยละความครบของแต่ละขั้น (ถ้า null จะใช้จาก done)
     * @return array{overall_percent:float,score_percent:float,pass_percent:float,weight_total:float,is_passed:int,weights:array<string,float>}
     */
    public static function decide(array $settings, array $scorePercents, array $done, ?array $completionPercents = null): array
    {
        $weights = [
            'pretest' => max(0.0, (float) ($settings['weight_pretest'] ?? ModuleLearningService::WEIGHT_PRETEST)),
            'content' => max(0.0, (float) ($settings['weight_content'] ?? ModuleLearningService::WEIGHT_CONTENT)),
            'task' => max(0.0, (float) ($settings['weight_task'] ?? ModuleLearningService::WEIGHT_TASK)),
            'posttest' => max(0.0, (float) ($settings['weight_posttest'] ?? ModuleLearningService::WEIGHT_POSTTEST)),
            'satisfaction' => max(0.0, (float) ($settings['weight_satisfaction'] ?? ModuleLearningService::WEIGHT_SATISFACTION)),
        ];
        $total = array_sum($weights);
        if ($total <= 0) {
            $total = 0.01;
        }

        $completion = $completionPercents ?? [
            'pretest' => (int) ($done['pretest_done'] ?? 0) === 1 ? 100.0 : 0.0,
            'content' => (int) ($done['content_done'] ?? 0) === 1 ? 100.0 : 0.0,
            'task' => (int) ($done['task_done'] ?? 0) === 1 ? 100.0 : 0.0,
            'posttest' => (int) ($done['posttest_done'] ?? 0) === 1 ? 100.0 : 0.0,
            'satisfaction' => (int) ($done['satisfaction_done'] ?? 0) === 1 ? 100.0 : 0.0,
        ];

        // ความก้าวหน้า: ตามการดำเนินการของแต่ละองค์ประกอบ (ไม่ใช้คะแนนสอบ)
        $overall = 0.0;
        foreach ($weights as $key => $weight) {
            if ($weight <= 0) {
                continue;
            }
            $overall += (max(0.0, min(100.0, (float) ($completion[$key] ?? 0))) / 100) * $weight;
        }
        $overall = round($overall / $total * 100, 2);

        // คะแนนรวม: ใช้ตัดสินผ่านแผนและแสดงในเกียรติบัตร
        $scoreOverall = 0.0;
        foreach ($weights as $key => $weight) {
            if ($weight <= 0) {
                continue;
            }
            $scoreOverall += (max(0.0, min(100.0, (float) ($scorePercents[$key] ?? 0))) / 100) * $weight;
        }
        $scoreOverall = round($scoreOverall / $total * 100, 2);

        $passPercent = (float) ($settings['pass_percent'] ?? 80);
        if ($passPercent <= 0) {
            $passPercent = 80.0;
        }

        // เกณฑ์พิมพ์เกียรติบัตร: ครบทุกขั้น + ภาระงาน ≥70% + สอบหลัง ≥70%
        $passed = self::certificateReady($done, $scorePercents, $settings) ? 1 : 0;

        // ผ่านสมบูรณ์ตามเงื่อนไข → ความก้าวหน้า 100%
        if ($passed === 1) {
            $overall = 100.0;
        }

        return [
            'overall_percent' => $overall,
            'score_percent' => $scoreOverall,
            'pass_percent' => $passPercent,
            'weight_total' => round($total, 2),
            'is_passed' => $passed,
            'weights' => $weights,
        ];
    }

    /**
     * เงื่อนไขพิมพ์เกียรติบัตรรายแผน
     * @param array<string, int|float|bool> $done
     * @param array<string, float|int> $percents
     * @param array<string, mixed> $settings
     */
    public static function certificateReady(array $done, array $percents = [], array $settings = []): bool
    {
        // รองรับส่ง progress ทั้งก้อนจาก training_module_progress
        if ($percents === [] && isset($done['pretest_percent'])) {
            $percents = [
                'pretest' => (float) ($done['pretest_percent'] ?? 0),
                'content' => (float) ($done['content_percent'] ?? 0),
                'task' => (float) ($done['task_percent'] ?? 0),
                'posttest' => (float) ($done['posttest_percent'] ?? 0),
                'satisfaction' => (float) ($done['satisfaction_percent'] ?? 0),
            ];
        }

        if ((int) ($settings['require_pretest'] ?? 1) === 1 && (int) ($done['pretest_done'] ?? 0) !== 1) {
            return false;
        }
        if ((int) ($done['content_done'] ?? 0) !== 1) {
            return false;
        }
        if ((int) ($done['task_done'] ?? 0) !== 1) {
            return false;
        }
        if ((float) ($percents['task'] ?? $done['task_percent'] ?? 0) + 0.001 < 70.0) {
            return false;
        }
        if ((int) ($settings['require_pass_posttest'] ?? 1) === 1) {
            if ((int) ($done['posttest_done'] ?? 0) !== 1) {
                return false;
            }
            if ((float) ($percents['posttest'] ?? $done['posttest_percent'] ?? 0) + 0.001 < 70.0) {
                return false;
            }
        }
        if ((int) ($settings['require_satisfaction'] ?? 1) === 1 && (int) ($done['satisfaction_done'] ?? 0) !== 1) {
            return false;
        }
        return true;
    }

    /**
     * ข้อสอบก่อน หลัง และแบบประเมิน: คะแนนต่อข้อคงที่ 1 คะแนน จำนวนข้อคงที่ตามที่ตั้งสุ่มหรือชุดกลาง
     * เนื้อหาและภาระงาน: คะแนนรวมเท่ากับจำนวนรายการบังคับ แล้วแปลงคะแนนนั้นเป็นร้อยละตามน้ำหนักของแผน
     *
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public static function shares(
        array $settings,
        int $drawCount,
        int $bankCount,
        int $requiredContents,
        int $requiredTasks,
        int $satisfactionQuestions
    ): array {
        $weights = [
            'pretest' => max(0.0, (float) ($settings['weight_pretest'] ?? ModuleLearningService::WEIGHT_PRETEST)),
            'content' => max(0.0, (float) ($settings['weight_content'] ?? ModuleLearningService::WEIGHT_CONTENT)),
            'task' => max(0.0, (float) ($settings['weight_task'] ?? ModuleLearningService::WEIGHT_TASK)),
            'posttest' => max(0.0, (float) ($settings['weight_posttest'] ?? ModuleLearningService::WEIGHT_POSTTEST)),
            'satisfaction' => max(0.0, (float) ($settings['weight_satisfaction'] ?? ModuleLearningService::WEIGHT_SATISFACTION)),
        ];
        $drawn = $bankCount > 0 ? min(max(1, $drawCount), $bankCount) : 0;
        $parts = [
            'pretest' => self::piece($weights['pretest'], $drawn, 'ข้อ', true),
            'content' => self::piece($weights['content'], $requiredContents, 'เรื่อง', false),
            'task' => self::piece($weights['task'], $requiredTasks, 'งาน', false),
            'posttest' => self::piece($weights['posttest'], $drawn, 'ข้อ', true),
            'satisfaction' => self::piece($weights['satisfaction'], $satisfactionQuestions, 'ข้อ', true),
        ];
        $parts['total'] = round(array_sum($weights), 2);
        $parts['drawn'] = $drawn;
        $parts['bank'] = $bankCount;
        return $parts;
    }

    /** @return array{weight:float,count:int,each:float,unit:string,points_each:float,points_total:float,fixed:bool} */
    private static function piece(float $weight, int $count, string $unit, bool $fixed): array
    {
        $weight = round($weight, 2);
        $count = max(0, $count);
        return [
            'weight' => $weight,
            'count' => $count,
            'each' => $count > 0 ? round($weight / $count, 2) : 0.0,
            'unit' => $unit,
            'points_each' => 1.0,
            'points_total' => (float) $count,
            'fixed' => $fixed,
        ];
    }
}
