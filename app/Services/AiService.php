<?php

declare(strict_types=1);

namespace App\Services;

/**
 * OpenAI Chat Completions client สำหรับ TAVIS AI ผู้ช่วย
 */
class AiService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;
    private int $timeout;
    private int $maxTokens;

    public function __construct(?array $config = null)
    {
        $config = $config ?? (is_array(config('ai')) ? config('ai') : []);
        $this->apiKey = trim((string) ($config['api_key'] ?? env('OPENAI_API_KEY', '')));
        $this->model = trim((string) ($config['model'] ?? env('OPENAI_MODEL', 'gpt-4o-mini'))) ?: 'gpt-4o-mini';
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? 'https://api.openai.com/v1'), '/');
        $this->timeout = max(5, (int) ($config['timeout'] ?? 45));
        $this->maxTokens = max(256, (int) ($config['max_tokens'] ?? 1200));
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function model(): string
    {
        return $this->model;
    }

    /**
     * @param list<array{role:string,content:string}> $messages
     * @return array{ok:bool,content?:string,error?:string,usage?:array,model?:string,raw?:mixed}
     */
    public function chat(array $messages, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'ok' => false,
                'error' => 'ยังไม่ได้ตั้งค่า OPENAI_API_KEY ในไฟล์ .env',
            ];
        }

        $payload = [
            'model' => (string) ($options['model'] ?? $this->model),
            'messages' => array_values($messages),
            'temperature' => (float) ($options['temperature'] ?? 0.4),
            'max_tokens' => (int) ($options['max_tokens'] ?? $this->maxTokens),
        ];

        $response = $this->request('/chat/completions', $payload);
        if (!$response['ok']) {
            return $response;
        }

        $data = $response['data'] ?? [];
        $content = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
        if ($content === '') {
            return [
                'ok' => false,
                'error' => 'ไม่ได้รับข้อความตอบกลับจากโมเดล',
                'raw' => $data,
            ];
        }

        return [
            'ok' => true,
            'content' => $content,
            'model' => (string) ($data['model'] ?? $payload['model']),
            'usage' => is_array($data['usage'] ?? null) ? $data['usage'] : [],
        ];
    }

    /**
     * สนทนาผู้ช่วยใช้งานระบบ / หลักสูตร
     *
     * @return array{ok:bool,content?:string,error?:string,model?:string}
     */
    public function assistant(string $userMessage, string $mode = 'chat'): array
    {
        $userMessage = trim($userMessage);
        if ($userMessage === '') {
            return ['ok' => false, 'error' => 'กรุณากรอกข้อความ'];
        }
        if (mb_strlen($userMessage) > 8000) {
            return ['ok' => false, 'error' => 'ข้อความยาวเกินไป (สูงสุด 8,000 ตัวอักษร)'];
        }

        $system = $this->systemPrompt($mode);
        $context = $this->curriculumContext($userMessage);

        $messages = [
            ['role' => 'system', 'content' => $system],
        ];
        if ($context !== '') {
            $messages[] = [
                'role' => 'system',
                'content' => "ข้อมูลหลักสูตรที่เกี่ยวข้อง:\n" . $context,
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $temperature = match ($mode) {
            'summarize', 'search', 'dashboard' => 0.2,
            'news' => 0.6,
            default => 0.4,
        };

        return $this->chat($messages, ['temperature' => $temperature]);
    }

    /**
     * ค้นหาแผนการเรียนรู้ในระบบ แล้วให้ AI อธิบายเพิ่ม
     *
     * @return array{ok:bool,content?:string,error?:string,matches?:list<array>,model?:string}
     */
    public function search(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return ['ok' => false, 'error' => 'กรุณาระบุคำค้น'];
        }

        $matches = $this->matchCurriculum($query, 5);
        $lines = [];
        foreach ($matches as $m) {
            $lines[] = sprintf(
                '- แผนที่ %d: %s (%s)',
                (int) $m['plan_no'],
                (string) $m['title'],
                (string) ($m['topic_group'] ?? '')
            );
        }
        $matchText = $lines !== [] ? implode("\n", $lines) : 'ไม่พบแผนที่ตรงคำค้นโดยตรง';

        $prompt = "คำค้นของผู้ใช้: {$query}\n\nผลการค้นหาในระบบ:\n{$matchText}\n\n"
            . "สรุปเป็นภาษาไทยสั้นๆ ว่าควรดูแผนไหน และประเด็นสำคัญคืออะไร "
            . "ถ้าเกี่ยวข้องกับกฎหมาย/ความปลอดภัย ให้เตือนว่าข้อมูลเพื่อการศึกษา ไม่ใช่คำปรึกษาทางกฎหมาย";

        $ai = $this->assistant($prompt, 'search');
        if (!$ai['ok']) {
            // ยังคืนผลการค้นหาในระบบได้แม้ไม่มี API key
            if (!$this->isConfigured()) {
                return [
                    'ok' => true,
                    'content' => $matchText . "\n\n(ยังไม่ได้ตั้ง OPENAI_API_KEY — แสดงเฉพาะผลการค้นหาในระบบ)",
                    'matches' => $matches,
                    'offline' => true,
                ];
            }
            return $ai;
        }

        return [
            'ok' => true,
            'content' => (string) $ai['content'],
            'matches' => $matches,
            'model' => $ai['model'] ?? $this->model,
            'usage' => $ai['usage'] ?? [],
        ];
    }

    /**
     * @return list<array{plan_no:int,title:string,topic_group?:string,score:int}>
     */
    public function matchCurriculum(string $query, int $limit = 5): array
    {
        $plans = function_exists('curriculum_plans') ? curriculum_plans() : [];
        $q = mb_strtolower(trim($query));
        $tokens = preg_split('/\s+/u', $q) ?: [];
        $scored = [];

        foreach ($plans as $no => $plan) {
            $hay = mb_strtolower(implode(' ', [
                (string) ($plan['title'] ?? ''),
                (string) ($plan['topic_group'] ?? ''),
                (string) ($plan['instructor_label'] ?? ''),
                implode(' ', $plan['knowledge_sheets'] ?? []),
                implode(' ', $plan['worksheets'] ?? []),
            ]));
            $score = 0;
            if ($q !== '' && str_contains($hay, $q)) {
                $score += 10;
            }
            foreach ($tokens as $t) {
                if ($t !== '' && mb_strlen($t) >= 2 && str_contains($hay, $t)) {
                    $score += 2;
                }
            }
            if ($score > 0) {
                $scored[] = [
                    'plan_no' => (int) $no,
                    'title' => (string) ($plan['title'] ?? ''),
                    'topic_group' => (string) ($plan['topic_group'] ?? ''),
                    'score' => $score,
                ];
            }
        }

        usort($scored, static fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($scored, 0, max(1, $limit));
    }

    private function systemPrompt(string $mode): string
    {
        $name = function_exists('app_brand') ? app_brand('name') : 'TAVIS – วัคซีนจราจร';
        $full = function_exists('app_brand') ? app_brand('full_name') : 'วัคซีนจราจร เพื่อพัฒนาความรู้และทักษะด้านจราจรและความปลอดภัยทางถนน';
        $en = function_exists('app_brand') ? app_brand('name_en') : 'Traffic Awareness Vaccine Intelligent System';
        $org = (string) (function_exists('setting') ? setting('org_name') : 'สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม');
        $base = <<<TXT
คุณเป็นผู้ช่วย AI ของระบบ {$name} ({$en})
{$full}
หน่วยงาน: {$org}

หน้าที่:
- ตอบเป็นภาษาไทย สุภาพ กระชับ เป็นมืออาชีพ
- อ้างอิงหลักสูตร 14 ชั่วโมง / TSY modules / SHELL / DECIDE / AMISAFE เมื่อเกี่ยวข้อง
- ช่วยใช้งานระบบ (แผนการเรียนรู้ ผู้เข้ารับการพัฒนา รายงาน แบบฟอร์ม)
- ไม่แต่งสถิติเท็จ และไม่ให้คำแนะนำที่อันตราย
- หากไม่แน่ใจ ให้บอกขอบเขตและแนะนำแหล่งข้อมูลราชการ (กรมการขนส่งทางบก / สธ.)
TXT;

        return match ($mode) {
            'summarize' => $base . "\nโหมดปัจจุบัน: สรุปข้อความเป็นประเด็นสั้นๆ พร้อมหัวข้อย่อย",
            'news' => $base . "\nโหมดปัจจุบัน: ช่วยร่างข่าว/ประชาสัมพันธ์กิจกรรมการอบรม น้ำเสียงทางการเหมาะกับหน่วยงาน",
            'search' => $base . "\nโหมดปัจจุบัน: อธิบายผลการค้นหาหลักสูตรและแนะนำแผนที่เกี่ยวข้อง",
            'dashboard' => $base . "\nโหมดปัจจุบัน: วิเคราะห์ตัวเลขแดชบอร์ดสั้นๆ ชี้จุดที่ควรติดตาม",
            default => $base . "\nโหมดปัจจุบัน: ตอบคำถามผู้ใช้ระบบและเนื้อหาหลักสูตร",
        };
    }

    private function curriculumContext(string $userMessage): string
    {
        $matches = $this->matchCurriculum($userMessage, 3);
        if ($matches === []) {
            // สรุปภาพรวมหลักสูตรสั้นๆ
            $plans = function_exists('curriculum_plans') ? curriculum_plans() : [];
            $course = function_exists('app_brand') ? app_brand('full_name') : 'วัคซีนจราจร';
            $lines = ["หลักสูตร{$course} รวม 14 ชั่วโมง"];
            foreach (array_slice($plans, 0, 5, true) as $no => $p) {
                $lines[] = "แผน {$no}: " . ($p['title'] ?? '');
            }
            $lines[] = '... (รวม 14 แผน)';
            return implode("\n", $lines);
        }

        $plans = function_exists('curriculum_plans') ? curriculum_plans() : [];
        $blocks = [];
        foreach ($matches as $m) {
            $p = $plans[$m['plan_no']] ?? null;
            if (!$p) {
                continue;
            }
            $blocks[] = sprintf(
                "แผนที่ %d — %s\nกลุ่ม: %s\nผู้สอน: %s\nใบความรู้: %s\nใบงาน: %s",
                (int) $p['plan_no'],
                (string) $p['title'],
                (string) ($p['topic_group'] ?? '-'),
                (string) ($p['instructor_label'] ?? '-'),
                implode('; ', $p['knowledge_sheets'] ?? []),
                implode('; ', $p['worksheets'] ?? [])
            );
        }
        return implode("\n\n", $blocks);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{ok:bool,data?:array,error?:string,http_status?:int}
     */
    private function request(string $path, array $payload): array
    {
        $url = $this->baseUrl . $path;
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return ['ok' => false, 'error' => 'ไม่สามารถเข้ารหัส JSON ได้'];
        }

        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'เซิร์ฟเวอร์ไม่รองรับ cURL'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            error_log('AiService cURL error: ' . $error);
            return ['ok' => false, 'error' => 'เชื่อมต่อ OpenAI ไม่สำเร็จ: ' . $error, 'http_status' => $status];
        }

        $data = json_decode((string) $body, true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'ตอบกลับจาก OpenAI ไม่ใช่ JSON', 'http_status' => $status];
        }

        if ($status < 200 || $status >= 300) {
            $msg = (string) ($data['error']['message'] ?? ('HTTP ' . $status));
            error_log('AiService API error: ' . $msg);
            return ['ok' => false, 'error' => $msg, 'http_status' => $status, 'data' => $data];
        }

        return ['ok' => true, 'data' => $data, 'http_status' => $status];
    }
}
