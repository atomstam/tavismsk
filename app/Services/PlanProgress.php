<?php

declare(strict_types=1);

namespace App\Services;

/**
 * เกณฑ์ผ่านของทุกแผน: รวมร้อยละความก้าวหน้าตามน้ำหนัก แล้วเทียบกับเกณฑ์ผ่านของแผนนั้น
 */
final class PlanProgress
{
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
     * @param array<string, mixed> $settings
     * @param array<string, float|int> $percents pretest|content|task|posttest|satisfaction เป็น 0–100
     * @param array<string, int> $done คีย์ *_done
     * @return array{overall_percent:float,pass_percent:float,weight_total:float,is_passed:int,weights:array<string,float>}
     */
    public static function decide(array $settings, array $percents, array $done): array
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
        $overall = 0.0;
        foreach ($weights as $key => $weight) {
            if ($weight <= 0) {
                continue;
            }
            $overall += ((float) ($percents[$key] ?? 0) / 100) * $weight;
        }
        $overall = round($overall / $total * 100, 2);
        $passPercent = (float) ($settings['pass_percent'] ?? 80);
        if ($passPercent <= 0) {
            $passPercent = 80.0;
        }
        $gates = (int) ($done['content_done'] ?? 0) === 1
            && (int) ($done['task_done'] ?? 0) === 1;
        if ((int) ($settings['require_pretest'] ?? 1) === 1 && (int) ($done['pretest_done'] ?? 0) !== 1) {
            $gates = false;
        }
        if ((int) ($settings['require_pass_posttest'] ?? 1) === 1 && (int) ($done['posttest_done'] ?? 0) !== 1) {
            $gates = false;
        }
        if ((int) ($settings['require_satisfaction'] ?? 1) === 1 && (int) ($done['satisfaction_done'] ?? 0) !== 1) {
            $gates = false;
        }
        $passed = $overall + 0.001 >= $passPercent && $gates;

        return [
            'overall_percent' => $overall,
            'pass_percent' => $passPercent,
            'weight_total' => round($total, 2),
            'is_passed' => $passed ? 1 : 0,
            'weights' => $weights,
        ];
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
