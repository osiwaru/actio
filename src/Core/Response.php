<?php
/**
 * ACTIO - HTTP Response Handler
 * 
 * Provides methods for sending HTTP responses (HTML, JSON, redirects).
 * 
 * @package Actio\Core
 */

declare(strict_types=1);

namespace Actio\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $body = '';

    /**
     * Set HTTP status code
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Add a response header
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set response body
     */
    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Send the response
     */
    public function send(): void
    {
        // Set status code
        http_response_code($this->statusCode);

        // Set headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Output body
        echo $this->body;
    }

    /**
     * Send HTML response
     */
    public static function html(string $content, int $status = 200): void
    {
        $response = new self();
        $response
            ->setStatusCode($status)
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody($content)
            ->send();
    }

    /**
     * Send JSON response
     */
    public static function json(mixed $data, int $status = 200): void
    {
        $response = new self();
        $response
            ->setStatusCode($status)
            ->setHeader('Content-Type', 'application/json; charset=UTF-8')
            ->setBody(json_encode($data, JSON_UNESCAPED_UNICODE))
            ->send();
    }

    /**
     * Redirect to another URL
     */
    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: $url");
        exit;
    }

    /**
     * Send 404 Not Found
     */
    public static function notFound(string $message = 'Page not found'): void
    {
        self::html("<h1>404 - Not Found</h1><p>$message</p>", 404);
    }

    /**
     * Send 403 Forbidden
     */
    public static function forbidden(string $message = 'Access denied'): void
    {
        self::html("<h1>403 - Forbidden</h1><p>$message</p>", 403);
    }

    /**
     * Send 500 Internal Server Error
     */
    public static function error(string $message = 'Internal server error'): void
    {
        self::html("<h1>500 - Error</h1><p>$message</p>", 500);
    }

    /**
     * Render a view file with data
     * 
     * Uses output buffering to capture view content.
     * C04 - All data should be escaped in views using h() or htmlspecialchars()
     */
    public static function view(string $view, array $data = [], int $status = 200): void
    {
        $viewPath = BASE_PATH . '/views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            self::error("View not found: $view");
            return;
        }

        // Extract data to make variables available in view
        extract($data);

        // Capture view output
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        self::html($content, $status);
    }

    /**
     * Render a view with layout
     * 
     * Wraps view content in header + sidebar + footer layout
     */
    public static function viewWithLayout(
        string $view,
        array $data = [],
        int $status = 200,
        string $pageTitle = 'ACTIO'
    ): void {
        $viewPath = BASE_PATH . '/views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            self::error("View not found: $view");
            return;
        }

        // Add page title to data
        $data['pageTitle'] = $pageTitle;

        // Extract data to make variables available in views
        extract($data);

        // Capture main content
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Render full layout
        ob_start();
        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/layout/sidebar.php';

        // Main content wrapper
        echo '<div class="main-content">';
        require BASE_PATH . '/views/layout/topbar.php';
        echo '<main class="p-3 p-lg-4">';
        echo $content;
        echo '</main>';
        echo '</div>';

        require BASE_PATH . '/views/layout/footer.php';
        $fullContent = ob_get_clean();

        self::html($fullContent, $status);
    }

    /**
     * Render a standalone view (without layout)
     * 
     * Used for login page, error pages, and other standalone content.
     */
    public static function viewStandalone(string $view, array $data = [], int $status = 200): void
    {
        $viewPath = BASE_PATH . '/views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            self::error("View not found: $view");
            return;
        }

        // Extract data to make variables available in view
        extract($data);

        // Capture view output
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        self::html($content, $status);
    }
}
