<?php
/**
 * ACTIO - Application Bootstrap
 * 
 * Initializes the application, loads configuration, and sets up error handling.
 * 
 * @package Actio\Core
 */

declare(strict_types=1);

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load environment variables (E01, E04)
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^["\'](.*)["\']\s*$/', $value, $matches)) {
                $value = $matches[1];
            }

            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Validate required environment variables (E04)
$requiredEnv = ['APP_NAME', 'APP_ENV'];
foreach ($requiredEnv as $var) {
    if (empty($_ENV[$var] ?? getenv($var))) {
        die("Missing required environment variable: $var. Please copy .env.example to .env");
    }
}

// Set timezone
date_default_timezone_set('Europe/Prague');

// Session configuration (I02, I03, I04)
$isSecure = ($_ENV['APP_ENV'] ?? 'development') === 'production';
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
if ($isSecure) {
    ini_set('session.cookie_secure', '1');
}
ini_set('session.name', $_ENV['SESSION_NAME'] ?? 'actio_session');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Get environment variable
 */
function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

/**
 * Check if in development mode (E06)
 */
function isDevMode(): bool
{
    return env('APP_ENV') === 'development' && env('DEV_MODE') === 'true';
}

/**
 * Escape HTML output (C04 - XSS prevention)
 * Shorthand for htmlspecialchars
 */
function h(?string $string): string
{
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Generate URL for a route
 */
function url(string $path = ''): string
{
    $baseUrl = env('APP_URL', 'http://localhost:8080');
    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}

/**
 * Get asset URL
 */
function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

/**
 * Generate CSRF token (C06)
 */
function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate CSRF token field for forms (C06)
 */
function csrfField(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . h(csrfToken()) . '">';
}

/**
 * Verify CSRF token (C07)
 */
function verifyCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get flashed session message
 */
function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

/**
 * Check if user has a flash message
 */
function hasFlash(string $key): bool
{
    return isset($_SESSION['flash'][$key]);
}

/**
 * Get current logged-in user
 * Uses Auth class for proper session management
 */
function currentUser(): ?array
{
    return \Actio\Core\Auth::user();
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool
{
    return \Actio\Core\Auth::check();
}

// ============================================================================
// AUTOLOADER
// ============================================================================

spl_autoload_register(function (string $class) {
    // Only autoload Actio namespace
    if (!str_starts_with($class, 'Actio\\')) {
        return;
    }

    // Convert namespace to path
    $path = str_replace('Actio\\', '', $class);
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
    $file = BASE_PATH . '/src/' . $path . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ============================================================================
// ROUTER INITIALIZATION
// ============================================================================

use Actio\Core\Request;
use Actio\Core\Router;
use Actio\Core\Response;
use Actio\Core\Auth;

// Create request and router
$request = new Request();
$router = new Router($request);

// Load routes
$router->loadRoutes(BASE_PATH . '/config/routes.php');

// Add auth middleware (C02 - Auth on all endpoints)
$publicPaths = ['/login'];
$router->addMiddleware(function (Request $req) use ($publicPaths) {
    $path = $req->getPath();

    // Allow public paths
    if (in_array($path, $publicPaths)) {
        return true;
    }

    // Check if logged in
    if (!Auth::check()) {
        Response::redirect(url('/login'));
        return false;
    }

    return true;
});

// Dispatch the request
$router->dispatch();