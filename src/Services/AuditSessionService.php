<?php
/**
 * ACTIO - AuditSession Service
 * 
 * Business logic layer for Audit Sessions CRUD operations.
 * 
 * @package Actio\Services
 */

declare(strict_types=1);

namespace Actio\Services;

use Actio\Core\Storage;
use Actio\Core\Auth;
use Actio\Models\AuditSession;

class AuditSessionService
{
    private const DATA_FILE = 'audit_sessions.json';

    private Storage $storage;

    public function __construct(?Storage $storage = null)
    {
        $this->storage = $storage ?? new Storage();
    }

    /**
     * Get all audit sessions
     * 
     * @return array List of audit sessions
     */
    public function getAll(): array
    {
        $data = $this->storage->load(self::DATA_FILE);
        $sessions = $data['audit_sessions'] ?? [];

        // Sort by date descending (most recent first)
        usort($sessions, fn($a, $b) => ($b['date'] ?? '') <=> ($a['date'] ?? ''));

        return $sessions;
    }

    /**
     * Find audit session by ID
     * 
     * @param int $id Session ID
     * @return array|null Session data or null
     */
    public function find(int $id): ?array
    {
        $data = $this->storage->load(self::DATA_FILE);
        $sessions = $data['audit_sessions'] ?? [];

        return Storage::findById($sessions, $id);
    }

    /**
     * Create new audit session
     * 
     * @param array $input Input data (will be filtered through model)
     * @return array Created session data
     * @throws \InvalidArgumentException If validation fails
     */
    public function create(array $input): array
    {
        // Validate input
        $errors = AuditSession::validate($input);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(json_encode($errors));
        }

        // Create model with fillable protection (C10)
        $session = new AuditSession($input);

        // Load existing data
        $data = $this->storage->load(self::DATA_FILE);
        $sessions = $data['audit_sessions'] ?? [];

        // Generate ID
        $id = Storage::nextId($sessions);

        // Set system fields
        $session->setRaw([
            'id' => $id,
            'created_at' => date('c'),
        ]);

        $sessionData = $session->toArray();

        // Add to collection
        $sessions[] = $sessionData;
        $data['audit_sessions'] = $sessions;

        // Save
        $this->storage->save(self::DATA_FILE, $data);

        return $sessionData;
    }

    /**
     * Update existing audit session
     * 
     * @param int $id Session ID
     * @param array $input Input data
     * @return array Updated session data
     * @throws \InvalidArgumentException If validation fails or session not found
     */
    public function update(int $id, array $input): array
    {
        // Load existing data
        $data = $this->storage->load(self::DATA_FILE);
        $sessions = $data['audit_sessions'] ?? [];

        // Find session index
        $index = Storage::findIndexById($sessions, $id);
        if ($index === null) {
            throw new \InvalidArgumentException('Auditní sezení nebylo nalezeno.');
        }

        // Merge with existing data for validation
        $mergedData = array_merge($sessions[$index], $input);
        $errors = AuditSession::validate($mergedData);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(json_encode($errors));
        }

        // Create model from existing data and fill with new data (C10)
        $session = AuditSession::fromArray($sessions[$index]);
        $session->fill($input);

        // Update audit fields
        $session->setRaw([
            'updated_at' => date('c'),
        ]);

        // Update in collection
        $sessions[$index] = $session->toArray();
        $data['audit_sessions'] = $sessions;

        // Save
        $this->storage->save(self::DATA_FILE, $data);

        return $sessions[$index];
    }

    /**
     * Get all actions for a specific audit session
     * 
     * @param int $sessionId Audit session ID
     * @return array List of actions
     */
    public function getActionsForSession(int $sessionId): array
    {
        $actionService = new ActionService($this->storage);
        $allActions = $actionService->getAll(true); // Include archived

        return array_values(array_filter($allActions, function ($action) use ($sessionId) {
            return isset($action['audit_session_id']) && (int) $action['audit_session_id'] === $sessionId;
        }));
    }

    /**
     * Count actions for each session
     * 
     * @return array Map of session_id => action_count
     */
    public function getActionCounts(): array
    {
        $actionService = new ActionService($this->storage);
        $allActions = $actionService->getAll(true);

        $counts = [];
        foreach ($allActions as $action) {
            $sessionId = $action['audit_session_id'] ?? null;
            if ($sessionId !== null) {
                $counts[$sessionId] = ($counts[$sessionId] ?? 0) + 1;
            }
        }

        return $counts;
    }
}
