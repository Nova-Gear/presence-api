<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\UploadedFile;
use Exception;

class StorageConfigService
{
    /**
     * Get the configured storage disk
     */
    public function getStorageDisk(): string
    {
        return config('filesystems.default', 'local');
    }
    
    /**
     * Check if S3 storage is configured and available
     */
    public function isS3Available(): bool
    {
        try {
            $s3Config = config('filesystems.disks.s3');
            
            return !empty($s3Config['key']) && 
                   !empty($s3Config['secret']) && 
                   !empty($s3Config['region']) && 
                   !empty($s3Config['bucket']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Store a file using the configured storage
     */
    public function storeFile(UploadedFile $file, string $path = 'uploads', string $disk = null): array
    {
        try {
            $disk = $disk ?? $this->getStorageDisk();
            
            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $fullPath = $path . '/' . $filename;
            
            // Store the file
            $storedPath = $file->storeAs($path, $filename, $disk);
            
            return [
                'success' => true,
                'path' => $storedPath,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'disk' => $disk,
                'url' => $this->getFileUrl($storedPath, $disk)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get file URL based on storage disk
     */
    public function getFileUrl(string $path, string $disk = null): string
    {
        $disk = $disk ?? $this->getStorageDisk();
        
        try {
            if ($disk === 's3') {
                return Storage::disk('s3')->url($path);
            } else {
                return Storage::disk('local')->url($path);
            }
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Delete a file from storage
     */
    public function deleteFile(string $path, string $disk = null): bool
    {
        try {
            $disk = $disk ?? $this->getStorageDisk();
            return Storage::disk($disk)->delete($path);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if file exists in storage
     */
    public function fileExists(string $path, string $disk = null): bool
    {
        try {
            $disk = $disk ?? $this->getStorageDisk();
            return Storage::disk($disk)->exists($path);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get file size
     */
    public function getFileSize(string $path, string $disk = null): int
    {
        try {
            $disk = $disk ?? $this->getStorageDisk();
            return Storage::disk($disk)->size($path);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get storage configuration info
     */
    public function getStorageInfo(): array
    {
        $currentDisk = $this->getStorageDisk();
        
        return [
            'current_disk' => $currentDisk,
            's3_available' => $this->isS3Available(),
            'local_available' => true,
            'supported_disks' => ['local', 's3'],
            'max_file_size' => $this->getMaxFileSize(),
            'allowed_extensions' => $this->getAllowedExtensions()
        ];
    }
    
    /**
     * Get maximum file size allowed
     */
    public function getMaxFileSize(): string
    {
        return config('app.max_file_size', '10MB');
    }
    
    /**
     * Get allowed file extensions
     */
    public function getAllowedExtensions(): array
    {
        return config('app.allowed_file_extensions', [
            'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'
        ]);
    }
    
    /**
     * Validate file before upload
     */
    public function validateFile(UploadedFile $file): array
    {
        $errors = [];
        
        // Check file size
        $maxSize = $this->convertToBytes($this->getMaxFileSize());
        if ($file->getSize() > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size of ' . $this->getMaxFileSize();
        }
        
        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->getAllowedExtensions())) {
            $errors[] = 'File extension not allowed. Allowed extensions: ' . implode(', ', $this->getAllowedExtensions());
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Convert file size string to bytes
     */
    protected function convertToBytes(string $size): int
    {
        $size = trim($size);
        $unit = strtolower(substr($size, -2));
        $value = (int) substr($size, 0, -2);
        
        switch ($unit) {
            case 'gb':
                return $value * 1024 * 1024 * 1024;
            case 'mb':
                return $value * 1024 * 1024;
            case 'kb':
                return $value * 1024;
            default:
                return (int) $size;
        }
    }
}