# Bezpečnostní požadavky pro webové aplikace

> **Verze:** 1.1  
> **Datum:** 2026-01-10  
> **Zdroj:** Lesson learned z projektu EVALIO + OWASP guidelines

---

## Přehled kategorií

| Kategorie | Popis |
|-----------|-------|
| **Code** | Implementace v aplikačním kódu |
| **File Handling** | Práce se soubory a uploady |
| **Environment** | Konfigurace projektu a prostředí |
| **Infrastructure** | Nastavení serveru a HTTP |
| **Identity** | Autentizace, autorizace, session |

---

## Škála závažnosti

| Symbol | Úroveň | Skóre | Popis |
|--------|--------|-------|-------|
| 💀 | Kritická | 9-10 | Okamžité riziko kompromitace systému |
| 🔥 | Vysoká | 7-8 | Významné bezpečnostní riziko |
| ⚠️ | Střední | 4-6 | Potenciální zranitelnost |
| ℹ️ | Nízká | 1-3 | Best practice, hardening |

---

## 💀 Kritická (9-10)

### Code

| ID | Opatření | Kde nastavit | Obtížnost | Fáze |
|----|----------|--------------|-----------|------|
| C01 | Bezpečné hašování hesel (`password_hash`, Argon2/bcrypt) | Kód (Auth) | Nízká | Návrh |
| C02 | Autentizace na všech API endpointech (včetně GET) | Kód (Router/Middleware) | Střední | Vývoj |
| C03 | Prepared Statements / parametrizované dotazy | Kód (DB vrstva) | Nízká | Vývoj |
| C04 | XSS prevence (`htmlspecialchars` na všech výstupech) | Kód (View/Template) | Střední | Vývoj |
| C05 | SQL Injection prevence i v logování | Kód (Logging) | Nízká | Vývoj |

### Environment

| ID | Opatření | Kde nastavit | Obtížnost | Fáze |
|----|----------|--------------|-----------|------|
| E01 | Credentials a secrets v `.env` souboru | Konfigurace projektu | Nízká | Start |
| E02 | `.env` v `.gitignore` | Konfigurace projektu | Nízká | Start |

---

## 🔥 Vysoká (7-8)

### Identity

| ID | Opatření | Kde nastavit | Obtížnost | Fáze |
|----|----------|--------------|-----------|------|
| I01 | RBAC (Role-Based Access Control) | Kód + DB | Vysoká | Návrh |
| I02 | Session cookie: `secure` flag | Server / php.ini | Nízká | Deployment |
| I03 | Session cookie: `httponly` flag | Server / php.ini | Nízká | Deployment |
| I04 | Session cookie: `SameSite=Strict` | Server / php.ini | Nízká | Deployment |
| I05 | Regenerace Session ID po přihlášení | Kód (Auth) | Nízká | Vývoj |
| I06 | Rate limiting na login (max 5 pokusů/min) | Kód + Cache/DB | Střední | Vývoj |
| I07 | Session timeout / expirace | Server / Kód | Nízká | Konfigurace |
| I08 | Open Redirect prevence (whitelist redirect URL) | Kód (Auth) | Střední | Vývoj |

### Code

| ID | Opatření | Kde nastavit | Obtížnost | Fáze |
|----|----------|--------------|-----------|------|
| C06 | CSRF tokeny (`bin2hex(random_bytes(32))`) | Kód (Forms) | Střední | Vývoj |
| C07 | CSRF validace na POST/PUT/DELETE | Kód (Middleware) | Střední | Vývoj |
| C08 | Path Traversal prevence (regex whitelist `^...$`) | Kód (Input) | Střední | Vývoj |
| C09 | Timing-safe porovnání tokenů (`hash_equals`) | Kód (Auth/CSRF) | Nízká | Vývoj |
| C10 | Mass Assignment prevence (whitelist polí) | Kód (Model/Service) | Střední | Vývoj |

