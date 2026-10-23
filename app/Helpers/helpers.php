<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $val = $_ENV[$key] ?? getenv($key);
        if ($val === false || $val === null || $val === '') {
            return $default;
        }
        return $val;
    }
}

if (!function_exists('config')) {
    function config(string $file, ?string $key = null, mixed $default = null): mixed
    {
        static $cache = [];
        if (!isset($cache[$file])) {
            $path = CONFIG_PATH . '/' . $file . '.php';
            $cache[$file] = file_exists($path) ? require $path : [];
        }
        if ($key === null) {
            return $cache[$file];
        }
        $keys = explode('.', $key);
        $value = $cache[$file];
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        return $value;
    }
}

if (!function_exists('auth')) {
    function auth(): Auth
    {
        return Auth::getInstance();
    }
}

if (!function_exists('db')) {
    function db(): Database
    {
        return Database::getInstance();
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return View::escape($value);
    }
}

if (!function_exists('app_base_path')) {
    /**
     * Detect URL base for subdirectory installs.
     * Supports both /project/public and root .htaccess rewrite into public/.
     */
    function app_base_path(?string $uri = null): string
    {
        if ($uri === null) {
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        }
        $uri = '/' . trim(str_replace('\\', '/', (string) $uri), '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        $candidates = [];

        $appUrl = (string) env('APP_URL', '');
        if ($appUrl !== '') {
            $urlPath = parse_url($appUrl, PHP_URL_PATH);
            if (is_string($urlPath) && $urlPath !== '' && $urlPath !== '/') {
                $candidates[] = rtrim(str_replace('\\', '/', $urlPath), '/');
            }
        }

        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $dir = rtrim(dirname($script), '/');
        if ($dir !== '' && $dir !== '/' && $dir !== '.') {
            $candidates[] = $dir;
            // Root .htaccess rewrites into public/ — browser URI may omit /public
            if (str_ends_with($dir, '/public')) {
                $parent = rtrim(substr($dir, 0, -strlen('/public')), '/');
                if ($parent !== '' && $parent !== '/') {
                    $candidates[] = $parent;
                }
            }
        }

        $best = '';
        foreach (array_unique($candidates) as $base) {
            if ($base === '' || $base === '/') {
                continue;
            }
            if (($uri === $base || str_starts_with($uri, $base . '/')) && strlen($base) > strlen($best)) {
                $best = $base;
            }
        }

        return $best;
    }
}

if (!function_exists('app_base_url')) {
    /**
     * Public site origin. On the production host, ignore a localhost APP_URL
     * left over from a local .env so links stay on https://tavis.spmmsk.go.th.
     */
    function app_base_url(): string
    {
        $configured = rtrim((string) env('APP_URL', ''), '/');
        $host = strtolower((string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
        if ($host === 'tavis.spmmsk.go.th') {
            $configuredHost = strtolower((string) (parse_url($configured, PHP_URL_HOST) ?: ''));
            if ($configuredHost !== $host) {
                return 'https://tavis.spmmsk.go.th';
            }
        }
        if ($configured !== '') {
            return $configured;
        }
        return 'https://tavis.spmmsk.go.th';
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = app_base_url();
        $path = '/' . ltrim($path, '/');
        return $base . ($path === '/' ? '' : $path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(?string $token = null): bool
    {
        $token = $token ?? ($_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], (string) $token);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old_input'][$key] ?? $default;
    }
}

if (!function_exists('set_old_input')) {
    function set_old_input(array $data): void
    {
        $_SESSION['_old_input'] = $data;
    }
}

if (!function_exists('clear_old_input')) {
    function clear_old_input(): void
    {
        unset($_SESSION['_old_input']);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('flash')) {
    function flash(string $type, ?string $message = null): mixed
    {
        if ($message !== null) {
            $_SESSION['flash'] = ['type' => $type, 'message' => $message];
            return null;
        }
        $flash = $_SESSION['flash'] ?? null;
        if ($flash && ($flash['type'] ?? '') === $type) {
            unset($_SESSION['flash']);
            return $flash['message'] ?? null;
        }
        return null;
    }
}

if (!function_exists('can')) {
    function can(array|string $roles): bool
    {
        return auth()->hasRole((array) $roles);
    }
}

if (!function_exists('can_perm')) {
    function can_perm(string $module, string $action = 'view'): bool
    {
        if (!auth()->check()) {
            return false;
        }
        return (new \App\Services\PermissionService())->can(auth()->role(), $module, $action);
    }
}

if (!function_exists('can_any_perm')) {
    function can_any_perm(string $module, array $actions = ['view']): bool
    {
        foreach ($actions as $action) {
            if (can_perm($module, (string) $action)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('data_json_attr')) {
    function data_json_attr(array $data): string
    {
        return htmlspecialchars(
            (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('current_path')) {
    function current_path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri = '/' . trim(str_replace('\\', '/', (string) $uri), '/');
        if (str_contains($uri, '/index.php')) {
            $uri = preg_replace('#/index\.php#', '', $uri, 1) ?: '/';
            $uri = '/' . trim((string) $uri, '/');
        }
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }
        $base = app_base_path($uri);
        if ($base !== '' && ($uri === $base || str_starts_with($uri, $base . '/'))) {
            $uri = substr($uri, strlen($base)) ?: '/';
            $uri = '/' . ltrim((string) $uri, '/');
        }
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }
        return $uri === '' ? '/' : $uri;
    }
}

if (!function_exists('active_menu')) {
    function active_menu(string $path, bool $exact = false): string
    {
        $uri = current_path();
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        if ($exact) {
            return $uri === $path ? 'active' : '';
        }
        if ($uri === $path || str_starts_with($uri, $path . '/')) {
            return 'active';
        }
        return '';
    }
}

if (!function_exists('menu_path_matches')) {
    function menu_path_matches(string $path, bool $exact = false): bool
    {
        return active_menu($path, $exact) === 'active';
    }
}

if (!function_exists('is_active_menu')) {
    function is_active_menu(string $path): string
    {
        return active_menu($path);
    }
}

if (!function_exists('truncate')) {
    function truncate(?string $text, int $limit = 100): string
    {
        $text = (string) $text;
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        return mb_substr($text, 0, $limit) . '...';
    }
}

if (!function_exists('upload_url')) {
    function upload_url(?string $path, string $size = 'full'): string
    {
        if (!$path) {
            return '';
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        if (media_is_gdrive($path)) {
            return gdrive_photo_url($path, $size);
        }
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (!str_starts_with($path, 'uploads/')) {
            $path = 'uploads/' . $path;
        }
        return url($path);
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);
        $bytes /= (1024 ** $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('partial')) {
    function partial(string $name, array $data = []): void
    {
        View::partial($name, $data);
    }
}

if (!function_exists('thai_months')) {
    function thai_months(): array
    {
        return ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    }
}

if (!function_exists('thai_chart_day')) {
    function thai_chart_day(?string $date): string
    {
        if (!$date) {
            return '-';
        }
        $ts = strtotime($date);
        if (!$ts) {
            return '-';
        }
        $months = thai_months();
        return (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts)];
    }
}

if (!function_exists('thai_chart_month')) {
    function thai_chart_month(?string $date): string
    {
        if (!$date) {
            return '-';
        }
        $ts = strtotime($date);
        if (!$ts) {
            return '-';
        }
        $months = thai_months();
        $yy = ((int) date('Y', $ts) + 543) % 100;

        return $months[(int) date('n', $ts)] . ' ' . sprintf('%02d', $yy);
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd/m/Y'): string
    {
        if (!$date) {
            return '-';
        }
        $ts = strtotime($date);
        if (!$ts) {
            return '-';
        }
        if ($format === 'd/m/Y' || $format === 'thai') {
            return thai_date($date, false);
        }
        if (str_contains($format, 'Y')) {
            $be = (int) date('Y', $ts) + 543;
            return str_replace('Y', (string) $be, date(str_replace('Y', '\Y', $format), $ts));
        }
        return date($format, $ts);
    }
}

if (!function_exists('thai_date')) {
    function thai_date(?string $date, bool $withTime = false): string
    {
        if (!$date) {
            return '-';
        }
        $ts = strtotime($date);
        if (!$ts) {
            return '-';
        }
        $months = thai_months();
        $d = (int) date('j', $ts);
        $m = $months[(int) date('n', $ts)];
        $y = (int) date('Y', $ts) + 543;
        $result = "{$d} {$m} {$y}";
        if ($withTime) {
            $result .= ' ' . date('H:i', $ts) . ' น.';
        }
        return $result;
    }
}

if (!function_exists('time_ago')) {
    function time_ago(?string $date, bool $withFallback = true): string
    {
        if (!$date) {
            return '-';
        }
        $ts = strtotime($date);
        if (!$ts) {
            return '-';
        }
        $diff = max(0, time() - $ts);
        if ($diff < 45) {
            return 'เมื่อสักครู่';
        }
        if ($diff < 3600) {
            return ((int) floor($diff / 60)) . ' นาทีที่แล้ว';
        }
        if ($diff < 86400) {
            return ((int) floor($diff / 3600)) . ' ชม.ที่แล้ว';
        }
        if ($diff < 172800) {
            return 'เมื่อวาน';
        }
        if ($diff < 604800) {
            return ((int) floor($diff / 86400)) . ' วันที่แล้ว';
        }
        return $withFallback ? thai_date($date, true) : thai_date($date);
    }
}

if (!function_exists('request_ip')) {
    function request_ip(): string
    {
        $trustProxy = filter_var(env('TRUST_PROXY', false), FILTER_VALIDATE_BOOLEAN);
        if ($trustProxy) {
            $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
            if (is_string($xff) && $xff !== '') {
                $first = trim(explode(',', $xff)[0]);
                if (filter_var($first, FILTER_VALIDATE_IP)) {
                    return $first;
                }
            }
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }
}

if (!function_exists('validateThaiId')) {
    function validateThaiId(string $id): bool
    {
        $id = preg_replace('/\D/', '', $id) ?? '';
        if (strlen($id) !== 13) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $id[$i] * (13 - $i);
        }
        $check = (11 - ($sum % 11)) % 10;
        return $check === (int) $id[12];
    }
}

if (!function_exists('hash_password')) {
    function password_hash_options(): array
    {
        return ['cost' => 10];
    }

    function hash_password(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, password_hash_options());
    }
}

if (!function_exists('verify_password')) {
    function verify_password(string $password, string $hash): bool
    {
        return $hash !== '' && password_verify($password, $hash);
    }
}

if (!function_exists('password_should_rehash')) {
    function password_should_rehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, password_hash_options());
    }
}

if (!function_exists('generate_otp')) {
    function generate_otp(int $length = 6): string
    {
        $max = (10 ** $length) - 1;
        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text) ?? $text;
        return strtolower(trim($text, '-')) . '-' . substr(md5(uniqid('', true)), 0, 6);
    }
}

if (!function_exists('upload_file')) {
    function upload_file(array $file, string $folder = 'documents', array $allowed = [], array $options = []): ?string
    {
        try {
            $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
            if ($allowed !== [] && !in_array($ext, $allowed, true)) {
                return null;
            }
            if ($options === [] && function_exists('auth') && auth()->check()) {
                $options = ['user_id' => (int) auth()->id()];
            }
            $up = (new \App\Services\MediaStorageService())->upload($file, $folder, $options);
            return $up['file_path'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('activity_log')) {
    function activity_log(string $action, string $module = '', ?string $description = null, ?array $oldData = null, ?array $newData = null): void
    {
        try {
            db()->insert('activity_logs', [
                'user_id' => auth()->id(),
                'action' => $action,
                'module' => $module,
                'description' => $description,
                'old_data' => $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
                'new_data' => $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => request_ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
        }
    }
}

if (!function_exists('notify')) {
    function notify(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        try {
            db()->insert('notifications', [
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'link' => $link,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
        }
    }
}

if (!function_exists('setting_defaults')) {
    /** @return array<string,string> */
    function setting_defaults(): array
    {
        $org = config('app', 'organization');
        $org = is_array($org) ? $org : [];
        $dev = config('app', 'developer');
        $dev = is_array($dev) ? $dev : [];
        $devName = (string) ($dev['name'] ?? 'สพม.มหาสารคาม');
        return [
            'site_name' => (string) config('app', 'name', 'TAVIS – วัคซีนจราจร'),
            'site_short_name' => (string) config('app', 'short_name', 'TAVIS'),
            'site_full_name' => (string) config('app', 'full_name', 'วัคซีนจราจร เพื่อพัฒนาสมรรถนะด้านจราจรและความปลอดภัยทางถนน'),
            'site_tagline' => (string) config('app', 'tagline', 'เพื่อพัฒนาสมรรถนะด้านจราจรและความปลอดภัยทางถนน'),
            'site_name_en' => (string) config('app', 'name_en', 'Traffic Awareness Vaccine Intelligent System'),
            'site_nav_title' => (string) config('app', 'nav_title', 'TAVIS วัคซีนจราจร'),
            'site_nav_tag' => (string) config('app', 'nav_tag', 'เพื่อพัฒนาสมรรถนะด้านจราจร'),
            'system_purpose' => (string) config('app', 'description', 'วัคซีนจราจร เพื่อพัฒนาสมรรถนะด้านจราจรและความปลอดภัยทางถนน'),
            'org_name' => (string) ($org['name'] ?? 'สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม'),
            'org_name_en' => (string) ($org['name_en'] ?? 'Secondary Educational Service Area Office Maha Sarakham'),
            'org_affiliation' => (string) ($org['affiliation'] ?? 'สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน'),
            'org_address' => (string) ($org['address'] ?? ''),
            'org_phone' => (string) ($org['phone'] ?? ''),
            'org_email' => (string) ($org['email'] ?? ''),
            'org_facebook' => (string) ($org['facebook'] ?? ''),
            'org_line' => (string) ($org['line'] ?? ''),
            'org_website' => (string) ($org['website'] ?? ''),
            'developer_name' => $devName,
            'footer_credit' => (string) ($dev['credit'] ?? 'จัดทำโดย สำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม'),
            'certificate_prefix' => (string) config('app', 'training.certificate_prefix', 'TAVIS'),
            'director_name' => (string) config('app', 'certificate.director_name', 'นายอดุลย์ ภูปลื้ม'),
            'director_position' => (string) config(
                'app',
                'certificate.director_position',
                'ผู้อำนวยการสำนักงานเขตพื้นที่การศึกษามัธยมศึกษามหาสารคาม'
            ),
        ];
    }
}

if (!function_exists('setting_cache')) {
    /** @return array<string,mixed> */
    function setting_cache(bool $reset = false): array
    {
        static $settings = null;
        if ($reset) {
            $settings = null;
        }
        if ($settings === null) {
            try {
                $rows = db()->fetchAll('SELECT setting_key, setting_value FROM settings');
                $settings = [];
                foreach ($rows as $row) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            } catch (\Throwable $e) {
                $settings = [];
            }
        }
        return $settings;
    }
}

if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        $settings = setting_cache();
        if (array_key_exists($key, $settings) && $settings[$key] !== null && $settings[$key] !== '') {
            return $settings[$key];
        }
        if ($default !== null) {
            return $default;
        }
        return setting_defaults()[$key] ?? null;
    }
}

if (!function_exists('setting_flush')) {
    function setting_flush(): void
    {
        setting_cache(true);
    }
}

if (!function_exists('app_brand')) {
    /**
     * ค่าแบรนด์ระบบจาก settings (DB) หรือ config/app.php
     * keys: name|short_name|full_name|tagline|name_en|nav_title|nav_tag|description
     */
    function app_brand(string $key = 'name'): string
    {
        $map = [
            'name' => 'site_name',
            'short_name' => 'site_short_name',
            'full_name' => 'site_full_name',
            'tagline' => 'site_tagline',
            'name_en' => 'site_name_en',
            'nav_title' => 'site_nav_title',
            'nav_tag' => 'site_nav_tag',
            'description' => 'system_purpose',
        ];
        $settingKey = $map[$key] ?? null;
        if ($settingKey) {
            $fromSetting = setting($settingKey);
            if (is_string($fromSetting) && trim($fromSetting) !== '') {
                return trim($fromSetting);
            }
        }
        $fromConfig = config('app', $key);
        return is_string($fromConfig) ? trim($fromConfig) : '';
    }
}

if (!function_exists('site_logo_url')) {
    function site_logo_url(): string
    {
        $logo = setting('logo');
        if (is_string($logo) && $logo !== '') {
            return upload_url($logo);
        }
        return asset('img/logo.svg');
    }
}

if (!function_exists('visitor_stats')) {
    /** @return array{today:int,month:int,all:int} */
    function visitor_stats(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }
        try {
            $today = (int) (db()->fetch("SELECT COUNT(*) AS c FROM visitors WHERE DATE(visited_at) = CURDATE()")['c'] ?? 0);
            $month = (int) (db()->fetch("SELECT COUNT(*) AS c FROM visitors WHERE YEAR(visited_at)=YEAR(CURDATE()) AND MONTH(visited_at)=MONTH(CURDATE())")['c'] ?? 0);
            $all = (int) (db()->fetch('SELECT COUNT(*) AS c FROM visitors')['c'] ?? 0);
            $cache = ['today' => $today, 'month' => $month, 'all' => $all];
        } catch (\Throwable $e) {
            $cache = ['today' => 0, 'month' => 0, 'all' => 0];
        }
        return $cache;
    }
}

if (!function_exists('developer_credit')) {
    function developer_credit(): string
    {
        $fromConfig = config('app', 'developer.credit');
        if (is_string($fromConfig) && trim($fromConfig) !== '') {
            return trim($fromConfig);
        }
        $name = (string) config('app', 'developer.name', '');
        if ($name === '') {
            $name = (string) setting('developer_name');
        }
        return $name !== '' ? ('พัฒนาโดย : ' . $name) : '';
    }
}

if (!function_exists('site_footer_text')) {
    function site_footer_text(): string
    {
        $year = (int) date('Y') + 543;
        $sys = trim((string) (setting('site_short_name') ?: app_brand('short_name') ?: 'TAVIS'));
        $org = trim((string) (setting('org_name') ?: ''));
        if ($org === '') {
            $orgCfg = config('app', 'organization');
            $org = is_array($orgCfg) ? trim((string) ($orgCfg['name'] ?? '')) : '';
        }
        $text = $sys . ' © ' . $year;
        if ($org !== '') {
            $text .= ' · โดย ' . $org;
        }
        return $text;
    }
}

if (!function_exists('role_name')) {
    function role_name(string $role): string
    {
        $fromRoles = config('roles', "{$role}.label");
        if (is_string($fromRoles) && $fromRoles !== '') {
            return $fromRoles;
        }
        return (string) config('app', "roles.{$role}", $role);
    }
}

if (!function_exists('user_full_name')) {
    function user_full_name(?array $user): string
    {
        if (!$user) {
            return '-';
        }
        return trim(($user['title'] ?? '') . ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    }
}

if (!function_exists('current_academic_year')) {
    function current_academic_year(): int
    {
        $y = setting('academic_year');
        if ($y) {
            return (int) $y;
        }
        return (int) date('Y') + 543;
    }
}

if (!function_exists('academic_years_list')) {
    /** @return list<int> */
    function academic_years_list(): array
    {
        $cur = current_academic_year();
        $years = [];
        for ($i = $cur + 1; $i >= $cur - 6; $i--) {
            $years[] = $i;
        }
        return $years;
    }
}

if (!function_exists('personnel_photo_url')) {
    function personnel_photo_url(?string $photo): ?string
    {
        if (!$photo || trim($photo) === '') {
            return null;
        }
        if (media_is_gdrive($photo)) {
            return gdrive_photo_url($photo, 'thumb') ?: gdrive_photo_url($photo, 'full');
        }
        $rel = ltrim(str_replace('\\', '/', $photo), '/');
        if (!str_starts_with($rel, 'uploads/')) {
            $rel = 'uploads/' . ltrim($rel, '/');
        }
        $full = PUBLIC_PATH . '/' . $rel;
        if (!is_file($full)) {
            return null;
        }
        return url($rel);
    }
}

if (!function_exists('avatar_url')) {
    function avatar_url(?string $path): string
    {
        if ($path) {
            if (media_is_gdrive($path)) {
                return gdrive_photo_url($path, 'thumb') ?: gdrive_photo_url($path, 'full');
            }
            $rel = ltrim(str_replace('\\', '/', $path), '/');
            if (!str_starts_with($rel, 'uploads/') && !str_starts_with($rel, 'http')) {
                $rel = 'uploads/' . $rel;
            }
            if (is_file(PUBLIC_PATH . '/' . $rel)) {
                return url($rel);
            }
        }
        return asset('img/default-avatar.svg');
    }
}

if (!function_exists('json_response')) {
    function json_response(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('portfolio_modules')) {
    function portfolio_modules(): array
    {
        $mods = config('portfolio');
        return is_array($mods) ? $mods : [];
    }
}

if (!function_exists('portfolio_module')) {
    function portfolio_module(string $key): ?array
    {
        $mod = config('portfolio', $key);
        return is_array($mod) ? $mod : null;
    }
}

if (!function_exists('curriculum_plans')) {
    /** @return array<int,array<string,mixed>> */
    function curriculum_plans(): array
    {
        static $cache = null;
        if ($cache === null) {
            $file = CONFIG_PATH . '/curriculum.php';
            $cache = is_file($file) ? (require $file) : [];
            if (!is_array($cache)) {
                $cache = [];
            }
        }
        return $cache;
    }
}

if (!function_exists('curriculum_plan')) {
    function curriculum_plan(int $no): ?array
    {
        $plans = curriculum_plans();
        return $plans[$no] ?? null;
    }
}

if (!function_exists('curriculum_title_key')) {
    /** คีย์เปรียบเทียบชื่อรายการเพื่อตัดซ้ำ */
    function curriculum_title_key(string $title): string
    {
        $t = mb_strtolower(trim($title), 'UTF-8');
        $t = preg_replace('/\s+/u', '', $t) ?? $t;
        return $t;
    }
}

if (!function_exists('curriculum_plan_sections')) {
    /**
     * รวมรายการแสดงผลต่อแผน โดยแยกหมวดและตัดรายการซ้ำข้ามแหล่ง
     * - เนื้อหา/ภาระงาน = จาก LMS เท่านั้น
     * - ใบความรู้ = จาก config
     * - ใบงาน = config+DB ที่ยังไม่ซ้ำกับภาระงาน LMS
     * - สื่อ = config+DB ที่ยังไม่ซ้ำกันเอง และไม่ซ้ำชื่อกับเนื้อหา LMS
     *
     * @param array<string,mixed> $cfg
     * @param list<array<string,mixed>> $lmsContents
     * @param list<array<string,mixed>> $lmsTasks
     * @param list<array<string,mixed>> $dbResources
     * @param list<array<string,mixed>> $dbWorksheets
     * @return array{
     *   contents:list<array{title:string}>,
     *   tasks:list<array{title:string}>,
     *   knowledge:list<string>,
     *   worksheets:list<string>,
     *   worksheets_covered_by_tasks:bool,
     *   media:list<array{title:string,url:?string,type?:string}>
     * }
     */
    function curriculum_plan_sections(
        array $cfg,
        array $lmsContents = [],
        array $lmsTasks = [],
        array $dbResources = [],
        array $dbWorksheets = []
    ): array {
        $contents = [];
        $contentKeys = [];
        foreach ($lmsContents as $c) {
            $title = trim((string) ($c['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $key = curriculum_title_key($title);
            if (isset($contentKeys[$key])) {
                continue;
            }
            $contentKeys[$key] = true;
            $contents[] = ['title' => $title];
        }

        $tasks = [];
        $taskKeys = [];
        foreach ($lmsTasks as $t) {
            $title = trim((string) ($t['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $key = curriculum_title_key($title);
            if (isset($taskKeys[$key])) {
                continue;
            }
            $taskKeys[$key] = true;
            $tasks[] = ['title' => $title];
        }

        $knowledge = [];
        $knowKeys = [];
        foreach (($cfg['knowledge_sheets'] ?? []) as $ks) {
            $title = trim((string) $ks);
            if ($title === '') {
                continue;
            }
            $key = curriculum_title_key($title);
            if (isset($knowKeys[$key])) {
                continue;
            }
            $knowKeys[$key] = true;
            $knowledge[] = $title;
        }

        $worksheetCandidates = [];
        foreach (($cfg['worksheets'] ?? []) as $ws) {
            $worksheetCandidates[] = trim((string) $ws);
        }
        foreach ($dbWorksheets as $w) {
            $worksheetCandidates[] = trim((string) ($w['title'] ?? ''));
        }
        $worksheets = [];
        $wsKeys = [];
        $hadWorksheetSource = false;
        foreach ($worksheetCandidates as $title) {
            if ($title === '') {
                continue;
            }
            $hadWorksheetSource = true;
            $key = curriculum_title_key($title);
            if (isset($wsKeys[$key]) || isset($taskKeys[$key])) {
                continue;
            }
            $wsKeys[$key] = true;
            $worksheets[] = $title;
        }

        $mediaCandidates = [];
        foreach (($cfg['resources'] ?? []) as $res) {
            if (!is_array($res)) {
                continue;
            }
            $mediaCandidates[] = [
                'title' => trim((string) ($res['title'] ?? '')),
                'url' => trim((string) ($res['url'] ?? '')) ?: null,
                'type' => (string) ($res['type'] ?? ''),
            ];
        }
        foreach ($dbResources as $r) {
            $mediaCandidates[] = [
                'title' => trim((string) ($r['title'] ?? '')),
                'url' => trim((string) ($r['url'] ?? '')) ?: null,
                'type' => (string) ($r['type'] ?? 'link'),
            ];
        }
        $media = [];
        $mediaKeys = [];
        foreach ($mediaCandidates as $m) {
            $title = $m['title'];
            if ($title === '') {
                continue;
            }
            $key = curriculum_title_key($title);
            $urlKey = $m['url'] ? ('u:' . mb_strtolower($m['url'], 'UTF-8')) : ('t:' . $key);
            // ซ้ำกับรายการสื่ออื่น หรือชื่อซ้ำกับเนื้อหา LMS → ไม่แสดงซ้ำ
            if (isset($mediaKeys[$urlKey]) || isset($mediaKeys['t:' . $key]) || isset($contentKeys[$key])) {
                continue;
            }
            $mediaKeys[$urlKey] = true;
            $mediaKeys['t:' . $key] = true;
            $media[] = $m;
        }

        return [
            'contents' => $contents,
            'tasks' => $tasks,
            'knowledge' => $knowledge,
            'worksheets' => $worksheets,
            'worksheets_covered_by_tasks' => $hadWorksheetSource && $worksheets === [] && $taskKeys !== [],
            'media' => $media,
        ];
    }
}

if (!function_exists('is_admin_like')) {
    function is_admin_like(): bool
    {
        return auth()->hasRole(['admin']);
    }
}

if (!function_exists('is_trainer_like')) {
    /** Admin or trainer (can manage training ops). */
    function is_trainer_like(): bool
    {
        return auth()->hasRole(['admin', 'trainer']);
    }
}

if (!function_exists('is_learner')) {
    /** End-user learner only (not admin/trainer). */
    function is_learner(): bool
    {
        return auth()->check() && auth()->hasRole(['user']) && !auth()->hasRole(['admin', 'trainer']);
    }
}

if (!function_exists('can_see_all_personnel')) {
    function can_see_all_personnel(): bool
    {
        return auth()->hasRole(['admin', 'trainer']);
    }
}

if (!function_exists('can_view_owner_sidebar')) {
    /** แสดงการ์ดข้อมูลส่วนตัวด้านขวาเฉพาะเมื่อเจ้าของล็อกอินอยู่ */
    function can_view_owner_sidebar(?array $teacher): bool
    {
        if (!$teacher || empty($teacher['id']) || !auth()->check()) {
            return false;
        }
        return (int) auth()->id() === (int) $teacher['id'];
    }
}

if (!function_exists('resolve_cropped_upload')) {
    /**
     * บันทึกรูปจาก crop token หรือไฟล์อัปโหลดธรรมดา (Google Drive เมื่อพร้อม)
     *
     * @param array{user_id?:int|null,personnel_id?:int|null,person_key?:string|null,system?:bool} $options
     */
    function resolve_cropped_upload(
        string $type,
        string $field,
        string $tokenField,
        ?string $oldPath = null,
        array $options = []
    ): ?string {
        $storage = new \App\Services\MediaStorageService();
        $token = trim((string) ($_POST[$tokenField] ?? ''));
        if ($token !== '') {
            return $storage->commitCropToken($type, $token, $oldPath, $options);
        }
        if (!empty($_FILES[$field]['name'])) {
            $folder = \App\Services\ImageCropService::presets()[$type]['folder'] ?? 'temp';
            $up = $storage->upload($_FILES[$field], $folder, $options);
            if ($oldPath) {
                $storage->delete($oldPath);
            }
            return $up['file_path'];
        }
        return null;
    }
}

if (!function_exists('gdrive_service_account_email')) {
    function gdrive_service_account_email(): string
    {
        $path = (string) config('google_drive', 'credentials', '');
        if ($path === '') {
            return '';
        }
        if (!str_starts_with($path, '/') && !preg_match('#^[A-Za-z]:[/\\\\]#', $path)) {
            $path = BASE_PATH . '/' . ltrim(str_replace('\\', '/', $path), '/');
        }
        if (!is_readable($path)) {
            return '';
        }
        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) ? (string) ($data['client_email'] ?? '') : '';
    }
}

if (!function_exists('gdrive_project_id')) {
    function gdrive_project_id(): string
    {
        $path = (string) config('google_drive', 'credentials', '');
        if ($path === '') {
            return '';
        }
        if (!str_starts_with($path, '/') && !preg_match('#^[A-Za-z]:[/\\\\]#', $path)) {
            $path = BASE_PATH . '/' . ltrim(str_replace('\\', '/', $path), '/');
        }
        if (!is_readable($path)) {
            return '';
        }
        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) ? (string) ($data['project_id'] ?? '') : '';
    }
}

if (!function_exists('gdrive_user_error_message')) {
    function gdrive_user_error_message(\Throwable $e): string
    {
        $raw = $e->getMessage();
        $detail = $raw;
        $prev = $e->getPrevious();
        if ($prev !== null && trim($prev->getMessage()) !== '') {
            $detail = $prev->getMessage();
        }
        $haystack = $raw . ' ' . $detail;

        if (str_contains($haystack, 'invalid_grant') || str_contains($haystack, 'Invalid JWT')) {
            return 'Google Drive ไม่ยอมรับ token (invalid_grant) — ตรวจสอบเวลาเซิร์ฟเวอร์ / Domain-wide delegation';
        }
        $project = gdrive_project_id() ?: 'Google Cloud';
        if (str_contains($haystack, 'SERVICE_DISABLED') || str_contains($haystack, 'accessNotConfigured')) {
            return "Google Drive API ยังไม่เปิดใช้งาน — เปิดที่ Google Cloud Console โปรเจกต์ {$project}";
        }
        if (str_contains($haystack, 'unauthorized_client') || str_contains($haystack, 'unauthorized to retrieve access tokens')) {
            return 'ยังไม่ได้ตั้ง Domain-wide delegation ใน Google Workspace Admin';
        }
        if (str_contains($haystack, 'storageQuotaExceeded') || str_contains($haystack, 'do not have storage quota')) {
            $impersonate = trim((string) config('google_drive', 'impersonate_user', ''));
            if ($impersonate === '') {
                return 'ตั้ง GOOGLE_DRIVE_IMPERSONATE_USER เป็นอีเมลเจ้าของโฟลเดอร์ (Domain-wide delegation)';
            }
            return 'อัปโหลดไม่สำเร็จ — ตรวจสอบสิทธิ์ของ ' . $impersonate;
        }
        if (str_contains($haystack, '403') || str_contains($haystack, 'PERMISSION_DENIED')) {
            $email = gdrive_service_account_email();
            $hint = $email !== '' ? " ({$email})" : '';
            return 'ไม่มีสิทธิ์เข้าถึงโฟลเดอร์ Google Drive' . $hint;
        }
        if (str_contains($haystack, '404') || str_contains($haystack, 'notFound')) {
            return 'ไม่พบโฟลเดอร์ Google Drive — ตรวจสอบ GOOGLE_DRIVE_FOLDER_ID';
        }
        return 'อัปโหลด Google Drive ไม่สำเร็จ: ' . mb_strimwidth($detail !== '' ? $detail : $raw, 0, 180);
    }
}

if (!function_exists('gdrive_parts')) {
    /** @return array{full: string, thumb: string} */
    function gdrive_parts(string $ref): array
    {
        if (!str_starts_with($ref, 'gdrive:')) {
            return ['full' => '', 'thumb' => ''];
        }
        $rest = substr($ref, 7);
        $parts = explode(':', $rest, 2);
        return [
            'full' => trim($parts[0] ?? ''),
            'thumb' => trim($parts[1] ?? ''),
        ];
    }
}

if (!function_exists('gdrive_ref_from_ids')) {
    function gdrive_ref_from_ids(string $fullId, string $thumbId = ''): string
    {
        $fullId = trim($fullId);
        if ($fullId === '') {
            return '';
        }
        $thumbId = trim($thumbId);
        if ($thumbId !== '' && $thumbId !== $fullId) {
            return 'gdrive:' . $fullId . ':' . $thumbId;
        }
        return 'gdrive:' . $fullId;
    }
}

if (!function_exists('gdrive_file_id')) {
    function gdrive_file_id(string $ref): string
    {
        return gdrive_parts($ref)['full'];
    }
}

if (!function_exists('gdrive_thumb_file_id')) {
    function gdrive_thumb_file_id(string $ref): string
    {
        return gdrive_parts($ref)['thumb'];
    }
}

if (!function_exists('media_is_gdrive')) {
    function media_is_gdrive(string $ref): bool
    {
        return str_starts_with($ref, 'gdrive:');
    }
}

if (!function_exists('gdrive_media_url')) {
    function gdrive_media_url(string $fileId): string
    {
        $fileId = trim($fileId);
        if ($fileId === '') {
            return '';
        }
        $url = url('/media/gdrive/' . rawurlencode($fileId));
        $bust = trim((string) config('google_drive', 'media_cache_bust', ''));
        if ($bust !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . rawurlencode($bust);
        }
        return $url;
    }
}

if (!function_exists('gdrive_photo_url')) {
    function gdrive_photo_url(string $ref, string $size = 'full'): string
    {
        $parts = gdrive_parts($ref);
        $id = $parts['full'];
        if ($id === '') {
            return '';
        }
        if ($size === 'thumb') {
            $thumbId = $parts['thumb'];
            if ($thumbId !== '') {
                return gdrive_media_url($thumbId);
            }
        }
        return gdrive_media_url($id);
    }
}
