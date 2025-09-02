<?php

namespace Cabinet\Filament\Components\Concerns;

use Closure;
use Filament\Actions\Action;
use Exception;
use Cabinet\Filament\Livewire\Finder\FileTypeDto;
use Cabinet\Filament\Livewire\Finder\SelectionMode;
use Cabinet\Filament\Components\FileEntry;
use Cabinet\Filament\Components\FileInput;
use Cabinet\Filament\Livewire\Finder;
use Cabinet\Filament\Livewire\FinderModal;
use Cabinet\FileType;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Component;

trait HasSelectAction
{
	protected Closure|Action|null $selectAction = null;

    public function selectAction(Closure|Action $selectAction): static
    {
        $this->selectAction = $selectAction;

        $this->registerActions([$selectAction]);

        return $this;
    }

    public function getSelectAction(): Action|null
    {
        return $this->evaluate($this->selectAction);
    }

    /**
     * @param 'form'|'infolist' $type
     */
    public function makeSelectAction(string $type = 'form'): Action
    {
        $class = match ($type) {
            'form' => Action::class,
            'infolist' => Action::class,
            default => throw new Exception("Unknown action type: $type")
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
                        ->map(fn (FileType $type) => new FileTypeDto(
                            slug: $type->slug(),
                            mime: method_exists($type, 'getMime')
                                ? $type->getMime()
                                : null
                        ))
                        ->all(),
                    'mode' => new SelectionMode(
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
                            ->map(fn (FileType $type) => new FileTypeDto(
                                slug: $type->slug(),
                                mime: method_exists($type, 'getMime')
                                    ? $type->getMime()
                                    : null
                            ))
                            ->all(),
                        mode: new SelectionMode(
                            livewireId: $livewire->getId(),
                            statePath: $component->getStatePath(),
                            max: $component->getMax(),
                        )
                    )
                    ->to(Finder::class);
            });
    }
}
