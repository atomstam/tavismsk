<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\CertificatePdfService;
use App\Services\EnrollmentManageService;
use App\Services\ModuleLearningService;
use App\Services\PlanOwnerService;
use App\Services\PlanProgress;
use App\Services\TrainingService;

class CertificateController extends Controller
{
    private TrainingService $training;
    private ModuleLearningService $lms;
    private EnrollmentManageService $manage;

    private const STAGE_LABELS = [
        'pretest' => 'ทดสอบก่อน',
        'content' => 'เนื้อหา',
        'task' => 'ภาระงาน',
        'posttest' => 'สอบหลัง',
        'satisfaction' => 'ความพึงพอใจ',
        'completed' => 'ครบแล้ว',
    ];

    public function __construct()
    {
        $this->training = new TrainingService();
        $this->lms = new ModuleLearningService();
        $this->manage = new EnrollmentManageService();
    }

    public function index(): void
    {
        $this->requirePerm('certificates');
        if (is_trainer_like()) {
            $this->manageIndex();
            return;
        }
        $this->learnerIndex();
    }

    /** มุมมองผู้เรียน — เกียรติบัตรของฉัน */
    private function learnerIndex(): void
    {
        $userId = (int) auth()->id();
        $modules = $this->lms->moduleOverviewForUser($userId);
        $requiredRows = [];
        $generalRows = [];
        $enrollId = 0;
        foreach ($modules as $m) {
            $plan = $m['plan'] ?? [];
            $progress = $m['progress'] ?? [];
            $settings = $this->lms->settings((int) ($plan['id'] ?? 0));
            $ready = PlanProgress::certificateReady($progress, [], $settings);
            $cert = null;
            $enrollId = (int) ($m['enrollment']['id'] ?? $enrollId);
            if ($enrollId > 0 && !empty($plan['id'])) {
                $cert = db()->fetch(
                    "SELECT id, cert_no FROM certificates
                     WHERE enrollment_id = ? AND plan_id = ? AND status = 'active'",
                    [$enrollId, (int) $plan['id']]
                );
            }
            $row = [
                'plan' => $plan,
                'progress' => $progress,
                'ready' => $ready,
                'certificate' => $cert,
            ];
            if (PlanProgress::isRequired($plan)) {
                $requiredRows[] = $row;
            } else {
                $generalRows[] = $row;
            }
        }

        $diploma = $enrollId > 0
            ? $this->training->diplomaStanding($enrollId)
            : ['total' => 0, 'passed' => 0, 'ready' => false, 'hours' => 0.0];
        $diplomaCert = $enrollId > 0
            ? db()->fetch(
                "SELECT id, cert_no FROM certificates
                 WHERE enrollment_id = ? AND plan_id IS NULL AND status = 'active'",
                [$enrollId]
            )
            : null;

        $this->view('admin/certificates/index', [
            'title' => 'เกียรติบัตรของฉัน',
            'breadcrumb' => 'หน้าหลัก / เกียรติบัตร',
            'canIssue' => can_perm('certificates', 'issue_cert'),
            'requiredRows' => $requiredRows,
            'generalRows' => $generalRows,
            'diploma' => $diploma,
            'diplomaCert' => $diplomaCert,
            'passNoteTask' => (float) config('app', 'training.task_pass_percent', 70),
            'passNotePost' => (float) config('app', 'training.posttest_pass_percent', 70),
        ]);
    }

