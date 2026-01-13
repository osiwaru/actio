<?php
/**
 * ACTIO - AuditSession Controller
 * 
 * Handles HTTP requests for Audit Sessions CRUD.
 * 
 * Security Requirements:
 * - C07: CSRF validation on POST
 * - C04: XSS prevention (handled in views via h())
 * 
 * @package Actio\Controllers
 */

declare(strict_types=1);

namespace Actio\Controllers;

use Actio\Core\Request;
use Actio\Core\Response;
use Actio\Core\Auth;
use Actio\Services\AuditSessionService;
use Actio\Models\AuditSession;

class AuditSessionController
{
    private Request $request;
    private AuditSessionService $sessionService;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->sessionService = new AuditSessionService();
    }

    /**
     * Display list of audit sessions
     * GET /audit-sessions
     */
    public function index(array $params = []): void
    {
        $sessions = $this->sessionService->getAll();
        $actionCounts = $this->sessionService->getActionCounts();

        Response::viewWithLayout('audit-sessions/list', [
            'sessions' => $sessions,
            'actionCounts' => $actionCounts,
            'currentPage' => 'audit-sessions',
        ], 200, 'Auditní sezení | ACTIO');
    }

    /**
     * Display form for creating new audit session
     * GET /audit-sessions/create
     */
    public function create(array $params = []): void
    {
        $errors = flash('errors') ? json_decode(flash('errors'), true) : [];
        $oldInput = flash('old_input') ? json_decode(flash('old_input'), true) : [];

        Response::viewWithLayout('audit-sessions/form', [
            'session' => null,
            'errors' => $errors,
            'oldInput' => $oldInput,
            'auditTypes' => AuditSession::AUDIT_TYPES,
            'currentPage' => 'audit-sessions',
        ], 200, 'Nové auditní sezení | ACTIO');
    }

    /**
     * Store new audit session
     * POST /audit-sessions
     */
    public function store(array $params = []): void
    {
        // Validate CSRF token (C07)
        $csrfToken = $this->request->input('_csrf_token', '');
        if (!verifyCsrfToken($csrfToken)) {
            flash('error', 'Neplatný bezpečnostní token.');
            Response::redirect(url('/audit-sessions/create'));
            return;
        }

        // Check permission
        if (!Auth::canEdit()) {
            Response::forbidden('Nemáte oprávnění vytvářet auditní sezení.');
            return;
        }

        try {
            $input = $this->getFormInput();
            $session = $this->sessionService->create($input);

            flash('success', 'Auditní sezení bylo úspěšně vytvořeno.');
            Response::redirect(url('/audit-sessions/' . $session['id']));
        } catch (\InvalidArgumentException $e) {
            flash('errors', $e->getMessage());
            flash('old_input', json_encode($this->getFormInput()));
            Response::redirect(url('/audit-sessions/create'));
        }
    }

    /**
     * Display audit session detail
     * GET /audit-sessions/{id}
     */
    public function show(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $session = $this->sessionService->find($id);

        if (!$session) {
            Response::notFound('Auditní sezení nebylo nalezeno.');
            return;
        }

        // Get actions linked to this session
        $actions = $this->sessionService->getActionsForSession($id);

        Response::viewWithLayout('audit-sessions/detail', [
            'session' => $session,
            'actions' => $actions,
            'currentPage' => 'audit-sessions',
        ], 200, h($session['name']) . ' | ACTIO');
    }

    /**
     * Display form for editing audit session
     * GET /audit-sessions/{id}/edit
     */
    public function edit(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $session = $this->sessionService->find($id);

        if (!$session) {
            Response::notFound('Auditní sezení nebylo nalezeno.');
            return;
        }

        $errors = flash('errors') ? json_decode(flash('errors'), true) : [];
        $oldInput = flash('old_input') ? json_decode(flash('old_input'), true) : [];

        Response::viewWithLayout('audit-sessions/form', [
            'session' => $session,
            'errors' => $errors,
            'oldInput' => $oldInput,
            'auditTypes' => AuditSession::AUDIT_TYPES,
            'isEdit' => true,
            'currentPage' => 'audit-sessions',
        ], 200, 'Upravit ' . h($session['name']) . ' | ACTIO');
    }

    /**
     * Update audit session
     * PUT /audit-sessions/{id}
     */
    public function update(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);

        // Validate CSRF token (C07)
        $csrfToken = $this->request->input('_csrf_token', '');
        if (!verifyCsrfToken($csrfToken)) {
            flash('error', 'Neplatný bezpečnostní token.');
            Response::redirect(url('/audit-sessions/' . $id . '/edit'));
            return;
        }

        // Check permission
        if (!Auth::canEdit()) {
            Response::forbidden('Nemáte oprávnění upravovat auditní sezení.');
            return;
        }

        $session = $this->sessionService->find($id);
        if (!$session) {
            Response::notFound('Auditní sezení nebylo nalezeno.');
            return;
        }

        try {
            $input = $this->getFormInput();
            $this->sessionService->update($id, $input);

            flash('success', 'Auditní sezení bylo úspěšně aktualizováno.');
            Response::redirect(url('/audit-sessions/' . $id));
        } catch (\InvalidArgumentException $e) {
            flash('errors', $e->getMessage());
            flash('old_input', json_encode($this->getFormInput()));
            Response::redirect(url('/audit-sessions/' . $id . '/edit'));
        }
    }

    /**
     * Get form input data
     */
    private function getFormInput(): array
    {
        return [
            'name' => $this->request->input('name', ''),
            'type' => $this->request->input('type', ''),
            'date' => $this->request->input('date', ''),
            'auditor' => $this->request->input('auditor', ''),
            'standard' => $this->request->input('standard', ''),
            'notes' => $this->request->input('notes', ''),
        ];
    }
}
