<?php

namespace Cabinet\Filament\Livewire\Finder\Actions;

use Cabinet\Cabinet;
use Cabinet\Filament\Livewire\Finder\Actions\Concerns\ValidatesFileAttributes;
use Cabinet\Folder;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

class RenameFile extends \Filament\Actions\Action
{
    use ValidatesFileAttributes;

    public static function getDefaultName(): ?string
    {
        return 'rename-file';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('cabinet::actions.rename'));
        $this->iconButton();
        $this->icon('heroicon-o-pencil-square');

        $this->modalSubmitActionLabel(__('cabinet::actions.rename'));
        $this->modalWidth('sm');
        $this->modalAlignment('center');

        $this->mountUsing(function (array $arguments, Cabinet $cabinet, Form $form, RenameFile $action) {
            $action->verifyFileArguments($arguments);

            if ($arguments['type'] === (new \Cabinet\Types\Folder)->slug()) {
                $file = $cabinet->folder($arguments['id']);
            } else {
                $file = $cabinet->file($arguments['source'], $arguments['id']);
            }

            abort_if($file === null, 404);

            $form->fill([
                'name' => $file->name
            ]);
        });

        $this->form([
            TextInput::make('name')
                ->label('Name')
                ->autofocus()
                ->required()
                ->maxLength(255),
        ]);

        $this->action(function (Cabinet $cabinet, array $data, array $arguments, RenameFile $action) {
            $action->verifyFileArguments($arguments);

            if ($arguments['type'] === (new \Cabinet\Types\Folder)->slug()) {
                $file = $cabinet->folder($arguments['id']);
            } else {
                $file = $cabinet->file($arguments['source'], $arguments['id']);
            }

            abort_if($file === null, 404);

            $cabinet->rename($file, $data['name']);
        });
    }
}
