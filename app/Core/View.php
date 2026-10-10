<?php

declare(strict_types=1);

namespace App\Core;

/**
 * View Renderer with Layout support
 */
class View
{
    public static function render(string $name, array $data = [], ?string $layout = null): void
    {
        $file = VIEW_PATH . '/' . str_replace('.', '/', $name) . '.php';
        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: {$name}");
        }

        extract($data, EXTR_SKIP);
        $app = config('app');
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        ob_start();
        require $file;
        $content = ob_get_clean();

        if ($layout) {
            $layoutFile = VIEW_PATH . '/' . str_replace('.', '/', $layout) . '.php';
            if (file_exists($layoutFile)) {
                require $layoutFile;
                return;
            }
        }

        echo $content;
    }

    public static function partial(string $name, array $data = []): void
    {
        $file = VIEW_PATH . '/' . str_replace('.', '/', $name) . '.php';
        if (file_exists($file)) {
            extract($data, EXTR_SKIP);
            require $file;
        }
    }

    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
