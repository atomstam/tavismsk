<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base Controller
 */
abstract class Controller
{
    protected function view(string $name, array $data = [], ?string $layout = 'layouts/admin'): void
    {
        View::render($name, $data, $layout);
    }

    protected function webView(string $name, array $data = []): void
    {
        $layout = !empty($data['layout_clean']) ? 'layouts/portfolio-clean' : 'layouts/web';
        // Public site uses dark UI matching reference (navbar + pages)
        if ($layout === 'layouts/web' && !array_key_exists('darkUi', $data)) {
            $data['darkUi'] = true;
        }
        // Footer stats — always available so every public page looks the same
        if ($layout === 'layouts/web' && empty($data['visitorStats']) && function_exists('visitor_stats')) {
            $data['visitorStats'] = visitor_stats();
        }
        View::render($name, $data, $layout);
    }

    protected function authView(string $name, array $data = []): void
    {
        View::render($name, $data, 'layouts/auth');
    }

    protected function json(mixed $data, int $status = 200): void
    {
        $this->releaseSession();
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function releaseSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    protected function redirect(string $url, int $code = 302): void
    {
        if (!str_starts_with($url, 'http')) {
            $url = url($url);
        }
        header("Location: {$url}", true, $code);
        exit;
    }

    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
        header("Location: {$referer}");
        exit;
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    protected function validate(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $ruleStr) {
            $ruleList = explode('|', $ruleStr);
            $value = $data[$field] ?? null;

            foreach ($ruleList as $rule) {
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $errors[$field][] = "กรุณากรอกข้อมูล {$field}";
                }
                if (str_starts_with($rule, 'min:') && is_string($value)) {
                    $min = (int) substr($rule, 4);
                    if (mb_strlen($value) < $min) {
                        $errors[$field][] = "{$field} ต้องมีอย่างน้อย {$min} ตัวอักษร";
                    }
                }
                if (str_starts_with($rule, 'max:') && is_string($value)) {
                    $max = (int) substr($rule, 4);
                    if (mb_strlen($value) > $max) {
                        $errors[$field][] = "{$field} ต้องไม่เกิน {$max} ตัวอักษร";
                    }
                }
                if ($rule === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = 'รูปแบบอีเมลไม่ถูกต้อง';
                }
                if ($rule === 'numeric' && $value !== null && $value !== '' && !is_numeric($value)) {
                    $errors[$field][] = "{$field} ต้องเป็นตัวเลข";
                }
                if ($rule === 'thai_id' && $value && !validateThaiId($value)) {
                    $errors[$field][] = 'เลขบัตรประชาชนไม่ถูกต้อง';
                }
            }
        }
        return $errors;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function allInput(): array
    {
        return array_merge($_GET, $_POST);
    }

    protected function requireAuth(): void
    {
        if (!auth()->check()) {
            $this->flash('error', 'กรุณาเข้าสู่ระบบ');
            $this->redirect('/login');
        }
    }

    protected function requireRole(array $roles): void
    {
        $this->requireAuth();
        if (!auth()->hasRole($roles)) {
            $this->flash('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
            $this->redirect('/admin/dashboard');
        }
    }

    protected function requirePerm(string $module, string $action = 'view'): void
    {
        $this->requireAuth();
        if (!can_perm($module, $action)) {
            $this->flash('error', 'สิทธิ์ไม่เพียงพอสำหรับการดำเนินการนี้');
            $this->redirect('/admin/dashboard');
        }
    }

    protected function requireMeasurePerm(int $measureNo, string $action = 'view'): void
    {
        $this->requireAuth();
        if (!can_measure($measureNo, $action)) {
            $this->flash('error', 'สิทธิ์ไม่เพียงพอสำหรับมาตรการนี้');
            $this->redirect('/admin/measures');
        }
    }
}
