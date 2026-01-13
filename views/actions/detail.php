<?php
/**
 * ACTIO - Action Detail View
 * 
 * Displays action details in read-only format.
 * 
 * Security: XSS prevention via h() (C04)
 * 
 * @package Actio\Views\Actions
 * @var array $action Action data
 */

use Actio\Core\Auth;

$success = flash('success');
$error = flash('error');

// Format dates
$findingDate = !empty($action['finding_date']) ? (new DateTime($action['finding_date']))->format('d.m.Y') : '-';
$deadline = !empty($action['deadline']) ? (new DateTime($action['deadline']))->format('d.m.Y') : '-';
$createdAt = !empty($action['created_at']) ? (new DateTime($action['created_at']))->format('d.m.Y H:i') : '-';
$updatedAt = !empty($action['updated_at']) ? (new DateTime($action['updated_at']))->format('d.m.Y H:i') : '-';
$completedAt = !empty($action['completed_at']) ? (new DateTime($action['completed_at']))->format('d.m.Y H:i') : null;

// Status calculations
$isComplete = !empty($action['completed_at']);
$isOverdue = !$isComplete && !empty($action['deadline']) && $action['deadline'] < date('Y-m-d');
?>

<!-- Page Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Akce #
            <?= h($action['number']) ?>
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= url('/actions') ?>">Zjištění</a></li>
                <li class="breadcrumb-item active" aria-current="page">#
                    <?= h($action['number']) ?>
                </li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/actions/' . $action['id'] . '/edit') ?>" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i>Upravit
        </a>
        <a href="<?= url('/actions') ?>" class="btn btn-outline-secondary">
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