### File Handling

| ID | Opatření | Kde nastavit | Obtížnost | Fáze |
|----|----------|--------------|-----------|------|
| F01 | MIME type validace (`finfo_file`) | Kód (Upload) | Nízká | Vývoj |
| F02 | Extension whitelist (ne blacklist) | Kód (Upload) | Nízká | Vývoj |
| F03 | Generované náhodné názvy souborů | Kód (Upload) | Nízká | Vývoj |
| F04 | Upload adresář mimo web root | Architektura | Střední | Návrh |
| F05 | Maximální velikost uploadu | Server + Kód | Nízká | Konfigurace |

### Infrastructure

| ID | Opatření | Kde nastavit | Obtížnost | Fáze |
|----|----------|--------------|-----------|------|
| N01 | HSTS Header (`Strict-Transport-Security`) | Server | Nízká | Deployment |
| N02 | Blokovat přístup k `.log` souborům | Server (.htaccess/nginx) | Nízká | Konfigurace |
| N03 | Blokovat přístup k backup souborům (`.bak`, `.old`, `.sql`, `.zip`) | Server | Nízká | Konfigurace |
| N04 | Vypnout Directory Listing | Server | Nízká | Konfigurace |

### Environment

| ID | Opatření | Kde nastavit | Obtížnost | Fáze |
|----|----------|--------------|-----------|------|
| E03 | Silent logging v produkci (bez verbose chyb) | Server / php.ini | Nízká | Deployment |

---

## ⚠️ Střední (4-6)

### Code

| ID | Opatření | Kde nastavit | Obtížnost | Fáze |
|----|----------|--------------|-----------|------|
| C11 | SSL verifikace v cURL (`CURLOPT_CAINFO`) | Kód (HTTP client) | Nízká | Vývoj |
| C12 | Striktní porovnání (`===` místo `==`) | Kód | Nízká | Vývoj |

### File Handling

| ID | Opatření | Kde nastavit | Obtížnost | Fáze |
|----|----------|--------------|-----------|------|
| F06 | Sanitizace názvů souborů | Kód (Upload) | Nízká | Vývoj |

### Infrastructure

| ID | Opatření | Kde nastavit | Obtížnost | Fáze |
|----|----------|--------------|-----------|------|
| N05 | `X-Frame-Options: DENY` | Server | Nízká | Deployment |
| N06 | Content Security Policy (CSP) včetně `frame-ancestors` | Server / Meta tag | Vysoká | Deployment |
| N07 | Vypnutí nebezpečných PHP funkcí | php.ini | Nízká | Konfigurace |
| N08 | Zakázat `X-HTTP-Method-Override` header | Server / Kód | Nízká | Konfigurace |
| N09 | CORS konfigurace (pokud API používá jiná doména) | Server / Kód | Střední | Deployment |

### Environment

| ID | Opatření | Kde nastavit | Obtížnost | Fáze |
|----|----------|--------------|-----------|------|
| E04 | Validace povinných ENV proměnných při startu | Kód (Bootstrap) | Nízká | Start |

---

## ℹ️ Nízká (1-3)

### Code

| ID | Opatření | Kde nastavit | Obtížnost | Fáze |
|----|----------|--------------|-----------|------|
| C13 | Atomické zápisy souborů (temp → rename) | Kód (File I/O) | Střední | Vývoj |

### Environment

| ID | Opatření | Kde nastavit | Obtížnost | Fáze |
|----|----------|--------------|-----------|------|
| E05 | `.env.example` v repozitáři (bez hodnot) | Konfigurace projektu | Nízká | Start |
| E06 | Dev účty pouze v podmínce `APP_ENV=development` | Kód (Auth) | Nízká | Vývoj |

---

## Checklist podle fáze projektu

### 🚀 Start projektu
- [ ] E01 – Credentials v `.env`
- [ ] E02 – `.env` v `.gitignore`
- [ ] E04 – Validace ENV proměnných
- [ ] E05 – `.env.example` v repo

