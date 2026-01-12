<?php
/**
 * ACTIO - Login Page
 * 
 * Standalone login form (no sidebar/topbar).
 * Based on dashio-template/src/pages/login.html
 * 
 * Security Requirements:
 * - C06: CSRF token in form
 * 
 * @package Actio\Views\Auth
 * @var string $pageTitle Page title
 * @var string|null $error Error message
 */
?>
<!DOCTYPE html>
<html lang="cs" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ACTIO - Přihlášení">
    <title><?= h($pageTitle ?? 'Přihlášení | ACTIO') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('favicon.svg') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashio.css') ?>">
</head>
<body class="bg-body-tertiary">
    
    <div class="min-vh-100 d-flex align-items-center justify-content-center p-3">
        <div class="w-100" style="max-width: 400px;">
            
            <!-- Logo -->
            <div class="text-center mb-4">
                <a href="<?= url('/') ?>" class="d-inline-flex align-items-center text-decoration-none mb-3">
                    <span class="fs-3 fw-bold text-primary">ACTIO</span>
                </a>
                <h1 class="h4 mb-1">Vítejte zpět</h1>
                <p class="text-body-secondary">Přihlaste se pro přístup do aplikace</p>
            </div>
            
            <!-- Login Card -->
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    
                    <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?= h($error) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($successMsg = flash('login_success')): ?>
                    <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div><?= h($successMsg) ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?= url('/login') ?>">
                        <!-- CSRF Token (C06) -->
                        <?= csrfField() ?>
                        
                        <!-- Login ID -->
                        <div class="mb-3">
                            <label for="login" class="form-label">Přihlašovací jméno</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" 
                                       class="form-control" 
                                       id="login" 
                                       name="login" 
                                       placeholder="Zadejte přihlašovací jméno" 
                                       autocomplete="username"
                                       required 
                                       autofocus>
                            </div>
                        </div>
                        
                        <!-- Password -->
                        <div class="mb-4">
                            <label for="password" class="form-label">Heslo</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Zadejte heslo"
                                       autocomplete="current-password" 
                                       required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Submit -->
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Přihlásit se
                        </button>
                        
                    </form>
                </div>
            </div>
            
            <!-- Info text -->
            <p class="text-center mt-4 mb-0 text-body-secondary small">
                <i class="bi bi-info-circle me-1"></i>
                Použijte své firemní přihlašovací údaje (SELIO)
            </p>
            
        </div>
    </div>
    
    <script src="<?= asset('js/dashio.js') ?>"></script>
    <script>
        // Password toggle
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    </script>
</body>
</html>
