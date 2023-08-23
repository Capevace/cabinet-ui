<?php

namespace Cabinet\Filament\Livewire\Finder\Actions\Concerns;

trait ValidatesFileAttributes
{
    public function verifyFileArguments(array $arguments): void
    {
        abort_if($arguments['id'] === null, 404);
        abort_if($arguments['source'] === null, 404);
    }
}
