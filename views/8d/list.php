<?php
/**
 * ACTIO - 8D Cases List View
 * 
 * Displays list of 8D cases.
 * 
 * Security: XSS prevention via h() (C04)
 * 
 * @package Actio\Views\8D
 * @var \Actio\Models\EightDCase[] $cases List of 8D cases
 * @var array $stats Statistics
 */

use Actio\Models\EightDCase;

$success = flash('success');
$error = flash('error');
?>

<!-- Page Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">8D Případy</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">8D Případy</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-warning text-dark fs-6">
            <i class="bi bi-clock me-1"></i><?= $stats['open'] ?> otevřených
        </span>
        <span class="badge bg-success fs-6">
            <i class="bi bi-check-circle me-1"></i><?= $stats['closed'] ?> uzavřených
        </span>
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

<!-- 8D Cases Table -->
<div class="card">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">Seznam 8D případů</h5>
        <div class="d-flex gap-2">
            <span class="text-body-secondary small">Celkem: <?= count($cases) ?></span>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($cases)): ?>
            <div class="p-4 text-center text-body-secondary">
                <i class="bi bi-diagram-3 fs-1 mb-3 d-block text-muted"></i>
                <p class="mb-0">Zatím nejsou žádné 8D případy.</p>
                <p class="small text-muted mt-2">8D případy se vytvářejí pomocí Claude AI a ukládají se jako JSON soubory.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 100px;">Číslo</th>
                            <th scope="col" style="width: 30%;">Název</th>
                            <th scope="col" style="width: 150px;">Zákazník</th>
                            <th scope="col" style="width: 110px;">Datum vzniku</th>
                            <th scope="col" style="width: 100px;">Status</th>
                            <th scope="col" style="width: 80px;">Progress</th>
                            <th scope="col" class="text-end" style="width: 80px;">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cases as $case): ?>
                            <tr>
                                <th scope="row">
                                    <a href="<?= url('/8d/' . urlencode($case->getCaseNumber())) ?>" class="text-decoration-none fw-bold">
                                        <?= h($case->getCaseNumber()) ?>
                                    </a>
                                </th>
                                <td>
                                    <div class="fw-medium text-truncate" style="max-width: 350px;" title="<?= h($case->getName()) ?>">
                                        <?= h(mb_substr($case->getName(), 0, 60)) ?>
                                        <?php if (mb_strlen($case->getName()) > 60): ?>...<?php endif; ?>
                                    </div>
                                    <small class="text-body-secondary">
                                        <i class="bi bi-people me-1"></i><?= count($case->getTeamMembers()) ?> členů
                                        &bull;
                                        <i class="bi bi-exclamation-triangle me-1"></i><?= $case->getCausesCount() ?> příčin
                                        &bull;
                                        <i class="bi bi-check2-square me-1"></i><?= $case->getCorrectiveActionsCount() ?> opatření
                                    </small>
                                </td>
                                <td>
                                    <?= h($case->getCustomer()) ?>
                                </td>
                                <td>
                                    <?php 
                                    $dateStr = $case->getCreatedDate();
                                    $dateValid = $dateStr && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr);
                                    ?>
                                    <?php if ($dateValid): ?>
                                        <?= (new DateTime($dateStr))->format('d.m.Y') ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $case->getStatusBadgeClass() ?>">
                                        <?= h($case->getStatusLabel()) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 8px; width: 60px;" title="<?= $case->getProgress() ?>% dokončeno">
                                        <div class="progress-bar bg-primary" style="width: <?= $case->getProgress() ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?= $case->getProgress() ?>%</small>
                                </td>
                                <td class="text-end">
                                    <a href="<?= url('/8d/' . urlencode($case->getCaseNumber())) ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Otevřít detail">
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
