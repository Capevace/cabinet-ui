<?php

namespace Cabinet\Filament\Components\Concerns;

use Cabinet\Exceptions\FileNotFound;
use Cabinet\Facades\Cabinet;
use Cabinet\Folder;
use Cabinet\Models\Directory;
use Closure;

trait HasRootDirectory
{
    protected Closure|Folder|Directory|string $rootDirectory;

    public function root(Closure|Folder|Directory|string $rootDirectory): static
    {
        $this->rootDirectory = $rootDirectory;

        return $this;
    }

    public function getRootDirectory(): Folder
    {
        $root = $this->evaluate($this->rootDirectory);

        if ($root instanceof Folder) {
            return $root;
        }

        if ($root instanceof Directory) {
            return $root->asFolder();
        }

        if (is_string($root)) {
            $folder = Cabinet::folder($root);

            if ($folder) {
                return $folder;
            }
        }

        throw new FileNotFound("Folder [{$root}] not found");
    }
}