<!-- Status Banner -->
<?php if ($isComplete): ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        <div>
            <strong>Akce dokončena</strong>
            <?php if ($completedAt): ?> dne
                <?= $completedAt ?>
            <?php endif; ?>
            <?php if (isset($action['timeliness'])): ?> -
                <?= h($action['timeliness']) ?>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($isOverdue): ?>
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div>
            <strong>Akce po termínu!</strong> Termín byl
            <?= $deadline ?>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Main Content -->
    <div class="col-lg-8">
        <!-- Finding Card -->
        <div class="card mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Zjištění</h5>
                <?php if (!empty($action['rating'])): ?>
                    <span class="badge bg-primary">
                        <?= h($action['rating']) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label text-body-secondary small">Zjištění</label>
                    <p class="mb-0">
                        <?= nl2br(h($action['finding'])) ?>
                    </p>
                </div>

                <?php if (!empty($action['description'])): ?>
                    <div class="mb-4">
                        <label class="form-label text-body-secondary small">Popis</label>
                        <p class="mb-0">
                            <?= nl2br(h($action['description'])) ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($action['chapter'])): ?>
                    <div class="mb-4">
                        <label class="form-label text-body-secondary small">Kapitola normy</label>
                        <p class="mb-0">
                            <?= h($action['chapter']) ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Analysis Card -->
        <div class="card mb-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">Analýza a opatření</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label text-body-secondary small">Příčina problému</label>
                    <p class="mb-0">
                        <?= nl2br(h($action['problem_cause'])) ?>
                    </p>
                </div>

                <div>
                    <label class="form-label text-body-secondary small">Opatření</label>
                    <p class="mb-0">
                        <?= nl2br(h($action['measure'])) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- PDCA Status -->
        <div class="card mb-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">PDCA Status</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge <?= !empty($action['status_plan']) ? 'bg-success' : 'bg-secondary' ?>"
                            style="width: 28px;">P</span>
                        <span class="<?= !empty($action['status_plan']) ? 'text-success' : 'text-body-secondary' ?>">
                            Plan - Naplánováno
                            <?php if (!empty($action['status_plan'])): ?><i class="bi bi-check-lg"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge <?= !empty($action['status_do']) ? 'bg-success' : 'bg-secondary' ?>"
                            style="width: 28px;">D</span>
                        <span class="<?= !empty($action['status_do']) ? 'text-success' : 'text-body-secondary' ?>">
                            Do - Realizováno
                            <?php if (!empty($action['status_do'])): ?><i class="bi bi-check-lg"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge <?= !empty($action['status_check']) ? 'bg-success' : 'bg-secondary' ?>"
                            style="width: 28px;">C</span>
                        <span class="<?= !empty($action['status_check']) ? 'text-success' : 'text-body-secondary' ?>">
                            Check - Ověřeno
                            <?php if (!empty($action['status_check'])): ?><i class="bi bi-check-lg"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge <?= !empty($action['status_act']) ? 'bg-success' : 'bg-secondary' ?>"
                            style="width: 28px;">A</span>
                        <span class="<?= !empty($action['status_act']) ? 'text-success' : 'text-body-secondary' ?>">
                            Act - Uzavřeno
                            <?php if (!empty($action['status_act'])): ?><i class="bi bi-check-lg"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attachments Card -->
        <div class="card mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-paperclip me-2"></i>Přílohy</h5>
                <span class="badge bg-secondary"><?= count($attachments ?? []) ?></span>
            </div>
            <div class="card-body">
                <?php if (!empty($attachments)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($attachments as $att): ?>
                            <?php
                            $icon = \Actio\Models\Attachment::getIconForMime($att['mime_type']);
                            $size = \Actio\Models\Attachment::formatSize($att['size']);
                            ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div class="d-flex align-items-center">
                                    <i class="bi <?= $icon ?> fs-4 me-3 text-primary"></i>
                                    <div>
                                        <div class="fw-medium"><?= h($att['filename']) ?></div>
                                        <small class="text-body-secondary">
                                            <?= $size ?>
                                            <?php if (!empty($att['description'])): ?>
                                                · <?= h($att['description']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                <a href="<?= url('/attachments/' . $att['id'] . '/download') ?>"
                                    class="btn btn-sm btn-outline-primary" title="Stáhnout">
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-body-secondary mb-0">Žádné přílohy.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Details Card -->
        <div class="card mb-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">Detaily</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label text-body-secondary small mb-1">Datum zjištění</label>
                        <p class="mb-0 fw-medium"><?= $findingDate ?></p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-body-secondary small mb-1">Kapitola normy</label>
                        <p class="mb-0 fw-medium"><?= h($action['chapter'] ?? '-') ?></p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-body-secondary small mb-1">Termín plánu</label>
                        <p class="mb-0 fw-medium">
                            <?= !empty($action['deadline_plan']) ? (new DateTime($action['deadline_plan']))->format('d.m.Y') : '-' ?>
                        </p>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-body-secondary small mb-1">Termín realizace</label>
                        <p class="mb-0 fw-medium <?= $isOverdue ? 'text-danger' : '' ?>"><?= $deadline ?></p>
                    </div>
                    <?php if (!empty($action['process'])): ?>
                        <div class="col-6">
                            <label class="form-label text-body-secondary small mb-1">Proces</label>
                            <p class="mb-0 fw-medium"><?= h($action['process']) ?></p>
                        </div>
                    <?php endif; ?>
                    <div class="col-6">
                        <label class="form-label text-body-secondary small mb-1">Majitel procesu</label>
                        <p class="mb-0 fw-medium"><?= h($action['process_owner'] ?? '-') ?></p>
                    </div>
                    <?php if (!empty($action['responsible'])): ?>
                        <div class="col-12">
                            <label class="form-label text-body-secondary small mb-1">Odpovědný za realizaci</label>
                            <p class="mb-0 fw-medium"><?= h($action['responsible']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Audit Info Card -->
        <div class="card">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">Audit informace</h5>
            </div>
            <div class="card-body">
                <div class="small text-body-secondary">
                    <div class="mb-2">
                        <i class="bi bi-person-plus me-1"></i>
                        Vytvořil: <strong>
                            <?= h($action['created_by'] ?? '-') ?>
                        </strong>
                        <br><span class="ms-4">
                            <?= $createdAt ?>
                        </span>
                    </div>
                    <div>
                        <i class="bi bi-pencil me-1"></i>
                        Upravil: <strong>
                            <?= h($action['updated_by'] ?? '-') ?>
                        </strong>
                        <br><span class="ms-4">
                            <?= $updatedAt ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>