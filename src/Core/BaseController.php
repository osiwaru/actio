<?php
/**
 * ACTIO - Base Controller
 * 
 * Abstract controller providing common functionality for all controllers.
 * 
 * Features:
 * - Request handling
 * - CSRF validation
 * - Permission checks
 * - JSON responses
 * 
 * @package Actio\Core
 */

declare(strict_types=1);

namespace Actio\Core;

abstract class BaseController
{
    /**
     * HTTP Request instance
     */
    protected Request $request;

    /**
     * Create controller instance
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Validate CSRF token from request (C07)
     * 
     * @param string|null $redirectUrl URL to redirect on failure
     * @return bool True if valid, false if invalid (and redirected)
     */
    protected function validateCsrf(?string $redirectUrl = null): bool
    {
        $token = $this->request->input('_csrf_token', '');
        
        if (!verifyCsrfToken($token)) {
            flash('error', 'Neplatný bezpečnostní token. Zkuste to znovu.');
            
            if ($redirectUrl) {
                Response::redirect($redirectUrl);
            }
            
            return false;
        }
        
        return true;
    }

    /**
     * Require user to have edit permission
     * Sends 403 response if not authorized
     * 
     * @param string $message Custom error message
     * @return bool True if authorized
     */
    protected function requireCanEdit(string $message = 'Nemáte oprávnění k této akci.'): bool
    {
        if (!Auth::canEdit()) {
            Response::forbidden($message);
            return false;
        }
        return true;
    }

    /**
     * Require user to be auditor or admin
     * Sends 403 response if not authorized
     * 
     * @param string $message Custom error message
     * @return bool True if authorized
     */
    protected function requireAuditor(string $message = 'Nemáte oprávnění k této akci.'): bool
    {
        if (!Auth::isAuditor()) {
            Response::forbidden($message);
            return false;
        }
        return true;
    }

    /**
     * Require user to be admin
     * Sends 403 response if not authorized
     * 
     * @param string $message Custom error message
     * @return bool True if authorized
     */
    protected function requireAdmin(string $message = 'Nemáte oprávnění k této akci.'): bool
    {
        if (!Auth::isAdmin()) {
            Response::forbidden($message);
            return false;
        }
        return true;
    }

    /**
     * Send JSON response
     * 
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        Response::json($data, $statusCode);
    }

    /**
     * Send success JSON response
     * 
     * @param string $message Success message
     * @param array $data Additional data
     */
    protected function jsonSuccess(string $message, array $data = []): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Send error JSON response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     */
    protected function jsonError(string $message, int $statusCode = 400): void
    {
        $this->json([
            'success' => false,
            'error' => $message,
        ], $statusCode);
    }

    /**
     * Get input value from request with default
     * 
     * @param string $key Input key
     * @param mixed $default Default value
     * @return mixed Input value or default
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $this->request->input($key, $default);
    }

    /**
     * Get all input from request
     * 
     * @return array All input data
     */
    protected function allInput(): array
    {
        return $this->request->all();
    }

    /**
     * Check if request has input key
     * 
     * @param string $key Input key
     * @return bool True if key exists
     */
    protected function hasInput(string $key): bool
    {
        return $this->request->has($key);
    }

    /**
     * Get query parameter from request
     * 
     * @param string $key Query key
     * @param mixed $default Default value
     * @return mixed Query value or default
     */
    protected function query(string $key, mixed $default = null): mixed
    {
        return $this->request->query($key, $default);
    }

    /**
     * Redirect to URL with optional flash message
     * 
     * @param string $url URL to redirect to
     * @param string|null $flashKey Flash message key
     * @param string|null $flashMessage Flash message content
     */
    protected function redirect(string $url, ?string $flashKey = null, ?string $flashMessage = null): void
    {
        if ($flashKey && $flashMessage) {
            flash($flashKey, $flashMessage);
        }
        
        Response::redirect($url);
    }
}
