<?php

namespace Cabinet\Filament\Livewire\Finder\Actions;

use Cabinet\Cabinet;
use Cabinet\Filament\Livewire\Finder\Actions\Concerns\HasFolder;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\HtmlString;

class CreateFolder extends \Filament\Actions\Action
{
    use HasFolder;
    public static function getDefaultName(): ?string
    {
        return 'create-folder';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('cabinet::actions.create-folder'));
        $this->tooltip(__('cabinet::actions.create-folder'));
        $this->iconButton();
        $this->icon('heroicon-o-folder-plus');

        $this->modalWidth('sm');
        $this->modalAlignment('center');
        $this->extraAttributes([
            'x-on:click' => new HtmlString("setTimeout(() => document.getElementById('mountedActionsData.0.name').focus(), 200)")
        ]);

        $this->form([
            TextInput::make('name')
                ->label('Name')
                ->autofocus()
                ->required()
                ->maxLength(255),
        ]);

        $this->action(function (Cabinet $cabinet, array $data, CreateFolder $action) {
            $cabinet->createDirectory($data['name'], $action->getParentFolder());
        });
    }
}
