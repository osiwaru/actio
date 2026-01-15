<?php
/**
 * ACTIO - 8D Service
 * 
 * Service for loading and managing 8D cases from JSON files.
 * 
 * @package Actio\Services
 */

declare(strict_types=1);

namespace Actio\Services;

use Actio\Models\EightDCase;

class EightDService
{
    /**
     * Directory containing 8D JSON files
     */
    private string $dataDir;

    /**
     * Create service instance
     */
    public function __construct(?string $dataDir = null)
    {
        $this->dataDir = $dataDir ?? BASE_PATH . '/_edyio';
    }

    /**
     * Get all 8D cases
     * 
     * @return EightDCase[] List of 8D cases
     */
    public function getAll(): array
    {
        $cases = [];
        $files = $this->findJsonFiles();

        foreach ($files as $file) {
            $case = $this->loadFromFile($file);
            if ($case !== null) {
                // Add file info for reference
                $case->setRaw([
                    'filename' => basename($file),
                    'filepath' => $file,
                ]);
                $cases[] = $case;
            }
        }

        // Sort by creation date descending
        usort($cases, function ($a, $b) {
            return strcmp($b->getCreatedDate(), $a->getCreatedDate());
        });

        return $cases;
    }

    /**
     * Find 8D case by case number
     * 
     * @param string $caseNumber Case number (e.g., "PC-123")
     * @return EightDCase|null
     */
    public function findByCaseNumber(string $caseNumber): ?EightDCase
    {
        $cases = $this->getAll();
        
        foreach ($cases as $case) {
            if ($case->getCaseNumber() === $caseNumber) {
                return $case;
            }
        }
        
        return null;
    }

    /**
     * Find 8D case by filename
     * 
     * @param string $filename Filename (e.g., "8D_PC-123_v1.0.json")
     * @return EightDCase|null
     */
    public function findByFilename(string $filename): ?EightDCase
    {
        $filepath = $this->dataDir . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return null;
        }
        
        $case = $this->loadFromFile($filepath);
        if ($case !== null) {
            $case->setRaw([
                'filename' => $filename,
                'filepath' => $filepath,
            ]);
        }
        
        return $case;
    }

    /**
     * Load 8D case from JSON file
     * 
     * @param string $filepath Path to JSON file
     * @return EightDCase|null
     */
    private function loadFromFile(string $filepath): ?EightDCase
    {
        if (!file_exists($filepath)) {
            return null;
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Failed to parse 8D JSON file: $filepath - " . json_last_error_msg());
            return null;
        }

        return EightDCase::fromArray($data);
    }

    /**
     * Find all JSON files in the data directory
     * 
     * @return string[] List of file paths
     */
    private function findJsonFiles(): array
    {
        if (!is_dir($this->dataDir)) {
            return [];
        }

        $files = [];
        $pattern = $this->dataDir . '/8D_*.json';
        
        $matches = glob($pattern);
        if ($matches === false) {
            return [];
        }

        // Filter out template/structure files
        return array_filter($matches, function ($file) {
            $filename = basename($file);
            return $filename !== '8D_structure.json' && !str_contains($filename, '_template');
        });
    }

    /**
     * Get statistics for dashboard
     * 
     * @return array Statistics
     */
    public function getStats(): array
    {
        $cases = $this->getAll();
        
        $total = count($cases);
        $open = 0;
        $closed = 0;
        
        foreach ($cases as $case) {
            if ($case->isClosed()) {
                $closed++;
            } else {
                $open++;
            }
        }
        
        return [
            'total' => $total,
            'open' => $open,
            'closed' => $closed,
        ];
    }

    /**
     * Get data directory path
     * 
     * @return string Data directory path
     */
    public function getDataDir(): string
    {
        return $this->dataDir;
    }
}
