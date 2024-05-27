<?php

namespace Cabinet\Filament\Components\Concerns;

use Cabinet\Filament\Components\FileEntry;
use Cabinet\Filament\Components\FileInput;
use Cabinet\Filament\Livewire\Finder;
use Cabinet\Filament\Livewire\FinderModal;
use Cabinet\FileType;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Component;

trait HasSelectAction
{
	protected \Closure|FormAction|InfolistAction|null $selectAction = null;

    public function selectAction(\Closure|FormAction|InfolistAction $selectAction): static
    {
        $this->selectAction = $selectAction;

        $this->registerActions([$selectAction]);

        return $this;
    }

    public function getSelectAction(): FormAction|InfolistAction|null
    {
        return $this->evaluate($this->selectAction);
    }

    /**
     * @param 'form'|'infolist' $type
     */
    public function makeSelectAction(string $type = 'form'): FormAction|InfolistAction
    {
        $class = match ($type) {
            'form' => FormAction::class,
            'infolist' => InfolistAction::class,
            default => throw new \Exception("Unknown action type: $type")
        };


        return $class::make('select')
            ->label(fn () => trans_choice('cabinet::actions.select-file', $this->getMax() ?? 9999))
//            ->modalContent(
//                view('cabinet-filament::modal-test', [
//                    'folderId' => '1305570d-0be4-4226-b282-1a9f2f7a985e'
//                ])
//            )
            ->size('sm')
            ->iconPosition('after')
            ->extraAttributes(function (Component $livewire, FileInput|FileEntry $component) {
                $data = [
                    'folderId' => $this->getRootDirectory()->id,
                    'sidebarItems' => $this->getSidebarItems(),
                    'selectedFiles' => $this->getFileIdentifiers()->all(),
                    'acceptedTypes' => collect($component->getAcceptedTypes())
                        ->map(fn (FileType $type) => new Finder\FileTypeDto(
                            slug: $type->slug(),
                            mime: method_exists($type, 'getMime')
                                ? $type->getMime()
                                : null
                        ))
                        ->all(),
                    'mode' => new Finder\SelectionMode(
                        livewireId: $livewire->getId(),
                        statePath: $component->getStatePath(),
                        max: $component->getMax(),
                    )
                ];

                return [
                    'x-on:click' => new HtmlString(Blade::render('$dispatch(\'open\', @js($data))', ['data' => $data]))
                ];
            })
            ->action(function (Component $livewire, FileInput|FileEntry $component) {
                $livewire
                    ->dispatch(
                        'open',
                        folderId: $this->getRootDirectory()->id,
                        sidebarItems: $this->getSidebarItems(),
                        selectedFiles: $this->getFileIdentifiers()->all(),
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
