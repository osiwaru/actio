<?php
/**
 * ACTIO - Authentication & Session Management
 * 
 * Handles user session, login/logout, and role-based access control.
 * 
 * Security Requirements:
 * - I05: Session ID regeneration after login
 * - I01: Role-Based Access Control (RBAC)
 * 
 * @package Actio\Core
 */

declare(strict_types=1);

namespace Actio\Core;

class Auth
{
    /**
     * Session key for storing user data
     */
    private const SESSION_KEY = 'actio_user';

    /**
     * Log in a user and regenerate session ID (I05)
     * 
     * @param array $userData User data to store in session
     */
    public static function login(array $userData): void
    {
        // Regenerate session ID to prevent session fixation (I05)
        session_regenerate_id(true);

        $_SESSION[self::SESSION_KEY] = [
            'login_id' => $userData['login_id'] ?? '',
            'name' => $userData['name'] ?? '',
            'email' => $userData['email'] ?? '',
            'role' => $userData['role'] ?? 'viewer',
            'logged_in_at' => time(),
        ];
    }

    /**
     * Log out the current user
     */
    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);

        // Regenerate session ID for security
        session_regenerate_id(true);
    }

    /**
     * Check if a user is currently logged in
     */
    public static function check(): bool
    {
        // In DEV_MODE, always consider logged in
        if (self::isDevMode()) {
            return true;
        }

        return isset($_SESSION[self::SESSION_KEY])
            && !empty($_SESSION[self::SESSION_KEY]['login_id']);
    }

    /**
     * Get the current logged-in user data
     * 
     * @return array|null User data or null if not logged in
     */
    public static function user(): ?array
    {
        // In DEV_MODE, return dev user from .env (E06)
        if (self::isDevMode()) {
            return [
                'login_id' => $_ENV['DEV_USER_EMAIL'] ?? 'dev@actio.local',
                'name' => $_ENV['DEV_USER_NAME'] ?? 'Developer',
                'email' => $_ENV['DEV_USER_EMAIL'] ?? 'dev@actio.local',
                'role' => $_ENV['DEV_USER_ROLE'] ?? 'admin',
            ];
        }

        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    /**
     * Get current user's role
     */
    public static function role(): string
    {
        $user = self::user();
        return $user['role'] ?? 'viewer';
    }

    /**
     * Check if user has a specific role (I01)
     */
    public static function hasRole(string $role): bool
    {
        return self::role() === $role;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }

    /**
     * Check if user is auditor or higher
     */
    public static function isAuditor(): bool
    {
        return in_array(self::role(), ['admin', 'auditor']);
    }

    /**
     * Check if user can edit (editor, auditor, or admin)
     */
    public static function canEdit(): bool
    {
        return in_array(self::role(), ['admin', 'auditor', 'editor']);
    }

    /**
     * Check if application is in development mode (E06)
     */
    private static function isDevMode(): bool
    {
        return ($_ENV['APP_ENV'] ?? 'development') === 'development'
            && ($_ENV['DEV_MODE'] ?? 'false') === 'true';
    }

    /**
     * Get user's display name
     */
    public static function userName(): string
    {
        $user = self::user();
        return $user['name'] ?? 'Unknown';
    }

    /**
     * Get user's login ID
     */
    public static function loginId(): string
    {
        $user = self::user();
        return $user['login_id'] ?? '';
    }
}
