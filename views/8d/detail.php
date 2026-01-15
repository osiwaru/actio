<?php
/**
 * ACTIO - 8D Case Detail View
 * 
 * Displays detailed 8D case with collapsible D1-D8 sections.
 * 
 * Security: XSS prevention via h() (C04)
 * 
 * @package Actio\Views\8D
 * @var \Actio\Models\EightDCase $case 8D Case
 */

use Actio\Models\EightDCase;

$success = flash('success');
$error = flash('error');

/**
 * Render a data field with label
 */
function renderField(string $label, $value, bool $escape = true): void {
    if (empty($value) || (is_array($value) && empty($value))) return;
    
    $displayValue = is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $value;
    if ($escape) {
        $displayValue = h($displayValue);
    }
    echo '<div class="mb-2"><strong class="text-muted">' . h($label) . ':</strong> ' . nl2br($displayValue) . '</div>';
}

/**
 * Render array of items as list
 */
function renderList(array $items, string $keyField = null): void {
    if (empty($items)) {
        echo '<p class="text-muted">Žádná data</p>';
        return;
    }
    
    echo '<ul class="list-unstyled mb-0">';
    foreach ($items as $item) {
        if (is_array($item)) {
            $text = $keyField && isset($item[$keyField]) ? $item[$keyField] : json_encode($item, JSON_UNESCAPED_UNICODE);
            echo '<li class="mb-1"><i class="bi bi-dot"></i>' . h($text) . '</li>';
        } else {
            echo '<li class="mb-1"><i class="bi bi-dot"></i>' . h($item) . '</li>';
        }
    }
    echo '</ul>';
}

/**
 * Render person info
 */
function renderPerson(array $person, string $role = ''): void {
    if (empty($person)) return;
    
    $name = $person['jmeno'] ?? 'N/A';
    $dept = $person['oddeleni'] ?? $person['pozice'] ?? '';
    $contact = $person['kontakt'] ?? '';
    
    echo '<div class="d-flex align-items-center gap-2 mb-2">';
    echo '<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-size: 0.9rem;">';
    echo h(strtoupper(substr($name, 0, 1)));
    echo '</div>';
    echo '<div>';
    echo '<div class="fw-medium">' . h($name) . '</div>';
    if ($dept || $role) {
        echo '<small class="text-muted">' . h($role ?: $dept) . '</small>';
    }
    echo '</div>';
    echo '</div>';
}
?>

<!-- Page Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">
            <span class="badge <?= $case->getStatusBadgeClass() ?> me-2"><?= h($case->getCaseNumber()) ?></span>
            <?= h($case->getName()) ?>
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= url('/8d') ?>">8D Případy</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= h($case->getCaseNumber()) ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/8d') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Zpět na seznam
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

<!-- Meta Info Card -->
<div class="card mb-4">
    <div class="card-header bg-transparent">
        <h5 class="card-title mb-0">
            <i class="bi bi-info-circle me-2"></i>Základní informace
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label text-muted small">Číslo případu</label>
                    <div class="fw-bold"><?= h($case->getCaseNumber()) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label text-muted small">Zákazník</label>
                    <div class="fw-bold"><?= h($case->getCustomer()) ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-3">
                    <label class="form-label text-muted small">Status</label>
                    <div>
                        <span class="badge <?= $case->getStatusBadgeClass() ?> fs-6"><?= h($case->getStatusLabel()) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-3">
                    <label class="form-label text-muted small">Datum vzniku</label>
                    <div><?= $case->getCreatedDate() ? (new DateTime($case->getCreatedDate()))->format('d.m.Y') : '-' ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-3">
                    <label class="form-label text-muted small">Poslední aktualizace</label>
                    <div><?= $case->getUpdatedDate() ? (new DateTime($case->getUpdatedDate()))->format('d.m.Y') : '-' ?></div>
                </div>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="mt-2">
            <label class="form-label text-muted small">Průběh 8D procesu</label>
            <div class="progress" style="height: 24px;">
                <div class="progress-bar bg-primary" style="width: <?= $case->getProgress() ?>%">
                    <?= $case->getProgress() ?>%
                </div>
            </div>
            <div class="d-flex justify-content-between mt-1">
                <?php foreach (EightDCase::D_STEPS as $step): ?>
                    <small class="<?= $case->hasStep($step) ? 'text-primary fw-bold' : 'text-muted' ?>">
                        <?= $step ?>
                    </small>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- D1-D8 Accordion -->
