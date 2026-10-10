<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\MailService;
use App\Services\MediaStorageService;

class TicketController extends Controller
{
    public function index(): void
    {
        $this->requirePerm('tickets');
        $this->view('admin/tickets/index', [
            'title' => 'ระบบบันทึก',
            'breadcrumb' => 'หน้าหลัก / บันทึก',
        ]);
    }

    public function datatable(): void
    {
        $this->requirePerm('tickets');
        $this->json(\App\Services\DataTableService::tickets(dt_params()));
    }

    public function create(): void
    {
        $this->requirePerm('tickets', 'create');
        $this->view('admin/tickets/form', [
            'title' => 'เปิดบันทึกใหม่',
            'breadcrumb' => 'หน้าหลัก / บันทึก',
        ]);
    }

    public function store(): void
    {
        $this->requirePerm('tickets', 'create');
        $no = 'TK' . date('ymd') . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $id = db()->insert('tickets', [
            'ticket_no' => $no,
            'user_id' => auth()->id(),
            'subject' => trim((string) $this->input('subject', '')),
            'message' => trim((string) $this->input('message', '')),
            'status' => 'open',
            'priority' => (string) $this->input('priority', 'normal'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if (!empty($_FILES['attachment']['name'])) {
            try {
                $saved = (new MediaStorageService())->upload($_FILES['attachment'], 'tickets', [
                    'user_id' => (int) auth()->id(),
                ]);
                db()->insert('ticket_files', [
                    'ticket_id' => $id,
                    'file_path' => $saved['file_path'],
                    'original_name' => $saved['original_name'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
            }
        }
        $this->flash('success', 'เปิดบันทึกแล้ว เลขที่ ' . $no);
        $this->redirect('/admin/tickets/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePerm('tickets');
        $row = db()->fetch(
            "SELECT t.*, CONCAT(u.first_name,' ',u.last_name) AS user_name, u.email
             FROM tickets t JOIN users u ON u.id = t.user_id WHERE t.id = ?",
            [(int) $id]
        );
        if (!$row) {
            $this->redirect('/admin/tickets');
        }
        if (!auth()->hasRole(['admin', 'director']) && (int) $row['user_id'] !== (int) auth()->id()) {
            $this->redirect('/admin/tickets');
        }
        $replies = db()->fetchAll(
            "SELECT r.*, CONCAT(u.first_name,' ',u.last_name) AS user_name
             FROM ticket_replies r JOIN users u ON u.id = r.user_id WHERE r.ticket_id = ? ORDER BY r.id",
            [(int) $id]
        );
        $files = db()->fetchAll('SELECT * FROM ticket_files WHERE ticket_id = ?', [(int) $id]);
        $this->view('admin/tickets/show', [
            'title' => $row['ticket_no'],
            'breadcrumb' => 'หน้าหลัก / บันทึก',
            'row' => $row,
            'replies' => $replies,
            'files' => $files,
        ]);
    }

    public function reply(string $id): void
    {
        $this->requirePerm('tickets', 'edit');
        $isAdmin = auth()->hasRole(['admin', 'director']);
        db()->insert('ticket_replies', [
            'ticket_id' => (int) $id,
            'user_id' => auth()->id(),
            'message' => trim((string) $this->input('message', '')),
            'is_admin' => $isAdmin ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        db()->update('tickets', [
            'status' => $isAdmin ? 'replied' : 'in_progress',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $id]);
        $ticket = db()->fetch(
            'SELECT t.*, u.email, u.first_name FROM tickets t JOIN users u ON u.id = t.user_id WHERE t.id = ?',
            [(int) $id]
        );
        if ($isAdmin && !empty($ticket['email'])) {
            (new MailService())->sendTicketReply($ticket['email'], $ticket['ticket_no'], $ticket['subject']);
        }
        $this->flash('success', 'บันทึกการตอบกลับแล้ว');
        $this->redirect('/admin/tickets/' . $id);
    }
}
