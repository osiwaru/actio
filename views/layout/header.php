<?php
/**
 * ACTIO - Layout Header
 * 
 * Contains the HTML head and opening body/wrapper tags.
 * Based on dashio-template/src/index.html
 * 
 * @package Actio\Views\Layout
 * @var string $pageTitle Page title
 */
?>
<!DOCTYPE html>
<html lang="cs" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ACTIO - Správa akčních plánů z auditů">
    <title>
        <?= h($pageTitle ?? 'ACTIO') ?>
    </title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('favicon.svg') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashio.css') ?>">
</head>

<body>

    <div class="wrapper">