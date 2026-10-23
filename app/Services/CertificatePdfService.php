<?php

declare(strict_types=1);

namespace App\Services;

use Mpdf\Mpdf;

/**
 * สร้างเกียรติบัตร / วุฒิบัตร PDF ขนาด A4 แนวนอน
 */
class CertificatePdfService
{
    /**
     * @param array<string, mixed> $cert
     * @param array<string, mixed> $plan
     * @param array{diploma?:bool,plans_count?:int,hours?:float} $opts
     */
    /**
     * @param array<string, mixed> $cert
     * @param array<string, mixed> $plan
     * @param array{diploma?:bool,plans_count?:int,hours?:float} $opts
     * @param 'D'|'I' $dest D=ดาวน์โหลด I=แสดงในเบราว์เซอร์
     */
    public function download(array $cert, array $plan = [], array $opts = [], string $dest = 'D'): never
    {
        $this->output($cert, $plan, $opts, $dest === 'I' ? 'I' : 'D');
    }

    /**
     * @param array<string, mixed> $cert
     * @param array<string, mixed> $plan
     * @param array{diploma?:bool,plans_count?:int,hours?:float} $opts
     * @param 'D'|'I' $dest
     */
    public function output(array $cert, array $plan = [], array $opts = [], string $dest = 'D'): never
    {
        if (!class_exists(Mpdf::class)) {
            throw new \RuntimeException('mPDF ไม่ได้ติดตั้ง');
        }

        $cfg = config('app', 'certificate');
        $cfg = is_array($cfg) ? $cfg : [];
        $training = config('app', 'training');
        $training = is_array($training) ? $training : [];

        $isDiploma = !empty($opts['diploma']) || empty($cert['plan_id']);
        $plansCount = (int) ($opts['plans_count'] ?? $cert['final_score'] ?? 0);
        $hoursVal = isset($opts['hours']) ? (float) $opts['hours'] : (float) ($cert['hours'] ?? 0);

        $directorName = (string) setting(
            'director_name',
            $cfg['director_name'] ?? 'นายอดุลย์ ภูปลื้ม'
        );
        $directorPosition = (string) setting(
            'director_position',
            $cfg['director_position'] ?? 'ผู้อำนวยการสำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม'
        );
        $orgName = (string) setting('org_name', $cfg['org_name'] ?? 'สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม');

        $courseTitle = trim((string) ($cert['title'] ?? ''));
        if (!$isDiploma && $courseTitle === '' && $plan !== []) {
            $courseTitle = 'แผนที่ ' . (int) ($plan['plan_no'] ?? 0) . ' ' . (string) ($plan['title'] ?? '');
        }
        if ($courseTitle === '') {
            $courseTitle = (string) ($training['course_title'] ?? app_brand('full_name'));
        }

        $logoSrc = $this->resolveImageSrc(
            (string) setting('logo', ''),
            (string) ($cfg['logo_path'] ?? 'assets/img/logo.svg')
        );
        $signSrc = $this->resolveImageSrc(
            (string) setting('director_signature', ''),
            (string) ($cfg['signature_path'] ?? 'assets/img/signature-director.png')
        );

        $esc = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $recipient = $esc((string) ($cert['recipient_name'] ?? ''));
        $hours = number_format($hoursVal, 1);
        $issued = $esc(thai_date($cert['issued_at'] ?? date('Y-m-d H:i:s')));
        $certNo = $esc((string) ($cert['cert_no'] ?? ''));
        $verify = $esc(url('/certificate/verify?code=' . ($cert['verify_code'] ?? '')));
        $title = $esc($isDiploma ? 'วุฒิบัตร' : (string) ($cfg['title'] ?? 'เกียรติบัตร'));
        $subtitle = $esc($isDiploma
            ? 'หนังสือรับรองการผ่านการพัฒนาหลักสูตร'
            : (string) ($cfg['subtitle'] ?? 'หนังสือรับรองการผ่านการอบรม'));
        $org = $esc($orgName);
        $dirName = $esc($directorName);
        $dirPos = $esc($directorPosition);
        $course = $esc($courseTitle);
        $brandShort = $esc(app_brand('short_name'));
        $brandEn = $esc(app_brand('name_en'));
        $accent = $isDiploma ? '#9a3412' : '#1d4ed8';
        $border = $isDiploma ? '#c2410c' : '#1d4ed8';

        $detailLine = $isDiploma
            ? 'ได้ผ่านการพัฒนาตามหลักสูตรบังคับครบถ้วน'
            : 'ได้ผ่านการอบรมตามหลักสูตร';
        $metaExtra = $isDiploma
            ? '<div class="meta">ผ่านการพัฒนาจำนวน <strong>' . max(0, $plansCount) . '</strong> หลักสูตร รวม <strong>' . $hours . '</strong> ชั่วโมง</div>'
            : '<div class="meta">จำนวน ' . $hours . ' ชั่วโมง</div>';

        $logoHtml = $logoSrc !== ''
            ? '<img src="' . $esc($logoSrc) . '" style="height:72px;width:auto;" alt="logo">'
            : '<div style="font-size:28px;font-weight:700;color:' . $accent . ';">' . $brandShort . '</div>';
        $signHtml = $signSrc !== ''
            ? '<img src="' . $esc($signSrc) . '" style="height:56px;width:auto;margin-bottom:4px;" alt="signature"><br>'
            : '<div style="height:48px;"></div>';

        $html = <<<HTML
<style>
  body { font-family: garuda, sans-serif; color: #0f172a; }
  .sheet {
    border: 6px double {$border};
    border-radius: 10px;
    padding: 28px 40px;
    background: #ffffff;
    min-height: 170mm;
  }
  .org { font-size: 15px; color: #1e3a8a; font-weight: 700; margin: 6px 0 0; }
  .brand { font-size: 12px; color: #64748b; letter-spacing: .12em; margin: 2px 0 0; }
  .title { font-size: 36px; color: {$accent}; font-weight: 700; margin: 18px 0 4px; }
  .sub { font-size: 14px; color: #475569; margin: 0 0 16px; }
  .line { font-size: 15px; margin: 6px 0; }
  .name { font-size: 28px; color: #0f172a; font-weight: 700; margin: 10px 0 14px; }
  .course { font-size: 16px; font-weight: 700; color: #1e40af; margin: 4px 0 10px; }
  .meta { font-size: 14px; color: #334155; margin: 4px 0; }
  .foot { font-size: 11px; color: #64748b; margin-top: 10px; }
</style>
<div class="sheet">
  <table width="100%" style="border:0;border-collapse:collapse;">
    <tr>
      <td width="18%" style="border:0;text-align:left;vertical-align:top;">{$logoHtml}</td>
      <td style="border:0;text-align:center;vertical-align:middle;">
        <div class="org">{$org}</div>
        <div class="brand">{$brandShort} · {$brandEn}</div>
      </td>
      <td width="18%" style="border:0;"></td>
    </tr>
  </table>
  <div style="text-align:center;">
    <div class="title">{$title}</div>
    <div class="sub">{$subtitle}</div>
    <div class="line">ขอรับรองว่า</div>
    <div class="name">{$recipient}</div>
    <div class="line">{$detailLine}</div>
    <div class="course">{$course}</div>
    {$metaExtra}
    <div class="meta">ออกให้ ณ วันที่ {$issued}</div>
  </div>
  <table width="100%" style="border:0;border-collapse:collapse;margin-top:28px;">
    <tr>
      <td width="33%" style="border:0;text-align:center;vertical-align:bottom;font-size:12px;color:#64748b;">
        เลขที่ {$certNo}
      </td>
      <td width="34%" style="border:0;text-align:center;vertical-align:bottom;">
        {$signHtml}
        <div style="border-top:1px solid #94a3b8;width:220px;margin:0 auto;padding-top:6px;font-size:13px;">
          ({$dirName})<br>
          {$dirPos}
        </div>
      </td>
      <td width="33%" style="border:0;text-align:center;vertical-align:bottom;font-size:12px;color:#64748b;">
        {$org}
      </td>
    </tr>
  </table>
  <div class="foot" style="text-align:center;">ตรวจสอบความถูกต้อง: {$verify}</div>
</div>
HTML;

        $tmp = $this->ensureMpdfTempDir();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'orientation' => 'L',
            'default_font' => 'garuda',
            'tempDir' => $tmp,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);
        $mpdf->WriteHTML($html);
        $safeNo = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', (string) ($cert['cert_no'] ?? 'cert')) ?: 'cert';
        $prefix = $isDiploma ? 'diploma_' : 'certificate_';
        $filename = $prefix . $safeNo . '.pdf';
        $mode = $dest === 'I' ? 'I' : 'D';
        activity_log(
            'export',
            'certificates',
            ($mode === 'I' ? 'ดู' : 'ดาวน์โหลด') . ($isDiploma ? 'วุฒิบัตร' : 'เกียรติบัตร') . ' PDF ' . $safeNo
        );
        $mpdf->Output($filename, $mode);
        exit;
    }

    private function resolveImageSrc(string $settingPath, string $fallbackRel): string
    {
        $candidates = [];
        if ($settingPath !== '') {
            $p = ltrim(str_replace('\\', '/', $settingPath), '/');
            if (!str_starts_with($p, 'uploads/')) {
                $candidates[] = PUBLIC_PATH . '/uploads/' . $p;
            }
            $candidates[] = PUBLIC_PATH . '/' . $p;
            $candidates[] = BASE_PATH . '/' . $p;
        }
        $fallbackRel = ltrim(str_replace('\\', '/', $fallbackRel), '/');
        $candidates[] = PUBLIC_PATH . '/' . $fallbackRel;
        $candidates[] = BASE_PATH . '/public/' . $fallbackRel;

        foreach ($candidates as $file) {
            if (is_string($file) && is_file($file)) {
                return $file;
            }
        }
        return '';
    }

    /** mPDF จะสร้างโฟลเดอร์ย่อย {tempDir}/mpdf — ต้องเขียนได้ทั้งคู่ */
    private function ensureMpdfTempDir(): string
    {
        $candidates = [
            STORAGE_PATH . '/tmp/mpdf',
            rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '/tavis-mpdf',
        ];
        foreach ($candidates as $base) {
            $nested = $base . '/mpdf';
            foreach ([$base, $nested] as $dir) {
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
                if (is_dir($dir)) {
                    @chmod($dir, 0777);
                }
            }
            if (is_dir($nested) && is_writable($nested)) {
                return $base;
            }
        }
        throw new \RuntimeException('ไม่สามารถสร้างโฟลเดอร์ชั่วคราวสำหรับสร้าง PDF ได้');
    }
}
