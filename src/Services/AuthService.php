<?php
/**
 * ACTIO - Authentication Service
 * 
 * Handles user authentication against DEV mode credentials or SELIO database.
 * 
 * Security Requirements:
 * - E06: DEV accounts only in development mode
 * - E01: Credentials in .env file
 * 
 * @package Actio\Services
 */

declare(strict_types=1);

namespace Actio\Services;

class AuthService
{
    /**
     * Path to settings.json for role lookup
     */
    private const SETTINGS_FILE = BASE_PATH . '/data/settings.json';

    /**
     * Authenticate a user
     * 
     * In DEV_MODE: Accepts any credentials and returns dev user
     * In production: Authenticates against SELIO SQL Server database
     * 
     * @param string $login User login ID
     * @param string $password User password
     * @return array|null User data on success, null on failure
     */
    public function authenticate(string $login, string $password): ?array
    {
        // Trim inputs
        $login = trim($login);
        $password = trim($password);

        // Validate inputs
        if (empty($login) || empty($password)) {
            return null;
        }

        // DEV_MODE authentication (E06)
        if ($this->isDevMode()) {
            return $this->authenticateDev($login);
        }

        // Production: SELIO database authentication
        return $this->authenticateSelio($login, $password);
    }

    /**
     * DEV mode authentication - returns dev user from .env
     * Only works when APP_ENV=development AND DEV_MODE=true (E06)
     */
    private function authenticateDev(string $login): array
    {
        return [
            'login_id' => $login,
            'name' => $_ENV['DEV_USER_NAME'] ?? 'Developer',
            'email' => $_ENV['DEV_USER_EMAIL'] ?? 'dev@actio.local',
            'role' => $_ENV['DEV_USER_ROLE'] ?? 'admin',
        ];
    }

    /**
     * SELIO database authentication
     * Authenticates against Oil_LOG_LogData table in SELIO SQL Server database
     */
    private function authenticateSelio(string $login, string $password): ?array
    {
        // Get connection parameters from .env
        $serverName = $_ENV['SELIO_DB_SERVER'] ?? 'ocm-oiles\\sqlexpress';
        $database = $_ENV['SELIO_DB_NAME'] ?? 'Selio';
        $username = $_ENV['SELIO_DB_USER'] ?? 'Selio';
        $dbPassword = $_ENV['SELIO_DB_PASSWORD'] ?? '';

        // Connection options
        $connectionOptions = [
            "Database" => $database,
            "Uid" => $username,
            "PWD" => $dbPassword,
            "CharacterSet" => "UTF-8",
            "LoginTimeout" => 5,
        ];

        // Connect to SQL Server
        $conn = @sqlsrv_connect($serverName, $connectionOptions);

        if ($conn === false) {
            error_log('SELIO DB connection failed: ' . print_r(sqlsrv_errors(), true));
            return null;
        }

        try {
            // Query user from database (C03 - Prepared Statement)
            $query = "SELECT login_id, MD5_hash, jmeno, prijmeni, active_ 
                      FROM Oil_LOG_LogData 
                      WHERE login_id = ? AND active_ = 1";

            $params = [$login];
            $options = ["Scrollable" => SQLSRV_CURSOR_KEYSET];
            $result = sqlsrv_query($conn, $query, $params, $options);

            if ($result === false) {
                error_log('SELIO DB query failed: ' . print_r(sqlsrv_errors(), true));
                return null;
            }

            $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);

            if (!$row) {
                // User not found or not active
                return null;
            }

            // Verify password (MD5 hash comparison)
            // Note: MD5 is not secure but required for SELIO compatibility
            $storedHash = $row['MD5_hash'];
            $enteredHash = md5($password);

            if ($enteredHash !== $storedHash) {
                // Invalid password
                return null;
            }

            // Build user data
            $fullName = trim($row['jmeno'] . ' ' . $row['prijmeni']);
            $role = $this->getUserRole($login);

            return [
                'login_id' => $row['login_id'],
                'name' => $fullName,
                'email' => $login, // SELIO uses login_id as identifier
                'role' => $role,
            ];

        } finally {
            sqlsrv_close($conn);
        }
    }

    /**
     * Get user role from settings.json
     * Falls back to default_role if user not found
     */
    private function getUserRole(string $loginId): string
    {
        $defaultRole = 'viewer';

        if (!file_exists(self::SETTINGS_FILE)) {
            return $defaultRole;
        }

        $content = file_get_contents(self::SETTINGS_FILE);
        if ($content === false) {
            return $defaultRole;
        }

        $settings = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $defaultRole;
        }

        // Check roles mapping
        $roles = $settings['roles'] ?? [];
        if (isset($roles[$loginId])) {
            return $roles[$loginId];
        }

        // Return default role
        return $settings['default_role'] ?? $defaultRole;
    }

    /**
     * Check if application is in development mode (E06)
     */
    private function isDevMode(): bool
    {
        return ($_ENV['APP_ENV'] ?? 'development') === 'development'
            && ($_ENV['DEV_MODE'] ?? 'false') === 'true';
    }
}
