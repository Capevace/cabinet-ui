<?php

namespace Cabinet\Filament\Components\Concerns;

use Cabinet\Filament\Components\FileInput;
use Cabinet\Filament\Livewire\Finder;
use Cabinet\Filament\Livewire\FinderModal;
use Cabinet\FileType;
use Filament\Forms\Components\Actions\Action;
use Livewire\Component;

trait HasSelectAction
{
	protected \Closure|Action $selectAction;

    public function selectAction(\Closure|Action $selectAction): static
    {
        $this->selectAction = $selectAction;

        $this->registerActions([$selectAction]);

        return $this;
    }

    public function getSelectAction(): Action
    {
        return $this->evaluate($this->selectAction);
    }

    public function makeSelectAction(): Action
    {
        return Action::make('select')
            ->label(fn () => trans_choice('cabinet::actions.select-file', $this->getMax() ?? 9999))
//            ->modalContent(
//                view('cabinet-filament::modal-test', [
//                    'folderId' => '1305570d-0be4-4226-b282-1a9f2f7a985e'
//                ])
//            )
            ->size('sm')
            ->action(function (Component $livewire, FileInput $component) {
                $livewire
                    ->dispatch(
                        'open',
                        folderId: $this->getRootDirectory()->id,
                        sidebarItems: $this->getSidebarItems(),
                        acceptedTypes: collect($component->getAcceptedTypes())
                            ->map(fn (FileType $type) => new Finder\FileTypeDto(
                                slug: $type->slug(),
                                mime: method_exists($type, 'getMime')
                                    ? $type->getMime()
                                    : null
                            ))
                            ->all(),
                        mode: new Finder\SelectionMode(
                            livewireId: $livewire->getId(),
                            statePath: $component->getStatePath(),
                            max: $component->getMax(),
                        )
                    )
                    ->to(Finder::class);
            });
    }
}
