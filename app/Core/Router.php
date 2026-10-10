<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP Router
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private string $prefix = '';
    private array $groupMiddleware = [];

    public function get(string $path, array|callable $handler, array $middleware = []): self
    {
        return $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): self
    {
        return $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array|callable $handler, array $middleware = []): self
    {
        return $this->add('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, array|callable $handler, array $middleware = []): self
    {
        return $this->add('DELETE', $path, $handler, $middleware);
    }

    public function any(string $path, array|callable $handler, array $middleware = []): self
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
            $this->add($method, $path, $handler, $middleware);
        }
        return $this;
    }

    public function group(array $options, callable $callback): void
    {
        $prevPrefix = $this->prefix;
        $prevMiddleware = $this->groupMiddleware;

        $this->prefix .= $options['prefix'] ?? '';
        $this->groupMiddleware = array_merge($this->groupMiddleware, $options['middleware'] ?? []);

        $callback($this);

        $this->prefix = $prevPrefix;
        $this->groupMiddleware = $prevMiddleware;
    }

    private function add(string $method, string $path, array|callable $handler, array $middleware = []): self
    {
        $path = $this->prefix . $path;
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
        return $this;
    }

    public function dispatch(string $uri, string $method): mixed
    {
        $uri = parse_url($uri, PHP_URL_PATH) ?? '/';
        $uri = '/' . trim($uri, '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        // Strip base path for subdirectory installs (incl. rewrite into public/)
        $basePath = function_exists('app_base_path') ? app_base_path($uri) : $this->detectBasePath();
        if ($basePath !== '' && ($uri === $basePath || str_starts_with($uri, $basePath . '/'))) {
            $uri = substr($uri, strlen($basePath)) ?: '/';
            if ($uri !== '/') {
                $uri = rtrim($uri, '/');
            }
        }

        $method = strtoupper($method);
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            $params = $this->match($route['path'], $uri);
            if ($params !== false && $route['method'] === $method) {
                foreach ($route['middleware'] as $mw) {
                    $result = $this->runMiddleware($mw);
                    if ($result !== true) {
                        return $result;
                    }
                }
                return $this->runHandler($route['handler'], $params);
            }
        }

        http_response_code(404);
        return View::render('errors/404', ['title' => 'ไม่พบหน้า']);
    }

    private function match(string $routePath, string $uri): array|false
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (!preg_match($pattern, $uri, $matches)) {
            return false;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }

    private function runHandler(array|callable $handler, array $params): mixed
    {
        if (is_callable($handler) && !is_array($handler)) {
            return call_user_func_array($handler, $params);
        }

        [$class, $method] = $handler;
        $controller = new $class();
        return call_user_func_array([$controller, $method], $params);
    }

    private function runMiddleware(string $middleware): mixed
    {
        $map = [
            'auth' => \App\Middleware\AuthMiddleware::class,
            'guest' => \App\Middleware\GuestMiddleware::class,
            'admin' => \App\Middleware\AdminMiddleware::class,
            'csrf' => \App\Middleware\CsrfMiddleware::class,
            'role' => \App\Middleware\RoleMiddleware::class,
            'api' => \App\Middleware\ApiAuthMiddleware::class,
            'rate' => \App\Middleware\RateLimitMiddleware::class,
        ];

        // Support role:admin,director format
        $parts = explode(':', $middleware, 2);
        $name = $parts[0];
        $arg = $parts[1] ?? null;

        if (!isset($map[$name])) {
            return true;
        }

        $instance = new $map[$name]();
        return $instance->handle($arg);
    }

    private function detectBasePath(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($script));
        if ($dir === '/' || $dir === '\\' || $dir === '.') {
            return '';
        }
        return rtrim($dir, '/');
    }
}