    /** มุมมองวิทยากร/แอดมิน — รายการแผน */
    private function manageIndex(): void
    {
        $role = (string) (auth()->role() ?? 'admin');
        $uid = (int) auth()->id();
        $plans = $this->manage->planSummaries($role, $uid);
        $settingsCache = [];
        $rows = [];
        $sumLearners = 0;
        $sumCertReady = 0;
        $sumCertIssued = 0;
        $sumDiplomaIssued = 0;

        foreach ($plans as $plan) {
            $planId = (int) $plan['id'];
            if (!isset($settingsCache[$planId])) {
                $settingsCache[$planId] = $this->lms->settings($planId);
            }
            $settings = $settingsCache[$planId];
            $learners = db()->fetchAll(
                "SELECT tmp.*, e.id AS enrollment_id, e.is_passed AS enrollment_passed
                 FROM training_module_progress tmp
                 INNER JOIN course_enrollments e ON e.id = tmp.enrollment_id
                 WHERE tmp.plan_id = ?",
                [$planId]
            );
            $certReady = 0;
            $certIssued = 0;
            $diplomaIssued = 0;
            $issuedMap = [];
            if ($learners !== []) {
                $enrollIds = array_map(static fn (array $r): int => (int) $r['enrollment_id'], $learners);
                $ph = implode(',', array_map('intval', $enrollIds));
                foreach (db()->fetchAll(
                    "SELECT enrollment_id FROM certificates
                     WHERE status = 'active' AND plan_id = ? AND enrollment_id IN ({$ph})",
                    [$planId]
                ) as $c) {
                    $issuedMap[(int) $c['enrollment_id']] = true;
                }
                $certIssued = count($issuedMap);
                $diplomaIssued = (int) (db()->fetch(
                    "SELECT COUNT(*) AS c FROM certificates
                     WHERE status = 'active' AND plan_id IS NULL AND enrollment_id IN ({$ph})"
                )['c'] ?? 0);
            }
            foreach ($learners as $L) {
                $eid = (int) $L['enrollment_id'];
                if (PlanProgress::certificateReady($L, [], $settings) && empty($issuedMap[$eid])) {
                    $certReady++;
                }
            }
            $learnerCount = count($learners);
            $sumLearners += $learnerCount;
            $sumCertReady += $certReady;
            $sumCertIssued += $certIssued;
            $sumDiplomaIssued += $diplomaIssued;
            $rows[] = [
                'plan' => $plan,
                'learner_count' => $learnerCount,
                'cert_ready' => $certReady,
                'cert_issued' => $certIssued,
                'diploma_issued' => $diplomaIssued,
                'passed_count' => (int) ($plan['passed_count'] ?? 0),
                'avg_percent' => $plan['avg_percent'] ?? null,
                'trainers' => $plan['trainers'] ?? [],
            ];
        }

        $this->view('admin/certificates/manage', [
            'title' => 'เกียรติบัตรออนไลน์',
            'breadcrumb' => 'หน้าหลัก / เกียรติบัตร',
            'rows' => $rows,
            'stats' => [
                'plans' => count($rows),
                'learners' => $sumLearners,
                'cert_ready' => $sumCertReady,
                'cert_issued' => $sumCertIssued,
                'diploma_issued' => $sumDiplomaIssued,
            ],
        ]);
    }

