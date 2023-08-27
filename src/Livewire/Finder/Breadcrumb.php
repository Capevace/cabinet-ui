<?php

namespace Cabinet\Filament\Livewire\Finder;

class Breadcrumb
{
    public readonly string $action;

    public function __construct(
        public readonly string $folderId,
        public readonly string $label
    )
    {
        $this->action = "openFolder('{$folderId}')";
    }


}
