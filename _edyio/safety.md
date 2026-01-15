# Security & Safety Checklist - 8D Viewer

## Přehled

Tento dokument definuje bezpečnostní požadavky pro 8D Viewer aplikaci. Všechna kritická a vysoká rizika musí být implementována před nasazením do produkce.

## Doporučená struktura projektu

```
8d-viewer/
├── index.php
├── config.php
├── .env (NIKDY necommitovat!)
├── .env.example
├── api/
│   ├── load-cases.php
│   ├── load-case.php
│   ├── save-case.php
│   ├── export-pdf.php
│   └── sharepoint-connector.php
├── core/
│   ├── Security.php (CSRF, rate limiting, file validation)
│   ├── Auth.php (session management, RBAC)
│   ├── Database.php (JSON store s atomickými zápisy)
│   ├── ErrorHandler.php
│   └── helpers.php (XSS escapování - h() funkce)
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── navigation.php
├── templates/
│   ├── case-list.php
│   ├── case-detail.php
│   └── d1-d8-sections.php
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── data/ (JSON soubory - správná oprávnění!)
└── logs/ (error logy - 700 permissions)
```

---

## Kritická rizika (MUST HAVE)

### 1. CSRF Ochrana
- **Kde implementovat:** `core/Security.php` třída `Csrf`
- **Požadavek:** CSRF token na všech POST/PUT/DELETE requestech
- **Implementace:**
  ```php
  // Generování tokenu v session
  class Csrf {
      public static function generateToken() {
          if (!isset($_SESSION['csrf_token'])) {
              $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
          }
          return $_SESSION['csrf_token'];
      }
      
      public static function validateToken($token) {
          if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
              http_response_code(403);
              die('CSRF token validation failed');
          }
      }
  }
  
  // V HTML formulářích
  <input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">
  
  // V API endpointech (api/save-case.php)
  Csrf::validateToken($_POST['csrf_token'] ?? '');
  ```
- **Status:** [ ] TODO
- **Priorita:** P0 - KRITICKÉ

### 2. Rate Limiting
- **Kde implementovat:** `core/Security.php` třída `RateLimiter`
- **Požadavek:** Omezení počtu requestů per IP/session
- **Scénáře:**
  - Login: Max 5 pokusů / 15 minut per IP
  - API save: Max 100 requestů / hodinu per session
  - Export PDF: Max 50 exportů / hodinu per session
- **Implementace:**
  ```php
  class RateLimiter {
      public static function check($action, $identifier, $maxAttempts, $windowSeconds) {
          $key = "ratelimit_{$action}_{$identifier}";
          $attempts = $_SESSION[$key]['count'] ?? 0;
          $window_start = $_SESSION[$key]['start'] ?? time();
          
          if (time() - $window_start > $windowSeconds) {
              $_SESSION[$key] = ['count' => 1, 'start' => time()];
              return true;
          }
          
          if ($attempts >= $maxAttempts) {
              http_response_code(429);
              die('Rate limit exceeded');
          }
          
          $_SESSION[$key]['count']++;
          return true;
      }
  }
  
  // Použití v API
  RateLimiter::check('api_save', $_SERVER['REMOTE_ADDR'], 100, 3600);
  ```
- **Status:** [ ] TODO
- **Priorita:** P0 - KRITICKÉ

### 3. Session Security
- **Kde implementovat:** `core/Auth.php`
- **Požadavek:** 
  - Session regeneration po přihlášení
  - Secure cookie flags
  - Session timeout
- **Implementace:**
  ```php
  class Auth {
      public static function initSession() {
          ini_set('session.cookie_httponly', 1);
          ini_set('session.cookie_secure', 1);
          ini_set('session.cookie_samesite', 'Strict');
          ini_set('session.use_strict_mode', 1);
          
          session_start();
          
          // Session timeout (30 minut)
          if (isset($_SESSION['last_activity']) && 
              (time() - $_SESSION['last_activity'] > 1800)) {
              session_unset();
              session_destroy();
              session_start();
          }
          $_SESSION['last_activity'] = time();
      }
      
      public static function login($user) {
          session_regenerate_id(true); // Regenerace po loginu
          $_SESSION['user'] = $user;
          $_SESSION['login_time'] = time();
      }
      
      public static function logout() {
          session_unset();
          session_destroy();
      }
  }
  ```
