<?php

namespace Cabinet\Filament\Livewire\Finder\Actions;

use Cabinet\Cabinet;
use Cabinet\Filament\Livewire\Finder;
use Cabinet\Filament\Livewire\Finder\Actions\Concerns\ValidatesFileAttributes;
use Cabinet\Folder;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

class DeleteFile extends \Filament\Actions\Action
{
    use ValidatesFileAttributes;

    public static function getDefaultName(): ?string
    {
        return 'delete-file';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('cabinet::actions.delete'));
        $this->iconButton();
        $this->icon('heroicon-o-trash');
        $this->color('danger');

        $this->requiresConfirmation();

        $this->action(function (Cabinet $cabinet, array $data, array $arguments, DeleteFile $action, Finder $livewire) {
            $action->verifyFileArguments($arguments);

            $file = null;

            if ($arguments['type'] === (new \Cabinet\Types\Folder)->slug()) {
                $file = $cabinet->folder($arguments['id']);
            } else {
                $file = $cabinet->file($arguments['source'], $arguments['id']);
            }

            abort_if($file === null, 404);

            $id = $file->id;
            $source = $file->source;

            $cabinet->delete($file);
            $livewire->deselectFile($source, $id);
        });
    }
}
