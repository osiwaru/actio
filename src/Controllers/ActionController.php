<?php
/**
 * ACTIO - Action Controller
 * 
 * Handles HTTP requests for Actions (Zjištění/Opatření) CRUD.
 * Extends BaseController for common functionality.
 * 
 * Security Requirements:
 * - C07: CSRF validation on POST/PUT/DELETE (via BaseController)
 * - C04: XSS prevention (handled in views via h())
 * 
 * @package Actio\Controllers
 */

declare(strict_types=1);

namespace Actio\Controllers;

use Actio\Core\BaseController;
use Actio\Core\Request;
use Actio\Core\Response;
use Actio\Services\ActionService;
use Actio\Services\AuditSessionService;
use Actio\Services\AttachmentService;

class ActionController extends BaseController
{
    private ActionService $actionService;
    private AuditSessionService $auditSessionService;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->actionService = new ActionService();
        $this->auditSessionService = new AuditSessionService();
    }

    /**
     * Display list of actions
     * GET /actions
     */
    public function index(array $params = []): void
    {
        $actions = $this->actionService->getAll();
        
        // Get audit sessions for display
        $auditSessions = $this->auditSessionService->getAll();
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

        // Handle preselected audit session from URL
        $preselectedSessionId = $this->query('audit_session_id');
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
            'auditSessions' => $this->auditSessionService->getAll(),
            'currentPage' => 'actions',
        ], 200, 'Nová akce | ACTIO');
    }

    /**
     * Store new action
     * POST /actions
     */
    public function store(array $params = []): void
    {
        // Validate CSRF token (C07) - using BaseController method
        if (!$this->validateCsrf(url('/actions/create'))) {
            return;
        }

        // Check permission - using BaseController method
        if (!$this->requireCanEdit('Nemáte oprávnění vytvářet akce.')) {
            return;
        }

        try {
            $input = $this->getFormInput();
            $action = $this->actionService->create($input);

            $this->redirect(url('/actions/' . $action['id']), 'success', 'Akce byla úspěšně vytvořena.');
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
        $attachmentService = new AttachmentService();

        Response::viewWithLayout('actions/form', [
            'action' => $action,
            'errors' => $errors,
            'oldInput' => $oldInput,
            'isEdit' => true,
            'processes' => $processData['processes'],
            'processOwners' => $processData['owners'],
            'auditSessions' => $this->auditSessionService->getAll(),
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

        // Validate CSRF token (C07) - using BaseController method
        if (!$this->validateCsrf(url('/actions/' . $id . '/edit'))) {
            return;
        }

        // Check permission - using BaseController method
        if (!$this->requireCanEdit('Nemáte oprávnění upravovat akce.')) {
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

            $this->redirect(url('/actions/' . $id), 'success', 'Akce byla úspěšně aktualizována.');
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

        // Validate CSRF token (C07) - using BaseController method
        if (!$this->validateCsrf(url('/actions'))) {
            return;
        }

        // Check permission - only admin and auditor can delete
        if (!$this->requireAuditor('Nemáte oprávnění mazat akce.')) {
            return;
        }

        $action = $this->actionService->find($id);
        if (!$action) {
            Response::notFound('Akce nebyla nalezena.');
            return;
        }

        $this->actionService->delete($id);

        $this->redirect(url('/actions'), 'success', 'Akce byla úspěšně smazána.');
    }

    /**
     * Archive action
     * POST /actions/{id}/archive
     */
    public function archive(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);

        // Validate CSRF token (C07) - using BaseController method
        if (!$this->validateCsrf(url('/actions/' . $id))) {
            return;
        }

        // Check permission - using BaseController method
        if (!$this->requireCanEdit('Nemáte oprávnění archivovat akce.')) {
            return;
        }

        $result = $this->actionService->archive($id);

        if ($result) {
            $this->redirect(url('/actions'), 'success', 'Akce byla úspěšně archivována.');
        } else {
            $this->redirect(url('/actions'), 'error', 'Akci se nepodařilo archivovat.');
        }
    }

    /**
     * Get form input data
     */
    private function getFormInput(): array
    {
        return [
            'rating' => $this->input('rating', ''),
            'finding' => $this->input('finding', ''),
            'description' => $this->input('description', ''),
            'chapter' => $this->input('chapter', ''),
            'problem_cause' => $this->input('problem_cause', ''),
            'measure' => $this->input('measure', ''),
            'process' => $this->input('process', ''),
            'process_owner' => $this->input('process_owner', ''),
            'responsible' => $this->input('responsible', ''),
            'deadline' => $this->input('deadline', ''),
            'deadline_plan' => $this->input('deadline_plan', ''),
            'finding_date' => $this->input('finding_date', ''),
            'status_plan' => $this->hasInput('status_plan'),
            'status_do' => $this->hasInput('status_do'),
            'status_check' => $this->hasInput('status_check'),
            'status_act' => $this->hasInput('status_act'),
            'audit_session_id' => $this->input('audit_session_id') ?: null,
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
