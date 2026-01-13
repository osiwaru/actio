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
$auditSessions = $auditSessions ?? [];
$attachments = $attachments ?? [];

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
    <div class="d-flex gap-2">
        <button type="submit" form="action-form" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Uložit změny' : 'Vytvořit akci' ?>
        </button>
        <a href="<?= $isEdit ? url('/actions/' . $action['id']) : url('/actions') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-lg me-1"></i>Zrušit
        </a>
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

<!-- Main form (contains only hidden fields, inputs use form="action-form" attribute) -->
<form id="action-form" action="<?= $formAction ?>" method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>
</form>

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
                    <!-- Audit Session -->
                    <div class="<?= $isEdit ? 'col-md-6' : 'col-md-3' ?>">
                        <label for="audit_session_id" class="form-label">Auditní sezení</label>
                        <select class="form-select" id="audit_session_id" name="audit_session_id" form="action-form">
                            <option value="">-- Bez přiřazení --</option>
                            <?php foreach ($auditSessions as $session): ?>
                                <?php
                                $dateObj = !empty($session['date']) ? new DateTime($session['date']) : null;
                                $sessionDate = $dateObj ? $dateObj->format('d.m.Y') : '';
                                $sessionDateIso = $dateObj ? $dateObj->format('Y-m-d') : '';
                                $sessionLabel = $session['name'] . ($sessionDate ? ' (' . $sessionDate . ')' : '');
                                $isSelected = (int) $getValue('audit_session_id') === (int) $session['id'];
                                ?>
                                <option value="<?= h($session['id']) ?>" data-date="<?= h($sessionDateIso) ?>" <?= $isSelected ? 'selected' : '' ?>>
                                    <?= h($sessionLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Rating -->
                    <div class="<?= $isEdit ? 'col-md-6' : 'col-md-3' ?>">
                        <label for="rating" class="form-label">Hodnocení <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['rating']) ? 'is-invalid' : '' ?>" id="rating"
                            name="rating" form="action-form" required>
                            <option value="">-- Vyberte --</option>
                            <option value="Neshoda" <?= $getValue('rating', 'Neshoda') === 'Neshoda' ? 'selected' : '' ?>>Neshoda
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
                    <div class="<?= $isEdit ? 'col-md-6' : 'col-md-3' ?>">
                        <label for="finding_date" class="form-label">Datum zjištění <span
                                class="text-danger">*</span></label>
                        <input type="date"
                            class="form-control <?= isset($errors['finding_date']) ? 'is-invalid' : '' ?>"
                            id="finding_date" name="finding_date" form="action-form"
                            value="<?= h($getValue('finding_date', date('Y-m-d'))) ?>" required>
                        <?php if (isset($errors['finding_date'])): ?>
                            <div class="invalid-feedback"><?= h($errors['finding_date']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Deadline Plan -->
                    <div class="<?= $isEdit ? 'col-md-6' : 'col-md-3' ?>">
                        <label for="deadline_plan" class="form-label">Termín plánu</label>
                        <input type="date" class="form-control" id="deadline_plan" name="deadline_plan"
                            form="action-form" value="<?= h($getValue('deadline_plan')) ?>">
                    </div>

                    <!-- Chapter -->
                    <div class="<?= $isEdit ? 'col-md-6' : 'col-md-4' ?>">
                        <label for="chapter" class="form-label">Kapitola normy <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['chapter']) ? 'is-invalid' : '' ?>"
                            id="chapter" name="chapter" form="action-form" value="<?= h($getValue('chapter')) ?>"
                            placeholder="např. 8.5.1" required>
                        <?php if (isset($errors['chapter'])): ?>
                            <div class="invalid-feedback"><?= h($errors['chapter']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Process -->
                    <div class="<?= $isEdit ? 'col-md-6' : 'col-md-4' ?>">
                        <label for="process" class="form-label">Proces</label>
                        <select class="form-select" id="process" name="process" form="action-form">
                            <option value="">-- Vyberte proces --</option>
                            <?php foreach ($processes as $proc): ?>
                                <option value="<?= h($proc) ?>" <?= $getValue('process') === $proc ? 'selected' : '' ?>>
                                    <?= h($proc) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Process Owner -->
                    <div class="<?= $isEdit ? 'col-md-6' : 'col-md-4' ?>">
                        <label for="process_owner" class="form-label">Majitel procesu <span
                                class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['process_owner']) ? 'is-invalid' : '' ?>"
                            id="process_owner" name="process_owner" form="action-form" required>
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



                    <!-- Finding -->
                    <div class="col-12">
                        <label for="finding" class="form-label">Zjištění <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors['finding']) ? 'is-invalid' : '' ?>" id="finding"
                            name="finding" form="action-form" rows="3" required
                            placeholder="Popište zjištěný nález..."><?= h($getValue('finding')) ?></textarea>
                        <?php if (isset($errors['finding'])): ?>
                            <div class="invalid-feedback"><?= h($errors['finding']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label for="description" class="form-label">Popis</label>
                        <textarea class="form-control" id="description" name="description" form="action-form" rows="2"
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
                        <textarea class="form-control" id="problem_cause" name="problem_cause" form="action-form"
                            rows="2"
                            placeholder="Analýza příčiny problému..."><?= h($getValue('problem_cause')) ?></textarea>
                    </div>

                    <!-- Measure -->
                    <div class="col-12">
                        <label for="measure" class="form-label">Opatření</label>
                        <textarea class="form-control" id="measure" name="measure" form="action-form" rows="2"
                            placeholder="Navržené nápravné opatření..."><?= h($getValue('measure')) ?></textarea>
                    </div>

                    <!-- Responsible -->
                    <div class="col-md-6">
                        <label for="responsible" class="form-label">Odpovědný za realizaci</label>
                        <input type="text" class="form-control" id="responsible" name="responsible" form="action-form"
                            value="<?= h($getValue('responsible')) ?>" placeholder="Jméno odpovědné osoby">
                    </div>

                    <!-- Deadline -->
                    <div class="col-md-6">
                        <label for="deadline" class="form-label">Termín realizace</label>
                        <input type="date" class="form-control" id="deadline" name="deadline" form="action-form"
                            value="<?= h($getValue('deadline')) ?>">
                        <div class="form-text">Do kdy má být opatření implementováno</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Side Panel (outside main form but in same visual row) -->
    <div class="col-lg-4">
        <!-- PDCA Status Card -->
        <div class="card mb-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">PDCA Status</h5>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="status_plan" name="status_plan"
                        form="action-form" <?= $isChecked('status_plan') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="status_plan">
                        <strong>P</strong>lan - Naplánováno
                    </label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="status_do" name="status_do" form="action-form"
                        <?= $isChecked('status_do') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="status_do">
                        <strong>D</strong>o - Realizováno
                    </label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="status_check" name="status_check"
                        form="action-form" <?= $isChecked('status_check') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="status_check">
                        <strong>C</strong>heck - Ověřeno
                    </label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="status_act" name="status_act" form="action-form"
                        <?= $isChecked('status_act') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="status_act">
                        <strong>A</strong>ct - Uzavřeno
                    </label>
                </div>
            </div>
        </div>


        <?php if ($isEdit): ?>
            <!-- Attachments Card -->
            <div class="card mb-4">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="bi bi-paperclip me-2"></i>Přílohy</h5>
                    <span class="badge bg-secondary"><?= count($attachments ?? []) ?></span>
                </div>
                <div class="card-body">
                    <?php if (!empty($attachments)): ?>
                        <div class="list-group list-group-flush mb-3">
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
                                    <div class="d-flex gap-2">
                                        <a href="<?= url('/attachments/' . $att['id'] . '/download') ?>"
                                            class="btn btn-sm btn-outline-primary" title="Stáhnout">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <form action="<?= url('/attachments/' . $att['id']) ?>" method="POST" style="display:inline"
                                            onsubmit="return confirm('Opravdu smazat přílohu?')">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Smazat">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-body-secondary mb-0">Zatím žádné přílohy.</p>
                    <?php endif; ?>

                    <!-- Upload Form -->
                    <div class="border-top pt-3 mt-3">
                        <h6 class="mb-2"><i class="bi bi-upload me-1"></i>Nahrát přílohu</h6>
                        <form id="upload-form" action="<?= url('/actions/' . $action['id'] . '/attachments') ?>" method="POST"
                            enctype="multipart/form-data">
                            <?= csrfField() ?>
                            <div class="mb-2">
                                <input type="file" class="form-control form-control-sm" name="attachment" required
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.txt">
                                <div class="form-text">Max 10 MB</div>
                            </div>
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" name="description"
                                    placeholder="Popis (volitelné)">
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-upload me-1"></i>Nahrát
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            </div>
        <?php else: ?>
            <!-- Attachments Info (Create Mode) -->
            <div class="card mb-4">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0"><i class="bi bi-paperclip me-2"></i>Přílohy</h5>
                </div>
                <div class="card-body text-center py-4">
                    <i class="bi bi-info-circle text-primary fs-3 mb-2 d-block"></i>
                    <p class="text-body-secondary mb-0">
                        Přílohy bude možné nahrát až po vytvoření akce.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

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

    // Helper to add working days
    function addWorkDays(startDate, days) {
        let currentDate = new Date(startDate);
        let addedDays = 0;
        while (addedDays < days) {
            currentDate.setDate(currentDate.getDate() + 1);
            const day = currentDate.getDay();
            if (day !== 0 && day !== 6) {
                addedDays++;
            }
        }
        return currentDate;
    }

    // Calculate deadline plan
    function updateDeadlinePlan() {
        const findingDateInput = document.getElementById('finding_date');
        const deadlinePlanInput = document.getElementById('deadline_plan');
        
        if (findingDateInput && findingDateInput.value && deadlinePlanInput) {
            const findingDate = new Date(findingDateInput.value);
            const deadlineDate = addWorkDays(findingDate, 10);
            deadlinePlanInput.value = deadlineDate.toISOString().split('T')[0];
        }
    }

    // Auto-fill finding date from audit session
    function updateFindingDateFromSession() {
        const select = document.getElementById('audit_session_id');
        const selectedOption = select.options[select.selectedIndex];
        const date = selectedOption ? selectedOption.dataset.date : null;
        const dateInput = document.getElementById('finding_date');

        // Only update if dateInput is empty or if we want to force update on session change
        // For pre-selection, we probably want to update if it matches the session logic
        if (date && dateInput) {
             // Check if it's already set to something else manually? 
             // Requirement says: "Datum zjištění se nepropíše". So if session is selected, use that date.
            dateInput.value = date;
            updateDeadlinePlan();
        }
    }

    const auditSessionSelect = document.getElementById('audit_session_id');
    auditSessionSelect.addEventListener('change', updateFindingDateFromSession);
    
    // Run on load if session is selected
    if (auditSessionSelect.value) {
        updateFindingDateFromSession();
    }

    // Recalculate deadline when finding date changes
    document.getElementById('finding_date').addEventListener('change', updateDeadlinePlan);

    // AJAX Upload Handling
    const uploadForm = document.getElementById('upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function (e) {
            e.preventDefault();
            
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Nahrávám...';
            
            fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || 'Chyba nahrávání'); });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload page to show new attachment
                } else {
                    throw new Error(data.message || 'Neznámá chyba');
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                alert(error.message || 'Došlo k chybě při nahrávání souboru.');
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }

    // AJAX Delete Handling
    document.querySelectorAll('form[action*="/attachments/"][method="POST"]').forEach(form => {
        if (form.querySelector('input[name="_method"][value="DELETE"]')) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!confirm('Opravdu smazat přílohu?')) return;
                
                const btn = this.querySelector('button[type="submit"]');
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';

                fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Chyba: ' + (data.message || 'Nepodařilo se smazat přílohu'));
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Chyba při komunikaci se serverem.');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                });
            });
        }
    });
</script>