- **Status:** [ ] TODO
- **Priorita:** P0 - KRITICKÉ

### 4. File Upload Validace
- **Kde implementovat:** `core/Security.php` třída `FileUpload`
- **Požadavek:** MIME type validace, ne pouze přípona
- **Povolené typy pro 8D Viewer:**
  - JSON: `application/json`
  - PDF: `application/pdf`
  - Obrázky: `image/jpeg`, `image/png`, `image/gif`, `image/webp`
  - Word: `application/vnd.openxmlformats-officedocument.wordprocessingml.document`
  - Excel: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- **Implementace:**
  ```php
  class FileUpload {
      private static $allowedMimes = [
          'application/json',
          'application/pdf',
          'image/jpeg',
          'image/png',
          'image/gif',
          'image/webp'
      ];
      
      public static function validate($file) {
          // Kontrola existence
          if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
              throw new Exception('Invalid upload');
          }
          
          // MIME type validace (ne jen přípona!)
          $finfo = finfo_open(FILEINFO_MIME_TYPE);
          $mimeType = finfo_file($finfo, $file['tmp_name']);
          finfo_close($finfo);
          
          if (!in_array($mimeType, self::$allowedMimes)) {
              throw new Exception('Invalid file type');
          }
          
          // Velikost (max 10MB)
          if ($file['size'] > 10 * 1024 * 1024) {
              throw new Exception('File too large');
          }
          
          return true;
      }
  }
  ```
- **Status:** [ ] TODO
- **Priorita:** P0 - KRITICKÉ

### 5. Environment Variables (.env)
- **Kde implementovat:** `.env` soubor (root projektu)
- **Požadavek:** Všechny citlivé údaje v `.env`, NIKDY v kódu
- **Obsah `.env` pro 8D Viewer:**
  ```ini
  # Application
  APP_ENV=production
  APP_DEBUG=false
  
  # Paths
  DATA_PATH=/var/www/8d-viewer/data
  LOG_PATH=/var/www/8d-viewer/logs
  
  # Security
  CSRF_SECRET=<random_64_chars>
  SESSION_SECRET=<random_64_chars>
  
  # Email notifications
  SMTP_HOST=smtp.example.com
  SMTP_PORT=587
  SMTP_USER=notifications@example.com
  SMTP_PASS=<password>
  SMTP_FROM=8d-viewer@example.com
  
  # SharePoint (pokud používáno)
  SP_CLIENT_ID=<client_id>
  SP_CLIENT_SECRET=<client_secret>
  SP_TENANT_ID=<tenant_id>
  
  # Optional: Teams notifications
  TEAMS_WEBHOOK_URL=https://...
  ```
  
  **Vytvoř také `.env.example` (BEZ citlivých dat):**
  ```ini
  APP_ENV=production
  APP_DEBUG=false
  DATA_PATH=/path/to/data
  SMTP_HOST=smtp.example.com
  SMTP_USER=user@example.com
  # atd... (bez skutečných hodnot)
  ```
  
  **Načítání v `config.php`:**
  ```php
  // Jednoduchý .env loader
  function loadEnv($path) {
      if (!file_exists($path)) {
          die('.env file not found');
      }
      
      $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      foreach ($lines as $line) {
          if (strpos(trim($line), '#') === 0) continue;
          
          list($name, $value) = explode('=', $line, 2);
          $name = trim($name);
          $value = trim($value);
          
          if (!array_key_exists($name, $_ENV)) {
              putenv("$name=$value");
              $_ENV[$name] = $value;
          }
      }
  }
  
  loadEnv(__DIR__ . '/.env');
  ```
  
  **KRITICKY DŮLEŽITÉ:**
  ```
  # .gitignore
  .env
  /data/*.json
  /logs/*.log
  ```
