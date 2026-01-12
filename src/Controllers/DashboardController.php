<?php
/**
 * ACTIO - Dashboard Controller
 * 
 * Handles the main dashboard view.
 * 
 * @package Actio\Controllers
 */

declare(strict_types=1);

namespace Actio\Controllers;

use Actio\Core\Request;
use Actio\Core\Response;

class DashboardController
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Display the dashboard
     */
    public function index(array $params = []): void
    {
        // TODO: Load actual statistics from ActionService
        $stats = [
            'total_open' => 12,
            'overdue' => 3,
            'due_this_week' => 5,
            'completed_this_month' => 8,
        ];

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
