<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\Auth\AuthController;
use App\Controllers\Web\HomeController;
use App\Controllers\Web\PortfolioPublicController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\PersonnelController;
use App\Controllers\Admin\DepartmentController;
use App\Controllers\Admin\CatalogController;
use App\Controllers\Admin\PortfolioController;
use App\Controllers\Admin\ReportController;
use App\Controllers\Admin\SettingController;
use App\Controllers\Admin\LogController;
use App\Controllers\Admin\NotificationController;
use App\Controllers\Admin\TicketController;
use App\Controllers\Admin\FormController;
use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\GalleryController;
use App\Controllers\Admin\MediaCropController;
use App\Controllers\Admin\CurriculumController;
use App\Controllers\Admin\ClubMemberController;
use App\Controllers\Admin\AiController;
use App\Controllers\Admin\EnrollmentController;
use App\Controllers\Admin\CertificateController;
use App\Controllers\Admin\OrgUnitController;
use App\Controllers\Admin\OrgStatsController;
use App\Controllers\Admin\TrainingManageController;
use App\Controllers\Admin\SatisfactionController;
use App\Controllers\Admin\TrainerApplyController;
use App\Controllers\Admin\TrainerManageController;
use App\Controllers\Admin\PlanOwnerController;
use App\Controllers\Admin\PlanChatController;
use App\Controllers\Admin\LearnController;
use App\Controllers\Admin\ProfileController;
use App\Controllers\Web\LearnController as WebLearnController;
use App\Controllers\Web\GdriveMediaController;
use App\Controllers\Web\CurriculumPublicController;
use App\Controllers\Web\CertificatePublicController;
use App\Controllers\Api\ApiController;

