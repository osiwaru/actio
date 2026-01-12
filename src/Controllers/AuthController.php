<?php
/**
 * ACTIO - Authentication Controller
 * 
 * Handles login and logout HTTP requests.
 * 
 * Security Requirements:
 * - C06, C07: CSRF token validation
 * - I05: Session regeneration (via Auth class)
 * 
 * @package Actio\Controllers
 */

declare(strict_types=1);

namespace Actio\Controllers;

use Actio\Core\Auth;
use Actio\Core\Request;
use Actio\Core\Response;
use Actio\Services\AuthService;

class AuthController
{
    private Request $request;
    private AuthService $authService;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->authService = new AuthService();
    }

    /**
     * Display login form
     * GET /login
     */
    public function showLogin(array $params = []): void
    {
        // If already logged in, redirect to dashboard
        if (Auth::check() && !$this->isDevModeAutoLogin()) {
            Response::redirect(url('/'));
            return;
        }

        // Get flash messages
        $error = flash('login_error');

        Response::viewStandalone('auth/login', [
            'error' => $error,
            'pageTitle' => 'Přihlášení | ACTIO',
        ]);
    }

    /**
     * Process login form
     * POST /login
     */
    public function login(array $params = []): void
    {
        // Validate CSRF token (C07)
        $csrfToken = $this->request->input('_csrf_token', '');
        if (!verifyCsrfToken($csrfToken)) {
            flash('login_error', 'Neplatný bezpečnostní token. Zkuste to znovu.');
            Response::redirect(url('/login'));
            return;
        }

        // Get credentials
        $login = $this->request->input('login', '');
        $password = $this->request->input('password', '');

        // Validate inputs
        if (empty($login) || empty($password)) {
            flash('login_error', 'Vyplňte prosím přihlašovací jméno a heslo.');
            Response::redirect(url('/login'));
            return;
        }

        // Authenticate
        $userData = $this->authService->authenticate($login, $password);

        if ($userData === null) {
            flash('login_error', 'Nesprávné přihlašovací údaje nebo účet není aktivní.');
            Response::redirect(url('/login'));
            return;
        }

        // Log in the user (includes session regeneration - I05)
        Auth::login($userData);

        // Redirect to dashboard
        Response::redirect(url('/'));
    }

    /**
     * Process logout
     * POST /logout
     */
    public function logout(array $params = []): void
    {
        // Validate CSRF token for logout (C07)
        $csrfToken = $this->request->input('_csrf_token', '');
        if (!verifyCsrfToken($csrfToken)) {
            Response::redirect(url('/'));
            return;
        }

        // Log out
        Auth::logout();

        // Flash success message
        flash('login_success', 'Byli jste úspěšně odhlášeni.');

        // Redirect to login
        Response::redirect(url('/login'));
    }

    /**
     * Check if we're in DEV_MODE with auto-login
     */
    private function isDevModeAutoLogin(): bool
    {
        return ($_ENV['APP_ENV'] ?? 'development') === 'development'
            && ($_ENV['DEV_MODE'] ?? 'false') === 'true';
    }
}
