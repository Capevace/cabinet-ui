<?php

declare(strict_types=1);

namespace Cabinet\Filament\DTO;

use Cabinet\File;

/**
 * Names one file by its source and id, for example when the browser sends a file.
 */
final readonly class FileIdentifier
{
    public function __construct(
        public string $source,
        public string $id,
    ) {}

    /**
     * Read an identifier from untrusted browser input. Returns null when the value is not an identifier.
     */
    public static function tryFromBrowser(mixed $value): ?self
    {
        $source = is_array($value) ? ($value['source'] ?? null) : null;
        $id = is_array($value) ? ($value['id'] ?? null) : null;

        if (! is_string($source) || ! is_string($id)) {
            return null;
        }

        return new self(source: $source, id: $id);
    }

    public function isFile(File $file): bool
    {
        return $file->source === $this->source && $file->id === $this->id;
    }
}
