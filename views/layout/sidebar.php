<?php
/**
 * ACTIO - Layout Sidebar
 * 
 * Main navigation sidebar with dynamic menu from config/menu.php.
 * Based on dashio-template/src/index.html
 * 
 * @package Actio\Views\Layout
 * @var string $currentPage Current page identifier for active state
 */

$user = currentUser();
$userName = $user['name'] ?? 'Uživatel';
$userEmail = $user['email'] ?? '';
$userInitials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));
$userRole = $user['role'] ?? 'viewer';

// Load menu configuration
$menuItems = require BASE_PATH . '/config/menu.php';
?>
        <!-- Sidebar -->
        <aside class="sidebar bg-body-tertiary border-end">
            <div class="d-flex flex-column h-100">
                
                <!-- Sidebar Header -->
                <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                    <a href="<?= url('/') ?>" class="d-flex align-items-center text-decoration-none">
                        <span class="fs-5 fw-semibold text-primary">ACTIO</span>
                    </a>
                    <button type="button" class="btn btn-link text-body d-lg-none p-0" data-dismiss="sidebar" aria-label="Zavřít menu">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                
                <!-- Sidebar Navigation -->
                <nav class="sidebar-nav flex-grow-1 p-3">
                    <ul class="nav flex-column gap-1">
                        <?php foreach ($menuItems as $item): ?>
                            <?php 
                            // Check role-based visibility
                            if (isset($item['roles']) && !in_array($userRole, $item['roles'])) {
                                continue;
                            }
                            ?>
                            
                            <?php if (isset($item['header'])): ?>
                                <!-- Section Header -->
                                <li class="nav-header"><?= h($item['header']) ?></li>
                            <?php else: ?>
                                <!-- Navigation Link -->
                                <li class="nav-item">
                                    <a class="nav-link<?= ($currentPage ?? '') === $item['page'] ? ' active' : '' ?>" 
                                       href="<?= url($item['url']) ?>">
                                        <i class="<?= h($item['icon']) ?>"></i>
                                        <span><?= h($item['label']) ?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </nav>
                
                <!-- Sidebar Footer - User Info -->
                <div class="p-3 border-top">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <?= h($userInitials) ?>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-medium text-truncate"><?= h($userName) ?></div>
                            <div class="small text-body-secondary text-truncate"><?= h($userEmail) ?></div>
                        </div>
                    </div>
                </div>
                
            </div>
        </aside>