- **Status:** [ ] TODO
- **Priorita:** P0 - KRITICKÉ

### 6. Path Traversal Protection
- **Kde implementovat:** `core/Database.php` + `api/load-case.php`
- **Požadavek:** Zabránit `../` útokům při načítání souborů
- **Implementace:**
  ```php
  class Database {
      private $basePath;
      
      public function __construct($basePath) {
          $this->basePath = realpath($basePath);
      }
      
      public function loadCase($filename) {
          // Sanitizace filename
          $filename = basename($filename); // Odstraní path
          $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
          
          $filepath = $this->basePath . '/' . $filename;
          $realpath = realpath($filepath);
          
          // Kontrola, že soubor je v povoleném adresáři
          if ($realpath === false || strpos($realpath, $this->basePath) !== 0) {
              throw new Exception('Invalid file path');
          }
          
          if (!file_exists($realpath)) {
              throw new Exception('File not found');
          }
          
          return json_decode(file_get_contents($realpath), true);
      }
  }
  ```
- **Status:** [ ] TODO
- **Priorita:** P0 - KRITICKÉ

---

## Vysoká rizika (SHOULD HAVE)

### 7. XSS Escapování
- **Kde implementovat:** `core/helpers.php`
- **Požadavek:** Helper funkce `h()` pro escapování outputu ve všech templates
- **Implementace:**
  ```php
  // core/helpers.php
  function h($string) {
      if ($string === null) return '';
      return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }
  
  function json_h($data) {
      return htmlspecialchars(json_encode($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }
  
  // Použití v templates/case-detail.php
  <h1><?= h($case['meta']['nazev']) ?></h1>
  <td><?= h($case['D1']['must_have']['vedouci_tymu']['jmeno']) ?></td>
  
  // Pro JSON data v JavaScript
  <script>
  const caseData = <?= json_h($case) ?>;
  </script>
  ```
- **Status:** [ ] TODO
- **Priorita:** P1

### 8. Security Headers
- **Kde implementovat:** `includes/header.php` nebo `index.php`
- **Požadavek:** Nastavit bezpečnostní HTTP hlavičky
- **Implementace:**
  ```php
  // Na začátku každého PHP souboru (nebo v header.php)
  header('X-Frame-Options: DENY');
  header('X-Content-Type-Options: nosniff');
  header('X-XSS-Protection: 1; mode=block');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  
  // Pouze přes HTTPS v produkci
  if ($_ENV['APP_ENV'] === 'production') {
      header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
  }
  
  // Content Security Policy (upravit podle potřeby)
  header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
  ```
- **Status:** [ ] TODO
- **Priorita:** P1

### 9. RBAC (Role-Based Access Control)
- **Kde implementovat:** `core/Auth.php`
- **Požadavek:** Kontrola oprávnění na každém requestu
- **Role pro 8D Viewer:**
  - `admin` - plný přístup (read, write, delete, export, settings)
  - `quality_manager` - read all, write all, export, no delete, no settings
  - `team_member` - read assigned cases, write assigned cases, export assigned
  - `viewer` - read only assigned cases
