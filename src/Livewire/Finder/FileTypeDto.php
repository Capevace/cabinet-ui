<?php

namespace Cabinet\Filament\Livewire\Finder;

use Cabinet\Facades\Cabinet;
use Cabinet\File;
use Cabinet\FileType;
use Livewire\Wireable;

class FileTypeDto implements Wireable
{
    public function __construct(
        public readonly string $slug,
        public readonly ?string $mime = null,
    )
    {
    }

    public static function make(FileType $type): static
    {
        return app(static::class, [
            'slug' => $type->slug(),
            'mime' => method_exists($type, 'getMime')
                ? $type->getMime()
                : null,
        ]);
    }

    public function toLivewire()
    {
        return [
            'slug' => $this->slug,
            'mime' => $this->mime,
        ];
    }

    public static function fromLivewire($value)
    {
        return new static(
            slug: $value['slug'],
            mime: $value['mime'],
        );
    }

    public function toFileType(): FileType
    {
        return Cabinet::makeFileType($this->slug, $this->mime);
    }
}
