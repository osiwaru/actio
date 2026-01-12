<?php
/**
 * ACTIO - Route Definitions
 * 
 * All application routes are defined here.
 * 
 * @package Actio
 * @var \Actio\Core\Router $router
 */

declare(strict_types=1);

// Dashboard
$router->get('/', 'DashboardController@index');

// Actions (Zjištění/Opatření)
$router->get('/actions', 'ActionController@index');
$router->get('/actions/create', 'ActionController@create');
$router->post('/actions', 'ActionController@store');
$router->get('/actions/{id}', 'ActionController@show');
$router->get('/actions/{id}/edit', 'ActionController@edit');
$router->put('/actions/{id}', 'ActionController@update');
$router->delete('/actions/{id}', 'ActionController@destroy');
$router->post('/actions/{id}/archive', 'ActionController@archive');

// Audit Sessions (Auditní sezení)
// $router->get('/audit-sessions', 'AuditSessionController@index');
// $router->get('/audit-sessions/create', 'AuditSessionController@create');
// $router->post('/audit-sessions', 'AuditSessionController@store');
// $router->get('/audit-sessions/{id}', 'AuditSessionController@show');

// Archive
// $router->get('/archive', 'ArchiveController@index');
// $router->post('/archive/{id}/restore', 'ArchiveController@restore');

// Export
// $router->get('/export/csv', 'ExportController@csv');
// $router->get('/export/excel', 'ExportController@excel');

// Auth
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');
