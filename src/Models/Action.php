<?php
/**
 * ACTIO - Action Model
 * 
 * Data model for Actions (Zjištění/Opatření) with Mass Assignment prevention.
 * 
 * Security Requirements:
 * - C10: Mass Assignment prevention via $fillable whitelist
 * 
 * @package Actio\Models
 */

declare(strict_types=1);

namespace Actio\Models;

class Action
{
    /**
     * Whitelist of fields that can be mass-assigned (C10)
     */
    private static array $fillable = [
        'rating',
        'finding',
        'description',
        'chapter',
        'problem_cause',
        'measure',
        'process',
        'process_owner',
        'responsible',
        'deadline',
        'deadline_plan',
        'finding_date',
        'status_plan',
        'status_do',
        'status_check',
        'status_act',
        'archived',
        'audit_session_id',
    ];

    /**
     * Field types for casting
     */
    private static array $casts = [
        'id' => 'int',
        'number' => 'int',
        'audit_session_id' => 'int',
        'status_plan' => 'bool',
        'status_do' => 'bool',
        'status_check' => 'bool',
        'status_act' => 'bool',
        'on_time' => 'bool',
        'archived' => 'bool',
    ];

    /**
     * Required fields for validation (Phase 1 - initial finding)
     */
    private static array $required = [
        'rating',
        'finding_date',
        'finding',
        'process_owner',
        'chapter',
    ];

    /**
     * Model attributes
     */
    private array $attributes = [];

    /**
     * Create new Action instance
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->fill($data);
        }
    }

    /**
     * Fill model with data using whitelist (C10 - Mass Assignment Prevention)
     * 
     * Only fields in $fillable will be set. All other fields are ignored.
     */
    public function fill(array $data): self
    {
        $allowed = array_intersect_key($data, array_flip(self::$fillable));

        foreach ($allowed as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Set a single attribute with type casting
     */
    public function setAttribute(string $key, mixed $value): self
    {
        // Cast value if type is specified
        if (isset(self::$casts[$key])) {
            $value = $this->castValue($value, self::$casts[$key]);
        }

        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Get attribute value
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Get all attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set attributes that bypass fillable (for internal use)
     */
    public function setRaw(array $data): self
    {
        foreach ($data as $key => $value) {
            if (isset(self::$casts[$key])) {
                $value = $this->castValue($value, self::$casts[$key]);
            }
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Convert model to array for JSON storage
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Create model from stored data
     */
    public static function fromArray(array $data): self
    {
        $model = new self();
        return $model->setRaw($data);
    }

    /**
     * Validate data and return errors
     * 
     * @param array $data Data to validate
     * @return array Validation errors (empty if valid)
     */
    public static function validate(array $data): array
    {
        $errors = [];

        foreach (self::$required as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $errors[$field] = self::getFieldLabel($field) . ' je povinné pole.';
            }
        }

        // Validate date format for deadline
        if (!empty($data['deadline']) && !self::isValidDate($data['deadline'])) {
            $errors['deadline'] = 'Neplatný formát data termínu.';
        }

        // Validate date format for finding_date
        if (!empty($data['finding_date']) && !self::isValidDate($data['finding_date'])) {
            $errors['finding_date'] = 'Neplatný formát data zjištění.';
        }

        return $errors;
    }

    /**
     * Check if all PDCA statuses are complete
     */
    public function isPdcaComplete(): bool
    {
        return $this->getAttribute('status_plan') === true
            && $this->getAttribute('status_do') === true
            && $this->getAttribute('status_check') === true
            && $this->getAttribute('status_act') === true;
    }

    /**
     * Get PDCA completion status
     */
    public function getPdcaStatus(): array
    {
        return [
            'plan' => (bool) $this->getAttribute('status_plan'),
            'do' => (bool) $this->getAttribute('status_do'),
            'check' => (bool) $this->getAttribute('status_check'),
            'act' => (bool) $this->getAttribute('status_act'),
            'complete' => $this->isPdcaComplete(),
        ];
    }

    /**
     * Get human-readable field labels
     */
    private static function getFieldLabel(string $field): string
    {
        $labels = [
            'rating' => 'Hodnocení',
            'finding' => 'Zjištění',
            'description' => 'Popis',
            'chapter' => 'Kapitola normy',
            'problem_cause' => 'Příčina problému',
            'measure' => 'Opatření',
            'process' => 'Proces',
            'process_owner' => 'Majitel procesu',
            'responsible' => 'Odpovědný',
            'deadline' => 'Termín realizace',
            'deadline_plan' => 'Termín plánu',
            'finding_date' => 'Datum zjištění',
        ];

        return $labels[$field] ?? $field;
    }

    /**
     * Cast value to specified type
     */
    private function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int' => $value === null ? null : (int) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'string' => (string) $value,
            default => $value,
        };
    }

    /**
     * Validate date format (YYYY-MM-DD)
     */
    private static function isValidDate(string $date): bool
    {
        $parsed = \DateTime::createFromFormat('Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    /**
     * Magic getter
     */
    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    /**
     * Magic setter
     */
    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }
}
