<?php

namespace Cabinet\Filament\Livewire\Finder\Actions;

use Cabinet\Cabinet;
use Cabinet\Filament\Livewire\Finder\Actions\Concerns\ValidatesFileAttributes;
use Cabinet\Folder;
use Closure;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Support\HtmlString;

class ShareFile extends \Filament\Actions\Action
{
    use ValidatesFileAttributes;

    public static function getDefaultName(): ?string
    {
        return 'share-file';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('cabinet::actions.share'));
        $this->iconButton();
        $this->icon('heroicon-o-share');

        $this->modalWidth('sm');
        $this->modalAlignment('center');

        $this->mountUsing(function (array $arguments, Cabinet $cabinet, Form $form, ShareFile $action) {
            $action->verifyFileArguments($arguments);

            if ($arguments['type'] === (new \Cabinet\Types\Folder)->slug()) {
                throw new \Exception('Cannot share folders.');
            } else {
                $file = $cabinet->file($arguments['source'], $arguments['id']);
            }

            abort_if($file === null, 404);

            $form->fill([
                'url' => $file->url()
            ]);
        });

        $this->form([
            TextInput::make('url')
                ->label('URL')
                ->disabled()
                ->suffixAction(fn (string $state) =>
                    Action::make('copy-url')
                        ->icon('heroicon-o-clipboard')
                        ->label(__('cabinet::actions.copy-link'))
                        ->tooltip(__('cabinet::actions.copy-link'))
                        ->extraAttributes([
                            'x-on:click' => new HtmlString("navigator.clipboard.writeText('{$state}');")
                        ])
                        ->submit()
                )
                ->autofocus()
                ->required()
                ->maxLength(255),
        ]);

        $this->action(function (Cabinet $cabinet, array $data, array $arguments, ShareFile $action) {

        });

        $this->modalSubmitAction(fn ($action) => $action->hidden());
        $this->modalCancelAction(null);
    }
}
