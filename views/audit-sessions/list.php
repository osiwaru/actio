<?php
/**
 * ACTIO - Audit Sessions List View
 * 
 * Displays list of audit sessions in a table.
 * Based on dashio-template/src/pages/tables.html
 * 
 * Security: XSS prevention via h() (C04)
 * 
 * @package Actio\Views\AuditSessions
 * @var array $sessions List of audit sessions
 * @var array $actionCounts Map of session_id => action count
 */

$success = flash('success');
$error = flash('error');
?>

<!-- Page Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Auditní sezení</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Auditní sezení</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="<?= url('/audit-sessions/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus me-1"></i>Nové auditní sezení
        </a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= h($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zavřít"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= h($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zavřít"></button>
    </div>
<?php endif; ?>

<!-- Audit Sessions Table -->
<div class="card">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">Seznam auditních sezení</h5>
        <div class="d-flex gap-2">
            <span class="text-body-secondary small">Celkem:
                <?= count($sessions) ?>
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($sessions)): ?>
            <div class="p-4 text-center text-body-secondary">
                <i class="bi bi-journal-x fs-1 mb-3 d-block text-muted"></i>
                <p class="mb-0">Zatím nejsou žádná auditní sezení.</p>
                <a href="<?= url('/audit-sessions/create') ?>" class="btn btn-primary mt-3">
                    <i class="bi bi-plus me-1"></i>Vytvořit první sezení
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Název</th>
                            <th scope="col" style="width: 180px;">Typ</th>
                            <th scope="col" style="width: 110px;">Datum</th>
                            <th scope="col" style="width: 150px;">Auditor</th>
                            <th scope="col" style="width: 100px;">Akcí</th>
                            <th scope="col" class="text-end" style="width: 80px;">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <?php
                            $sessionDate = !empty($session['date']) ? (new DateTime($session['date']))->format('d.m.Y') : '-';
                            $actionCount = $actionCounts[$session['id']] ?? 0;
                            ?>
                            <tr>
                                <td>
                                    <a href="<?= url('/audit-sessions/' . $session['id']) ?>" class="text-decoration-none">
                                        <div class="fw-medium">
                                            <?= h($session['name']) ?>
                                        </div>
                                        <?php if (!empty($session['standard'])): ?>
                                            <small class="text-body-secondary">
                                                <?= h($session['standard']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= h($session['type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $sessionDate ?>
                                </td>
                                <td>
                                    <?= h($session['auditor'] ?? '-') ?>
                                </td>
                                <td>
                                    <?php if ($actionCount > 0): ?>
                                        <span class="badge bg-primary">
                                            <?= $actionCount ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-body-secondary">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= url('/audit-sessions/' . $session['id']) ?>"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>