    /** รายชื่อผู้เรียนในแผน + สถานะเกียรติบัตร/วุฒิบัตร */
    public function plan(string $planId): void
    {
        $this->requirePerm('certificates', 'view');
        if (!is_trainer_like()) {
            flash('error', 'หน้านี้สำหรับวิทยากรและผู้ดูแล');
            $this->redirect('/admin/certificates');
        }
        $pid = (int) $planId;
        $role = (string) (auth()->role() ?? '');
        $uid = (int) auth()->id();
        $plan = $this->manage->plan($pid);
        if (!$plan || !$this->manage->canViewPlan($pid, $role, $uid)) {
            flash('error', 'ไม่พบแผน หรือไม่มีสิทธิ์เข้าดู');
            $this->redirect('/admin/certificates');
        }

        $settings = $this->lms->settings($pid);
        $learners = db()->fetchAll(
            "SELECT tmp.*, e.id AS enrollment_id, e.enrollment_no, e.is_passed AS enrollment_passed,
                    u.title, u.first_name, u.last_name, u.username,
                    o.name AS org_name
             FROM training_module_progress tmp
             INNER JOIN course_enrollments e ON e.id = tmp.enrollment_id
             INNER JOIN users u ON u.id = e.user_id AND u.deleted_at IS NULL
             LEFT JOIN org_units o ON o.id = e.org_unit_id
             WHERE tmp.plan_id = ?
             ORDER BY tmp.overall_percent DESC, u.first_name ASC, u.last_name ASC",
            [$pid]
        );

        $enrollIds = array_map(static fn (array $r): int => (int) $r['enrollment_id'], $learners);
        $planCerts = [];
        $diplomaCerts = [];
        if ($enrollIds !== []) {
            $ph = implode(',', array_map('intval', $enrollIds));
            foreach (db()->fetchAll(
                "SELECT id, enrollment_id, cert_no FROM certificates
                 WHERE status = 'active' AND plan_id = ? AND enrollment_id IN ({$ph})",
                [$pid]
            ) as $c) {
                $planCerts[(int) $c['enrollment_id']] = $c;
            }
            foreach (db()->fetchAll(
                "SELECT id, enrollment_id, cert_no FROM certificates
                 WHERE status = 'active' AND plan_id IS NULL AND enrollment_id IN ({$ph})"
            ) as $c) {
                $diplomaCerts[(int) $c['enrollment_id']] = $c;
            }
        }

        $rows = [];
        $stats = [
            'learners' => count($learners),
            'cert_ready' => 0,
            'cert_issued' => 0,
            'diploma_ready' => 0,
            'diploma_issued' => 0,
        ];
        foreach ($learners as $L) {
            $eid = (int) $L['enrollment_id'];
            $planReady = PlanProgress::certificateReady($L, [], $settings);
            $planCert = $planCerts[$eid] ?? null;
            $diplomaCert = $diplomaCerts[$eid] ?? null;
            // วุฒิบัตร: ใช้สถานะ enrollment (ผ่านบังคับครบ) หรือมีเอกสารแล้ว — ตรวจซ้ำตอนพิมพ์ PDF
            $diplomaReady = !empty($diplomaCert) || (int) ($L['enrollment_passed'] ?? 0) === 1;
            if ($planReady && !$planCert) {
                $stats['cert_ready']++;
            }
            if ($planCert) {
                $stats['cert_issued']++;
            }
            if ($diplomaReady && !$diplomaCert) {
                $stats['diploma_ready']++;
            }
            if ($diplomaCert) {
                $stats['diploma_issued']++;
            }
            $stage = (string) ($L['stage'] ?? '');
            $rows[] = [
                'enrollment_id' => $eid,
                'enrollment_no' => (string) ($L['enrollment_no'] ?? ''),
                'name' => trim(($L['title'] ?? '') . ($L['first_name'] ?? '') . ' ' . ($L['last_name'] ?? '')),
                'username' => (string) ($L['username'] ?? ''),
                'org_name' => (string) ($L['org_name'] ?? ''),
                'stage' => $stage,
                'stage_label' => self::STAGE_LABELS[$stage] ?? ($stage !== '' ? $stage : '-'),
                'overall' => (float) ($L['overall_percent'] ?? 0),
                'plan_passed' => (int) ($L['is_passed'] ?? 0) === 1,
                'plan_ready' => $planReady,
                'plan_cert' => $planCert,
                'diploma_ready' => $diplomaReady,
                'diploma_cert' => $diplomaCert,
            ];
        }

        $this->view('admin/certificates/plan', [
            'title' => 'เกียรติบัตร · แผนที่ ' . (int) ($plan['plan_no'] ?? 0),
            'breadcrumb' => 'หน้าหลัก / เกียรติบัตร / รายละเอียดแผน',
            'plan' => $plan,
            'rows' => $rows,
            'stats' => $stats,
            'canIssue' => can_perm('certificates', 'issue_cert') || can_perm('certificates', 'edit'),
        ]);
    }

