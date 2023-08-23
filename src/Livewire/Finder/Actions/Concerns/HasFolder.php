<?php

namespace Cabinet\Filament\Livewire\Finder\Actions\Concerns;

use Cabinet\Folder;
use Closure;

trait HasFolder
{
    protected Closure|Folder|null $parentFolder = null;

    public function parentFolder(Closure|Folder|null $parentFolder): self
    {
        $this->parentFolder = $parentFolder;

        return $this;
    }

    public function getParentFolder(): ?Folder
    {
        return $this->evaluate($this->parentFolder);
    }
}
