<?php
/**
 * ACTIO - Actions List View
 * 
 * Displays list of actions in a table.
 * Based on dashio-template/src/pages/tables.html
 * 
 * Security: XSS prevention via h() (C04)
 * 
 * @package Actio\Views\Actions
 * @var array $actions List of actions
 */

$success = flash('success');
$error = flash('error');
?>

<!-- Page Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Zjištění / Opatření</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Zjištění</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="<?= url('/actions/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus me-1"></i>Nová akce
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

<!-- Actions Table -->
<div class="card">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">Seznam akcí</h5>
        <div class="d-flex gap-2">
            <span class="text-body-secondary small">Celkem:
                <?= count($actions) ?>
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($actions)): ?>
            <div class="p-4 text-center text-body-secondary">
                <i class="bi bi-clipboard-x fs-1 mb-3 d-block text-muted"></i>
                <p class="mb-0">Zatím nejsou žádné akce.</p>
                <a href="<?= url('/actions/create') ?>" class="btn btn-primary mt-3">
                    <i class="bi bi-plus me-1"></i>Vytvořit první akci
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive" style="overflow: visible;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 60px;">#</th>
                            <th scope="col">Zjištění</th>
                            <th scope="col" style="width: 150px;">Odpovědný</th>
                            <th scope="col" style="width: 110px;">Termín</th>
                            <th scope="col" style="width: 160px;">PDCA</th>
                            <th scope="col" class="text-end" style="width: 100px;">Akce</th>
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
                                    <div class="fw-medium text-truncate" style="max-width: 400px;"
                                        title="<?= h($action['finding']) ?>">
                                        <?= h(mb_substr($action['finding'], 0, 80)) ?>
                                        <?php if (mb_strlen($action['finding']) > 80): ?>...
                                        <?php endif; ?>
                                    </div>
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
                                        <span class="badge <?= !empty($action['status_do']) ? 'bg-success' : 'bg-secondary' ?>"
                                            title="Do">D</span>
                                        <span
                                            class="badge <?= !empty($action['status_check']) ? 'bg-success' : 'bg-secondary' ?>"
                                            title="Check">C</span>
                                        <span class="badge <?= !empty($action['status_act']) ? 'bg-success' : 'bg-secondary' ?>"
                                            title="Act">A</span>
                                        <?php if ($isComplete): ?>
                                            <span class="badge bg-primary ms-1" title="Dokončeno">✓</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-link text-body" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="<?= url('/actions/' . $action['id']) ?>"><i
                                                        class="bi bi-eye me-2"></i>Detail</a></li>
                                            <li><a class="dropdown-item"
                                                    href="<?= url('/actions/' . $action['id'] . '/edit') ?>"><i
                                                        class="bi bi-pencil me-2"></i>Upravit</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <form action="<?= url('/actions/' . $action['id']) ?>" method="POST"
                                                    onsubmit="return confirm('Opravdu chcete smazat tuto akci?');">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <button type="submit" class="dropdown-item text-danger"><i
                                                            class="bi bi-trash me-2"></i>Smazat</button>
                                                </form>
                                            </li>
                                        </ul>
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