- **Implementace:**
  ```php
  class Auth {
      private static $permissions = [
          'admin' => ['*'],
          'quality_manager' => ['cases.read', 'cases.write', 'cases.export'],
          'team_member' => ['cases.read_assigned', 'cases.write_assigned', 'cases.export_assigned'],
          'viewer' => ['cases.read_assigned']
      ];
      
      public static function can($permission, $caseId = null) {
          $user = $_SESSION['user'] ?? null;
          if (!$user) return false;
          
          $role = $user['role'] ?? 'viewer';
          $userPermissions = self::$permissions[$role] ?? [];
          
          // Admin má vše
          if (in_array('*', $userPermissions)) return true;
          
          // Kontrola oprávnění
          if (in_array($permission, $userPermissions)) {
              // Pokud je to "assigned" permission, kontroluj vlastnictví
              if (strpos($permission, '_assigned') !== false && $caseId) {
                  return self::isAssignedToCase($user['id'], $caseId);
              }
              return true;
          }
          
          return false;
      }
      
      public static function requirePermission($permission, $caseId = null) {
          if (!self::can($permission, $caseId)) {
              http_response_code(403);
              die('Access denied');
          }
      }
      
      private static function isAssignedToCase($userId, $caseId) {
          // Načti case a kontroluj, zda je user v týmu
          $db = new Database($_ENV['DATA_PATH']);
          $case = $db->loadCase($caseId);
          
          $teamMembers = $case['D1']['must_have']['clenove'] ?? [];
          foreach ($teamMembers as $member) {
              if ($member['user_id'] === $userId) {
                  return true;
              }
          }
          
          return false;
      }
  }
  
  // Použití v API
  // api/save-case.php
  Auth::requirePermission('cases.write', $_POST['case_id']);
  
  // api/load-case.php
  Auth::requirePermission('cases.read', $_GET['case_id']);
  ```
- **Status:** [ ] TODO
- **Priorita:** P1

### 10. Error Handling & Logging
- **Kde implementovat:** `core/ErrorHandler.php`
- **Požadavek:** Logy do souboru, zobrazování chyb pouze ve vývoji
- **Implementace:**
  ```php
  class ErrorHandler {
      public static function init() {
          $logPath = $_ENV['LOG_PATH'] ?? __DIR__ . '/../logs';
          $isDevelopment = $_ENV['APP_ENV'] === 'development';
          
          // Error reporting
          error_reporting(E_ALL);
          ini_set('display_errors', $isDevelopment ? '1' : '0');
          ini_set('log_errors', '1');
          ini_set('error_log', $logPath . '/php_errors.log');
          
          // Custom error handler
          set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logPath, $isDevelopment) {
              $message = date('[Y-m-d H:i:s] ') . "Error [$errno]: $errstr in $errfile on line $errline\n";
              error_log($message, 3, $logPath . '/app_errors.log');
              
              if ($isDevelopment) {
                  echo "<pre>$message</pre>";
              } else {
                  echo "An error occurred. Please contact support.";
              }
          });
          
          // Exception handler
          set_exception_handler(function($exception) use ($logPath, $isDevelopment) {
              $message = date('[Y-m-d H:i:s] ') . "Exception: " . $exception->getMessage() . 
                         " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n" .
                         $exception->getTraceAsString() . "\n";
              error_log($message, 3, $logPath . '/app_errors.log');
              
              if ($isDevelopment) {
                  echo "<pre>$message</pre>";
              } else {
                  http_response_code(500);
                  echo "An error occurred. Please contact support.";
              }
          });
      }
      
      public static function log($message, $level = 'INFO') {
          $logPath = $_ENV['LOG_PATH'] ?? __DIR__ . '/../logs';
          $logMessage = date('[Y-m-d H:i:s] ') . "[$level] $message\n";
          error_log($logMessage, 3, $logPath . '/app.log');
      }
  }
  
  // Zavolat na začátku aplikace (index.php)
  ErrorHandler::init();
  ```
- **Status:** [ ] TODO
- **Priorita:** P1

