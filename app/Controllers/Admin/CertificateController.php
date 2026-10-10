<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\TrainingService;

class CertificateController extends Controller
{
    private TrainingService $training;

    public function __construct()
    {
        $this->training = new TrainingService();
    }

    public function index(): void
    {
        $this->requirePerm('certificates');
        $this->view('admin/certificates/index', [
            'title' => 'เกียรติบัตรออนไลน์',
            'breadcrumb' => 'หน้าหลัก / เกียรติบัตร',
            'canIssue' => can_perm('certificates', 'issue_cert'),
        ]);
    }

    public function datatable(): void
    {
        $this->requirePerm('certificates');
        $this->json(\App\Services\DataTableService::certificates(dt_params()));
    }

    public function readyDatatable(): void
    {
        $this->requirePerm('certificates', 'issue_cert');
        $this->json(\App\Services\DataTableService::certificatesReady(dt_params()));
    }

    public function issue(string $enrollmentId): void
    {
        $this->requirePerm('certificates', 'issue_cert');
        $cert = $this->training->issueCertificate((int) $enrollmentId, (int) auth()->id());
        if ($cert) {
            activity_log('issue_cert', 'certificates', 'ออกเกียรติบัตร ' . $cert['cert_no']);
            flash('success', 'ออกเกียรติบัตรแล้ว: ' . $cert['cert_no']);
            $this->redirect('/admin/certificates/' . $cert['id']);
        }
        flash('error', 'ยังไม่ผ่านเกณฑ์ หรือออกเกียรติบัตรไม่ได้');
        $this->redirect('/admin/certificates');
    }

    public function show(string $id): void
    {
        $this->requirePerm('certificates');
        $cert = db()->fetch(
            'SELECT c.*, e.enrollment_no, e.attendance_percent
             FROM certificates c
             LEFT JOIN course_enrollments e ON e.id = c.enrollment_id
             WHERE c.id = ?',
            [(int) $id]
        );
        if (!$cert) {
            flash('error', 'ไม่พบเกียรติบัตร');
            $this->redirect('/admin/certificates');
        }
        if (is_learner() && (int) $cert['user_id'] !== (int) auth()->id()) {
            flash('error', 'ไม่มีสิทธิ์ดูเกียรติบัตรนี้');
            $this->redirect('/admin/certificates');
        }
        $this->view('admin/certificates/show', [
            'title' => 'เกียรติบัตร ' . $cert['cert_no'],
            'breadcrumb' => 'หน้าหลัก / เกียรติบัตร',
            'cert' => $cert,
        ]);
    }
}
