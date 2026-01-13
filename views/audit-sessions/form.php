<?php
/**
 * ACTIO - Audit Session Form View
 * 
 * Form for creating new audit sessions.
 * Based on dashio-template/src/pages/forms.html
 * 
 * Security: 
 * - XSS prevention via h() (C04)
 * - CSRF token via csrfField() (C06)
 * 
 * @package Actio\Views\AuditSessions
 * @var array|null $session Existing session data (for edit) or null (for create)
 * @var array $errors Validation errors
 * @var array $oldInput Previous form input (for repopulating after error)
 * @var array $auditTypes Available audit types
 */

$session = $session ?? [];
$errors = $errors ?? [];
$oldInput = $oldInput ?? [];
$auditTypes = $auditTypes ?? [];
$isEdit = $isEdit ?? false;

// Helper to get field value (old input > existing data > default)
$getValue = function ($field, $default = '') use ($oldInput, $session) {
    return $oldInput[$field] ?? $session[$field] ?? $default;
};

$formAction = $isEdit ? url('/audit-sessions/' . $session['id']) : url('/audit-sessions');
$formTitle = $isEdit ? 'Upravit ' . h($session['name']) : 'Nové auditní sezení';
?>

<!-- Page Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">
            <?= h($formTitle) ?>
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= url('/audit-sessions') ?>">Auditní sezení</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $isEdit ? 'Upravit' : 'Nové' ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" form="session-form" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Uložit změny' : 'Vytvořit sezení' ?>
        </button>
        <a href="<?= $isEdit ? url('/audit-sessions/' . $session['id']) : url('/audit-sessions') ?>"
            class="btn btn-outline-secondary">
            <i class="bi bi-x-lg me-1"></i>Zrušit
        </a>
    </div>
</div>

<?php if (!empty($errors) && is_array($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <strong>Opravte prosím následující chyby:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $field => $message): ?>
                <li>
                    <?= h($message) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form id="session-form" action="<?= $formAction ?>" method="POST">
    <?= csrfField() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <div class="row g-4">
        <!-- Main Form Card -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0"><i class="bi bi-journal-text me-2"></i>Údaje o auditu</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-12">
                            <label for="name" class="form-label">Název auditu <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                id="name" name="name" value="<?= h($getValue('name')) ?>"
                                placeholder="např. Certifikace ISO 9001 - leden 2026" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback">
                                    <?= h($errors['name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Type -->
                        <div class="col-md-6">
                            <label for="type" class="form-label">Typ auditu <span class="text-danger">*</span></label>
                            <select class="form-select <?= isset($errors['type']) ? 'is-invalid' : '' ?>" id="type"
                                name="type" required>
                                <option value="">-- Vyberte typ --</option>
                                <?php foreach ($auditTypes as $type): ?>
                                    <option value="<?= h($type) ?>" <?= $getValue('type') === $type ? 'selected' : '' ?>>
                                        <?= h($type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['type'])): ?>
                                <div class="invalid-feedback">
                                    <?= h($errors['type']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Date -->
                        <div class="col-md-6">
                            <label for="date" class="form-label">Datum auditu <span class="text-danger">*</span></label>
                            <input type="date" class="form-control <?= isset($errors['date']) ? 'is-invalid' : '' ?>"
                                id="date" name="date" value="<?= h($getValue('date', date('Y-m-d'))) ?>" required>
                            <?php if (isset($errors['date'])): ?>
                                <div class="invalid-feedback">
                                    <?= h($errors['date']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Auditor -->
                        <div class="col-md-6">
                            <label for="auditor" class="form-label">Auditor</label>
                            <input type="text" class="form-control" id="auditor" name="auditor"
                                value="<?= h($getValue('auditor')) ?>" placeholder="Jméno auditora">
                        </div>

                        <!-- Standard -->
                        <div class="col-md-6">
                            <label for="standard" class="form-label">Norma</label>
                            <input type="text" class="form-control" id="standard" name="standard"
                                value="<?= h($getValue('standard')) ?>" placeholder="např. ISO 9001:2015">
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label for="notes" class="form-label">Poznámky</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                placeholder="Volitelné poznámky k auditu..."><?= h($getValue('notes')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>