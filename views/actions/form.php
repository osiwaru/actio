<?php
/**
 * ACTIO - Action Form View
 * 
 * Form for creating and editing actions.
 * Based on dashio-template/src/pages/forms.html
 * 
 * Security: 
 * - XSS prevention via h() (C04)
 * - CSRF token via csrfField() (C06)
 * 
 * @package Actio\Views\Actions
 * @var array|null $action Existing action data (for edit) or null (for create)
 * @var array $errors Validation errors
 * @var array $oldInput Previous form input (for repopulating after error)
 * @var bool $isEdit Whether this is edit mode
 * @var array $processes List of processes from CSV
 * @var array $processOwners Map of process => owner
 */

$isEdit = $isEdit ?? false;
$action = $action ?? [];
$errors = $errors ?? [];
$oldInput = $oldInput ?? [];
$processes = $processes ?? [];
$processOwners = $processOwners ?? [];

// Helper to get field value (old input > existing data > default)
$getValue = function ($field, $default = '') use ($oldInput, $action) {
    return $oldInput[$field] ?? $action[$field] ?? $default;
};

// Helper to check checkbox
$isChecked = function ($field) use ($oldInput, $action) {
    if (!empty($oldInput)) {
        return !empty($oldInput[$field]);
    }
    return !empty($action[$field]);
};

$formAction = $isEdit ? url('/actions/' . $action['id']) : url('/actions');
$formTitle = $isEdit ? 'Upravit akci #' . $action['number'] : 'Nová akce';
?>

<!-- Page Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= h($formTitle) ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= url('/actions') ?>">Zjištění</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $isEdit ? 'Upravit' : 'Nová' ?></li>
            </ol>
        </nav>
    </div>
</div>

