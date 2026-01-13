<?php
/**
 * ACTIO - Attachment Model
 * 
 * Data model for file attachments with security validation.
 * 
 * Security Requirements:
 * - F01: MIME type validation
 * - F02: Extension whitelist
 * - F05: Max file size
 * - C10: Mass Assignment prevention
 * 
 * @package Actio\Models
 */

declare(strict_types=1);

namespace Actio\Models;

class Attachment
{
    /**
     * Maximum file size in bytes (10 MB)
     */
    public const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Allowed MIME types (F01)
     */
    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'image/jpeg',
        'image/png',
        'text/plain',
    ];

    /**
     * Allowed extensions (F02)
     */
    public const ALLOWED_EXTENSIONS = [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'jpg',
        'jpeg',
        'png',
        'txt',
    ];

    /**
     * MIME type to icon mapping
     */
    public const MIME_ICONS = [
        'application/pdf' => 'bi-file-earmark-pdf',
        'application/msword' => 'bi-file-earmark-word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'bi-file-earmark-word',
        'application/vnd.ms-excel' => 'bi-file-earmark-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'bi-file-earmark-excel',
        'application/vnd.ms-powerpoint' => 'bi-file-earmark-ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'bi-file-earmark-ppt',
        'image/jpeg' => 'bi-file-earmark-image',
        'image/png' => 'bi-file-earmark-image',
        'text/plain' => 'bi-file-earmark-text',
    ];

    /**
     * Whitelist of fields that can be mass-assigned (C10)
     */
    private static array $fillable = [
        'action_id',
        'filename',
        'stored_name',
        'mime_type',
        'size',
        'description',
    ];

    /**
     * Model attributes
     */
    private array $attributes = [];

    /**
     * Create new Attachment instance
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->fill($data);
        }
    }

    /**
     * Fill model with data using whitelist (C10)
     */
    public function fill(array $data): self
    {
        $allowed = array_intersect_key($data, array_flip(self::$fillable));

        foreach ($allowed as $key => $value) {
            $this->attributes[$key] = $value;
        }

        return $this;
    }

    /**
     * Set attributes that bypass fillable (for internal use)
     */
    public function setRaw(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Get all attributes
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
     * Validate uploaded file
     * 
     * @param array $file $_FILES array element
     * @return array Validation errors (empty if valid)
     */
    public static function validateUpload(array $file): array
    {
        $errors = [];

        // Check upload error
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors['file'] = self::getUploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE);
            return $errors;
        }

        // Check if file exists
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors['file'] = 'Nahraný soubor nebyl nalezen.';
            return $errors;
        }

        // Check file size (F05)
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $maxMb = self::MAX_FILE_SIZE / (1024 * 1024);
            $errors['file'] = "Soubor je příliš velký. Maximum je {$maxMb} MB.";
            return $errors;
        }

        // Check extension (F02)
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $errors['file'] = 'Nepodporovaný typ souboru. Povolené: ' . implode(', ', self::ALLOWED_EXTENSIONS);
            return $errors;
        }

        // Check MIME type with finfo (F01)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            $errors['file'] = 'Nepodporovaný typ souboru (nevalidní MIME typ).';
            return $errors;
        }

        return $errors;
    }

    /**
     * Get icon class for MIME type
     */
    public static function getIconForMime(string $mimeType): string
    {
        return self::MIME_ICONS[$mimeType] ?? 'bi-file-earmark';
    }

    /**
     * Format file size for display
     */
    public static function formatSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        } else {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }
    }

    /**
     * Sanitize filename (F06)
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove directory traversal
        $filename = basename($filename);

        // Remove null bytes
        $filename = str_replace("\0", '', $filename);

        // Replace dangerous characters
        $filename = preg_replace('/[^\w\-. ]/', '_', $filename);

        // Limit length
        if (strlen($filename) > 200) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 195) . '.' . $ext;
        }

        return $filename;
    }

    /**
     * Generate random stored filename (F03)
     */
    public static function generateStoredName(string $extension): string
    {
        return bin2hex(random_bytes(16)) . '.' . strtolower($extension);
    }

    /**
     * Get upload error message
     */
    private static function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Soubor je příliš velký.',
            UPLOAD_ERR_PARTIAL => 'Soubor byl nahrán pouze částečně.',
            UPLOAD_ERR_NO_FILE => 'Nebyl vybrán žádný soubor.',
            UPLOAD_ERR_NO_TMP_DIR => 'Chybí dočasný adresář.',
            UPLOAD_ERR_CANT_WRITE => 'Nepodařilo se zapsat soubor.',
            UPLOAD_ERR_EXTENSION => 'Nahrávání zastaveno rozšířením.',
            default => 'Neznámá chyba při nahrávání.',
        };
    }
}
