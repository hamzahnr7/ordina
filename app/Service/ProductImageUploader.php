<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Exception\ValidationException;
use RuntimeException;

/**
 * PRD-01: validates type/size and stores product images under a random
 * filename (so the on-disk name can't be guessed/enumerated) - never the
 * client-supplied filename.
 */
final class ProductImageUploader
{
    private const MAX_BYTES = 2 * 1024 * 1024;

    /** @var array<string, string> */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly string $uploadDir,
        private readonly string $publicPathPrefix = 'uploads/products',
    ) {
    }

    /**
     * @param array{name?:string, type?:string, tmp_name?:string, error?:int, size?:int}|null $file
     * @return string|null relative path to store on the Product, or null if no file was submitted
     */
    public function upload(?array $file): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException(['image' => 'Upload gambar gagal, silakan coba lagi.']);
        }

        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new ValidationException(['image' => 'Ukuran gambar maksimal 2MB.']);
        }

        $mimeType = mime_content_type($file['tmp_name']) ?: '';
        $extension = self::ALLOWED_MIME_TYPES[$mimeType] ?? null;

        if ($extension === null) {
            throw new ValidationException(['image' => 'Tipe file harus JPG, PNG, atau WEBP.']);
        }

        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0755, true) && !is_dir($this->uploadDir)) {
            throw new RuntimeException('Upload directory could not be created.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = rtrim($this->uploadDir, '/') . '/' . $filename;

        $moved = is_uploaded_file($file['tmp_name'])
            ? move_uploaded_file($file['tmp_name'], $destination)
            : rename($file['tmp_name'], $destination); // test/CLI context - no real HTTP upload

        if (!$moved) {
            throw new RuntimeException('Failed to store uploaded file.');
        }

        return $this->publicPathPrefix . '/' . $filename;
    }
}