### 11. Atomické File Zápisy
- **Kde implementovat:** `core/Database.php`
- **Požadavek:** Write to temp → rename (prevence korupce při paralelních zápisech)
- **Implementace:**
  ```php
  class Database {
      public function saveCase($filename, $data) {
          // Validace filename
          $filename = basename($filename);
          $filepath = $this->basePath . '/' . $filename;
          
          // JSON encode
          $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
          if ($json === false) {
              throw new Exception('JSON encoding failed');
          }
          
          // Atomický zápis: temp → rename
          $tempFile = $filepath . '.tmp.' . uniqid();
          
          // Zápis do temp souboru s file lock
          if (file_put_contents($tempFile, $json, LOCK_EX) === false) {
              throw new Exception('Failed to write temp file');
          }
          
          // Atomické přejmenování (rename je atomické na většině systémů)
          if (!rename($tempFile, $filepath)) {
              unlink($tempFile);
              throw new Exception('Failed to rename temp file');
          }
          
          // Nastavení oprávnění
          chmod($filepath, 0644);
          
          return true;
      }
  }
  ```
- **Status:** [ ] TODO
- **Priorita:** P1

### 12. Input Validation
- **Kde implementovat:** `core/Security.php` třída `Validator`
- **Požadavek:** Validace všech uživatelských vstupů
- **Implementace:**
  ```php
  class Validator {
      public static function validateCaseId($id) {
          // PC-XXX formát
          if (!preg_match('/^PC-\d{1,6}$/', $id)) {
              throw new Exception('Invalid case ID format');
          }
          return $id;
      }
      
      public static function validateFilename($filename) {
          // 8D_PC-XXX_v1.0.json formát
          if (!preg_match('/^8D_PC-\d{1,6}_v\d+\.\d+\.json$/', $filename)) {
              throw new Exception('Invalid filename format');
          }
          return $filename;
      }
      
      public static function validateEmail($email) {
          $email = filter_var($email, FILTER_VALIDATE_EMAIL);
          if ($email === false) {
              throw new Exception('Invalid email format');
          }
          return $email;
      }
      
      public static function sanitizeString($string, $maxLength = 255) {
          $string = strip_tags($string);
          $string = trim($string);
          return substr($string, 0, $maxLength);
      }
  }
  
  // Použití v API
  $caseId = Validator::validateCaseId($_POST['case_id']);
  $email = Validator::validateEmail($_POST['email']);
  ```
- **Status:** [ ] TODO
- **Priorita:** P1

---

## Střední rizika (NICE TO HAVE)

### 13. File Upload Limits
- **Požadavek:** Omezení velikosti uploadů
- **Implementace:**
  ```php
  // php.ini nebo .htaccess
  upload_max_filesize = 10M
  post_max_size = 10M
  max_file_uploads = 20
  
  // V kódu (core/Security.php)
  class FileUpload {
      const MAX_SIZE = 10 * 1024 * 1024; // 10MB
      
      public static function checkSize($file) {
          if ($file['size'] > self::MAX_SIZE) {
              throw new Exception('File too large (max 10MB)');
          }
      }
  }
  ```
- **Status:** [ ] TODO
- **Priorita:** P2

### 14. Audit Log
- **Požadavek:** Logování změn v 8D případech
- **Implementace:**
  ```php
  class AuditLog {
      public static function record($action, $caseId, $details = []) {
          $logPath = $_ENV['LOG_PATH'] ?? __DIR__ . '/../logs';
          $entry = [
              'timestamp' => date('Y-m-d H:i:s'),
              'user_id' => $_SESSION['user']['id'] ?? 'anonymous',
              'user_name' => $_SESSION['user']['name'] ?? 'Anonymous',
              'ip' => $_SERVER['REMOTE_ADDR'],
              'action' => $action,
              'case_id' => $caseId,
              'details' => $details
          ];
          
          $logLine = json_encode($entry) . "\n";
          file_put_contents($logPath . '/audit.log', $logLine, FILE_APPEND | LOCK_EX);
      }
  }
  
  // Použití
  AuditLog::record('case.update', 'PC-123', ['field' => 'D5', 'old' => '...', 'new' => '...']);
  AuditLog::record('case.export', 'PC-123', ['format' => 'pdf']);
  AuditLog::record('case.delete', 'PC-456');
  ```
- **Status:** [ ] TODO
- **Priorita:** P2