### 📐 Návrh architektury
- [ ] C01 – Hašování hesel
- [ ] I01 – RBAC model
- [ ] F04 – Upload mimo web root

### 💻 Vývoj
- [ ] C02 – Auth na endpointech
- [ ] C03 – Prepared Statements
- [ ] C04 – XSS prevence
- [ ] C05 – SQL Injection v logu
- [ ] C06 – CSRF tokeny
- [ ] C07 – CSRF validace
- [ ] C08 – Path Traversal
- [ ] C09 – Timing-safe porovnání
- [ ] C10 – Mass Assignment prevence
- [ ] C11 – SSL verifikace
- [ ] C12 – Striktní porovnání
- [ ] C13 – Atomické zápisy
- [ ] I05 – Session regenerace
- [ ] I06 – Rate limiting
- [ ] I08 – Open Redirect prevence
- [ ] F01 – MIME validace
- [ ] F02 – Extension whitelist
- [ ] F03 – Náhodné názvy
- [ ] F06 – Sanitizace názvů
- [ ] E06 – Dev účty podmíněně

### ⚙️ Konfigurace serveru
- [ ] I07 – Session timeout
- [ ] F05 – Max upload size
- [ ] N02 – Blokovat .log
- [ ] N03 – Blokovat backup soubory
- [ ] N04 – Vypnout Directory Listing
- [ ] N07 – Zakázané PHP funkce
- [ ] N08 – Zakázat X-HTTP-Method-Override

### 🚢 Deployment
- [ ] I02 – Cookie secure
- [ ] I03 – Cookie httponly
- [ ] I04 – Cookie SameSite
- [ ] N01 – HSTS
- [ ] N05 – X-Frame-Options
- [ ] N06 – CSP (včetně frame-ancestors)
- [ ] N09 – CORS (pokud potřeba)
- [ ] E03 – Silent logging

---

## Příklady implementace

### Open Redirect prevence (I08)
```php
// ❌ Špatně
$redirect = $_GET['redirect'];
header("Location: $redirect");

// ✅ Správně
$allowed = ['/dashboard', '/profile', '/settings'];
$redirect = $_GET['redirect'] ?? '/dashboard';
if (!in_array($redirect, $allowed) && !str_starts_with($redirect, '/')) {
    $redirect = '/dashboard';
}
header("Location: $redirect");
```

### Mass Assignment prevence (C10)
```php
// ❌ Špatně - uloží vše včetně role, is_admin...
$user->fill($_POST);

// ✅ Správně - whitelist povolených polí
$allowed = ['name', 'email', 'phone'];
$data = array_intersect_key($_POST, array_flip($allowed));
$user->fill($data);
```

### Blokování souborů v .htaccess (N02, N03)
```apache
# Blokovat citlivé soubory
<FilesMatch "\.(log|bak|old|sql|zip|tar|gz|env)$">
    Require all denied
</FilesMatch>

# Blokovat skryté soubory
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
```

### Directory Listing (N04)
```apache
# .htaccess
Options -Indexes
```

---

## Reference

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [OWASP PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [OWASP Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
- [Mozilla Web Security Guidelines](https://infosec.mozilla.org/guidelines/web_security)
- [CWE/SANS Top 25](https://cwe.mitre.org/top25/)

---

## Changelog

| Verze | Datum | Změny |
|-------|-------|-------|
| 1.0 | 2026-01-10 | Iniciální verze |
| 1.1 | 2026-01-10 | Přidáno: SQL Injection v logu (C05), Open Redirect (I08), Mass Assignment (C10), Directory Listing (N04), Backup soubory (N03), X-HTTP-Method-Override (N08), CORS (N09), příklady implementace |

---

*Dokument vytvořen jako lesson learned z projektu EVALIO, leden 2026*