    /** ออกเกียรติบัตรรายแผนให้ผู้เรียนที่พร้อมทั้งหมด */
    public function issuePlanReady(string $planId): void
    {
        $this->requirePerm('certificates', 'issue_cert');
        $pid = (int) $planId;
        if (!(new PlanOwnerService())->canManage($pid) && auth()->role() !== 'admin') {
            flash('error', 'ไม่มีสิทธิ์ออกเกียรติบัตรของแผนนี้');
            $this->redirect('/admin/certificates');
        }
        $settings = $this->lms->settings($pid);
        $learners = db()->fetchAll(
            'SELECT tmp.*, e.id AS enrollment_id
             FROM training_module_progress tmp
             INNER JOIN course_enrollments e ON e.id = tmp.enrollment_id
             WHERE tmp.plan_id = ?',
            [$pid]
        );
        $issued = 0;
        $issuer = (int) auth()->id();
        foreach ($learners as $L) {
            if (!PlanProgress::certificateReady($L, [], $settings)) {
                continue;
            }
            $cert = $this->training->issuePlanCertificate((int) $L['enrollment_id'], $pid, $issuer);
            if ($cert) {
                $issued++;
            }
        }
        activity_log('issue_cert', 'certificates', 'ออกเกียรติบัตรแผน #' . $pid . ' จำนวน ' . $issued);
        flash('success', $issued > 0 ? 'ออกเกียรติบัตรแล้ว ' . $issued . ' รายการ' : 'ไม่มีผู้เรียนที่พร้อมออกเกียรติบัตรเพิ่ม');
        $this->redirect('/admin/certificates/plans/' . $pid);
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
            activity_log('issue_cert', 'certificates', 'ออกวุฒิบัตร ' . $cert['cert_no']);
            flash('success', 'ออกวุฒิบัตรแล้ว: ' . $cert['cert_no']);
            $this->redirect('/admin/certificates/' . $cert['id']);
        }
        flash('error', 'ยังไม่ผ่านเกณฑ์ หรือออกวุฒิบัตรไม่ได้');
        $this->redirect('/admin/certificates');
    }

    /** ผู้เรียน — หน้าดูเกียรติบัตร PDF */
    public function planView(string $planId): void
    {
        $this->requirePerm('certificates', 'view');
        $planId = (int) $planId;
        $userId = (int) auth()->id();
        $enroll = $this->lms->ensureEnrollment($userId);
        $enrollId = (int) ($enroll['id'] ?? 0);
        if ($enrollId <= 0 || $planId <= 0) {
            flash('error', 'ไม่พบข้อมูลการลงทะเบียน');
            $this->redirect('/admin/certificates');
        }
        $this->renderPlanPdfView($enrollId, $planId, $userId, '/admin/certificates');
    }

    /** ผู้เรียนพิมพ์/สตรีมเกียรติบัตรของตนเอง */
    public function planPdf(string $planId): void
    {
        $this->requirePerm('certificates', 'view');
        $planId = (int) $planId;
        $userId = (int) auth()->id();
        $enroll = $this->lms->ensureEnrollment($userId);
        $enrollId = (int) ($enroll['id'] ?? 0);
        if ($enrollId <= 0 || $planId <= 0) {
            flash('error', 'ไม่พบข้อมูลการลงทะเบียน');
            $this->redirect('/admin/certificates');
        }
        $this->streamPlanPdf($enrollId, $planId, $userId, $this->pdfDest());
    }

    /** วิทยากร/แอดมิน — หน้าดูเกียรติบัตรรายคน */
    public function learnerPlanView(string $enrollmentId, string $planId): void
    {
        $this->requirePerm('certificates', 'view');
        $enrollId = (int) $enrollmentId;
        $pid = (int) $planId;
        if (!$this->canAccessEnrollmentPlan($enrollId, $pid)) {
            flash('error', 'ไม่มีสิทธิ์ดูเกียรติบัตรนี้');
            $this->redirect('/admin/certificates');
        }
        $this->renderPlanPdfView($enrollId, $pid, (int) auth()->id(), '/admin/certificates/plans/' . $pid);
    }

    /** วิทยากร/แอดมิน ดู-พิมพ์เกียรติบัตรรายคน */
    public function learnerPlanPdf(string $enrollmentId, string $planId): void
    {
        $this->requirePerm('certificates', 'view');
        $enrollId = (int) $enrollmentId;
        $pid = (int) $planId;
        if (!$this->canAccessEnrollmentPlan($enrollId, $pid)) {
            flash('error', 'ไม่มีสิทธิ์ดูเกียรติบัตรนี้');
            $this->redirect('/admin/certificates');
        }
        $this->streamPlanPdf($enrollId, $pid, (int) auth()->id(), $this->pdfDest());
    }

    /** ผู้เรียน — หน้าดูวุฒิบัตร PDF */
    public function diplomaView(): void
    {
        $this->requirePerm('certificates', 'view');
        $userId = (int) auth()->id();
        $enroll = $this->lms->ensureEnrollment($userId);
        $enrollId = (int) ($enroll['id'] ?? 0);
        if ($enrollId <= 0) {
            flash('error', 'ไม่พบข้อมูลการลงทะเบียน');
            $this->redirect('/admin/certificates');
        }
        $this->renderDiplomaPdfView($enrollId, $userId, '/admin/certificates');
    }

    /** ผู้เรียนพิมพ์/สตรีมวุฒิบัตรของตนเอง */
    public function diplomaPdf(): void
    {
        $this->requirePerm('certificates', 'view');
        $userId = (int) auth()->id();
        $enroll = $this->lms->ensureEnrollment($userId);
        $enrollId = (int) ($enroll['id'] ?? 0);
        if ($enrollId <= 0) {
            flash('error', 'ไม่พบข้อมูลการลงทะเบียน');
            $this->redirect('/admin/certificates');
        }
        $this->streamDiplomaPdf($enrollId, $userId, $this->pdfDest());
    }

    /** วิทยากร/แอดมิน — หน้าดูวุฒิบัตรรายคน */
    public function learnerDiplomaView(string $enrollmentId): void
    {
        $this->requirePerm('certificates', 'view');
        $enrollId = (int) $enrollmentId;
        if (!$this->canAccessEnrollmentDiploma($enrollId)) {
            flash('error', 'ไม่มีสิทธิ์ดูวุฒิบัตรนี้');
            $this->redirect('/admin/certificates');
        }
        $this->renderDiplomaPdfView($enrollId, (int) auth()->id(), '/admin/certificates');
    }

    /** วิทยากร/แอดมิน ดู-พิมพ์วุฒิบัตรรายคน */
    public function learnerDiplomaPdf(string $enrollmentId): void
    {
        $this->requirePerm('certificates', 'view');
        $enrollId = (int) $enrollmentId;
        if (!$this->canAccessEnrollmentDiploma($enrollId)) {
            flash('error', 'ไม่มีสิทธิ์ดูวุฒิบัตรนี้');
            $this->redirect('/admin/certificates');
        }
        $this->streamDiplomaPdf($enrollId, (int) auth()->id(), $this->pdfDest());
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
        if (is_trainer_like() && auth()->role() === 'trainer' && !empty($cert['plan_id'])) {
            if (!(new PlanOwnerService())->canManage((int) $cert['plan_id'])) {
                flash('error', 'ไม่มีสิทธิ์ดูเกียรติบัตรของแผนนี้');
                $this->redirect('/admin/certificates');
            }
        }

        $format = (string) ($_GET['format'] ?? '');
        $dest = $format === 'inline' ? 'I' : ($format === 'pdf' ? 'D' : '');
        if ($dest !== '') {
            if (!empty($cert['plan_id'])) {
                $plan = db()->fetch('SELECT * FROM learning_plans WHERE id = ?', [(int) $cert['plan_id']]) ?: [];
                (new CertificatePdfService())->download($cert, $plan, ['diploma' => false], $dest);
            }
            $standing = $this->training->diplomaStanding((int) $cert['enrollment_id']);
            (new CertificatePdfService())->download($cert, [], [
                'diploma' => true,
                'plans_count' => (int) ($standing['passed'] ?: $cert['final_score'] ?? 0),
                'hours' => (float) ($cert['hours'] ?? $standing['hours'] ?? 0),
            ], $dest);
        }

        $back = '/admin/certificates';
        if (is_trainer_like() && !empty($cert['plan_id'])) {
            $back = '/admin/certificates/plans/' . (int) $cert['plan_id'];
        }

        $this->view('admin/certificates/show', [
            'title' => (empty($cert['plan_id']) ? 'ดูวุฒิบัตร' : 'ดูเกียรติบัตร') . ' ' . $cert['cert_no'],
            'breadcrumb' => 'หน้าหลัก / เกียรติบัตร / ดูไฟล์',
            'cert' => $cert,
            'backUrl' => $back,
        ]);
    }

    /** @param 'D'|'I' $dest */
    private function streamPlanPdf(int $enrollId, int $planId, int $issuerId, string $dest = 'D'): void
    {
        $cert = $this->training->issuePlanCertificate($enrollId, $planId, $issuerId);
        if (!$cert) {
            flash('error', 'ยังไม่ผ่านเกณฑ์ของแผนนี้ จึงพิมพ์เกียรติบัตรไม่ได้');
            $this->redirect(is_trainer_like()
                ? '/admin/certificates/plans/' . $planId
                : '/admin/certificates');
        }
        $plan = db()->fetch('SELECT * FROM learning_plans WHERE id = ?', [$planId]) ?: [];
        activity_log('issue_cert', 'certificates', 'พิมพ์เกียรติบัตรแผน #' . $planId . ' ' . ($cert['cert_no'] ?? ''));
        (new CertificatePdfService())->download($cert, $plan, ['diploma' => false], $dest);
    }

    /** @param 'D'|'I' $dest */
    private function streamDiplomaPdf(int $enrollId, int $issuerId, string $dest = 'D'): void
    {
        $standing = $this->training->diplomaStanding($enrollId);
        if (!$standing['ready']) {
            flash('error', 'ต้องผ่านแผนบังคับครบทุกแผนก่อนพิมพ์วุฒิบัตร');
            $this->redirect('/admin/certificates');
        }
        $cert = $this->training->issueCertificate($enrollId, $issuerId);
        if (!$cert) {
            flash('error', 'ยังไม่สามารถออกวุฒิบัตรได้');
            $this->redirect('/admin/certificates');
        }
        activity_log('issue_cert', 'certificates', 'พิมพ์วุฒิบัตร ' . ($cert['cert_no'] ?? ''));
        (new CertificatePdfService())->download($cert, [], [
            'diploma' => true,
            'plans_count' => (int) $standing['passed'],
            'hours' => (float) $standing['hours'],
        ], $dest);
    }

    private function pdfDest(): string
    {
        $inline = (string) ($_GET['inline'] ?? $_GET['format'] ?? '');
        return in_array($inline, ['1', 'inline', 'I'], true) ? 'I' : 'D';
    }

    private function renderPlanPdfView(int $enrollId, int $planId, int $issuerId, string $backUrl): void
    {
        $cert = $this->training->issuePlanCertificate($enrollId, $planId, $issuerId);
        if (!$cert) {
            flash('error', 'ยังไม่ผ่านเกณฑ์ของแผนนี้ จึงพิมพ์เกียรติบัตรไม่ได้');
            $this->redirect($backUrl);
        }
        $plan = db()->fetch('SELECT * FROM learning_plans WHERE id = ?', [$planId]) ?: [];
        $fileName = 'certificate_' . preg_replace('/[^\p{L}\p{N}_-]+/u', '_', (string) ($cert['cert_no'] ?? 'cert')) . '.pdf';
        $base = is_trainer_like() && (int) auth()->id() !== (int) ($cert['user_id'] ?? 0)
            ? '/admin/certificates/enrollments/' . $enrollId . '/plan/' . $planId . '/pdf'
            : '/admin/certificates/plan/' . $planId . '/pdf';
        $info = 'เกียรติบัตร: ' . (string) ($cert['recipient_name'] ?? '')
            . ' — แผนที่ ' . (int) ($plan['plan_no'] ?? 0) . ' ' . (string) ($plan['title'] ?? '')
            . ' — ' . $fileName;

        $this->view('admin/certificates/view_pdf', [
            'title' => 'ดูเกียรติบัตร ' . ($cert['cert_no'] ?? ''),
            'breadcrumb' => 'หน้าหลัก / เกียรติบัตร / ดูไฟล์',
            'fileUrl' => url($base . '?inline=1'),
            'downloadUrl' => url($base),
            'fileName' => $fileName,
            'infoText' => $info,
            'backUrl' => $backUrl,
            'pageTitle' => 'ดูเกียรติบัตร',
        ]);
    }

    private function renderDiplomaPdfView(int $enrollId, int $issuerId, string $backUrl): void
    {
        $standing = $this->training->diplomaStanding($enrollId);
        if (!$standing['ready']) {
            flash('error', 'ต้องผ่านแผนบังคับครบทุกแผนก่อนพิมพ์วุฒิบัตร');
            $this->redirect($backUrl);
        }
        $cert = $this->training->issueCertificate($enrollId, $issuerId);
        if (!$cert) {
            flash('error', 'ยังไม่สามารถออกวุฒิบัตรได้');
            $this->redirect($backUrl);
        }
        $fileName = 'diploma_' . preg_replace('/[^\p{L}\p{N}_-]+/u', '_', (string) ($cert['cert_no'] ?? 'cert')) . '.pdf';
        $enrollUserId = (int) (db()->fetch('SELECT user_id FROM course_enrollments WHERE id = ?', [$enrollId])['user_id'] ?? 0);
        $base = is_trainer_like() && (int) auth()->id() !== $enrollUserId
            ? '/admin/certificates/enrollments/' . $enrollId . '/diploma/pdf'
            : '/admin/certificates/diploma/pdf';
        $info = 'วุฒิบัตร: ' . (string) ($cert['recipient_name'] ?? '')
            . ' — ผ่าน ' . (int) $standing['passed'] . ' แผน · ' . number_format((float) $standing['hours'], 1) . ' ชม.'
            . ' — ' . $fileName;

        $this->view('admin/certificates/view_pdf', [
            'title' => 'ดูวุฒิบัตร ' . ($cert['cert_no'] ?? ''),
            'breadcrumb' => 'หน้าหลัก / เกียรติบัตร / ดูไฟล์',
            'fileUrl' => url($base . '?inline=1'),
            'downloadUrl' => url($base),
            'fileName' => $fileName,
            'infoText' => $info,
            'backUrl' => $backUrl,
            'pageTitle' => 'ดูวุฒิบัตร',
        ]);
    }

    private function canAccessEnrollmentPlan(int $enrollmentId, int $planId): bool
    {
        if ($enrollmentId <= 0 || $planId <= 0) {
            return false;
        }
        if (auth()->role() === 'admin') {
            return true;
        }
        if (auth()->role() === 'trainer') {
            return (new PlanOwnerService())->canManage($planId);
        }
        $enroll = db()->fetch('SELECT user_id FROM course_enrollments WHERE id = ?', [$enrollmentId]);
        return $enroll && (int) $enroll['user_id'] === (int) auth()->id();
    }

    private function canAccessEnrollmentDiploma(int $enrollmentId): bool
    {
        if ($enrollmentId <= 0) {
            return false;
        }
        if (auth()->role() === 'admin') {
            return true;
        }
        if (auth()->role() === 'trainer') {
            $owned = (new PlanOwnerService())->approvedPlanIds((int) auth()->id());
            if ($owned === []) {
                return false;
            }
            $ph = implode(',', array_map('intval', $owned));
            $hit = db()->fetch(
                "SELECT tmp.id FROM training_module_progress tmp
                 WHERE tmp.enrollment_id = ? AND tmp.plan_id IN ({$ph}) LIMIT 1",
                [$enrollmentId]
            );
            return (bool) $hit;
        }
        $enroll = db()->fetch('SELECT user_id FROM course_enrollments WHERE id = ?', [$enrollmentId]);
        return $enroll && (int) $enroll['user_id'] === (int) auth()->id();
    }
}
