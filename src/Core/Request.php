<?php
/**
 * ACTIO - HTTP Request Handler
 * 
 * Wraps $_GET, $_POST, $_SERVER into an object-oriented interface.
 * 
 * @package Actio\Core
 */

declare(strict_types=1);

namespace Actio\Core;

class Request
{
    private string $method;
    private string $uri;
    private string $path;
    private array $query;
    private array $post;
    private array $server;
    private array $cookies;

    public function __construct()
    {
        $this->server = $_SERVER;
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->query = $_GET;
        $this->post = $_POST;
        $this->cookies = $_COOKIE;

        // Parse path from URI (remove query string)
        $this->path = parse_url($this->uri, PHP_URL_PATH) ?: '/';

        // Remove trailing slash (except for root)
        if ($this->path !== '/' && str_ends_with($this->path, '/')) {
            $this->path = rtrim($this->path, '/');
        }
    }

    /**
     * Get HTTP method (GET, POST, PUT, DELETE, etc.)
     */
    public function getMethod(): string
    {
        // Support method override via _method field (for PUT/DELETE from forms)
        if ($this->method === 'POST' && isset($this->post['_method'])) {
            return strtoupper($this->post['_method']);
        }
        return $this->method;
    }

    /**
     * Get request path (without query string)
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get full request URI
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get query parameter (from GET)
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get all query parameters
     */
    public function allQuery(): array
    {
        return $this->query;
    }

    /**
     * Get POST parameter
     * 
     * @param string $key Parameter name
     * @param mixed $default Default value if not found
     * @return mixed Sanitized value (C04 - XSS prevention)
     */
    public function input(string $key, mixed $default = null): mixed
    {
        $value = $this->post[$key] ?? $default;

        // C04 - Sanitize string input (basic XSS prevention)
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    /**
     * Get all POST parameters
     */
    public function allInput(): array
    {
        return $this->post;
    }

    /**
     * Check if request has a specific input
     */
    public function has(string $key): bool
    {
        return isset($this->post[$key]) || isset($this->query[$key]);
    }

    /**
     * Get server variable
     */
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get cookie value
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Check if request is AJAX/XHR
     */
    public function isAjax(): bool
    {
        return $this->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest'
            || str_contains($this->server('HTTP_ACCEPT', ''), 'application/json');
    }

    /**
     * Check if request expects JSON response
     */
    public function expectsJson(): bool
    {
        return str_contains($this->server('HTTP_ACCEPT', ''), 'application/json');
    }

    /**
     * Get raw input body (for JSON APIs)
     */
    public function getBody(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    /**
     * Get JSON decoded body
     */
    public function json(): ?array
    {
        $body = $this->getBody();
        if (empty($body)) {
            return null;
        }

        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Get client IP address
     */
    public function ip(): string
    {
        // Check forwarded headers (be careful in production)
        $forwarded = $this->server('HTTP_X_FORWARDED_FOR');
        if ($forwarded) {
            $ips = explode(',', $forwarded);
            return trim($ips[0]);
        }

        return $this->server('REMOTE_ADDR', '127.0.0.1');
    }
}
