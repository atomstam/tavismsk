<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Models\User;
use App\Services\MailService;

class AuthController extends Controller
{
    private User $users;
    private ?MailService $mail = null;

    public function __construct()
    {
        $this->users = new User();
    }

    private function mailer(): MailService
    {
        return $this->mail ??= new MailService();
    }

    public function showLogin(): void
    {
        $redirect = trim((string) $this->input('redirect', ''));
        if ($redirect !== '' && str_starts_with($redirect, '/') && !str_starts_with($redirect, '//')) {
            $_SESSION['intended_url'] = $redirect;
        }
        $this->authView('auth/login', [
            'title' => 'เข้าสู่ระบบ',
            'oauthProviders' => \App\Services\SocialAuthService::providersForLogin(),
        ]);
    }

    public function login(): void
    {
        $username = trim((string) $this->input('username', ''));
        $password = (string) $this->input('password', '');
        $remember = (bool) $this->input('remember', false);
        $otp = trim((string) $this->input('otp', ''));

        if ($username === '' || $password === '') {
            $this->flash('error', 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
            set_old_input(['username' => $username]);
            $this->redirect('/login');
        }

        $user = $this->users->findByLogin($username);

        if (!$user || (int) $user['status'] !== 1) {
            $this->logLogin(null, $username, 'failed', 'ไม่พบผู้ใช้หรือถูกระงับ');
            $this->flash('error', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
            set_old_input(['username' => $username]);
            $this->redirect('/login');
        }

        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            $this->logLogin((int) $user['id'], $username, 'locked');
            $this->flash('error', 'บัญชีถูกล็อกชั่วคราว กรุณาลองใหม่ภายหลัง');
            $this->redirect('/login');
        }

        if (!verify_password($password, (string) $user['password'])) {
            $this->users->incrementLoginAttempts((int) $user['id']);
            $this->logLogin((int) $user['id'], $username, 'failed', 'รหัสผ่านผิด');
            $this->flash('error', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
            set_old_input(['username' => $username]);
            $this->redirect('/login');
        }

        $userId = (int) $user['id'];

        // 2FA / OTP — ข้ามการรอ SMTP ถ้ายังไม่ได้ตั้งค่าเมล
        if ((int) $user['two_factor_enabled'] === 1 && $this->mailer()->isConfigured()) {
            if ($otp === '') {
                $code = generate_otp();
                $this->users->update($userId, [
                    'otp_code' => $code,
                    'otp_expires_at' => date('Y-m-d H:i:s', time() + 600),
                ]);
                $this->mailer()->sendOtp((string) $user['email'], $code, (string) $user['first_name']);
                $_SESSION['pending_2fa_user'] = $userId;
                $_SESSION['pending_portal'] = (string) $this->input('portal', 'user');
                $this->flash('info', 'ส่งรหัส OTP ไปยังอีเมลของคุณแล้ว');
                $this->authView('auth/otp', ['title' => 'ยืนยัน OTP', 'email' => $this->maskEmail((string) $user['email'])]);
                return;
            }
            if ($user['otp_code'] !== $otp || strtotime((string) $user['otp_expires_at']) < time()) {
                $this->flash('error', 'รหัส OTP ไม่ถูกต้องหรือหมดอายุ');
                $this->authView('auth/otp', ['title' => 'ยืนยัน OTP', 'email' => $this->maskEmail((string) $user['email'])]);
                return;
            }
            $this->users->update($userId, ['otp_code' => null, 'otp_expires_at' => null]);
        }

        $portal = (string) $this->input('portal', 'user');
        if (!in_array($portal, \App\Services\UserRoleService::PORTALS, true)) {
            $portal = 'user';
        }
        $roles = (new \App\Services\UserRoleService())->of($userId);
        if (!in_array($portal, $roles, true)) {
            $this->flash('error', 'บัญชีนี้ไม่มีสิทธิ์เข้าในโหมด ' . \App\Services\UserRoleService::label($portal));
            set_old_input(['username' => $username, 'portal' => $portal]);
            $this->redirect('/login');
        }

        $rehash = password_should_rehash((string) $user['password']) ? hash_password($password) : null;
        $this->users->markLoginSuccess($userId, $rehash);
        $_SESSION['pending_portal'] = $portal;
        auth()->login($user, $remember, $portal);
        $this->logLogin($userId, $username, 'success');
        activity_log('login', 'auth', 'เข้าสู่ระบบสำเร็จ');
        clear_old_input();
        $this->flash('success', 'ยินดีต้อนรับ ' . $user['first_name']);
        $this->redirectAfterLogin();
    }

    public function verifyOtp(): void
    {
        $otp = trim((string) $this->input('otp', ''));
        $userId = (int) ($_SESSION['pending_2fa_user'] ?? 0);
        if (!$userId) {
            $this->redirect('/login');
        }
        $user = $this->users->find($userId);
        if (!$user || $user['otp_code'] !== $otp || strtotime((string) $user['otp_expires_at']) < time()) {
            $this->flash('error', 'รหัส OTP ไม่ถูกต้องหรือหมดอายุ');
            $this->authView('auth/otp', ['title' => 'ยืนยัน OTP', 'email' => $this->maskEmail($user['email'] ?? '')]);
            return;
        }
        $this->users->update($userId, ['otp_code' => null, 'otp_expires_at' => null]);
        $this->users->resetLoginAttempts($userId);
        unset($_SESSION['pending_2fa_user']);
        $portal = (string) ($_SESSION['pending_portal'] ?? 'user');
        unset($_SESSION['pending_portal']);
        auth()->login($user, false, $portal);
        $this->logLogin($userId, $user['username'], '2fa');
        $this->flash('success', 'ยืนยันตัวตนสำเร็จ');
        $this->redirectAfterLogin();
    }

    private function redirectAfterLogin(): void
    {
        $intended = (string) ($_SESSION['intended_url'] ?? '');
        unset($_SESSION['intended_url']);
        if (
            $intended !== ''
            && str_starts_with($intended, '/')
            && !str_starts_with($intended, '//')
            && !str_contains($intended, "\n")
        ) {
            $this->redirect($intended);
        }
        $this->redirect('/admin/dashboard');
    }

    public function showForgot(): void
    {
        $this->authView('auth/forgot', ['title' => 'ลืมรหัสผ่าน']);
    }

    public function forgot(): void
    {
        $email = trim((string) $this->input('email', ''));
        $user = $this->users->findBy('email', $email);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $this->users->update((int) $user['id'], [
                'password_reset_token' => hash('sha256', $token),
                'password_reset_expires' => date('Y-m-d H:i:s', time() + 3600),
            ]);
            $this->mailer()->sendPasswordReset($email, $token, $user['first_name']);
        }
        $this->flash('success', 'หากอีเมลมีในระบบ จะได้รับลิงก์รีเซ็ตรหัสผ่าน');
        $this->redirect('/login');
    }

    public function showReset(): void
    {
        $token = (string) $this->input('token', '');
        $this->authView('auth/reset', ['title' => 'ตั้งรหัสผ่านใหม่', 'token' => $token]);
    }

    public function reset(): void
    {
        $token = (string) $this->input('token', '');
        $password = (string) $this->input('password', '');
        $confirm = (string) $this->input('password_confirmation', '');

        if (strlen($password) < 8 || $password !== $confirm) {
            $this->flash('error', 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร และตรงกัน');
            $this->redirect('/reset-password?token=' . urlencode($token));
        }

        $hash = hash('sha256', $token);
        $user = $this->users->db()->fetch(
            'SELECT * FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()',
            [$hash]
        );
        if (!$user) {
            $this->flash('error', 'ลิงก์หมดอายุหรือไม่ถูกต้อง');
            $this->redirect('/forgot-password');
        }

        $this->users->update((int) $user['id'], [
            'password' => hash_password($password),
            'password_reset_token' => null,
            'password_reset_expires' => null,
        ]);
        activity_log('reset_password', 'auth', 'รีเซ็ตรหัสผ่าน', null, ['user_id' => $user['id']]);
        $this->flash('success', 'ตั้งรหัสผ่านใหม่สำเร็จ กรุณาเข้าสู่ระบบ');
        $this->redirect('/login');
    }

    public function logout(): void
    {
        if (auth()->check()) {
            $this->logLogin(auth()->id(), auth()->user()['username'] ?? '', 'logout');
            activity_log('logout', 'auth', 'ออกจากระบบ');
        }
        auth()->logout();
        $this->flash('success', 'ออกจากระบบเรียบร้อย');
        $this->redirect('/login');
    }

    public function showRegister(): void
    {
        $org = new \App\Services\OrgMasterService();
        $geo = new \App\Services\GeoService();
        $area = $org->spmmskArea();
        $areaId = (int) ($area['id'] ?? 0);
        $provinceId = (int) old('province_id', 0);
        $amphurId = (int) old('amphur_id', 0);
        $memberType = (string) old('member_type', 'staff');
        if (!$org->isMemberType($memberType)) {
            $memberType = 'staff';
        }
        $this->authView('auth/register', [
            'title' => 'สมัครสมาชิก',
            'memberTypes' => $org->memberTypeLabels(),
            'memberType' => $memberType,
            'prefixes' => $org->prefixes($memberType),
            'staffPrefixes' => $org->prefixes('staff'),
            'studentPrefixes' => $org->prefixes('student'),
            'generalPrefixes' => $org->prefixes('general'),
            'staffPositions' => $org->personalTypes('staff'),
            'studentGrades' => $org->personalTypes('student'),
            'personalTypes' => in_array($memberType, ['staff', 'student'], true)
                ? $org->personalTypes($memberType)
                : [],
            'schools' => $areaId > 0 ? $org->schools($areaId, '', 200) : [],
            'ready' => $org->isReady(),
            'provinces' => $geo->provinces(),
            'amphurs' => $provinceId > 0 ? $geo->amphursByProvinceId($provinceId) : [],
            'tumbons' => $amphurId > 0 ? $geo->tumbonsByAmphurId($amphurId) : [],
        ]);
    }

    public function register(): void
    {
        $username = trim((string) $this->input('username', ''));
        $email = trim((string) $this->input('email', ''));
        $password = (string) $this->input('password', '');
        $password2 = (string) $this->input('password_confirmation', '');
        $memberType = trim((string) $this->input('member_type', ''));
        $personalTypeId = (int) $this->input('personal_type_id', 0);
        $positionText = trim((string) $this->input('position_text', ''));
        $title = trim((string) $this->input('title', ''));
        $first = trim((string) $this->input('first_name', ''));
        $last = trim((string) $this->input('last_name', ''));
        $phone = trim((string) $this->input('phone', ''));
        $provinceId = (int) $this->input('province_id', 0);
        $amphurId = (int) $this->input('amphur_id', 0);
        $tumbonId = (int) $this->input('tumbon_id', 0);
        $zipcode = trim((string) $this->input('zipcode', ''));
        $areaChoice = trim((string) $this->input('area_choice', ''));
        $schoolId = (int) $this->input('org_unit_id', 0);
        $orgName = trim((string) $this->input('org_name', ''));
        $affiliation = trim((string) $this->input('affiliation', ''));

        $old = [
            'username' => $username, 'email' => $email, 'title' => $title,
            'first_name' => $first, 'last_name' => $last, 'phone' => $phone,
            'member_type' => $memberType, 'personal_type_id' => $personalTypeId,
            'position_text' => $positionText,
            'province_id' => $provinceId, 'amphur_id' => $amphurId, 'tumbon_id' => $tumbonId,
            'zipcode' => $zipcode,
            'area_choice' => $areaChoice, 'org_unit_id' => $schoolId,
            'org_name' => $orgName, 'affiliation' => $affiliation,
        ];

        if ($username === '' || mb_strlen($username) < 3 || $first === '' || $last === '') {
            $this->flash('error', 'กรุณากรอกชื่อผู้ใช้ ชื่อ และนามสกุลให้ครบ');
            set_old_input($old);
            $this->redirect('/register');
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'กรุณากรอกอีเมลให้ถูกต้อง');
            set_old_input($old);
            $this->redirect('/register');
        }
        $org = new \App\Services\OrgMasterService();
        if (!$org->isMemberType($memberType)) {
            $this->flash('error', 'กรุณาเลือกประเภทผู้ใช้งาน');
            set_old_input($old);
            $this->redirect('/register');
        }
        if ($memberType === 'general') {
            $personalTypeId = 0;
            if ($positionText === '' || mb_strlen($positionText) > 120) {
                $this->flash('error', 'กรุณากรอกตำแหน่ง');
                set_old_input($old);
                $this->redirect('/register');
            }
        } else {
            $positionText = '';
            if (!$org->personalTypeBelongs($personalTypeId, $memberType)) {
                $this->flash('error', $memberType === 'student' ? 'กรุณาเลือกระดับชั้น' : 'กรุณาเลือกตำแหน่ง');
                set_old_input($old);
                $this->redirect('/register');
            }
        }
        if ($title === '' || !$org->prefixAllowed($title, $memberType)) {
            $this->flash('error', 'กรุณาเลือกคำนำหน้าให้ถูกต้องตามประเภทผู้ใช้งาน');
            set_old_input($old);
            $this->redirect('/register');
        }
        if (mb_strlen($password) < 8 || $password !== $password2) {
            $this->flash('error', 'รหัสผ่านอย่างน้อย 8 ตัวอักษร และต้องตรงกัน');
            set_old_input($old);
            $this->redirect('/register');
        }

        $geo = new \App\Services\GeoService();
        $geoError = $geo->validateSelection($provinceId, $amphurId, $tumbonId);
        if ($geoError !== null) {
            $this->flash('error', $geoError);
            set_old_input($old);
            $this->redirect('/register');
        }
        $tumbon = $geo->tumbon($tumbonId);
        $zipcode = $tumbon['zipcode'] !== '' ? $tumbon['zipcode'] : $zipcode;

        $membership = (new \App\Services\OrgMembershipService())->resolvePublic(
            $areaChoice,
            $schoolId,
            $orgName,
            $affiliation
        );
        if (!$membership['ok']) {
            $this->flash('error', $membership['error'] ?? 'กรุณาเลือกหน่วยงานให้ครบ');
            set_old_input($old);
            $this->redirect('/register');
        }

        $exists = $this->users->findBy('username', $username);
        if ($exists && empty($exists['deleted_at'])) {
            $this->flash('error', 'ชื่อผู้ใช้นี้ถูกใช้แล้ว');
            set_old_input($old);
            $this->redirect('/register');
        }

        $id = $this->users->create([
            'username' => $username,
            'email' => $email,
            'password' => hash_password($password),
            'title' => $title,
            'first_name' => $first,
            'last_name' => $last,
            'phone' => $phone !== '' ? $phone : null,
            'province_id' => $provinceId,
            'amphur_id' => $amphurId,
            'tumbon_id' => $tumbonId,
            'zipcode' => $zipcode !== '' ? $zipcode : null,
            'role' => 'user',
            'member_type' => $memberType,
            'personal_type_id' => $personalTypeId > 0 ? $personalTypeId : null,
            'position_text' => $positionText !== '' ? $positionText : null,
            'org_unit_id' => $membership['org_unit_id'],
            'area_id' => $membership['area_id'],
            'cluster_code' => $membership['cluster_code'],
            'school_code' => $membership['school_code'],
            'org_name' => $membership['org_name'],
            'affiliation' => $membership['affiliation'],
            'org_level' => $membership['org_level'],
            'status' => 1,
            'two_factor_enabled' => 0,
        ]);
        (new \App\Services\UserRoleService())->grant((int) $id, 'user');
        try {
            (new \App\Services\ModuleLearningService())->enrollRequiredPlans((int) $id);
        } catch (\Throwable $e) {
            // การลงทะเบียนหลักสูตรไม่ควรทำให้สมัครสมาชิกพัง
        }
        activity_log('register', 'auth', 'สมัครสมาชิกใหม่', null, [
            'user_id' => $id,
            'member_type' => $memberType,
            'personal_type_id' => $personalTypeId > 0 ? $personalTypeId : null,
            'position_text' => $positionText !== '' ? $positionText : null,
            'org_unit_id' => $membership['org_unit_id'],
            'area_id' => $membership['area_id'],
            'org_name' => $membership['org_name'],
            'affiliation' => $membership['affiliation'],
            'province_id' => $provinceId,
            'amphur_id' => $amphurId,
            'tumbon_id' => $tumbonId,
            'zipcode' => $zipcode,
        ]);
        $this->flash('success', 'สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ');
        $this->redirect('/login');
    }

    private function logLogin(?int $userId, string $username, string $status, ?string $message = null): void
    {
        try {
            db()->insert('login_logs', [
                'user_id' => $userId,
                'username' => $username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'status' => $status,
                'message' => $message,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }
        $name = $parts[0];
        $visible = substr($name, 0, 2);
        return $visible . str_repeat('*', max(strlen($name) - 2, 3)) . '@' . $parts[1];
    }
}