### 15. Backup Strategy
- **Požadavek:** Automatický backup JSON souborů
- **Implementace:**
  ```bash
  # Cron job (daily at 2am)
  0 2 * * * /usr/local/bin/8d-backup.sh
  
  # /usr/local/bin/8d-backup.sh
  #!/bin/bash
  DATE=$(date +%Y-%m-%d)
  SOURCE="/var/www/8d-viewer/data"
  BACKUP="/var/www/8d-viewer/backups/$DATE"
  
  mkdir -p "$BACKUP"
  cp -r "$SOURCE"/*.json "$BACKUP/"
  
  # Komprese
  tar -czf "$BACKUP.tar.gz" "$BACKUP"
  rm -rf "$BACKUP"
  
  # Smazání starších než 30 dní
  find /var/www/8d-viewer/backups -name "*.tar.gz" -mtime +30 -delete
  ```
- **Status:** [ ] TODO
- **Priorita:** P2

### 16. Two-Factor Authentication (2FA)
- **Požadavek:** 2FA pro admin účty
- **Implementace:** Použít knihovnu jako `phpGangsta/GoogleAuthenticator`
- **Status:** [ ] FUTURE
- **Priorita:** P3

---

## Checklist před produkčním nasazením

### Pre-deployment Security Audit

**Kritická rizika (P0) - MUST BE DONE:**
- [ ] CSRF ochrana implementována a testována
- [ ] Rate limiting na všech API endpointech
- [ ] Session security nastavena (regeneration, secure flags)
- [ ] File upload MIME validace (ne jen přípona!)
- [ ] `.env` soubor vytvořen a v `.gitignore`
- [ ] Path traversal protection implementována

**Vysoká rizika (P1) - SHOULD BE DONE:**
- [ ] XSS escapování pomocí `h()` ve všech templates
- [ ] Security headers nastaveny
- [ ] RBAC implementováno a testováno
- [ ] Error handling a logging funkční
- [ ] Atomické file zápisy implementovány
- [ ] Input validace na všech vstupech

**Infrastruktura:**
- [ ] HTTPS certifikát platný a nakonfigurovaný
- [ ] File permissions správně nastaveny:
  - Složky: `755` (rwxr-xr-x)
  - PHP soubory: `644` (rw-r--r--)
  - `.env`: `600` (rw-------)
  - `/data`: `700` (rwx------)
  - `/logs`: `700` (rwx------)
- [ ] PHP.ini nastaveno pro produkci:
  ```ini
  display_errors = Off
  log_errors = On
  error_log = /var/www/8d-viewer/logs/php_errors.log
  session.cookie_secure = 1
  session.cookie_httponly = 1
  session.cookie_samesite = Strict
  ```
- [ ] `.htaccess` nebo nginx config pro přesměrování HTTP → HTTPS
- [ ] Firewall pravidla nastavena (pouze porty 80, 443)

**Dokumentace:**
- [ ] `.env.example` vytvořen (bez citlivých dat)
- [ ] README.md s instalačními instrukcemi
- [ ] Security incident response plan
- [ ] Kontakty na security team

### Post-deployment Monitoring

**Logy ke sledování:**
- [ ] `/logs/app_errors.log` - aplikační chyby
- [ ] `/logs/php_errors.log` - PHP chyby
- [ ] `/logs/audit.log` - audit trail (pokud implementován)
- [ ] `/logs/access.log` - přístupové logy (webserver)

**Co monitorovat:**
- [ ] Failed login attempts (rate limiting triggery)
- [ ] File upload attempts (zamítnuté MIME typy)
- [ ] RBAC violations (odmítnuté přístupy)
- [ ] 403/404/500 error rate
- [ ] Disk space usage (`/data` a `/logs` složky)
- [ ] Session expiration issues

**Alerting:**
- [ ] Email notifikace při kritických chybách
- [ ] Daily summary report s bezpečnostními events
- [ ] Webhook na Teams/Slack při security incidents

---