<?php if (!empty($errors) && is_array($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <strong>Opravte prosím následující chyby:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $field => $message): ?>
                <li><?= h($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="<?= $formAction ?>" method="POST">
    <?= csrfField() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <div class="row g-4">
        <!-- Main Info Card -->
        <div class="col-lg-8">
            <!-- Phase 1: Zjištění -->
            <div class="card mb-4">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0"><i class="bi bi-1-circle me-2"></i>Zjištění</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Rating -->
                        <div class="col-md-6">
                            <label for="rating" class="form-label">Hodnocení <span class="text-danger">*</span></label>
                            <select class="form-select <?= isset($errors['rating']) ? 'is-invalid' : '' ?>" id="rating"
                                name="rating" required>
                                <option value="">-- Vyberte --</option>
                                <option value="Neshoda" <?= $getValue('rating') === 'Neshoda' ? 'selected' : '' ?>>Neshoda
                                </option>
                                <option value="Doporučení" <?= $getValue('rating') === 'Doporučení' ? 'selected' : '' ?>>
                                    Doporučení</option>
                                <option value="Příležitost ke zlepšení" <?= $getValue('rating') === 'Příležitost ke zlepšení' ? 'selected' : '' ?>>Příležitost ke zlepšení</option>
                                <option value="Pozorování" <?= $getValue('rating') === 'Pozorování' ? 'selected' : '' ?>>
                                    Pozorování</option>
                            </select>
                            <?php if (isset($errors['rating'])): ?>
                                <div class="invalid-feedback"><?= h($errors['rating']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Finding Date -->
                        <div class="col-md-6">
                            <label for="finding_date" class="form-label">Datum zjištění <span
                                    class="text-danger">*</span></label>
                            <input type="date"
                                class="form-control <?= isset($errors['finding_date']) ? 'is-invalid' : '' ?>"
                                id="finding_date" name="finding_date"
                                value="<?= h($getValue('finding_date', date('Y-m-d'))) ?>" required>
                            <?php if (isset($errors['finding_date'])): ?>
                                <div class="invalid-feedback"><?= h($errors['finding_date']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Chapter -->
                        <div class="col-md-6">
                            <label for="chapter" class="form-label">Kapitola normy <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['chapter']) ? 'is-invalid' : '' ?>"
                                id="chapter" name="chapter" value="<?= h($getValue('chapter')) ?>"
                                placeholder="např. 8.5.1" required>
                            <?php if (isset($errors['chapter'])): ?>
                                <div class="invalid-feedback"><?= h($errors['chapter']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Process -->
                        <div class="col-md-6">
                            <label for="process" class="form-label">Proces</label>
                            <select class="form-select" id="process" name="process">
                                <option value="">-- Vyberte proces --</option>
                                <?php foreach ($processes as $proc): ?>
                                    <option value="<?= h($proc) ?>" <?= $getValue('process') === $proc ? 'selected' : '' ?>>
                                        <?= h($proc) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Process Owner -->
                        <div class="col-md-6">
                            <label for="process_owner" class="form-label">Majitel procesu <span
                                    class="text-danger">*</span></label>
                            <select class="form-select <?= isset($errors['process_owner']) ? 'is-invalid' : '' ?>"
                                id="process_owner" name="process_owner" required>
                                <option value="">-- Vyberte --</option>
                                <?php
                                $uniqueOwners = array_unique(array_values($processOwners));
                                foreach ($uniqueOwners as $owner):
                                    ?>
                                    <option value="<?= h($owner) ?>" <?= $getValue('process_owner') === $owner ? 'selected' : '' ?>><?= h($owner) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['process_owner'])): ?>
                                <div class="invalid-feedback"><?= h($errors['process_owner']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Deadline Plan -->
                        <div class="col-md-6">
                            <label for="deadline_plan" class="form-label">Termín plánu</label>
                            <input type="date" class="form-control" id="deadline_plan" name="deadline_plan"
                                value="<?= h($getValue('deadline_plan')) ?>">
                            <div class="form-text">Do kdy má být stanoven plán opatření</div>
                        </div>

                        <!-- Finding -->
                        <div class="col-12">
                            <label for="finding" class="form-label">Zjištění <span class="text-danger">*</span></label>
                            <textarea class="form-control <?= isset($errors['finding']) ? 'is-invalid' : '' ?>"
                                id="finding" name="finding" rows="3" required
                                placeholder="Popište zjištěný nález..."><?= h($getValue('finding')) ?></textarea>
                            <?php if (isset($errors['finding'])): ?>
                                <div class="invalid-feedback"><?= h($errors['finding']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label for="description" class="form-label">Popis</label>
                            <textarea class="form-control" id="description" name="description" rows="2"
                                placeholder="Volitelný podrobnější popis..."><?= h($getValue('description')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Phase 2: Opatření -->
            <div class="card">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0"><i class="bi bi-2-circle me-2"></i>Opatření <small
                            class="text-body-secondary fw-normal">(vyplní se později)</small></h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Problem Cause -->
                        <div class="col-12">
                            <label for="problem_cause" class="form-label">Příčina problému</label>
                            <textarea class="form-control" id="problem_cause" name="problem_cause" rows="2"
                                placeholder="Analýza příčiny problému..."><?= h($getValue('problem_cause')) ?></textarea>
                        </div>

                        <!-- Measure -->
                        <div class="col-12">
                            <label for="measure" class="form-label">Opatření</label>
                            <textarea class="form-control" id="measure" name="measure" rows="2"
                                placeholder="Navržené nápravné opatření..."><?= h($getValue('measure')) ?></textarea>
                        </div>

                        <!-- Responsible -->
                        <div class="col-md-6">
                            <label for="responsible" class="form-label">Odpovědný za realizaci</label>
                            <input type="text" class="form-control" id="responsible" name="responsible"
                                value="<?= h($getValue('responsible')) ?>" placeholder="Jméno odpovědné osoby">
                        </div>

                        <!-- Deadline -->
                        <div class="col-md-6">
                            <label for="deadline" class="form-label">Termín realizace</label>
                            <input type="date" class="form-control" id="deadline" name="deadline"
                                value="<?= h($getValue('deadline')) ?>">
                            <div class="form-text">Do kdy má být opatření implementováno</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Side Panel -->
        <div class="col-lg-4">
            <!-- PDCA Status Card -->
            <div class="card mb-4">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">PDCA Status</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="status_plan" name="status_plan"
                            <?= $isChecked('status_plan') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="status_plan">
                            <strong>P</strong>lan - Naplánováno
                        </label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="status_do" name="status_do"
                            <?= $isChecked('status_do') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="status_do">
                            <strong>D</strong>o - Realizováno
                        </label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="status_check" name="status_check"
                            <?= $isChecked('status_check') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="status_check">
                            <strong>C</strong>heck - Ověřeno
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="status_act" name="status_act"
                            <?= $isChecked('status_act') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="status_act">
                            <strong>A</strong>ct - Uzavřeno
                        </label>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Uložit změny' : 'Vytvořit akci' ?>
                </button>
                <a href="<?= $isEdit ? url('/actions/' . $action['id']) : url('/actions') ?>"
                    class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Zrušit
                </a>
            </div>
        </div>
    </div>
</form>

<script>
    // Auto-fill process owner when process is selected
    document.getElementById('process').addEventListener('change', function () {
        const processOwners = <?= json_encode($processOwners) ?>;
        const selectedProcess = this.value;
        const ownerSelect = document.getElementById('process_owner');

        if (selectedProcess && processOwners[selectedProcess]) {
            // Find and select the matching owner
            const ownerValue = processOwners[selectedProcess];
            for (let option of ownerSelect.options) {
                if (option.value === ownerValue) {
                    option.selected = true;
                    break;
                }
            }
        }
    });
</script>