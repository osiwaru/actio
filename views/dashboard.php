<?php
/**
 * ACTIO - Dashboard View
 * 
 * Main dashboard with statistics and action lists.
 * Based on dashio-template/src/index.html
 * 
 * @package Actio\Views
 * @var array $stats Dashboard statistics
 * @var array $myActions Actions assigned to current user
 * @var array $overdueActions Overdue actions
 * @var array $upcomingActions Actions due in next 7 days
 */
?>
<!-- Page Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Domů</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/export/csv') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-download me-1"></i> Export
        </a>
        <a href="<?= url('/actions/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nové zjištění
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-body-secondary small mb-1">Otevřená zjištění</div>
                        <div class="h4 mb-0">
                            <?= h((string) ($stats['total_open'] ?? 0)) ?>
                        </div>
                        <div class="small text-body-secondary mt-1">
                            Celkem aktivních
                        </div>
                    </div>
                    <div class="rounded bg-primary bg-opacity-10 p-2">
                        <i class="bi bi-clipboard-check fs-4 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-body-secondary small mb-1">Po termínu</div>
                        <div class="h4 mb-0 text-danger">
                            <?= h((string) ($stats['overdue'] ?? 0)) ?>
                        </div>
                        <div class="small text-danger mt-1">
                            <i class="bi bi-exclamation-triangle"></i> Vyžaduje pozornost
                        </div>
                    </div>
                    <div class="rounded bg-danger bg-opacity-10 p-2">
                        <i class="bi bi-clock-history fs-4 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-body-secondary small mb-1">Termín tento týden</div>
                        <div class="h4 mb-0 text-warning">
                            <?= h((string) ($stats['due_this_week'] ?? 0)) ?>
                        </div>
                        <div class="small text-warning mt-1">
                            <i class="bi bi-calendar-event"></i> Příštích 7 dní
                        </div>
                    </div>
                    <div class="rounded bg-warning bg-opacity-10 p-2">
                        <i class="bi bi-calendar-week fs-4 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-body-secondary small mb-1">Dokončeno tento měsíc</div>
                        <div class="h4 mb-0 text-success">
                            <?= h((string) ($stats['completed_this_month'] ?? 0)) ?>
                        </div>
                        <div class="small text-success mt-1">
                            <i class="bi bi-check-circle"></i> Úspěšně uzavřeno
                        </div>
                    </div>
                    <div class="rounded bg-success bg-opacity-10 p-2">
                        <i class="bi bi-trophy fs-4 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Row -->
<div class="row g-3 mb-4">
    <!-- My Actions -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="bi bi-person-check me-2 text-primary"></i>
                    Moje zjištění
                </h5>
                <a href="<?= url('/actions?responsible=me') ?>" class="small text-decoration-none">Zobrazit vše</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($myActions)): ?>
                    <div class="text-center text-body-secondary py-5">
                        <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
                        <p class="mb-0">Nemáte žádná přiřazená zjištění</p>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($myActions as $action): ?>
                            <li class="list-group-item d-flex align-items-center gap-3 py-3">
                                <div class="rounded bg-body-secondary d-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px;">
                                    <span class="fw-medium small">#
                                        <?= h((string) $action['number']) ?>
                                    </span>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-medium text-truncate">
                                        <?= h($action['finding']) ?>
                                    </div>
                                    <small class="text-body-secondary">
                                        Termín:
                                        <?= h($action['deadline']) ?>
                                    </small>
                                </div>
                                <a href="<?= url('/actions/' . $action['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    Detail
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Overdue Actions -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2 text-danger"></i>
                    Po termínu
                </h5>
                <a href="<?= url('/actions?status=overdue') ?>" class="small text-decoration-none">Zobrazit vše</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($overdueActions)): ?>
                    <div class="text-center text-body-secondary py-5">
                        <i class="bi bi-check-circle fs-1 d-block mb-3 text-success opacity-50"></i>
                        <p class="mb-0">Žádná zjištění po termínu</p>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($overdueActions as $action): ?>
                            <li class="list-group-item d-flex align-items-center gap-3 py-3">
                                <div class="rounded bg-danger bg-opacity-10 d-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px;">
                                    <span class="fw-medium small text-danger">#
                                        <?= h((string) $action['number']) ?>
                                    </span>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-medium text-truncate">
                                        <?= h($action['finding']) ?>
                                    </div>
                                    <small class="text-danger">
                                        <i class="bi bi-clock"></i> Prošlý termín:
                                        <?= h($action['deadline']) ?>
                                    </small>
                                </div>
                                <a href="<?= url('/actions/' . $action['id']) ?>" class="btn btn-sm btn-outline-danger">
                                    Detail
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Actions -->
<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="bi bi-calendar-event me-2 text-warning"></i>
                    Blížící se termíny (7 dní)
                </h5>
                <a href="<?= url('/actions?due=week') ?>" class="small text-decoration-none">Zobrazit vše</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($upcomingActions)): ?>
                    <div class="text-center text-body-secondary py-5">
                        <i class="bi bi-calendar-check fs-1 d-block mb-3 opacity-50"></i>
                        <p class="mb-0">Žádná zjištění s termínem v příštích 7 dnech</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Zjištění</th>
                                    <th scope="col">Odpovědný</th>
                                    <th scope="col">Termín</th>
                                    <th scope="col">PDCA</th>
                                    <th scope="col" class="text-end">Akce</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingActions as $action): ?>
                                    <tr>
                                        <td><span class="fw-medium">
                                                <?= h((string) $action['number']) ?>
                                            </span></td>
                                        <td class="text-truncate" style="max-width: 300px;">
                                            <?= h($action['finding']) ?>
                                        </td>
                                        <td>
                                            <?= h($action['responsible']) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning-subtle text-warning">
                                                <?= h($action['deadline']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <!-- PDCA Progress -->
                                            <div class="d-flex gap-1">
                                                <span
                                                    class="badge <?= ($action['status_plan'] ?? false) ? 'bg-success' : 'bg-secondary' ?>"
                                                    title="Plán">P</span>
                                                <span
                                                    class="badge <?= ($action['status_do'] ?? false) ? 'bg-success' : 'bg-secondary' ?>"
                                                    title="Realizace">D</span>
                                                <span
                                                    class="badge <?= ($action['status_check'] ?? false) ? 'bg-success' : 'bg-secondary' ?>"
                                                    title="Ověření">C</span>
                                                <span
                                                    class="badge <?= ($action['status_act'] ?? false) ? 'bg-success' : 'bg-secondary' ?>"
                                                    title="Akce">A</span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('/actions/' . $action['id']) ?>"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= url('/actions/' . $action['id'] . '/edit') ?>"
                                                class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
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
    </div>
</div>