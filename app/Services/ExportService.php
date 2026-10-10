<?php

declare(strict_types=1);

namespace App\Services;

use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class ExportService
{
    public function spreadsheet(string $title, array $headers, array $rows, string $format = 'xlsx'): never
    {
        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle(mb_substr($title, 0, 31));
        $sheet->fromArray(array_merge([$headers], $rows), null, 'A1');
        $safe = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $title) ?: 'export';
        $filename = $safe . '_' . date('Ymd_His');

        activity_log('export', 'reports', 'ส่งออก ' . $title . ' (' . $format . ')');

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            $writer = new Csv($ss);
            $writer->save('php://output');
            exit;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        (new Xlsx($ss))->save('php://output');
        exit;
    }

    public function pdfDocument(string $html, string $filename = 'report.pdf', string $orientation = 'P'): never
    {
        if (!class_exists(Mpdf::class)) {
            throw new \RuntimeException('mPDF ไม่ได้ติดตั้ง');
        }
        $tmp = STORAGE_PATH . '/tmp/mpdf';
        if (!is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => $orientation,
            'default_font' => 'garuda',
            'tempDir' => $tmp,
            'margin_top' => 12,
            'margin_bottom' => 16,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);
        $mpdf->SetHTMLFooter('<div style="font-size:9px;color:#64748b;text-align:center;">TAVIS – วัคซีนจราจร สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม © ' . ((int) date('Y') + 543) . ' | {PAGENO}/{nbpg}</div>');
        $mpdf->WriteHTML($html);
        activity_log('export', 'reports', 'ดาวน์โหลด PDF ' . $filename);
        $mpdf->Output($filename, 'D');
        exit;
    }

    public function wordHtml(string $html, string $filename = 'report.doc'): never
    {
        activity_log('export', 'reports', 'ส่งออก Word ' . $filename);
        header('Content-Type: application/msword; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word">';
        echo '<head><meta charset="utf-8"><title>รายงาน</title></head><body>';
        echo $html;
        echo '</body></html>';
        exit;
    }
}
