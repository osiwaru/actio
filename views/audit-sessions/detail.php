<?php
/**
 * ACTIO - Audit Session Detail View
 * 
 * Displays audit session details with linked actions.
 * 
 * Security: XSS prevention via h() (C04)
 * 
 * @package Actio\Views\AuditSessions
 * @var array $session Audit session data
 * @var array $actions List of actions linked to this session
 */

$success = flash('success');
$error = flash('error');

// Format dates
$sessionDate = !empty($session['date']) ? (new DateTime($session['date']))->format('d.m.Y') : '-';
$createdAt = !empty($session['created_at']) ? (new DateTime($session['created_at']))->format('d.m.Y H:i') : '-';
?>

<!-- Page Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">
            <?= h($session['name']) ?>
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= url('/audit-sessions') ?>">Auditní sezení</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?= h($session['name']) ?>
                </li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/audit-sessions/' . $session['id'] . '/edit') ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i>Upravit
        </a>
        <a href="<?= url('/actions/create?audit_session_id=' . $session['id']) ?>" class="btn btn-primary">
            <i class="bi bi-plus me-1"></i>Přidat akci
        </a>
        <a href="<?= url('/audit-sessions') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Zpět
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

<div class="row g-4">
    <!-- Main Content -->
    <div class="col-lg-8">
        <!-- Actions Card -->
        <div class="card">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>Zjištění z auditu
                </h5>
                <span class="badge bg-primary">
                    <?= count($actions) ?> akcí
                </span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($actions)): ?>
                    <div class="p-4 text-center text-body-secondary">
                        <i class="bi bi-clipboard-x fs-1 mb-3 d-block text-muted"></i>
                        <p class="mb-0">K tomuto auditu zatím nejsou přiřazena žádná zjištění.</p>
                        <a href="<?= url('/actions/create?audit_session_id=' . $session['id']) ?>"
                            class="btn btn-primary mt-3">
                            <i class="bi bi-plus me-1"></i>Přidat první zjištění
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" style="width: 60px;">#</th>
                                    <th scope="col">Zjištění</th>
                                    <th scope="col" style="width: 150px;">Odpovědný</th>
                                    <th scope="col" style="width: 110px;">Termín</th>
                                    <th scope="col" style="width: 160px;">PDCA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($actions as $action): ?>
                                    <?php
                                    $isOverdue = !$action['completed_at'] && ($action['deadline'] ?? '') < date('Y-m-d');
                                    $isComplete = !empty($action['completed_at']);
                                    $rowClass = $isComplete ? 'table-success' : ($isOverdue ? 'table-warning' : '');
                                    ?>
                                    <tr class="<?= $rowClass ?>">
                                        <th scope="row">
                                            <a href="<?= url('/actions/' . $action['id']) ?>" class="text-decoration-none">
                                                <?= h($action['number']) ?>
                                            </a>
                                        </th>
                                        <td>
                                            <a href="<?= url('/actions/' . $action['id']) ?>" class="text-decoration-none">
                                                <div class="fw-medium text-truncate" style="max-width: 300px;"
                                                    title="<?= h($action['finding']) ?>">
                                                    <?= h(mb_substr($action['finding'], 0, 60)) ?>
                                                    <?php if (mb_strlen($action['finding']) > 60): ?>...
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                            <?php if (!empty($action['rating'])): ?>
                                                <small class="text-body-secondary">
                                                    <?= h($action['rating']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= h($action['responsible'] ?? '-') ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($action['deadline'])): ?>
                                                <?php
                                                $deadline = new DateTime($action['deadline']);
                                                $deadlineClass = $isOverdue ? 'text-danger fw-bold' : '';
                                                ?>
                                                <span class="<?= $deadlineClass ?>">
                                                    <?= $deadline->format('d.m.Y') ?>
                                                </span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <span
                                                    class="badge <?= !empty($action['status_plan']) ? 'bg-success' : 'bg-secondary' ?>"
                                                    title="Plan">P</span>
                                                <span
                                                    class="badge <?= !empty($action['status_do']) ? 'bg-success' : 'bg-secondary' ?>"
                                                    title="Do">D</span>
                                                <span
                                                    class="badge <?= !empty($action['status_check']) ? 'bg-success' : 'bg-secondary' ?>"
                                                    title="Check">C</span>
                                                <span
                                                    class="badge <?= !empty($action['status_act']) ? 'bg-success' : 'bg-secondary' ?>"
                                                    title="Act">A</span>
                                                <?php if ($isComplete): ?>
                                                    <span class="badge bg-primary ms-1" title="Dokončeno">✓</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Session Info Card -->
        <div class="card mb-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">Informace o auditu</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label text-body-secondary small mb-1">Typ auditu</label>
                        <p class="mb-0">
                            <span class="badge bg-secondary">
                                <?= h($session['type']) ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-body-secondary small mb-1">Datum auditu</label>
                        <p class="mb-0 fw-medium">
                            <?= $sessionDate ?>
                        </p>
                    </div>
                    <?php if (!empty($session['auditor'])): ?>
                        <div class="col-12">
                            <label class="form-label text-body-secondary small mb-1">Auditor</label>
                            <p class="mb-0 fw-medium">
                                <?= h($session['auditor']) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($session['standard'])): ?>
                        <div class="col-12">
                            <label class="form-label text-body-secondary small mb-1">Norma</label>
                            <p class="mb-0 fw-medium">
                                <?= h($session['standard']) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($session['notes'])): ?>
            <!-- Notes Card -->
            <div class="card mb-4">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">Poznámky</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        <?= nl2br(h($session['notes'])) ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Audit Info Card -->
        <div class="card">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">Systémové informace</h5>
            </div>
            <div class="card-body">
                <div class="small text-body-secondary">
                    <div>
                        <i class="bi bi-calendar-plus me-1"></i>
                        Vytvořeno: <strong>
                            <?= $createdAt ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>