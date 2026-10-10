<?php

declare(strict_types=1);

/**
 * การตั้งค่า AI (OpenAI)
 */

return [
    'api_key' => env('OPENAI_API_KEY', ''),
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    'timeout' => (int) env('OPENAI_TIMEOUT', 45),
    'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 1200),
];
