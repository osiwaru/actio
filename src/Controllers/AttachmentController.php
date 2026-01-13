<?php
/**
 * ACTIO - Attachment Controller
 * 
 * Handles HTTP requests for file attachments.
 * 
 * Security Requirements:
 * - C07: CSRF validation on POST/DELETE
 * - F01-F06: Secure file handling (delegated to service)
 * 
 * @package Actio\Controllers
 */

declare(strict_types=1);

namespace Actio\Controllers;

use Actio\Core\Request;
use Actio\Core\Response;
use Actio\Core\Auth;
use Actio\Services\AttachmentService;
use Actio\Services\ActionService;

class AttachmentController
{
    private Request $request;
    private AttachmentService $attachmentService;
    private ActionService $actionService;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->attachmentService = new AttachmentService();
        $this->actionService = new ActionService();
    }

    /**
     * Store new attachment
     * POST /actions/{id}/attachments
     */
    public function store(array $params = []): void
    {
        $actionId = (int) ($params['id'] ?? 0);
        $isAjax = $this->request->isAjax();

        // Response helper
        $respond = function (bool $success, string $message, int $statusCode = 200) use ($isAjax, $actionId) {
            if ($isAjax) {
                http_response_code($success ? 200 : $statusCode);
                header('Content-Type: application/json');
                echo json_encode(['success' => $success, 'message' => $message]);
                exit;
            }

            flash($success ? 'success' : 'error', $message);
            Response::redirect(url('/actions/' . $actionId));
        };

        // Validate CSRF token (C07)
        $csrfToken = $this->request->input('_csrf_token', '');
        if (!verifyCsrfToken($csrfToken)) {
            $respond(false, 'Neplatný bezpečnostní token.', 403);
            return;
        }

        // Check permission
        if (!Auth::canEdit()) {
            $respond(false, 'Nemáte oprávnění nahrávat přílohy.', 403);
            return;
        }

        // Verify action exists
        $action = $this->actionService->find($actionId);
        if (!$action) {
            $respond(false, 'Akce nebyla nalezena.', 404);
            return;
        }

        // Check if file was uploaded
        if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] === UPLOAD_ERR_NO_FILE) {
            $respond(false, 'Nebyl vybrán žádný soubor.', 400);
            return;
        }

        try {
            $description = $this->request->input('description', '');
            $this->attachmentService->upload($actionId, $_FILES['attachment'], $description);
            $respond(true, 'Příloha byla úspěšně nahrána.');
        } catch (\InvalidArgumentException $e) {
            $respond(false, $e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            $respond(false, $e->getMessage(), 500);
        }
    }

    /**
     * Download attachment
     * GET /attachments/{id}/download
     */
    public function download(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $attachment = $this->attachmentService->find($id);

        if (!$attachment) {
            Response::notFound('Příloha nebyla nalezena.');
            return;
        }

        try {
            $this->attachmentService->streamDownload($attachment);
        } catch (\RuntimeException $e) {
            Response::notFound($e->getMessage());
        }
    }

    /**
     * Delete attachment
     * DELETE /attachments/{id}
     */
    public function destroy(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $isAjax = $this->request->isAjax();

        // Response helper
        $respond = function (bool $success, string $message, int $statusCode = 200) use ($isAjax) {
            if ($isAjax) {
                http_response_code($success ? 200 : $statusCode);
                header('Content-Type: application/json');
                echo json_encode(['success' => $success, 'message' => $message]);
                exit;
            }

            flash($success ? 'success' : 'error', $message);
            // We can't easily redirect back to action detail without action ID, 
            // but usually we have referer or fallback.
            // For now, redirect to list if we strictly follow old logic, 
            // but ideally we should redirect to action detail.
            // Since we deleted the attachment, we must find where to go.
            // In AJAX mode, this redirect is ignored.
            // In non-AJAX mode, we try to go back to Referer or Actions list.
            if (isset($_SERVER['HTTP_REFERER'])) {
                Response::redirect($_SERVER['HTTP_REFERER']);
            } else {
                Response::redirect(url('/actions'));
            }
        };

        // Validate CSRF token (C07)
        $csrfToken = $this->request->input('_csrf_token', '');
        if (!verifyCsrfToken($csrfToken)) {
            $respond(false, 'Neplatný bezpečnostní token.', 403);
            return;
        }

        // Check permission
        if (!Auth::canEdit()) {
            $respond(false, 'Nemáte oprávnění mazat přílohy.', 403);
            return;
        }

        $attachment = $this->attachmentService->find($id);
        if (!$attachment) {
            $respond(false, 'Příloha nebyla nalezena.', 404);
            return;
        }

        if ($this->attachmentService->delete($id)) {
            $respond(true, 'Příloha byla úspěšně smazána.');
        } else {
            $respond(false, 'Nepodařilo se smazat přílohu.', 500);
        }
    }
}
