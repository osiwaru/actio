<?php
/**
 * ACTIO - Base Service
 * 
 * Abstract service providing common CRUD functionality for all services.
 * Uses JSON file storage via Storage class.
 * 
 * @package Actio\Core
 */

declare(strict_types=1);

namespace Actio\Core;

abstract class BaseService
{
    /**
     * Storage instance for JSON file operations
     */
    protected Storage $storage;

    /**
     * Storage key for this service's data
     * Must be defined in child classes
     */
    protected string $storageKey;

    /**
     * Create service instance
     * 
     * @param Storage|null $storage Optional storage instance (for testing)
     */
    public function __construct(?Storage $storage = null)
    {
        $this->storage = $storage ?? new Storage();
    }

    /**
     * Get all items
     * 
     * @return array List of all items
     */
    public function getAll(): array
    {
        return $this->storage->get($this->storageKey, []);
    }

    /**
     * Find item by ID
     * 
     * @param int $id Item ID
     * @return array|null Item data or null if not found
     */
    public function find(int $id): ?array
    {
        $items = $this->getAll();
        
        foreach ($items as $item) {
            if (($item['id'] ?? 0) === $id) {
                return $item;
            }
        }
        
        return null;
    }

    /**
     * Find item by field value
     * 
     * @param string $field Field name
     * @param mixed $value Value to match
     * @return array|null First matching item or null
     */
    public function findBy(string $field, mixed $value): ?array
    {
        $items = $this->getAll();
        
        foreach ($items as $item) {
            if (($item[$field] ?? null) === $value) {
                return $item;
            }
        }
        
        return null;
    }

    /**
     * Find all items matching field value
     * 
     * @param string $field Field name
     * @param mixed $value Value to match
     * @return array Matching items
     */
    public function findAllBy(string $field, mixed $value): array
    {
        $items = $this->getAll();
        
        return array_filter($items, function ($item) use ($field, $value) {
            return ($item[$field] ?? null) === $value;
        });
    }

    /**
     * Save item (create or update)
     * 
     * @param array $data Item data
     * @return array Saved item with ID
     */
    protected function save(array $data): array
    {
        $items = $this->getAll();
        
        // Generate new ID if not provided
        if (!isset($data['id'])) {
            $data['id'] = $this->getNextId($items);
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Find existing item index
        $existingIndex = null;
        foreach ($items as $index => $item) {
            if (($item['id'] ?? 0) === $data['id']) {
                $existingIndex = $index;
                break;
            }
        }
        
        // Update or add
        if ($existingIndex !== null) {
            $items[$existingIndex] = $data;
        } else {
            $items[] = $data;
        }
        
        $this->storage->set($this->storageKey, $items);
        
        return $data;
    }

    /**
     * Delete item by ID
     * 
     * @param int $id Item ID
     * @return bool True if deleted, false if not found
     */
    public function delete(int $id): bool
    {
        $items = $this->getAll();
        $originalCount = count($items);
        
        $items = array_filter($items, function ($item) use ($id) {
            return ($item['id'] ?? 0) !== $id;
        });
        
        if (count($items) === $originalCount) {
            return false; // Item not found
        }
        
        // Re-index array
        $items = array_values($items);
        
        $this->storage->set($this->storageKey, $items);
        
        return true;
    }

    /**
     * Count all items
     * 
     * @return int Number of items
     */
    public function count(): int
    {
        return count($this->getAll());
    }

    /**
     * Count items matching field value
     * 
     * @param string $field Field name
     * @param mixed $value Value to match
     * @return int Number of matching items
     */
    public function countBy(string $field, mixed $value): int
    {
        return count($this->findAllBy($field, $value));
    }

    /**
     * Check if item exists
     * 
     * @param int $id Item ID
     * @return bool True if exists
     */
    public function exists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * Get next available ID
     * 
     * @param array $items Current items
     * @return int Next ID
     */
    protected function getNextId(array $items): int
    {
        $maxId = 0;
        
        foreach ($items as $item) {
            if (isset($item['id']) && $item['id'] > $maxId) {
                $maxId = $item['id'];
            }
        }
        
        return $maxId + 1;
    }

    /**
     * Get storage key
     * 
     * @return string Storage key
     */
    public function getStorageKey(): string
    {
        return $this->storageKey;
    }
}
