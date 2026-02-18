<?php

class Router
{
    private array $routes = [];
    private array $middleware = [];

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, string $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
            'regex' => $this->pathToRegex($path),
            'parameters' => $this->getPathParameters($path)
        ];
    }

    public function resolve(): void
    {
        $method = $this->getRequestMethod();
        $uri = $this->getRequestUri();

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['regex'], $uri, $matches)) {
                // Extract route parameters
                $params = [];
                foreach ($route['parameters'] as $index => $paramName) {
                    $params[$paramName] = $matches[$index + 1] ?? null;
                }

                // Run middleware
                foreach ($route['middleware'] as $middlewareName) {
                    if (!$this->runMiddleware($middlewareName)) {
                        return; // Middleware handled the response (redirect, etc.)
                    }
                }

                // Execute controller
                $this->executeController($route['handler'], $params);
                return;
            }
        }

        // No route found - return 404
        $this->handle404();
    }

    private function getRequestMethod(): string
    {
        // Check for method override via _method form field
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
            if (in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
                return $method;
            }
        }

        return $_SERVER['REQUEST_METHOD'];
    }

    private function getRequestUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return rtrim($uri, '/') ?: '/';
    }

    private function pathToRegex(string $path): string
    {
        // Convert route parameters {id} to regex groups
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
        $pattern = str_replace('/', '\/', $pattern);
        return '/^' . $pattern . '$/';
    }

    private function getPathParameters(string $path): array
    {
        preg_match_all('/\{([^}]+)\}/', $path, $matches);
        return $matches[1] ?? [];
    }

    private function runMiddleware(string $middlewareName): bool
    {
        if (!isset($this->middleware[$middlewareName])) {
            throw new Exception("Middleware '$middlewareName' not found");
        }

        return call_user_func($this->middleware[$middlewareName]);
    }

    public function registerMiddleware(string $name, callable $callback): void
    {
        $this->middleware[$name] = $callback;
    }

    private function executeController(string $handler, array $params): void
    {
        [$controllerName, $methodName] = explode('@', $handler);

        $controllerClass = "App\\Controllers\\{$controllerName}";

        if (!class_exists($controllerClass)) {
            throw new Exception("Controller '$controllerClass' not found");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $methodName)) {
            throw new Exception("Method '$methodName' not found in '$controllerClass'");
        }

        // Pass parameters as arguments to the controller method
        call_user_func_array([$controller, $methodName], $params);
    }

    private function handle404(): void
    {
        http_response_code(404);

        // Check if it's an HTMX request
        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            echo '<div class="alert alert-danger">Page not found</div>';
        } else {
            // Load 404 page or show simple message
            if (file_exists(__DIR__ . '/../views/errors/404.php')) {
                include __DIR__ . '/../views/errors/404.php';
            } else {
                echo '<!DOCTYPE html>
                <html>
                <head><title>404 Not Found</title></head>
                <body>
                    <h1>404 - Page Not Found</h1>
                    <p>The requested page could not be found.</p>
                    <a href="/">Go Home</a>
                </body>
                </html>';
            }
        }
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}