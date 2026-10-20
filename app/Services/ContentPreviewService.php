<?php

declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Html;

/**
 * เปิดเนื้อหา LMS ในหน้าของระบบ ตามประเภทไฟล์
 */
class ContentPreviewService
{
    /** @return array{kind:string,frame_src:string,html:string,note:string} */
    public function present(array $content): array
    {
        $type = (string) ($content['content_type'] ?? 'text');
        $url = trim((string) ($content['external_url'] ?? ''));
        $body = trim((string) ($content['body_text'] ?? ''));
        $file = trim((string) ($content['file_path'] ?? ''));

        $detail = $this->detailHtml($content);

        if ($type === 'youtube') {
            $id = $this->youtubeId($url);
            return $this->frame(
                $id ? 'https://www.youtube-nocookie.com/embed/' . rawurlencode($id) : '',
                'video',
                $id ? '' : 'ไม่พบรหัสวิดีโอ YouTube',
                $detail
            );
        }

        if ($type === 'gdrive' || $type === 'link') {
            return [
                'kind' => 'text',
                'frame_src' => '',
                'html' => $detail,
                'note' => 'ประเภทนี้เปิดที่ลิงก์ภายนอก',
            ];
        }

        if ($file !== '' && in_array($type, ['pdf', 'word', 'excel'], true)) {
            $fileView = $this->presentFile($type, $file);
            $fileView['html'] = $detail . $fileView['html'];
            return $fileView;
        }

        return [
            'kind' => 'text',
            'frame_src' => '',
            'html' => $detail,
            'note' => $detail === '' ? 'ยังไม่มีเนื้อหาให้แสดง' : '',
        ];
    }

    private function detailHtml(array $content): string
    {
        $parts = [];
        $description = trim((string) ($content['description'] ?? ''));
        $body = trim((string) ($content['body_text'] ?? ''));
        if ($description !== '') {
            $parts[] = '<p class="content-lead">' . nl2br(e($description)) . '</p>';
        }
        if ($body !== '') {
            $parts[] = '<div class="content-copy">' . nl2br(e($body)) . '</div>';
        }
        return implode('', $parts);
    }

    /** @return array{kind:string,frame_src:string,html:string,note:string} */
    private function presentFile(string $type, string $path): array
    {
        if ($type === 'pdf') {
            return $this->frame($this->fileUrl($path), 'pdf');
        }

        $local = $this->materialize($path);
        if ($local === null) {
            return $this->frame('', 'text', 'เปิดไฟล์ไม่สำเร็จ');
        }
        try {
            if ($type === 'excel') {
                return $this->sheet($local);
            }
            return $this->document($local);
        } catch (\Throwable $e) {
            return [
                'kind' => 'text',
                'frame_src' => '',
                'html' => '',
                'note' => 'อ่านไฟล์ไม่สำเร็จ',
            ];
        } finally {
            if ($local !== $this->localPath($path)) {
                @unlink($local);
            }
        }
    }

    /** @return array{kind:string,frame_src:string,html:string,note:string} */
    private function sheet(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = (int) $sheet->getHighestDataRow();
        if ($highestRow > 300) {
            $sheet->removeRow(301, $highestRow - 300);
        }
        $writer = new Html($spreadsheet);
        $writer->setSheetIndex(0);
        $html = $writer->generateSheetData();
        $note = $highestRow >= 300 ? 'แสดง 300 แถวแรก' : '';
        unset($highestCol);
        return [
            'kind' => 'sheet',
            'frame_src' => '',
            'html' => $html,
            'note' => $note,
        ];
    }

    /** @return array{kind:string,frame_src:string,html:string,note:string} */
    private function document(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [
                'kind' => 'text',
                'frame_src' => '',
                'html' => '',
                'note' => 'เปิดไฟล์ Word นี้ในหน้าเว็บไม่ได้ ใช้ไฟล์ .docx',
            ];
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if (!is_string($xml) || $xml === '') {
            return [
                'kind' => 'text',
                'frame_src' => '',
                'html' => '',
                'note' => 'ไม่พบเนื้อหาในไฟล์ Word',
            ];
        }
        $xml = str_replace(['</w:p>', '</w:tr>'], "\n", $xml);
        $xml = preg_replace('/<w:tab[^>]*\/>/', "\t", $xml) ?? $xml;
        $text = trim(html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return [
            'kind' => 'doc',
            'frame_src' => '',
            'html' => $text !== '' ? nl2br(e($text)) : '',
            'note' => $text === '' ? 'ไฟล์ Word ว่าง' : '',
        ];
    }

    private function fileUrl(string $path): string
    {
        if (media_is_gdrive($path)) {
            return gdrive_media_url(gdrive_file_id($path));
        }
        return upload_url($path);
    }

    private function materialize(string $path): ?string
    {
        if (media_is_gdrive($path)) {
            try {
                $file = (new GoogleDriveService())->downloadFile(gdrive_file_id($path));
            } catch (\Throwable $e) {
                return null;
            }
            $tmp = rtrim((string) STORAGE_PATH, '/') . '/tmp/lms_' . bin2hex(random_bytes(6)) . '.bin';
            if (!is_dir(dirname($tmp))) {
                @mkdir(dirname($tmp), 0755, true);
            }
            file_put_contents($tmp, $file['content']);
            return $tmp;
        }
        $local = $this->localPath($path);
        return is_file($local) ? $local : null;
    }

    private function localPath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (!str_starts_with($path, 'uploads/')) {
            $path = 'uploads/' . $path;
        }
        return rtrim((string) PUBLIC_PATH, '/') . '/' . $path;
    }

    private function youtubeId(string $url): ?string
    {
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    /** @return array{kind:string,frame_src:string,html:string,note:string} */
    private function frame(string $src, string $kind, string $note = '', string $html = ''): array
    {
        return [
            'kind' => $src !== '' ? $kind : 'text',
            'frame_src' => $src,
            'html' => $html,
            'note' => $src === '' ? ($note !== '' ? $note : 'ไม่มีสื่อให้แสดง') : $note,
        ];
    }
}
