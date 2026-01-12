<?php
/**
 * ACTIO - JSON Storage Handler
 * 
 * Generic class for working with JSON files.
 * 
 * Security Requirements:
 * - C13: Atomic file writes (temp → rename)
 * 
 * @package Actio\Core
 */

declare(strict_types=1);

namespace Actio\Core;

class Storage
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? (defined('BASE_PATH') ? BASE_PATH . '/data' : __DIR__ . '/../../data');
    }

    /**
     * Load data from a JSON file
     * 
     * @param string $file Filename relative to data directory (e.g., 'actions.json')
     * @return array Parsed data or empty array if file doesn't exist
     */
    public function load(string $file): array
    {
        $path = $this->getPath($file);

        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Save data to a JSON file (atomic write - C13)
     * 
     * Uses temp file + rename for atomic operation to prevent corruption.
     * 
     * @param string $file Filename relative to data directory
     * @param array $data Data to save
     * @return bool Success status
     */
    public function save(string $file, array $data): bool
    {
        $path = $this->getPath($file);
        $dir = dirname($path);

        // Ensure directory exists
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Atomic write: write to temp file, then rename (C13)
        $tempPath = $path . '.tmp.' . uniqid();
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (file_put_contents($tempPath, $content, LOCK_EX) === false) {
            return false;
        }

        // Rename is atomic on most filesystems
        if (!rename($tempPath, $path)) {
            unlink($tempPath);
            return false;
        }

        return true;
    }

    /**
     * Get full path for a data file
     */
    private function getPath(string $file): string
    {
        return $this->basePath . '/' . ltrim($file, '/');
    }

    /**
     * Generate next ID for a collection
     * 
     * @param array $items Array of items with 'id' key
     * @return int Next available ID
     */
    public static function nextId(array $items): int
    {
        if (empty($items)) {
            return 1;
        }

        $maxId = max(array_column($items, 'id'));
        return (int) $maxId + 1;
    }

    /**
     * Find item by ID in collection
     * 
     * @param array $items Collection of items
     * @param int $id ID to find
     * @return array|null Found item or null
     */
    public static function findById(array $items, int $id): ?array
    {
        foreach ($items as $item) {
            if (isset($item['id']) && (int) $item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Find item index by ID
     * 
     * @param array $items Collection of items
     * @param int $id ID to find
     * @return int|null Index or null
     */
    public static function findIndexById(array $items, int $id): ?int
    {
        foreach ($items as $index => $item) {
            if (isset($item['id']) && (int) $item['id'] === $id) {
                return $index;
            }
        }
        return null;
    }

    /**
     * Filter items by field value
     * 
     * @param array $items Collection of items
     * @param string $field Field name to filter by
     * @param mixed $value Value to match
     * @return array Filtered items
     */
    public static function where(array $items, string $field, mixed $value): array
    {
        return array_values(array_filter($items, function ($item) use ($field, $value) {
            return isset($item[$field]) && $item[$field] === $value;
        }));
    }

    /**
     * Filter items where field is NOT equal to value
     * 
     * @param array $items Collection of items
     * @param string $field Field name
     * @param mixed $value Value to exclude
     * @return array Filtered items
     */
    public static function whereNot(array $items, string $field, mixed $value): array
    {
        return array_values(array_filter($items, function ($item) use ($field, $value) {
            return !isset($item[$field]) || $item[$field] !== $value;
        }));
    }
}
