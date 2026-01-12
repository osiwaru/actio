<?php
/**
 * ACTIO - Action Service
 * 
 * Business logic layer for Actions (Zjištění/Opatření) CRUD operations.
 * 
 * @package Actio\Services
 */

declare(strict_types=1);

namespace Actio\Services;

use Actio\Core\Storage;
use Actio\Core\Auth;
use Actio\Models\Action;

class ActionService
{
    private const DATA_FILE = 'actions.json';

    private Storage $storage;

    public function __construct(?Storage $storage = null)
    {
        $this->storage = $storage ?? new Storage();
    }

    /**
     * Get all actions (non-archived by default)
     * 
     * @param bool $includeArchived Include archived actions
     * @return array List of actions
     */
    public function getAll(bool $includeArchived = false): array
    {
        $data = $this->storage->load(self::DATA_FILE);
        $actions = $data['actions'] ?? [];

        if (!$includeArchived) {
            $actions = Storage::whereNot($actions, 'archived', true);
        }

        // Sort by number descending (newest first)
        usort($actions, fn($a, $b) => ($b['number'] ?? 0) <=> ($a['number'] ?? 0));

        return $actions;
    }

    /**
     * Find action by ID
     * 
     * @param int $id Action ID
     * @return array|null Action data or null
     */
    public function find(int $id): ?array
    {
        $data = $this->storage->load(self::DATA_FILE);
        $actions = $data['actions'] ?? [];

        return Storage::findById($actions, $id);
    }

    /**
     * Create new action
     * 
     * @param array $input Input data (will be filtered through model)
     * @return array Created action data
     * @throws \InvalidArgumentException If validation fails
     */
    public function create(array $input): array
    {
        // Validate input
        $errors = Action::validate($input);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(json_encode($errors));
        }

        // Create model with fillable protection (C10)
        $action = new Action($input);

        // Load existing data
        $data = $this->storage->load(self::DATA_FILE);
        $actions = $data['actions'] ?? [];

        // Generate IDs
        $id = Storage::nextId($actions);
        $number = $this->getNextNumber($actions);

        // Set system fields
        $action->setRaw([
            'id' => $id,
            'number' => $number,
            'created_at' => date('c'),
            'created_by' => Auth::loginId(),
            'updated_at' => date('c'),
            'updated_by' => Auth::loginId(),
            'completed_at' => null,
            'on_time' => null,
            'timeliness' => null,
            'archived' => false,
        ]);

        // Set default PDCA if not provided
        $actionData = $action->toArray();
        $actionData['status_plan'] = $actionData['status_plan'] ?? false;
        $actionData['status_do'] = $actionData['status_do'] ?? false;
        $actionData['status_check'] = $actionData['status_check'] ?? false;
        $actionData['status_act'] = $actionData['status_act'] ?? false;

        // Add to collection
        $actions[] = $actionData;
        $data['actions'] = $actions;

        // Save
        $this->storage->save(self::DATA_FILE, $data);

