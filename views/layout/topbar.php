<?php
/**
 * ACTIO - Layout Top Bar
 * 
 * Top header with search, theme toggle, notifications, and profile dropdown.
 * Based on dashio-template/src/index.html
 * 
 * @package Actio\Views\Layout
 */

$user = currentUser();
$userName = $user['name'] ?? 'Uživatel';
$userInitials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));
?>
<!-- Top Header -->
<header class="sticky-top bg-body border-bottom">
    <div class="d-flex align-items-center justify-content-between px-3 px-lg-4 py-2">

        <!-- Mobile menu toggle -->
        <button type="button" class="btn btn-link text-body d-lg-none p-0 me-3" data-toggle="sidebar"
            aria-label="Otevřít menu">
            <i class="bi bi-list fs-4"></i>
        </button>

        <!-- Search -->
        <div class="flex-grow-1 me-3" style="max-width: 400px;">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0">
                    <i class="bi bi-search"></i>
                </span>
                <input type="search" class="form-control border-start-0" placeholder="Hledat zjištění...">
            </div>
        </div>

        <!-- Right side items -->
        <div class="d-flex align-items-center gap-2">

            <!-- Theme toggle -->
            <button type="button" class="btn btn-link text-body p-2" data-toggle="theme" data-bs-toggle="tooltip"
                data-bs-placement="bottom" title="Přepnout téma">
                <i class="bi bi-moon-fill"></i>
            </button>

            <!-- Notifications -->
            <div class="dropdown">
                <button type="button" class="btn btn-link text-body p-2 position-relative" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <i class="bi bi-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                        style="font-size: 0.65rem;">
                        0
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width: 300px;">
                    <li>
                        <h6 class="dropdown-header">Oznámení</h6>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <div class="dropdown-item text-center text-body-secondary py-3">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            Žádná nová oznámení
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Profile dropdown -->
            <div class="dropdown">
                <button type="button" class="btn btn-link p-0" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px;">
                        <?= h($userInitials) ?>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <div class="dropdown-item-text">
                            <div class="fw-medium">
                                <?= h($userName) ?>
                            </div>
                            <div class="small text-body-secondary">
                                <?= h($user['email'] ?? '') ?>
                            </div>
                        </div>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="<?= url('/profile') ?>"><i
                                class="bi bi-person me-2"></i>Profil</a></li>
                    <li><a class="dropdown-item" href="<?= url('/settings') ?>"><i
                                class="bi bi-gear me-2"></i>Nastavení</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form action="<?= url('/logout') ?>" method="POST" class="d-inline w-100">
                            <?= csrfField() ?>
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>Odhlásit se
                            </button>
                        </form>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</header>