/** @var Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/media/gdrive/{fileId}', [GdriveMediaController::class, 'show']);
$router->get('/stats/card/{key}', [HomeController::class, 'card'], ['rate:pubstats:120:60']);
$router->get('/stats/chart/{key}', [HomeController::class, 'chart'], ['rate:pubstats:120:60']);
$router->get('/about', [HomeController::class, 'about']);
$router->get('/news', [HomeController::class, 'news']);
$router->get('/news/{slug}', [HomeController::class, 'newsShow']);
$router->get('/downloads', [HomeController::class, 'downloads']);
$router->get('/faq', [HomeController::class, 'faq']);
$router->get('/contact', [HomeController::class, 'contact']);
$router->post('/contact', [HomeController::class, 'contactStore'], ['csrf', 'rate:contact:5:60']);
$router->get('/captcha', [HomeController::class, 'captcha']);
$router->get('/forms/{slug}', [HomeController::class, 'formShow']);
$router->post('/forms/{slug}', [HomeController::class, 'formStore'], ['csrf']);

$router->get('/curriculum', [CurriculumPublicController::class, 'index']);
$router->get('/curriculum/{planNo}', [CurriculumPublicController::class, 'show']);
$router->get('/certificate/verify', [CertificatePublicController::class, 'verify']);

// Frontend LMS — เรียนแผน (auth) ลำดับเดียวกับ backend
$router->group(['middleware' => ['auth']], function (Router $r) {
    $r->get('/learn', [WebLearnController::class, 'index']);
    $r->post('/learn/diploma', [WebLearnController::class, 'diploma'], ['csrf']);
    $r->get('/learn/{planId}', [WebLearnController::class, 'show']);
    $r->post('/learn/{planId}/certificate', [WebLearnController::class, 'planCertificate'], ['csrf']);
    $r->get('/learn/{planId}/quiz/{type}', [WebLearnController::class, 'quiz']);
    $r->post('/learn/{planId}/quiz/{type}', [WebLearnController::class, 'submitQuiz'], ['csrf']);
    $r->get('/learn/{planId}/content/{contentId}', [WebLearnController::class, 'content']);
    $r->post('/learn/{planId}/content/{contentId}/complete', [WebLearnController::class, 'completeContent'], ['csrf']);
    $r->post('/learn/{planId}/task/{taskId}/submit', [WebLearnController::class, 'submitTask'], ['csrf']);
});

$router->get('/portfolios', [PortfolioPublicController::class, 'index']);
$router->get('/portfolios/gallery', [PortfolioPublicController::class, 'gallery']);
$router->get('/portfolios/{id}', [PortfolioPublicController::class, 'teacher']);
$router->get('/portfolios/{id}/summary', [PortfolioPublicController::class, 'summary']);
$router->get('/portfolios/{id}/works', [PortfolioPublicController::class, 'works']);
$router->get('/portfolios/{id}/works/{itemId}', [PortfolioPublicController::class, 'show']);

$router->group(['middleware' => ['guest']], function (Router $r) {
    $r->get('/login', [AuthController::class, 'showLogin']);
    $r->post('/login', [AuthController::class, 'login'], ['csrf', 'rate:login:8:60']);
    $r->post('/verify-otp', [AuthController::class, 'verifyOtp'], ['csrf', 'rate:otp:8:60']);
    $r->get('/register', [AuthController::class, 'showRegister']);
    $r->post('/register', [AuthController::class, 'register'], ['csrf', 'rate:register:5:300']);
    $r->get('/forgot-password', [AuthController::class, 'showForgot']);
    $r->post('/forgot-password', [AuthController::class, 'forgot'], ['csrf', 'rate:forgot:5:300']);
    $r->get('/reset-password', [AuthController::class, 'showReset']);
    $r->post('/reset-password', [AuthController::class, 'reset'], ['csrf', 'rate:reset:5:300']);
    $r->get('/auth/{provider}', [\App\Controllers\Auth\SocialLoginController::class, 'start'], ['rate:oauth:20:60']);
});
$router->get('/auth/{provider}/cb', [\App\Controllers\Auth\SocialLoginController::class, 'callback'], ['rate:oauth:30:60']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth', 'csrf']);

$router->post('/api/token', [ApiController::class, 'token'], ['rate:api-token:10:60']);
$router->get('/api/docs', [ApiController::class, 'docs']);
$router->get('/api/me', [ApiController::class, 'me'], ['api']);
$router->get('/api/portfolio', [ApiController::class, 'portfolio'], ['api']);
$router->get('/api/org/clusters', [\App\Controllers\Api\OrgLookupController::class, 'clusters'], ['rate:orglookup:120:60']);
$router->get('/api/org/areas', [\App\Controllers\Api\OrgLookupController::class, 'areas'], ['rate:orglookup:120:60']);
$router->get('/api/org/schools', [\App\Controllers\Api\OrgLookupController::class, 'schools'], ['rate:orglookup:120:60']);
$router->get('/api/org/meta', [\App\Controllers\Api\OrgLookupController::class, 'meta'], ['rate:orglookup:60:60']);
$router->get('/api/users/check', [\App\Controllers\Api\UserCheckController::class, 'check'], ['rate:usercheck:60:60']);
$router->get('/api/geo/provinces', [\App\Controllers\Api\GeoLookupController::class, 'provinces'], ['rate:geolookup:120:60']);
$router->get('/api/geo/amphurs', [\App\Controllers\Api\GeoLookupController::class, 'amphurs'], ['rate:geolookup:120:60']);
$router->get('/api/geo/tumbons', [\App\Controllers\Api\GeoLookupController::class, 'tumbons'], ['rate:geolookup:120:60']);

$router->group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function (Router $r) {
    $r->get('/dashboard', [DashboardController::class, 'index']);
    $r->get('/dashboard/widgets', [DashboardController::class, 'widgets']);
    $r->get('/dashboard/card/{key}', [DashboardController::class, 'card'], ['rate:dashstats:180:60']);
    $r->get('/dashboard/chart/{key}', [DashboardController::class, 'chart'], ['rate:dashstats:180:60']);
    $r->get('/search', [DashboardController::class, 'search']);

    $r->get('/profile', [ProfileController::class, 'edit']);
    $r->post('/profile', [ProfileController::class, 'update'], ['csrf']);

    $r->get('/users', [UserController::class, 'index']);
    $r->get('/users/datatable', [UserController::class, 'datatable']);
    $r->get('/users/lookup', [UserController::class, 'lookup']);
    $r->get('/users/create', [UserController::class, 'create']);
    $r->post('/users', [UserController::class, 'store'], ['csrf']);
    $r->get('/users/export', [UserController::class, 'export']);
    $r->get('/users/{id}', [UserController::class, 'show']);
    $r->get('/users/{id}/edit', [UserController::class, 'edit']);
    $r->post('/users/{id}', [UserController::class, 'update'], ['csrf']);
    $r->post('/users/{id}/delete', [UserController::class, 'destroy'], ['csrf']);
    $r->post('/users/{id}/reset-password', [UserController::class, 'resetPassword'], ['csrf']);

    $r->get('/roles', [RoleController::class, 'index']);
    $r->get('/roles/{key}', [RoleController::class, 'show']);

    $r->get('/personnel', [PersonnelController::class, 'index']);
    $r->get('/personnel/datatable', [PersonnelController::class, 'datatable']);
    $r->get('/personnel/lookup', [PersonnelController::class, 'lookup']);
    $r->get('/personnel/next-code', [PersonnelController::class, 'nextCode']);
    $r->get('/personnel/import-template', [PersonnelController::class, 'importTemplate']);
    $r->post('/personnel/import', [PersonnelController::class, 'import'], ['csrf']);
    $r->get('/personnel/create', [PersonnelController::class, 'create']);
    $r->post('/personnel', [PersonnelController::class, 'store'], ['csrf']);
    $r->get('/personnel/export', [PersonnelController::class, 'export']);
    $r->get('/personnel/{id}', [PersonnelController::class, 'show']);
    $r->get('/personnel/{id}/edit', [PersonnelController::class, 'edit']);
    $r->post('/personnel/{id}', [PersonnelController::class, 'update'], ['csrf']);
    $r->post('/personnel/{id}/delete', [PersonnelController::class, 'destroy'], ['csrf']);

    $r->post('/media/crop/upload-temp', [MediaCropController::class, 'uploadTemp'], ['csrf']);
    $r->post('/media/crop/apply', [MediaCropController::class, 'applyCrop'], ['csrf']);
    $r->post('/media/crop/transform', [MediaCropController::class, 'transform'], ['csrf']);
    $r->get('/media/crop/presets', [MediaCropController::class, 'presets']);

    $r->get('/departments', [DepartmentController::class, 'index']);
    $r->get('/departments/datatable', [DepartmentController::class, 'datatable']);
    $r->get('/departments/create', [DepartmentController::class, 'create']);
    $r->post('/departments', [DepartmentController::class, 'store'], ['csrf']);
    $r->get('/departments/{id}/edit', [DepartmentController::class, 'edit']);
    $r->post('/departments/{id}', [DepartmentController::class, 'update'], ['csrf']);
    $r->post('/departments/{id}/delete', [DepartmentController::class, 'destroy'], ['csrf']);

    $r->get('/catalog/{key}', [CatalogController::class, 'index']);
    $r->get('/catalog/{key}/datatable', [CatalogController::class, 'datatable']);
    $r->get('/catalog/{key}/create', [CatalogController::class, 'create']);
    $r->post('/catalog/{key}', [CatalogController::class, 'store'], ['csrf']);
    $r->get('/catalog/{key}/{id}/edit', [CatalogController::class, 'edit']);
    $r->post('/catalog/{key}/{id}', [CatalogController::class, 'update'], ['csrf']);
    $r->post('/catalog/{key}/{id}/delete', [CatalogController::class, 'destroy'], ['csrf']);

    $r->get('/portfolio/{type}', [PortfolioController::class, 'index']);
    $r->get('/portfolio/{type}/datatable', [PortfolioController::class, 'datatable']);
    $r->get('/portfolio/{type}/create', [PortfolioController::class, 'create']);
    $r->post('/portfolio/{type}', [PortfolioController::class, 'store'], ['csrf']);
    $r->get('/portfolio/{type}/{id}', [PortfolioController::class, 'show']);
    $r->get('/portfolio/{type}/{id}/edit', [PortfolioController::class, 'edit']);
    $r->post('/portfolio/{type}/{id}', [PortfolioController::class, 'update'], ['csrf']);
    $r->post('/portfolio/{type}/{id}/delete', [PortfolioController::class, 'destroy'], ['csrf']);

    $r->get('/reports/org-stats', [OrgStatsController::class, 'index']);
    $r->get('/reports/org-stats/export', [OrgStatsController::class, 'export']);
    $r->get('/reports/{type}', [ReportController::class, 'index']);
    $r->get('/reports/{type}/datatable', [ReportController::class, 'datatable']);
    $r->get('/reports/{type}/export', [ReportController::class, 'export']);
    $r->get('/reports/{type}/{id}', [ReportController::class, 'show']);
    $r->get('/reports/{type}/{id}/download', [ReportController::class, 'download']);

    $r->get('/tickets', [TicketController::class, 'index']);
    $r->get('/tickets/datatable', [TicketController::class, 'datatable']);
    $r->get('/tickets/create', [TicketController::class, 'create']);
    $r->post('/tickets', [TicketController::class, 'store'], ['csrf']);
    $r->get('/tickets/{id}', [TicketController::class, 'show']);
    $r->post('/tickets/{id}/reply', [TicketController::class, 'reply'], ['csrf']);

    $r->get('/forms', [FormController::class, 'index']);
    $r->get('/forms/datatable', [FormController::class, 'datatable']);
    $r->get('/forms/create', [FormController::class, 'create']);
    $r->post('/forms', [FormController::class, 'store'], ['csrf']);
    $r->get('/forms/{id}/edit', [FormController::class, 'edit']);
    $r->post('/forms/{id}', [FormController::class, 'update'], ['csrf']);
    $r->get('/forms/{id}/submissions', [FormController::class, 'submissions']);
    $r->get('/forms/{id}/export', [FormController::class, 'export']);

    $r->get('/gallery', [GalleryController::class, 'index']);
    $r->get('/gallery/datatable', [GalleryController::class, 'datatable']);
    $r->get('/gallery/create', [GalleryController::class, 'create']);
    $r->post('/gallery', [GalleryController::class, 'store'], ['csrf']);
    $r->get('/gallery/{id}/edit', [GalleryController::class, 'edit']);
    $r->post('/gallery/{id}', [GalleryController::class, 'update'], ['csrf']);
    $r->post('/gallery/{id}/delete', [GalleryController::class, 'destroy'], ['csrf']);

    $r->get('/curriculum', [CurriculumController::class, 'index']);
    $r->get('/curriculum/datatable', [CurriculumController::class, 'datatable']);
    $r->get('/curriculum/export', [CurriculumController::class, 'export']);
    $r->get('/curriculum/{id}', [CurriculumController::class, 'show']);
    $r->post('/curriculum/{id}/progress', [CurriculumController::class, 'saveProgress'], ['csrf']);

    $r->get('/enrollments', [EnrollmentController::class, 'index']);
    $r->get('/enrollments/datatable', [EnrollmentController::class, 'datatable']);
    $r->post('/enrollments', [EnrollmentController::class, 'store'], ['csrf']);
    $r->post('/enrollments/{id}/approve', [EnrollmentController::class, 'approve'], ['csrf']);

    // LMS — วิทยากรจัดการบทเรียน
    $r->get('/training', [TrainingManageController::class, 'index']);
    $r->get('/training/datatable', [TrainingManageController::class, 'datatable']);
    $r->get('/training/{planId}', [TrainingManageController::class, 'show']);
    $r->post('/training/{planId}/settings', [TrainingManageController::class, 'saveSettings'], ['csrf']);
    $r->post('/training/{planId}/content', [TrainingManageController::class, 'storeContent'], ['csrf']);
    $r->get('/training/{planId}/content/{id}/view', [TrainingManageController::class, 'viewContent']);
    $r->post('/training/{planId}/content/{id}/delete', [TrainingManageController::class, 'deleteContent'], ['csrf']);
    $r->post('/training/{planId}/task', [TrainingManageController::class, 'storeTask'], ['csrf']);
    $r->post('/training/{planId}/task/{id}/delete', [TrainingManageController::class, 'deleteTask'], ['csrf']);
    $r->post('/training/{planId}/submission/{id}/review', [TrainingManageController::class, 'reviewSubmission'], ['csrf']);
    $r->post('/training/{planId}/question', [TrainingManageController::class, 'storeQuestion'], ['csrf']);
    $r->post('/training/{planId}/question/{id}/delete', [TrainingManageController::class, 'deleteQuestion'], ['csrf']);
    $r->post('/training/{planId}/quiz-meta', [TrainingManageController::class, 'saveQuizMeta'], ['csrf']);

    $r->get('/trainer-apply', [TrainerApplyController::class, 'index']);
    $r->post('/trainer-apply', [TrainerApplyController::class, 'store'], ['csrf']);
    $r->get('/trainers', [TrainerManageController::class, 'index']);
    $r->post('/trainers/grant', [TrainerManageController::class, 'grant'], ['csrf']);
    $r->post('/trainers/revoke', [TrainerManageController::class, 'revoke'], ['csrf']);
    $r->get('/plan-owners', [PlanOwnerController::class, 'index']);
    $r->post('/plan-owners/review', [PlanOwnerController::class, 'review'], ['csrf']);
    $r->post('/plan-owners/assign', [PlanOwnerController::class, 'assign'], ['csrf']);
    $r->post('/plan-owners/remove', [PlanOwnerController::class, 'remove'], ['csrf']);

    $r->get('/satisfaction', [SatisfactionController::class, 'index']);
    $r->post('/satisfaction/meta', [SatisfactionController::class, 'saveMeta'], ['csrf']);
    $r->post('/satisfaction/question', [SatisfactionController::class, 'storeQuestion'], ['csrf']);
    $r->post('/satisfaction/question/{id}/delete', [SatisfactionController::class, 'deleteQuestion'], ['csrf']);

    // กระดานสนทนาต่อแผน (ผู้เรียน ↔ วิทยากร)
    $r->get('/plan-chat', [PlanChatController::class, 'index']);
    $r->post('/plan-chat/start', [PlanChatController::class, 'start'], ['csrf']);
    $r->get('/plan-chat/plan/{planId}', [PlanChatController::class, 'fromPlan']);
    $r->get('/plan-chat/{id}', [PlanChatController::class, 'show']);
    $r->get('/plan-chat/{id}/messages', [PlanChatController::class, 'messages']);
    $r->post('/plan-chat/{id}/messages', [PlanChatController::class, 'post'], ['csrf']);

    // LMS — ผู้เรียน
    $r->get('/learn', [LearnController::class, 'index']);
    $r->post('/learn/diploma', [LearnController::class, 'diploma'], ['csrf']);
    $r->get('/learn/{planId}', [LearnController::class, 'show']);
    $r->post('/learn/{planId}/certificate', [LearnController::class, 'planCertificate'], ['csrf']);
    $r->get('/learn/{planId}/quiz/{type}', [LearnController::class, 'quiz']);
    $r->post('/learn/{planId}/quiz/{type}', [LearnController::class, 'submitQuiz'], ['csrf']);
    $r->get('/learn/{planId}/content/{contentId}', [LearnController::class, 'content']);
    $r->post('/learn/{planId}/content/{contentId}/complete', [LearnController::class, 'completeContent'], ['csrf']);
    $r->post('/learn/{planId}/task/{taskId}/submit', [LearnController::class, 'submitTask'], ['csrf']);

    $r->get('/certificates', [CertificateController::class, 'index']);
    $r->get('/certificates/datatable', [CertificateController::class, 'datatable']);
    $r->get('/certificates/ready-datatable', [CertificateController::class, 'readyDatatable']);
    $r->post('/certificates/issue/{enrollmentId}', [CertificateController::class, 'issue'], ['csrf']);
    $r->get('/certificates/{id}', [CertificateController::class, 'show']);

    $r->get('/org-units', [OrgUnitController::class, 'index']);
    $r->get('/org-units/datatable', [OrgUnitController::class, 'datatable']);

    $r->get('/members', [ClubMemberController::class, 'index']);
    $r->get('/members/datatable', [ClubMemberController::class, 'datatable']);
    $r->get('/members/create', [ClubMemberController::class, 'create']);
    $r->post('/members', [ClubMemberController::class, 'store'], ['csrf']);
    $r->get('/members/{id}/edit', [ClubMemberController::class, 'edit']);
    $r->post('/members/{id}', [ClubMemberController::class, 'update'], ['csrf']);
    $r->post('/members/{id}/delete', [ClubMemberController::class, 'destroy'], ['csrf']);

    $r->get('/ai', [AiController::class, 'index']);
    $r->post('/ai/chat', [AiController::class, 'chat'], ['csrf', 'rate:ai:20:60']);
    $r->post('/ai/summarize', [AiController::class, 'summarize'], ['csrf', 'rate:ai:20:60']);
    $r->post('/ai/search', [AiController::class, 'search'], ['csrf', 'rate:ai:30:60']);
    $r->post('/ai/news', [AiController::class, 'news'], ['csrf', 'rate:ai:10:60']);
    $r->post('/ai/dashboard', [AiController::class, 'dashboard'], ['csrf', 'rate:ai:10:60']);

    $r->get('/notifications', [NotificationController::class, 'index']);
    $r->get('/notifications/datatable', [NotificationController::class, 'datatable']);
    $r->post('/notifications/read-all', [NotificationController::class, 'readAll'], ['csrf']);
    $r->get('/notifications/{id}', [NotificationController::class, 'read']);

    $r->get('/logs', [LogController::class, 'activity']);
    $r->get('/logs/activity', [LogController::class, 'activity']);
    $r->get('/logs/login', [LogController::class, 'login']);
    $r->get('/logs/chart/{key}', [LogController::class, 'chart'], ['rate:logstats:60:60']);
    $r->get('/logs/activity/datatable', [LogController::class, 'activityDatatable']);
    $r->get('/logs/login/datatable', [LogController::class, 'loginDatatable']);

    $r->get('/settings', [SettingController::class, 'index']);
    $r->post('/settings', [SettingController::class, 'save'], ['csrf']);
    $r->post('/settings/backup', [SettingController::class, 'backup'], ['csrf']);
    $r->get('/settings/backup/{id}', [SettingController::class, 'downloadBackup']);
    $r->post('/settings/backup/{id}/restore', [SettingController::class, 'restoreBackup'], ['csrf']);
});
