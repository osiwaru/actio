<?php
/**
 * ACTIO - 8D Case Model
 * 
 * Data model for 8D Cases loaded from JSON files.
 * Extends BaseModel for common functionality.
 * 
 * @package Actio\Models
 */

declare(strict_types=1);

namespace Actio\Models;

class EightDCase extends BaseModel
{
    /**
     * Status constants
     */
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_CLOSED = 'closed';

    /**
     * D-Steps as array
     */
    public const D_STEPS = ['D1', 'D2', 'D3', 'D4', 'D5', 'D6', 'D7', 'D8'];

    /**
     * D-Step labels (Czech)
     */
    public const D_STEP_LABELS = [
        'D1' => 'Sestavení týmu',
        'D2' => 'Popis problému',
        'D3' => 'Okamžitá opatření',
        'D4' => 'Analýza příčin',
        'D5' => 'Nápravná opatření',
        'D6' => 'Realizace a validace',
        'D7' => 'Prevence opakování',
        'D8' => 'Závěr a ocenění',
    ];

    /**
     * Whitelist of fillable fields
     */
    protected static array $fillable = [
        'meta',
        'D1', 'D2', 'D3', 'D4', 'D5', 'D6', 'D7', 'D8',
    ];

    /**
     * Get case number
     */
    public function getCaseNumber(): string
    {
        return $this->attributes['meta']['cislo_pripadu'] ?? '';
    }

    /**
     * Get case title/name
     */
    public function getName(): string
    {
        return $this->attributes['meta']['nazev'] ?? '';
    }

    /**
     * Get customer name
     */
    public function getCustomer(): string
    {
        return $this->attributes['meta']['zakaznik'] ?? '';
    }

    /**
     * Get status
     */
    public function getStatus(): string
    {
        return $this->attributes['meta']['status'] ?? self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if case is closed
     */
    public function isClosed(): bool
    {
        return $this->getStatus() === self::STATUS_CLOSED;
    }

    /**
     * Get creation date
     */
    public function getCreatedDate(): string
    {
        return $this->attributes['meta']['datum_vzniku'] ?? '';
    }

    /**
     * Get last update date
     */
    public function getUpdatedDate(): string
    {
        return $this->attributes['meta']['posledni_aktualizace'] ?? '';
    }

    /**
     * Get version
     */
    public function getVersion(): string
    {
        return $this->attributes['meta']['verze'] ?? '1.0';
    }

    /**
     * Get meta information
     */
    public function getMeta(): array
    {
        return $this->attributes['meta'] ?? [];
    }

    /**
     * Get specific D-step data
     */
    public function getStep(string $step): ?array
    {
        if (!in_array($step, self::D_STEPS)) {
            return null;
        }
        return $this->attributes[$step] ?? null;
    }

    /**
     * Get must_have data for a D-step
     */
    public function getMustHave(string $step): array
    {
        $stepData = $this->getStep($step);
        return $stepData['must_have'] ?? [];
    }

    /**
     * Get nice_to_have data for a D-step
     */
    public function getNiceToHave(string $step): array
    {
        $stepData = $this->getStep($step);
        return $stepData['nice_to_have'] ?? [];
    }

    /**
     * Check if D-step has meaningful data (not just empty structure)
     */
    public function hasStep(string $step): bool
    {
        if (!isset($this->attributes[$step])) {
            return false;
        }
        
        $stepData = $this->attributes[$step];
        $mustHave = $stepData['must_have'] ?? [];
        
        // Check if must_have has any meaningful content
        return $this->hasNonEmptyContent($mustHave);
    }

    /**
     * Recursively check if array has non-empty content
     */
    private function hasNonEmptyContent(mixed $data): bool
    {
        if (is_string($data)) {
            return trim($data) !== '';
        }
        
        if (is_array($data)) {
            foreach ($data as $value) {
                if ($this->hasNonEmptyContent($value)) {
                    return true;
                }
            }
            return false;
        }
        
        // booleans, numbers are considered as content
        return $data !== null;
    }

    /**
     * Get team leader (from D1)
     */
    public function getTeamLeader(): ?array
    {
        return $this->getMustHave('D1')['vedouci_tymu'] ?? null;
    }

    /**
     * Get team members (from D1)
     */
    public function getTeamMembers(): array
    {
        return $this->getMustHave('D1')['clenove'] ?? [];
    }

    /**
     * Get problem description (from D2)
     */
    public function getProblemDescription(): string
    {
        $d2 = $this->getMustHave('D2');
        $popis = $d2['popis_problemu'] ?? [];
        return ($popis['objekt'] ?? '') . ': ' . ($popis['odchylka'] ?? '');
    }

    /**
     * Get immediate actions count (from D3)
     */
    public function getImmediateActionsCount(): int
    {
        return count($this->getMustHave('D3')['opatreni'] ?? []);
    }

    /**
     * Get causes count (from D4)
     */
    public function getCausesCount(): int
    {
        return count($this->getMustHave('D4')['priciny'] ?? []);
    }

    /**
     * Get corrective actions count (from D5)
     */
    public function getCorrectiveActionsCount(): int
    {
        return count($this->getMustHave('D5')['opatreni'] ?? []);
    }

    /**
     * Get completion progress (percentage of completed D-steps)
     */
    public function getProgress(): int
    {
        $completed = 0;
        foreach (self::D_STEPS as $step) {
            if ($this->hasStep($step)) {
                $completed++;
            }
        }
        return (int) round(($completed / 8) * 100);
    }

    /**
     * Get D-step label
     */
    public static function getStepLabel(string $step): string
    {
        return self::D_STEP_LABELS[$step] ?? $step;
    }

    /**
     * Get status label (Czech)
     */
    public function getStatusLabel(): string
    {
        return match ($this->getStatus()) {
            self::STATUS_CLOSED => 'Uzavřeno',
            self::STATUS_IN_PROGRESS => 'Probíhá',
            default => 'Neznámý',
        };
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->getStatus()) {
            self::STATUS_CLOSED => 'bg-success',
            self::STATUS_IN_PROGRESS => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
    }
}