## Security Testing Checklist

Před nasazením provést tyto testy:

### 1. Authentication & Session Testing
- [ ] Pokus o přihlášení s neplatnými credentials (max 5x, pak block)
- [ ] Session timeout po 30 minutách neaktivity
- [ ] Session regeneration po přihlášení
- [ ] Logout správně maže session
- [ ] Cookie flags nastaveny (secure, httponly, samesite)

### 2. Authorization Testing (RBAC)
- [ ] Viewer nemůže editovat případy
- [ ] Team member může editovat pouze přiřazené případy
- [ ] Quality manager může editovat všechny případy
- [ ] Admin má plný přístup

### 3. Input Validation Testing
- [ ] Path traversal: `../../../etc/passwd` zamítnut
- [ ] XSS: `<script>alert('XSS')</script>` escapován
- [ ] File upload: nepovolený MIME type zamítnut
- [ ] Invalid case ID formát zamítnut
- [ ] SQL injection: N/A (JSON store)

### 4. CSRF Testing
- [ ] POST request bez CSRF tokenu zamítnut
- [ ] POST request s neplatným tokenem zamítnut
- [ ] POST request s platným tokenem úspěšný

### 5. Rate Limiting Testing
- [ ] 6. pokus o login během 15 minut zamítnut
- [ ] 101. API request během hodiny zamítnut

### 6. File Operations Testing
- [ ] Upload non-JSON souboru jako case zamítnut
- [ ] Upload příliš velkého souboru zamítnut (>10MB)
- [ ] Atomic file write - současné zápisy nerozbijí JSON
- [ ] Path traversal při načítání případu zamítnut

---

## Incident Response Plan

### Postup při detekci bezpečnostního incidentu:

**1. OKAMŽITÁ AKCE (0-15 minut)**
- Izolovat postižený systém (pokud nutné)
- Zablokovat útočící IP adresu
- Informovat security team

**2. HODNOCENÍ (15-60 minut)**
- Identifikovat typ útoku
- Zjistit rozsah kompromitace
- Zalogovat všechny detaily
- Určit prioritu (P0-P3)

**3. OBSAHENÍ (1-4 hodiny)**
- Implementovat dočasná opatření
- Změnit kompromitované credentials
- Provést immediate backup
- Komunikovat s postiženými uživateli (pokud nutné)

**4. ERADIKACE (4-24 hodin)**
- Odstranit příčinu incidentu
- Aplikovat security patch
- Provést security audit
- Testovat opravu

**5. RECOVERY (24-48 hodin)**
- Obnovit normální provoz
- Monitorovat systém
- Provést post-incident review

**6. LESSONS LEARNED (1 týden)**
- Dokumentovat incident
- Aktualizovat security measures
- Školení týmu
- Aktualizovat tento dokument

### Kontakty při incidentu

**Security Response Team:**
- Robert Veselý - robert.vesely@oiles.cz - +420 XXX XXX XXX
- [Další kontakty podle organizace]

**Eskalace:**
- Kritický incident (P0): Okamžitě volat
- Vysoký incident (P1): Email + SMS do 1 hodiny
- Střední incident (P2): Email do 4 hodin

---

## Compliance & Auditing

### GDPR Compliance (pokud applicable)
- [ ] Uživatelé mohou požádat o export svých dat
- [ ] Uživatelé mohou požádat o smazání svých dat
- [ ] Personal data jsou šifrována v přenosu (HTTPS)
- [ ] Access logy umožňují audit trail
- [ ] Data retention policy definována

### ISO 27001 (pokud applicable)
- [ ] Security policy dokumentována
- [ ] Risk assessment proveden
- [ ] Access control implementován
- [ ] Incident response plan existuje
- [ ] Regular security audits plánované

---

**Poslední aktualizace:** 2026-01-15  
**Verze dokumentu:** 2.0  
**Autor:** Robert Veselý  
**Reviewers:** [Seznam reviewerů]
