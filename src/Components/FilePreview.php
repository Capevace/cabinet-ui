<?php

namespace Cabinet\Filament\Components;

use Filament\Schemas\Components\Component;
use Cabinet\File;
use Illuminate\Support\Arr;

class FilePreview extends Component
{
    protected string $view = 'cabinet-filament::components.forms.file-preview';

    protected File $file;

    public static function make(File $file)
    {
        $static = app(static::class);
        $static->configure();

        $static->file($file);

        return $static;
    }

    public function file(File $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function getImageSrc(): ?string
    {
        return $this->getState();
    }

    public function getFile(): File
    {
        return $this->file;
    }
}
