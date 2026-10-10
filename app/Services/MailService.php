<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    public function isConfigured(): bool
    {
        $config = config('mail');
        $user = trim((string) ($config['username'] ?? ''));
        $pass = (string) ($config['password'] ?? '');
        $host = trim((string) ($config['host'] ?? ''));

        return $host !== '' && $user !== '' && $pass !== '';
    }

    public function send(string $to, string $subject, string $body, ?string $toName = null): bool
    {
        $config = config('mail');

        if (!$this->isConfigured()) {
            return false;
        }

        if (!class_exists(PHPMailer::class)) {
            return $this->fallbackSend($to, $subject, $body);
        }

        try {
            $mail = new PHPMailer(true);
            $mail->Timeout = 8;
            $mail->SMTPKeepAlive = false;
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->Port = $config['port'];
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($config['from']['address'], $config['from']['name']);
            $mail->addAddress($to, $toName ?? '');
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log('Mail error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendOtp(string $email, string $otp, string $name = ''): bool
    {
        $body = $this->template('รหัส OTP ของคุณ', "
            <p>สวัสดีคุณ {$name}</p>
            <p>รหัส OTP สำหรับยืนยันตัวตนคือ:</p>
            <h2 style=\"letter-spacing:8px;color:#2563eb;\">{$otp}</h2>
            <p>รหัสหมดอายุใน 10 นาที กรุณาอย่าเปิดเผยรหัสนี้แก่ผู้อื่น</p>
        ");
        return $this->send($email, 'รหัส OTP - TAVIS', $body, $name);
    }

    public function sendPasswordReset(string $email, string $token, string $name = ''): bool
    {
        $link = url('/reset-password?token=' . urlencode($token));
        $body = $this->template('รีเซ็ตรหัสผ่าน', "
            <p>สวัสดีคุณ {$name}</p>
            <p>คลิกลิงก์ด้านล่างเพื่อตั้งรหัสผ่านใหม่:</p>
            <p><a href=\"{$link}\" style=\"background:#2563eb;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;\">รีเซ็ตรหัสผ่าน</a></p>
            <p>ลิงก์หมดอายุใน 1 ชั่วโมง หากคุณไม่ได้ร้องขอ กรุณาเพิกเฉยอีเมลนี้</p>
        ");
        return $this->send($email, 'รีเซ็ตรหัสผ่าน - TAVIS', $body, $name);
    }

    public function sendTicketReply(string $email, string $ticketNo, string $subject): bool
    {
        $body = $this->template('ตอบกลับบันทึก', "
            <p>เลขที่: <strong>{$ticketNo}</strong></p>
            <p>หัวข้อ: {$subject}</p>
            <p>ผู้ดูแลระบบได้ตอบกลับแล้ว กรุณาเข้าสู่ระบบเพื่อดูรายละเอียด</p>
        ");
        return $this->send($email, "ตอบกลับบันทึก {$ticketNo} - TAVIS", $body);
    }

    private function template(string $title, string $content): string
    {
        $org = setting('org_name', 'สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม');
        $sys = setting('site_name', 'TAVIS – ระบบวัคซีนจราจร');
        return "<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head>
        <body style=\"font-family:Sarabun,sans-serif;background:#f1f5f9;padding:20px;\">
        <div style=\"max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.08);\">
          <div style=\"background:linear-gradient(135deg,#1d4ed8,#7c3aed);color:#fff;padding:24px;text-align:center;\">
            <h1 style=\"margin:0;font-size:20px;\">{$sys}</h1>
            <p style=\"margin:4px 0 0;opacity:.9;font-size:13px;\">{$org}</p>
          </div>
          <div style=\"padding:28px;\">
            <h2 style=\"color:#1e3a5f;font-size:18px;\">{$title}</h2>
            {$content}
          </div>
          <div style=\"background:#f8fafc;padding:16px;text-align:center;font-size:12px;color:#64748b;\">
            © " . ((int) date('Y') + 543) . " {$org}
          </div>
        </div></body></html>";
    }

    private function fallbackSend(string $to, string $subject, string $body): bool
    {
        $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=utf-8\r\n";
        $from = config('mail', 'from.address', 'noreply@cyp.ac.th');
        $headers .= "From: {$from}\r\n";
        return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
    }
}