<div class="accordion" id="accordion8D">
    <?php foreach (EightDCase::D_STEPS as $index => $step): ?>
        <?php 
        $stepData = $case->getStep($step);
        $hasData = $case->hasStep($step);
        $mustHave = $case->getMustHave($step);
        $niceToHave = $case->getNiceToHave($step);
        $collapseId = 'collapse' . $step;
        $headingId = 'heading' . $step;
        ?>
        <div class="accordion-item">
            <h2 class="accordion-header" id="<?= $headingId ?>">
                <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" 
                        data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" 
                        aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="<?= $collapseId ?>">
                    <span class="badge bg-primary me-2"><?= $step ?></span>
                    <strong><?= h(EightDCase::getStepLabel($step)) ?></strong>
                    <?php if (!$hasData): ?>
                        <span class="badge bg-secondary ms-2">Prázdné</span>
                    <?php endif; ?>
                </button>
            </h2>
            <div id="<?= $collapseId ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" 
                 aria-labelledby="<?= $headingId ?>" data-bs-parent="#accordion8D">
                <div class="accordion-body">
                    <?php if (!$hasData): ?>
                        <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Tento krok zatím nebyl vyplněn.</p>
                    <?php else: ?>
                        
                        <!-- Must Have Section -->
                        <?php if (!empty($mustHave)): ?>
                            <div class="mb-4">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-check-circle me-1"></i>Povinné položky (Must Have)
                                </h6>
                                <?php
                                // Render step-specific content
                                switch ($step) {
                                    case 'D1':
                                        // Team leader
                                        if (isset($mustHave['vedouci_tymu'])):
                                            echo '<div class="mb-3"><h6 class="text-muted">Vedoucí týmu</h6>';
                                            renderPerson($mustHave['vedouci_tymu'], 'Vedoucí týmu');
                                            echo '</div>';
                                        endif;
                                        
                                        // Sponsor
                                        if (isset($mustHave['sponzor'])):
                                            echo '<div class="mb-3"><h6 class="text-muted">Sponzor</h6>';
                                            renderPerson($mustHave['sponzor'], 'Sponzor');
                                            echo '</div>';
                                        endif;
                                        
                                        // Team members
                                        if (!empty($mustHave['clenove'])):
                                            echo '<div class="mb-3"><h6 class="text-muted">Členové týmu</h6>';
                                            echo '<div class="row">';
                                            foreach ($mustHave['clenove'] as $member):
                                                echo '<div class="col-md-6 col-lg-4 mb-2">';
                                                renderPerson($member, $member['role'] ?? '');
                                                echo '</div>';
                                            endforeach;
                                            echo '</div></div>';
                                        endif;
                                        break;
                                        
                                    case 'D2':
                                        // Problem description
                                        if (isset($mustHave['popis_problemu'])):
                                            echo '<div class="alert alert-warning mb-3">';
                                            echo '<strong>Objekt:</strong> ' . h($mustHave['popis_problemu']['objekt'] ?? '') . '<br>';
                                            echo '<strong>Odchylka:</strong> ' . h($mustHave['popis_problemu']['odchylka'] ?? '');
                                            echo '</div>';
                                        endif;
                                        
                                        // Facts overview
                                        if (isset($mustHave['prehled_skutecnosti'])):
                                            echo '<div class="row mb-3">';
                                            foreach (['co' => 'Co', 'kde' => 'Kde', 'kdy' => 'Kdy', 'kolik' => 'Kolik'] as $key => $label):
                                                if (isset($mustHave['prehled_skutecnosti'][$key])):
                                                    echo '<div class="col-md-6 mb-2">';
                                                    echo '<div class="card h-100"><div class="card-body p-2">';
                                                    echo '<strong class="text-primary">' . $label . ':</strong><br>';
                                                    echo '<small>' . h($mustHave['prehled_skutecnosti'][$key]) . '</small>';
                                                    echo '</div></div></div>';
                                                endif;
                                            endforeach;
                                            echo '</div>';
                                        endif;
                                        
                                        // Is/Is Not analysis
                                        if (!empty($mustHave['analyza_je_neni'])):
                                            echo '<h6 class="text-muted mt-3">Analýza Je / Není</h6>';
                                            echo '<div class="table-responsive"><table class="table table-sm table-bordered">';
                                            echo '<thead class="table-light"><tr><th>Kategorie</th><th class="text-success">Je</th><th class="text-danger">Není</th></tr></thead><tbody>';
                                            foreach ($mustHave['analyza_je_neni'] as $row):
                                                echo '<tr>';
                                                echo '<td class="fw-bold">' . h($row['kategorie'] ?? '') . '</td>';
                                                echo '<td><small>' . h(implode(', ', $row['je'] ?? [])) . '</small></td>';
                                                echo '<td><small>' . h(implode(', ', $row['neni'] ?? [])) . '</small></td>';
                                                echo '</tr>';
                                            endforeach;
                                            echo '</tbody></table></div>';
                                        endif;
                                        break;
                                        
                                    case 'D3':
                                    case 'D5':
                                        // Actions/measures
                                        if (!empty($mustHave['opatreni'])):
                                            echo '<div class="table-responsive"><table class="table table-sm">';
                                            echo '<thead class="table-light"><tr><th>ID</th><th>Popis</th><th>Odpovědná osoba</th><th>Termín</th></tr></thead><tbody>';
                                            foreach ($mustHave['opatreni'] as $action):
                                                echo '<tr>';
                                                echo '<td><code>' . h($action['id'] ?? '') . '</code></td>';
                                                echo '<td><small>' . h(mb_substr($action['popis'] ?? '', 0, 100)) . (mb_strlen($action['popis'] ?? '') > 100 ? '...' : '') . '</small></td>';
                                                echo '<td>' . h($action['odpovdena_osoba'] ?? $action['vybrane_opatreni']['odpovdena_osoba'] ?? '-') . '</td>';
                                                echo '<td>' . h($action['termin_zavedeni'] ?? $action['vybrane_opatreni']['termin'] ?? '-') . '</td>';
                                                echo '</tr>';
                                            endforeach;
                                            echo '</tbody></table></div>';
                                        endif;
                                        break;
                                        
                                    case 'D4':
                                        // Causes
                                        if (!empty($mustHave['priciny'])):
                                            echo '<div class="table-responsive"><table class="table table-sm">';
                                            echo '<thead class="table-light"><tr><th>ID</th><th>Kategorie</th><th>Úroveň</th><th>Popis</th><th>Ověřeno</th></tr></thead><tbody>';
                                            foreach ($mustHave['priciny'] as $cause):
                                                $verified = $cause['overeno'] ?? false;
                                                echo '<tr>';
                                                echo '<td><code>' . h($cause['id'] ?? '') . '</code></td>';
                                                echo '<td><span class="badge ' . ($cause['kategorie'] === 'vyskyt' ? 'bg-warning text-dark' : 'bg-info') . '">' . h($cause['kategorie'] ?? '') . '</span></td>';
                                                echo '<td><small>' . h($cause['uroven'] ?? '') . '</small></td>';
                                                echo '<td><small>' . h(mb_substr($cause['popis'] ?? '', 0, 80)) . '</small></td>';
                                                echo '<td>' . ($verified ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>') . '</td>';
                                                echo '</tr>';
                                            endforeach;
                                            echo '</tbody></table></div>';
                                        endif;
                                        break;
                                        
                                    case 'D6':
                                        // Realization
                                        if (!empty($mustHave['realizace'])):
                                            echo '<h6 class="text-muted">Stav realizace opatření</h6>';
                                            echo '<div class="table-responsive"><table class="table table-sm">';
                                            echo '<thead class="table-light"><tr><th>Opatření</th><th>Stav</th><th>Datum</th><th>Výsledek validace</th></tr></thead><tbody>';
                                            foreach ($mustHave['realizace'] as $real):
                                                $isComplete = ($real['stav_realizace'] ?? '') === 'realizovano';
                                                echo '<tr class="' . ($isComplete ? 'table-success' : '') . '">';
                                                echo '<td><code>' . h($real['opatreni_id'] ?? '') . '</code></td>';
                                                echo '<td><span class="badge ' . ($isComplete ? 'bg-success' : 'bg-warning text-dark') . '">' . h($real['stav_realizace'] ?? '') . '</span></td>';
                                                echo '<td>' . h($real['datum_realizace'] ?? '-') . '</td>';
                                                echo '<td><small>' . h(mb_substr($real['vysledek_validace'] ?? '', 0, 100)) . '</small></td>';
                                                echo '</tr>';
                                            endforeach;
                                            echo '</tbody></table></div>';
                                        endif;
                                        break;
                                        
                                    case 'D7':
                                        // Updated documents
                                        if (!empty($mustHave['aktualizovane_dokumenty'])):
                                            echo '<h6 class="text-muted">Aktualizované dokumenty</h6>';
                                            echo '<ul class="list-group mb-3">';
                                            foreach ($mustHave['aktualizovane_dokumenty'] as $doc):
                                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                                echo '<div><i class="bi bi-file-text me-2"></i>' . h($doc['nazev'] ?? '') . '</div>';
                                                echo '<small class="text-muted">' . h($doc['datum'] ?? '') . '</small>';
                                                echo '</li>';
                                            endforeach;
                                            echo '</ul>';
                                        endif;
                                        
                                        if (!empty($mustHave['posouzeni_aplikovatelnosti'])):
                                            echo '<div class="alert alert-info"><strong>Posouzení aplikovatelnosti:</strong><br>' . h($mustHave['posouzeni_aplikovatelnosti']) . '</div>';
                                        endif;
                                        break;
                                        
                                    case 'D8':
                                        // Closure
                                        if (isset($mustHave['uvolneni'])):
                                            echo '<div class="alert alert-success">';
                                            echo '<h6>Uvolnění případu</h6>';
                                            echo '<p class="mb-1"><strong>Datum:</strong> ' . h($mustHave['uvolneni']['datum'] ?? '') . '</p>';
                                            echo '<p class="mb-1"><strong>Sponzor:</strong> ' . h($mustHave['uvolneni']['sponzor'] ?? '') . '</p>';
                                            echo '<p class="mb-0"><strong>Vedoucí týmu:</strong> ' . h($mustHave['uvolneni']['vedouci_tymu'] ?? '') . '</p>';
                                            echo '</div>';
                                        endif;
                                        
                                        if (!empty($mustHave['stav_opatreni'])):
                                            echo '<h6 class="text-muted">Finální stav opatření</h6>';
                                            echo '<div class="d-flex flex-wrap gap-2 mb-3">';
                                            foreach ($mustHave['stav_opatreni'] as $actionStatus):
                                                $isComplete = ($actionStatus['stav'] ?? '') === 'dokonceno';
                                                echo '<span class="badge ' . ($isComplete ? 'bg-success' : 'bg-warning text-dark') . '">';
                                                echo h($actionStatus['opatreni_id'] ?? '') . ': ' . h($actionStatus['stav'] ?? '');
                                                echo '</span>';
                                            endforeach;
                                            echo '</div>';
                                        endif;
                                        
                                        if (!empty($mustHave['oceneni_tymu'])):
                                            echo '<div class="mb-2"><i class="bi bi-trophy text-warning me-2"></i><strong>Ocenění týmu:</strong> Ano</div>';
                                        endif;
                                        break;
                                        
                                    default:
                                        // Generic rendering
                                        echo '<pre class="bg-light p-3 rounded" style="max-height: 300px; overflow: auto;"><code>';
                                        echo h(json_encode($mustHave, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                                        echo '</code></pre>';
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Nice to Have Section -->
                        <?php if (!empty($niceToHave)): ?>
                            <div class="mt-3 pt-3 border-top">
                                <h6 class="text-secondary">
                                    <i class="bi bi-plus-circle me-1"></i>Doplňující informace (Nice to Have)
                                    <button class="btn btn-sm btn-link" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#niceToHave<?= $step ?>" aria-expanded="false">
                                        Zobrazit/Skrýt
                                    </button>
                                </h6>
                                <div class="collapse" id="niceToHave<?= $step ?>">
                                    <div class="bg-light p-3 rounded mt-2">
                                        <pre style="max-height: 200px; overflow: auto; margin: 0;"><code><?= h(json_encode($niceToHave, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></code></pre>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- JSON Debug (collapsible) -->
<div class="card mt-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0">
            <button class="btn btn-sm btn-link text-decoration-none p-0" type="button" 
                    data-bs-toggle="collapse" data-bs-target="#rawJson" aria-expanded="false">
                <i class="bi bi-code-slash me-1"></i>Raw JSON data
            </button>
        </h6>
    </div>
    <div class="collapse" id="rawJson">
        <div class="card-body">
            <pre class="bg-dark text-light p-3 rounded" style="max-height: 400px; overflow: auto;"><code><?= h(json_encode($case->getAttributes(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></code></pre>
        </div>
    </div>
</div>
