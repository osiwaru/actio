<?php
/**
 * ACTIO - Front Controller
 * 
 * All requests are routed through this file.
 * 
 * @package Actio
 */

declare(strict_types=1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Autoload (will be replaced with proper autoloader later)
require_once BASE_PATH . '/src/Core/bootstrap.php';