        return $actionData;
    }

    /**
     * Update existing action
     * 
     * @param int $id Action ID
     * @param array $input Input data
     * @return array Updated action data
     * @throws \InvalidArgumentException If validation fails or action not found
     */
    public function update(int $id, array $input): array
    {
        // Load existing data
        $data = $this->storage->load(self::DATA_FILE);
        $actions = $data['actions'] ?? [];

        // Find action index
        $index = Storage::findIndexById($actions, $id);
        if ($index === null) {
            throw new \InvalidArgumentException('Akce nebyla nalezena.');
        }

        // Merge with existing data for validation
        $mergedData = array_merge($actions[$index], $input);
        $errors = Action::validate($mergedData);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(json_encode($errors));
        }

        // Create model from existing data and fill with new data (C10)
        $action = Action::fromArray($actions[$index]);
        $action->fill($input);

        // Update audit fields
        $action->setRaw([
            'updated_at' => date('c'),
            'updated_by' => Auth::loginId(),
        ]);

        // Check PDCA completion
        $this->checkPdcaCompletion($action);

        // Update in collection
        $actions[$index] = $action->toArray();
        $data['actions'] = $actions;

        // Save
        $this->storage->save(self::DATA_FILE, $data);

        return $actions[$index];
    }

    /**
     * Delete action
     * 
     * @param int $id Action ID
     * @return bool Success
     */
    public function delete(int $id): bool
    {
        $data = $this->storage->load(self::DATA_FILE);
        $actions = $data['actions'] ?? [];

        $index = Storage::findIndexById($actions, $id);
        if ($index === null) {
            return false;
        }

        array_splice($actions, $index, 1);
        $data['actions'] = $actions;

        return $this->storage->save(self::DATA_FILE, $data);
    }

    /**
     * Archive action
     * 
     * @param int $id Action ID
     * @return array|null Updated action or null
     */
    public function archive(int $id): ?array
    {
        try {
            return $this->update($id, ['archived' => true]);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Restore action from archive
     * 
     * @param int $id Action ID
     * @return array|null Updated action or null
     */
    public function restore(int $id): ?array
    {
        try {
            return $this->update($id, ['archived' => false]);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Get statistics for dashboard
     */
    public function getStats(): array
    {
        $actions = $this->getAll(false);
        $today = date('Y-m-d');
        $weekFromNow = date('Y-m-d', strtotime('+7 days'));
        $monthStart = date('Y-m-01');

        $totalOpen = count($actions);
        $overdue = 0;
        $dueThisWeek = 0;
        $completedThisMonth = 0;

        foreach ($actions as $action) {
            $deadline = $action['deadline'] ?? null;
            $completedAt = $action['completed_at'] ?? null;

            // Check if overdue (not completed and past deadline)
            if (!$completedAt && $deadline && $deadline < $today) {
                $overdue++;
            }

            // Check if due this week (not completed)
            if (!$completedAt && $deadline && $deadline >= $today && $deadline <= $weekFromNow) {
                $dueThisWeek++;
            }

            // Check if completed this month
            if ($completedAt && substr($completedAt, 0, 7) === substr($monthStart, 0, 7)) {
                $completedThisMonth++;
            }
        }

        return [
            'total_open' => $totalOpen,
            'overdue' => $overdue,
            'due_this_week' => $dueThisWeek,
            'completed_this_month' => $completedThisMonth,
        ];
    }

    /**
     * Get next sequential number for actions
     */
    private function getNextNumber(array $actions): int
    {
        if (empty($actions)) {
            return 1;
        }

        $maxNumber = max(array_column($actions, 'number'));
        return (int) $maxNumber + 1;
    }

    /**
     * Check and update PDCA completion status
     */
    private function checkPdcaCompletion(Action $action): void
    {
        if ($action->isPdcaComplete()) {
            $completedAt = $action->getAttribute('completed_at');

            // Only set completion date if not already set
            if (!$completedAt) {
                $deadline = $action->getAttribute('deadline');
                $now = date('c');
                $today = date('Y-m-d');

                $action->setRaw([
                    'completed_at' => $now,
                ]);

                // Calculate timeliness
                if ($deadline) {
                    $onTime = $today <= $deadline;
                    $action->setRaw([
                        'on_time' => $onTime,
                        'timeliness' => $onTime
                            ? 'V termínu'
                            : $this->calculateDelay($deadline, $today),
                    ]);
                }
            }
        }
    }

    /**
     * Calculate delay description
     */
    private function calculateDelay(string $deadline, string $completedDate): string
    {
        $deadlineDate = new \DateTime($deadline);
        $completed = new \DateTime($completedDate);
        $diff = $completed->diff($deadlineDate);
        $days = $diff->days;

        if ($days === 1) {
            return 'Po termínu o 1 den';
        }

        return "Po termínu o {$days} dní";
    }
}
