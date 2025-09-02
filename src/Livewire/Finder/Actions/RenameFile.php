<?php

namespace Cabinet\Filament\Livewire\Finder\Actions;

use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Cabinet\Cabinet;
use Cabinet\Filament\Livewire\Finder\Actions\Concerns\ValidatesFileAttributes;
use Cabinet\Folder;
use Closure;
use Filament\Forms\Components\TextInput;

class RenameFile extends Action
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

        $this->mountUsing(function (array $arguments, Cabinet $cabinet, Schema $schema, RenameFile $action) {
            $action->verifyFileArguments($arguments);

            if ($arguments['type'] === (new \Cabinet\Types\Folder)->slug()) {
                $file = $cabinet->folder($arguments['id']);
            } else {
                $file = $cabinet->file($arguments['source'], $arguments['id']);
            }

            abort_if($file === null, 404);

            $schema->fill([
                'name' => $file->name
            ]);
        });

        $this->schema([
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
