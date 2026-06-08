<?php

namespace Cabinet\Filament\Livewire\Finder\Actions;

use Filament\Actions\Action;
use Cabinet\Facades\Cabinet;
use Cabinet\Filament\Livewire\Finder;

class DeleteBulk extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'delete-bulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('cabinet::actions.delete-bulk'));
        $this->icon('heroicon-o-trash');
        $this->color('danger');

        $this->requiresConfirmation();

        $this->modalHeading(fn (array $arguments) => trans_choice('cabinet::actions.delete-x-files', count($arguments['files'] ?? []), ['value' => count($arguments['files'] ?? [])]));
        $this->modalDescription(__('cabinet::actions.delete'));

        $this->action(function (array $arguments, Finder $livewire) {
            $files = $arguments['files'] ?? [];
            $deleted = 0;

            foreach ($files as $identifier) {
                $file = Cabinet::file($identifier['source'], $identifier['id']);

                if ($file === null) {
                    continue;
                }

                Cabinet::delete($file);
                $deleted++;
            }

            $livewire->clearSelection();
            $livewire->refresh();
        });
    }
}
