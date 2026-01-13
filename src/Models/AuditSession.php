<?php
/**
 * ACTIO - AuditSession Model
 * 
 * Data model for Audit Sessions with Mass Assignment prevention.
 * 
 * Security Requirements:
 * - C10: Mass Assignment prevention via $fillable whitelist
 * 
 * @package Actio\Models
 */

declare(strict_types=1);

namespace Actio\Models;

class AuditSession
{
    /**
     * Available audit types for selection
     */
    public const AUDIT_TYPES = [
        'Interní audit',
        'Externí audit (certifikace)',
        'Zákaznický audit',
        'Procesní audit',
        'Neohlášená kontrola',
        'Vlastní typ',
    ];

    /**
     * Whitelist of fields that can be mass-assigned (C10)
     */
    private static array $fillable = [
        'name',
        'type',
        'date',
        'auditor',
        'standard',
        'notes',
    ];

    /**
     * Field types for casting
     */
    private static array $casts = [
        'id' => 'int',
    ];

    /**
     * Required fields for validation
     */
    private static array $required = [
        'name',
        'type',
        'date',
    ];

    /**
     * Model attributes
     */
    private array $attributes = [];

    /**
     * Create new AuditSession instance
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

        // Validate date format
        if (!empty($data['date']) && !self::isValidDate($data['date'])) {
            $errors['date'] = 'Neplatný formát data auditu.';
        }

        return $errors;
    }

    /**
     * Get human-readable field labels
     */
    private static function getFieldLabel(string $field): string
    {
        $labels = [
            'name' => 'Název auditu',
            'type' => 'Typ auditu',
            'date' => 'Datum auditu',
            'auditor' => 'Auditor',
            'standard' => 'Norma',
            'notes' => 'Poznámky',
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
