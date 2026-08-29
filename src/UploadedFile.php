<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard;

use finfo;

final class UploadedFile
{
    public function __construct(
        private readonly string $name,
        private readonly string $tmpName,
        private readonly int $error,
        private readonly int $size,
        private readonly ?string $clientMimeType = null
    ) {
    }

    /** @param array<array-key, mixed> $file */
    public static function fromArray(array $file): ?self
    {
        if (!isset($file['name'], $file['tmp_name'], $file['error'], $file['size'])) {
            return null;
        }

        if (
            !is_string($file['name'])
            || !is_string($file['tmp_name'])
            || !is_numeric($file['error'])
            || !is_numeric($file['size'])
        ) {
            return null;
        }

        $clientMimeType = isset($file['type']) && is_string($file['type'])
            ? $file['type']
            : null;

        return new self(
            $file['name'],
            $file['tmp_name'],
            (int) $file['error'],
            (int) $file['size'],
            $clientMimeType
        );
    }

    public function isSuccessful(): bool
    {
        return $this->error === UPLOAD_ERR_OK
            && $this->tmpName !== ''
            && is_file($this->tmpName);
    }

    public function isMissing(): bool
    {
        return $this->error === UPLOAD_ERR_NO_FILE;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function tmpName(): string
    {
        return $this->tmpName;
    }

    public function error(): int
    {
        return $this->error;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function clientMimeType(): ?string
    {
        return $this->clientMimeType;
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }

    public function detectedMimeType(): ?string
    {
        if (!$this->isSuccessful()) {
            return null;
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($this->tmpName);

        return is_string($mime) && $mime !== '' ? $mime : null;
    }
}
