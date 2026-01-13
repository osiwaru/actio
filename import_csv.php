<?php

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

// Set environment for Auth (Simulate Dev Mode for CLI)
$_ENV['APP_ENV'] = 'development';
$_ENV['DEV_MODE'] = 'true';
$_ENV['DEV_USER_EMAIL'] = 'import_script@actio.local';
$_ENV['DEV_USER_NAME'] = 'Import Script';
$_ENV['DEV_USER_ROLE'] = 'admin';

// Define BASE_PATH for Storage
define('BASE_PATH', __DIR__);

// Manually require necessary files (no autoloader in standalone script)
require_once __DIR__ . '/src/Core/Storage.php';
require_once __DIR__ . '/src/Core/Auth.php';
require_once __DIR__ . '/src/Models/Action.php';
require_once __DIR__ . '/src/Services/ActionService.php';

use Actio\Services\ActionService;

// File paths
$processesFile = __DIR__ . '/data/processes.csv';
$importFile = __DIR__ . '/temp/ap_actio.csv';

// Validation
if (!file_exists($processesFile)) {
    die("Error: Processes map file not found: $processesFile\n");
}
if (!file_exists($importFile)) {
    die("Error: Import file not found: $importFile\n");
}

// 1. Load Process Map
$processMap = [];
if (($handle = fopen($processesFile, "r")) !== FALSE) {
    // Check/Skip header
    $header = fgetcsv($handle, 1000, ";"); 
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if (count($data) >= 2) {
            $processMap[trim($data[0])] = trim($data[1]);
        }
    }
    fclose($handle);
}

echo "Loaded " . count($processMap) . " processes.\n";

// Initialize Service
$actionService = new ActionService();
$importedCount = 0;
$skippedCount = 0;
$errors = [];
$rowNumber = 0;

echo "Starting import...\n";

if (($handle = fopen($importFile, "r")) !== FALSE) {
    // Skip Header
    $header = fgetcsv($handle, 0, ";");
    $rowNumber++;

    while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
        $rowNumber++;

        // Basic validation
        if (count($data) < 17) {
            $errors[] = "Row $rowNumber: Insufficient columns (" . count($data) . "). Expected 17.";
            $skippedCount++;
            continue;
        }

        // Extract raw data
        $raw = [
            'audit_session_id' => $data[0],
            'rating' => $data[1],
            'finding_date_raw' => $data[2],
            'deadline_plan_raw' => $data[3],
            'chapter' => $data[4],
            'process' => $data[5],
            'finding' => $data[7],
            'description' => $data[8],
            'problem_cause' => $data[9],
            'measure' => $data[10],
            'responsible' => $data[11],
            'deadline_raw' => $data[12],
            'p' => $data[13],
            'd' => $data[14],
            'c' => $data[15],
            'a' => $data[16],
        ];

        // Process Owner Lookup
        $processOwner = $processMap[trim($raw['process'])] ?? '';

        // Date Conversion (d.m.Y -> Y-m-d)
        // Note: User mentioned Excel numbers (e.g. 45999) were present but then "Opravil jsem datumy". 
        // Assuming d.m.Y format now based on first view.
        $findingDate = convertDate($raw['finding_date_raw']);
        $deadlinePlan = convertDate($raw['deadline_plan_raw']);
        $deadline = convertDate($raw['deadline_raw']);

        // Prepare Data for Service
        $actionData = [
            'audit_session_id' => (int) $raw['audit_session_id'],
            'rating' => trim($raw['rating']),
            'finding_date' => $findingDate,
            'chapter' => trim($raw['chapter']),
            'process' => trim($raw['process']),
            'process_owner' => $processOwner, // Auto-filled
            'finding' => trim($raw['finding']),
            'description' => trim($raw['description']),
            'problem_cause' => trim($raw['problem_cause']),
            'measure' => trim($raw['measure']),
            'responsible' => trim($raw['responsible']),
            'deadline' => $deadline,
            'deadline_plan' => $deadlinePlan,
            'status_plan' => (bool) $raw['p'],
            'status_do' => (bool) $raw['d'],
            'status_check' => (bool) $raw['c'],
            'status_act' => (bool) $raw['a'],
            'archived' => false
        ];

        try {
            // Import via Service (Handling ID generation, validation, saving)
            $actionService->create($actionData);
            $importedCount++;
            echo "."; 
        } catch (Exception $e) {
            $errors[] = "Row $rowNumber Error: " . $e->getMessage();
            $skippedCount++;
            echo "E";
        }
    }
    fclose($handle);
}

echo "\n\nImport Complete.\n";
echo "Imported: $importedCount\n";
echo "Skipped/Error: $skippedCount\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    print_r($errors);
}

/**
 * Helper to convert date from d.m.Y to Y-m-d
 */
function convertDate($dateStr) {
    if (empty($dateStr)) return null;
    $dateStr = trim($dateStr);
    
    // Check if it's already Y-m-d
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
        return $dateStr;
    }

    $d = DateTime::createFromFormat('d.m.Y', $dateStr);
    return $d ? $d->format('Y-m-d') : null;
}
