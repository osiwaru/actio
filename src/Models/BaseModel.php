<?php
/**
 * ACTIO - Base Model
 * 
 * Abstract model providing common functionality for all models.
 * Implements mass assignment protection and type casting.
 * 
 * Security Requirements:
 * - C10: Mass Assignment prevention via $fillable whitelist
 * 
 * @package Actio\Models
 */

declare(strict_types=1);

namespace Actio\Models;

abstract class BaseModel
{
    /**
     * Whitelist of fields that can be mass-assigned (C10)
     * Must be defined in child classes
     */
    protected static array $fillable = [];

    /**
     * Field types for automatic casting
     * Format: ['field_name' => 'type']
     * Supported types: int, bool, string, float
     */
    protected static array $casts = [];

    /**
     * Required fields for validation
     */
    protected static array $required = [];

    /**
     * Model attributes storage
     */
    protected array $attributes = [];

    /**
     * Create new model instance
     * 
     * @param array $data Initial data
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->fill($data);
        }
    }

    /**
     * Fill model with data using whitelist (C10 - Mass Assignment Prevention)
     * Only fields in $fillable will be set.
     * 
     * @param array $data Data to fill
     * @return static
     */
    public function fill(array $data): static
    {
        $allowed = array_intersect_key($data, array_flip(static::$fillable));

        foreach ($allowed as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Set a single attribute with type casting
     * 
     * @param string $key Attribute name
     * @param mixed $value Attribute value
     * @return static
     */
    public function setAttribute(string $key, mixed $value): static
    {
        if (isset(static::$casts[$key])) {
            $value = $this->castValue($value, static::$casts[$key]);
        }

        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Get attribute value
     * 
     * @param string $key Attribute name
     * @return mixed Attribute value or null
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Get all attributes
     * 
     * @return array All attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set attributes that bypass fillable (for internal use)
     * 
     * @param array $data Data to set
     * @return static
     */
    public function setRaw(array $data): static
    {
        foreach ($data as $key => $value) {
            if (isset(static::$casts[$key])) {
                $value = $this->castValue($value, static::$casts[$key]);
            }
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Convert model to array for JSON storage
     * 
     * @return array Model data
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Create model from stored data
     * 
     * @param array $data Stored data
     * @return static New model instance
     */
    public static function fromArray(array $data): static
    {
        $model = new static();
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

        foreach (static::$required as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $errors[$field] = static::getFieldLabel($field) . ' je povinné pole.';
            }
        }

        return $errors;
    }

    /**
     * Check if model has attribute
     * 
     * @param string $key Attribute name
     * @return bool True if exists
     */
    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Get human-readable field labels
     * Override in child classes for custom labels
     * 
     * @param string $field Field name
     * @return string Human-readable label
     */
    protected static function getFieldLabel(string $field): string
    {
        // Convert snake_case to Title Case
        return ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Cast value to specified type
     * 
     * @param mixed $value Value to cast
     * @param string $type Target type
     * @return mixed Casted value
     */
    protected function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int', 'integer' => $value === null ? null : (int) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'string' => (string) $value,
            'float', 'double' => $value === null ? null : (float) $value,
            default => $value,
        };
    }

    /**
     * Validate date format (YYYY-MM-DD)
     * 
     * @param string $date Date string
     * @return bool True if valid
     */
    protected static function isValidDate(string $date): bool
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

    /**
     * Magic isset check
     */
    public function __isset(string $name): bool
    {
        return $this->hasAttribute($name);
    }
}
