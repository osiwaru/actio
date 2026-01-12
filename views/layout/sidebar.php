<?php
/**
 * ACTIO - Layout Sidebar
 * 
 * Main navigation sidebar.
 * Based on dashio-template/src/index.html
 * 
 * @package Actio\Views\Layout
 * @var string $currentPage Current page identifier for active state
 */

$user = currentUser();
$userName = $user['name'] ?? 'Uživatel';
$userEmail = $user['email'] ?? '';
$userInitials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));
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
                        <li class="nav-item">
                            <a class="nav-link<?= ($currentPage ?? '') === 'dashboard' ? ' active' : '' ?>" href="<?= url('/') ?>">
                                <i class="bi bi-grid-1x2-fill"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        <li class="nav-header">Správa zjištění</li>

                        <li class="nav-item">
                            <a class="nav-link<?= ($currentPage ?? '') === 'actions' ? ' active' : '' ?>" href="<?= url('/actions') ?>">
                                <i class="bi bi-clipboard-check"></i>
                                <span>Zjištění / Opatření</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?= ($currentPage ?? '') === 'audit-sessions' ? ' active' : '' ?>" href="<?= url('/audit-sessions') ?>">
                                <i class="bi bi-journal-text"></i>
                                <span>Auditní sezení</span>
                            </a>
                        </li>

                        <li class="nav-header">Archiv</li>

                        <li class="nav-item">
                            <a class="nav-link<?= ($currentPage ?? '') === 'archive' ? ' active' : '' ?>" href="<?= url('/archive') ?>">
                                <i class="bi bi-archive"></i>
                                <span>Archiv</span>
                            </a>
                        </li>

                        <li class="nav-header">Export</li>

                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/export/csv') ?>">
                                <i class="bi bi-file-earmark-spreadsheet"></i>
                                <span>Export CSV</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/export/excel') ?>">
                                <i class="bi bi-file-earmark-excel"></i>
                                <span>Export Excel</span>
                            </a>
                        </li>
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
