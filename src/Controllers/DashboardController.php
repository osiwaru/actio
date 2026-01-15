<?php
/**
 * ACTIO - Dashboard Controller
 * 
 * Handles the main dashboard view.
 * Extends BaseController for common functionality.
 * 
 * @package Actio\Controllers
 */

declare(strict_types=1);

namespace Actio\Controllers;

use Actio\Core\BaseController;
use Actio\Core\Request;
use Actio\Core\Response;
use Actio\Services\ActionService;

class DashboardController extends BaseController
{
    private ActionService $actionService;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->actionService = new ActionService();
    }

    /**
     * Display the dashboard
     */
    public function index(array $params = []): void
    {
        // Get actual statistics from ActionService
        $stats = $this->actionService->getStats();

        // TODO: Load actual actions from ActionService
        $myActions = [];
        $overdueActions = [];
        $upcomingActions = [];

        Response::viewWithLayout('dashboard', [
            'stats' => $stats,
            'myActions' => $myActions,
            'overdueActions' => $overdueActions,
            'upcomingActions' => $upcomingActions,
            'currentPage' => 'dashboard',
        ], 200, 'Dashboard | ACTIO');
    }
}
