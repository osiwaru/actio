<?php
/**
 * ACTIO - Navigation Menu Configuration
 * 
 * Defines the sidebar navigation menu structure.
 * Each item can be a navigation link or a header.
 * 
 * Structure:
 * - For links: ['icon' => 'bi-*', 'label' => 'Label', 'url' => '/path', 'page' => 'page_id']
 * - For headers: ['header' => 'Section Name']
 * - Optional 'roles' key to restrict visibility: ['roles' => ['admin', 'auditor']]
 * 
 * @package Actio\Config
 */

return [
    // Dashboard
    [
        'icon' => 'bi-grid-1x2-fill',
        'label' => 'Dashboard',
        'url' => '/',
        'page' => 'dashboard',
    ],

    // Správa zjištění section
    ['header' => 'Správa zjištění'],
    
    [
        'icon' => 'bi-clipboard-check',
        'label' => 'Zjištění / Opatření',
        'url' => '/actions',
        'page' => 'actions',
    ],
    [
        'icon' => 'bi-journal-text',
        'label' => 'Auditní sezení',
        'url' => '/audit-sessions',
        'page' => 'audit-sessions',
    ],

    // 8D Reporting section (Phase 2 - uncomment when ready)
    // ['header' => '8D Reporting'],
    // [
    //     'icon' => 'bi-diagram-3',
    //     'label' => '8D Případy',
    //     'url' => '/8d',
    //     'page' => '8d',
    //     'roles' => ['admin', 'auditor'],
    // ],

    // Archiv section
    ['header' => 'Archiv'],
    
    [
        'icon' => 'bi-archive',
        'label' => 'Archiv',
        'url' => '/archive',
        'page' => 'archive',
    ],

    // Export section
    ['header' => 'Export'],
    
    [
        'icon' => 'bi-file-earmark-spreadsheet',
        'label' => 'Export CSV',
        'url' => '/export/csv',
        'page' => 'export-csv',
    ],
    [
        'icon' => 'bi-file-earmark-excel',
        'label' => 'Export Excel',
        'url' => '/export/excel',
        'page' => 'export-excel',
    ],
];
