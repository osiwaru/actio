<?php
/**
 * ACTIO - 8D Controller
 * 
 * Handles HTTP requests for 8D Reporting module.
 * Extends BaseController for common functionality.
 * 
 * @package Actio\Controllers
 */

declare(strict_types=1);

namespace Actio\Controllers;

use Actio\Core\BaseController;
use Actio\Core\Request;
use Actio\Core\Response;
use Actio\Services\EightDService;

class EightDController extends BaseController
{
    private EightDService $eightDService;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->eightDService = new EightDService();
    }

    /**
     * Display list of 8D cases
     * GET /8d
     */
    public function index(array $params = []): void
    {
        $cases = $this->eightDService->getAll();
        $stats = $this->eightDService->getStats();

        Response::viewWithLayout('8d/list', [
            'cases' => $cases,
            'stats' => $stats,
            'currentPage' => '8d',
        ], 200, '8D Případy | ACTIO');
    }

    /**
     * Display 8D case detail
     * GET /8d/{caseNumber}
     */
    public function show(array $params = []): void
    {
        $caseNumber = $params['filename'] ?? '';
        
        if (empty($caseNumber)) {
            Response::notFound('8D případ nebyl nalezen.');
            return;
        }

        $case = $this->eightDService->findByCaseNumber($caseNumber);

        if ($case === null) {
            Response::notFound('8D případ nebyl nalezen.');
            return;
        }

        $title = $case->getCaseNumber() . ' - ' . $case->getName() . ' | ACTIO';
        $data = [
            'case' => $case,
            'currentPage' => '8d',
        ];

        Response::viewWithLayout('8d/detail', $data, 200, $title);
    }
}
