<?php
/**
 * ACTIO - Router
 * 
 * Simple router with support for route parameters and middleware.
 * 
 * @package Actio\Core
 */

declare(strict_types=1);

namespace Actio\Core;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Register a GET route
     */
    public function get(string $path, string|callable $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, string|callable $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register a PUT route (via POST with _method override)
     */
    public function put(string $path, string|callable $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register a DELETE route (via POST with _method override)
     */
    public function delete(string $path, string|callable $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Add a route to the collection
     */
    private function addRoute(string $method, string $path, string|callable $handler): self
    {
        // Convert path parameters like {id} to regex
        $pattern = $this->pathToRegex($path);

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
        ];

        return $this;
    }

    /**
     * Convert path with {params} to regex pattern
     */
    private function pathToRegex(string $path): string
    {
        // Escape forward slashes
        $pattern = preg_quote($path, '/');

        // Convert {param} to named capture groups
        $pattern = preg_replace('/\\\{([a-zA-Z_]+)\\\}/', '(?P<$1>[^\/]+)', $pattern);

        return '/^' . $pattern . '$/';
    }

    /**
     * Add global middleware
     */
    public function addMiddleware(callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Dispatch the request to the appropriate handler
     */
    public function dispatch(): void
    {
        $method = $this->request->getMethod();
        $path = $this->request->getPath();

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);

                // Run middleware
                foreach ($this->middleware as $middleware) {
                    $result = $middleware($this->request);
                    if ($result === false) {
                        return; // Middleware blocked the request
                    }
                }

                // Call handler
                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        // No route found
        Response::notFound("The requested page was not found.");
    }

    /**
     * Call the route handler
     */
    private function callHandler(string|callable $handler, array $params): void
    {
        if (is_callable($handler)) {
            // Direct callable
            $handler($this->request, $params);
            return;
        }

        // Parse Controller@method string
        if (!str_contains($handler, '@')) {
            Response::error("Invalid handler format: $handler");
            return;
        }

        [$controllerClass, $method] = explode('@', $handler);

        // Build full class name
        $fullClassName = "Actio\\Controllers\\$controllerClass";

        // Check if controller file exists
        $controllerFile = BASE_PATH . '/src/Controllers/' . $controllerClass . '.php';
        if (!file_exists($controllerFile)) {
            Response::error("Controller not found: $controllerClass");
            return;
        }

        require_once $controllerFile;

        if (!class_exists($fullClassName)) {
            Response::error("Controller class not found: $fullClassName");
            return;
        }

        $controller = new $fullClassName($this->request);

        if (!method_exists($controller, $method)) {
            Response::error("Method not found: $controllerClass@$method");
            return;
        }

        // Call controller method with parameters
        $controller->$method($params);
    }

    /**
     * Load routes from file
     */
    public function loadRoutes(string $file): void
    {
        if (!file_exists($file)) {
            return;
        }

        // The routes file should call methods on $router
        $router = $this;
        require $file;
    }
}
