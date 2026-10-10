<?php

declare(strict_types=1);

namespace App\Services;

class NotificationService
{
    public function lineNotify(string $message): bool
    {
        $token = env('LINE_NOTIFY_TOKEN', '');
        if (!$token) {
            return false;
        }
        $ch = curl_init('https://notify-api.line.me/api/notify');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"],
            CURLOPT_POSTFIELDS => ['message' => $message],
            CURLOPT_TIMEOUT => 10,
        ]);
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code === 200;
    }

    public function telegram(string $message): bool
    {
        $token = env('TELEGRAM_BOT_TOKEN', '');
        $chatId = env('TELEGRAM_CHAT_ID', '');
        if (!$token || !$chatId) {
            return false;
        }
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ],
            CURLOPT_TIMEOUT => 10,
        ]);
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code === 200;
    }

    public function broadcast(string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        $users = db()->fetchAll("SELECT id FROM users WHERE role IN ('admin','director','manager','officer') AND status = 1");
        foreach ($users as $u) {
            notify((int) $u['id'], $title, $message, $type, $link);
        }
        $this->lineNotify("[{$title}] {$message}");
        $this->telegram("<b>{$title}</b>\n{$message}");
    }
}
