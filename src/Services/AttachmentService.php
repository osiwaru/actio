<?php
/**
 * ACTIO - Attachment Service
 * 
 * Business logic for secure file attachments.
 * 
 * Security Requirements:
 * - F01: MIME type validation (finfo_file)
 * - F02: Extension whitelist
 * - F03: Random filenames
 * - F04: Upload outside web root
 * - F05: Max file size
 * - F06: Filename sanitization
 * 
 * @package Actio\Services
 */

declare(strict_types=1);

namespace Actio\Services;

use Actio\Core\Storage;
use Actio\Core\Auth;
use Actio\Models\Attachment;

class AttachmentService
{
    private const DATA_FILE = 'attachments.json';
    private const UPLOAD_DIR = 'attachments';

    private Storage $storage;
    private string $basePath;

    public function __construct(?Storage $storage = null)
    {
        $this->storage = $storage ?? new Storage();
        $this->basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
    }

    /**
     * Get all attachments for an action
     * 
     * @param int $actionId Action ID
     * @return array List of attachments
     */
    public function getForAction(int $actionId): array
    {
        $data = $this->storage->load(self::DATA_FILE);
        $attachments = $data['attachments'] ?? [];

        return array_values(array_filter($attachments, function ($a) use ($actionId) {
            return (int) ($a['action_id'] ?? 0) === $actionId;
        }));
    }

    /**
     * Find attachment by ID
     * 
     * @param int $id Attachment ID
     * @return array|null Attachment data or null
     */
    public function find(int $id): ?array
    {
        $data = $this->storage->load(self::DATA_FILE);
        $attachments = $data['attachments'] ?? [];

        return Storage::findById($attachments, $id);
    }

    /**
     * Upload new attachment
     * 
     * @param int $actionId Action ID
     * @param array $file $_FILES element
     * @param string $description Optional description
     * @return array Created attachment data
     * @throws \InvalidArgumentException If validation fails
     * @throws \RuntimeException If upload fails
     */
    public function upload(int $actionId, array $file, string $description = ''): array
    {
        // Validate file (F01, F02, F05)
        $errors = Attachment::validateUpload($file);
        if (!empty($errors)) {
            throw new \InvalidArgumentException($errors['file']);
        }

        // Prepare file info
        $originalName = Attachment::sanitizeFilename($file['name']); // F06
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $storedName = Attachment::generateStoredName($extension); // F03

        // Get MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        // Create upload directory (F04 - outside web root, in data/)
        $uploadDir = $this->getUploadDirectory($actionId);
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new \RuntimeException('Nepodařilo se vytvořit adresář pro přílohy.');
            }
        }

        // Move uploaded file
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \RuntimeException('Nepodařilo se uložit soubor.');
        }

        // Create attachment record
        $attachment = new Attachment([
            'action_id' => $actionId,
            'filename' => $originalName,
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
            'size' => $file['size'],
            'description' => trim($description),
        ]);

        // Load existing data
        $data = $this->storage->load(self::DATA_FILE);
        $attachments = $data['attachments'] ?? [];

        // Generate ID
        $id = Storage::nextId($attachments);

        // Set system fields
        $attachment->setRaw([
            'id' => $id,
            'uploaded_at' => date('c'),
            'uploaded_by' => Auth::user()['name'] ?? 'Unknown',
        ]);

        $attachmentData = $attachment->toArray();

        // Add to collection
        $attachments[] = $attachmentData;
        $data['attachments'] = $attachments;

        // Save
        $this->storage->save(self::DATA_FILE, $data);

        return $attachmentData;
    }

    /**
     * Delete attachment
     * 
     * @param int $id Attachment ID
     * @return bool Success
     */
    public function delete(int $id): bool
    {
        $data = $this->storage->load(self::DATA_FILE);
        $attachments = $data['attachments'] ?? [];

        $index = Storage::findIndexById($attachments, $id);
        if ($index === null) {
            return false;
        }

        $attachment = $attachments[$index];

        // Delete file from disk
        $filePath = $this->getFilePath($attachment);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Remove from collection
        array_splice($attachments, $index, 1);
        $data['attachments'] = array_values($attachments);

        // Save
        $this->storage->save(self::DATA_FILE, $data);

        return true;
    }

    /**
     * Get full file path for attachment
     * 
     * @param array $attachment Attachment data
     * @return string Full file path
     */
    public function getFilePath(array $attachment): string
    {
        $uploadDir = $this->getUploadDirectory((int) $attachment['action_id']);
        return $uploadDir . DIRECTORY_SEPARATOR . $attachment['stored_name'];
    }

    /**
     * Get upload directory for action (F04)
     * 
     * @param int $actionId Action ID
     * @return string Directory path
     */
    private function getUploadDirectory(int $actionId): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR
            . self::UPLOAD_DIR . DIRECTORY_SEPARATOR . $actionId;
    }

    /**
     * Stream file for download
     * 
     * @param array $attachment Attachment data
     * @return void
     */
    public function streamDownload(array $attachment): void
    {
        $filePath = $this->getFilePath($attachment);

        if (!file_exists($filePath)) {
            throw new \RuntimeException('Soubor nebyl nalezen.');
        }

        // Set headers
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . $attachment['filename'] . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        // Stream file
        readfile($filePath);
        exit;
    }
}
