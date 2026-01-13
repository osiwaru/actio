<?php
/**
 * ACTIO - Action Controller
 * 
 * Handles HTTP requests for Actions (Zjištění/Opatření) CRUD.
 * 
 * Security Requirements:
 * - C07: CSRF validation on POST/PUT/DELETE
 * - C04: XSS prevention (handled in views via h())
 * 
 * @package Actio\Controllers
 */

declare(strict_types=1);

namespace Actio\Controllers;

use Actio\Core\Request;
use Actio\Core\Response;
use Actio\Core\Auth;
use Actio\Services\ActionService;
use Actio\Services\AuditSessionService;
use Actio\Services\AttachmentService;

class ActionController
{
    private Request $request;
    private ActionService $actionService;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->actionService = new ActionService();
    }

    /**
     * Display list of actions
     * GET /actions
     */
    public function index(array $params = []): void
    {
        $actions = $this->actionService->getAll();
        
        // Get audit sessions for display
        $auditSessionService = new AuditSessionService();
        $auditSessions = $auditSessionService->getAll();
        $auditSessionMap = [];
        foreach ($auditSessions as $session) {
            $auditSessionMap[$session['id']] = $session['name'];
        }

        Response::viewWithLayout('actions/list', [
            'actions' => $actions,
            'auditSessionMap' => $auditSessionMap,
            'currentPage' => 'actions',
        ], 200, 'Zjištění / Opatření | ACTIO');
    }

    /**
     * Display form for creating new action
     * GET /actions/create
     */
    public function create(array $params = []): void
    {
        $errorsJson = flash('errors');
        $errors = $errorsJson ? json_decode($errorsJson, true) : [];
        $oldInputJson = flash('old_input');
        $oldInput = $oldInputJson ? json_decode($oldInputJson, true) : [];
        $processData = self::getProcesses();
        $auditSessionService = new AuditSessionService();

        // Handle preselected audit session from URL
        $preselectedSessionId = $this->request->query('audit_session_id');
        if ($preselectedSessionId && empty($oldInput['audit_session_id'])) {
            $oldInput['audit_session_id'] = (int) $preselectedSessionId;
        }

        Response::viewWithLayout('actions/form', [
            'action' => null,
            'errors' => $errors,
            'oldInput' => $oldInput,
            'isEdit' => false,
            'processes' => $processData['processes'],
            'processOwners' => $processData['owners'],
            'auditSessions' => $auditSessionService->getAll(),
            'currentPage' => 'actions',
        ], 200, 'Nová akce | ACTIO');
    }

    /**
     * Store new action
     * POST /actions
     */
    public function store(array $params = []): void
    {
        // Validate CSRF token (C07)
        $csrfToken = $this->request->input('_csrf_token', '');
        if (!verifyCsrfToken($csrfToken)) {
            flash('error', 'Neplatný bezpečnostní token.');
            Response::redirect(url('/actions/create'));
            return;
        }

        // Check permission
        if (!Auth::canEdit()) {
            Response::forbidden('Nemáte oprávnění vytvářet akce.');
            return;
        }

        try {
            $input = $this->getFormInput();
            $action = $this->actionService->create($input);

            flash('success', 'Akce byla úspěšně vytvořena.');

            Response::redirect(url('/actions/' . $action['id']));
        } catch (\InvalidArgumentException $e) {
            flash('errors', $e->getMessage());
            flash('old_input', json_encode($this->getFormInput()));
            Response::redirect(url('/actions/create'));
        }
    }

    /**
     * Display action detail
     * GET /actions/{id}
     */
    public function show(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $action = $this->actionService->find($id);

        if (!$action) {
            Response::notFound('Akce nebyla nalezena.');
            return;
        }

        // Get attachments for this action
        $attachmentService = new AttachmentService();
        $attachments = $attachmentService->getForAction($id);

        Response::viewWithLayout('actions/detail', [
            'action' => $action,
            'attachments' => $attachments,
            'currentPage' => 'actions',
        ], 200, 'Akce #' . $action['number'] . ' | ACTIO');
    }

    /**
     * Display form for editing action
     * GET /actions/{id}/edit
     */
    public function edit(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $action = $this->actionService->find($id);

        if (!$action) {
            Response::notFound('Akce nebyla nalezena.');
            return;
        }

        $errorsJson = flash('errors');
        $errors = $errorsJson ? json_decode($errorsJson, true) : [];
        $oldInputJson = flash('old_input');
        $oldInput = $oldInputJson ? json_decode($oldInputJson, true) : [];
        $processData = self::getProcesses();
        $auditSessionService = new AuditSessionService();
        $attachmentService = new AttachmentService();

        Response::viewWithLayout('actions/form', [
            'action' => $action,
            'errors' => $errors,
            'oldInput' => $oldInput,
            'isEdit' => true,
            'processes' => $processData['processes'],
            'processOwners' => $processData['owners'],
            'auditSessions' => $auditSessionService->getAll(),
            'attachments' => $attachmentService->getForAction($id),
            'currentPage' => 'actions',
        ], 200, 'Upravit akci #' . $action['number'] . ' | ACTIO');
    }

    /**
     * Update action
     * PUT /actions/{id}
     */
    public function update(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);

        // Validate CSRF token (C07)
        $csrfToken = $this->request->input('_csrf_token', '');
        if (!verifyCsrfToken($csrfToken)) {
            flash('error', 'Neplatný bezpečnostní token.');
            Response::redirect(url('/actions/' . $id . '/edit'));
            return;
        }

        // Check permission
        if (!Auth::canEdit()) {
            Response::forbidden('Nemáte oprávnění upravovat akce.');
            return;
        }

        $action = $this->actionService->find($id);
        if (!$action) {
            Response::notFound('Akce nebyla nalezena.');
            return;
        }

        try {
            $input = $this->getFormInput();
            $this->actionService->update($id, $input);

            flash('success', 'Akce byla úspěšně aktualizována.');
            Response::redirect(url('/actions/' . $id));
        } catch (\InvalidArgumentException $e) {
            flash('errors', $e->getMessage());
            flash('old_input', json_encode($this->getFormInput()));
            Response::redirect(url('/actions/' . $id . '/edit'));
        }
    }

    /**
     * Delete action
     * DELETE /actions/{id}
     */
    public function destroy(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);

        // Validate CSRF token (C07)
        $csrfToken = $this->request->input('_csrf_token', '');
        if (!verifyCsrfToken($csrfToken)) {
            flash('error', 'Neplatný bezpečnostní token.');
            Response::redirect(url('/actions'));
            return;
        }

        // Check permission - only admin and auditor can delete
        if (!Auth::isAuditor()) {
            Response::forbidden('Nemáte oprávnění mazat akce.');
            return;
        }

        $action = $this->actionService->find($id);
        if (!$action) {
            Response::notFound('Akce nebyla nalezena.');
            return;
        }

        $this->actionService->delete($id);

        flash('success', 'Akce byla úspěšně smazána.');
        Response::redirect(url('/actions'));
    }

    /**
     * Archive action
     * POST /actions/{id}/archive
     */
    public function archive(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);

        // Validate CSRF token (C07)
        $csrfToken = $this->request->input('_csrf_token', '');
        if (!verifyCsrfToken($csrfToken)) {
            flash('error', 'Neplatný bezpečnostní token.');
            Response::redirect(url('/actions/' . $id));
            return;
        }

        // Check permission
        if (!Auth::canEdit()) {
            Response::forbidden('Nemáte oprávnění archivovat akce.');
            return;
        }

        $result = $this->actionService->archive($id);

        if ($result) {
            flash('success', 'Akce byla úspěšně archivována.');
        } else {
            flash('error', 'Akci se nepodařilo archivovat.');
        }

        Response::redirect(url('/actions'));
    }

    /**
     * Get form input data
     */
    private function getFormInput(): array
    {
        return [
            'rating' => $this->request->input('rating', ''),
            'finding' => $this->request->input('finding', ''),
            'description' => $this->request->input('description', ''),
            'chapter' => $this->request->input('chapter', ''),
            'problem_cause' => $this->request->input('problem_cause', ''),
            'measure' => $this->request->input('measure', ''),
            'process' => $this->request->input('process', ''),
            'process_owner' => $this->request->input('process_owner', ''),
            'responsible' => $this->request->input('responsible', ''),
            'deadline' => $this->request->input('deadline', ''),
            'deadline_plan' => $this->request->input('deadline_plan', ''),
            'finding_date' => $this->request->input('finding_date', ''),
            'status_plan' => $this->request->has('status_plan'),
            'status_do' => $this->request->has('status_do'),
            'status_check' => $this->request->has('status_check'),
            'status_act' => $this->request->has('status_act'),
            'audit_session_id' => $this->request->input('audit_session_id') ?: null,
        ];
    }

    /**
     * Load processes from CSV file
     * 
     * @return array Array with 'processes' and 'owners' lists
     */
    public static function getProcesses(): array
    {
        $csvPath = BASE_PATH . '/data/processes.csv';
        $processes = [];
        $owners = [];

        if (!file_exists($csvPath)) {
            return ['processes' => [], 'owners' => []];
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            return ['processes' => [], 'owners' => []];
        }

        // Skip header
        fgetcsv($handle, 1000, ';');

        while (($row = fgetcsv($handle, 1000, ';')) !== false) {
            if (count($row) >= 2) {
                $processes[] = $row[0];
                $owners[$row[0]] = $row[1]; // Map process to owner
            }
        }

        fclose($handle);

        return [
            'processes' => array_unique($processes),
            'owners' => $owners,
            'ownersList' => array_unique(array_values($owners)),
        ];
    }
}
