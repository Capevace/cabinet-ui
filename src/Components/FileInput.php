<?php

namespace Cabinet\Filament\Components;

use Filament\Forms\Components\Field;
use Cabinet\Filament\Components\Concerns\HasAcceptedTypes;
use Cabinet\Filament\Components\Concerns\HasEmptyState;
use Cabinet\Filament\Components\Concerns\HasHeadings;
use Cabinet\Filament\Components\Concerns\HasMax;
use Cabinet\Filament\Components\Concerns\HasRelationship;
use Cabinet\Filament\Components\Concerns\HasRootDirectory;
use Cabinet\Filament\Components\Concerns\HasSelectAction;
use Cabinet\Filament\Components\Concerns\HasSidebarItems;
use Cabinet\Filament\Components\Concerns\HasTooltip;
use Cabinet\Exceptions\FileTypeNotAccepted;
use Cabinet\Exceptions\InvalidFileData;
use Cabinet\Facades\Cabinet;
use Cabinet\File;
use Cabinet\FileType;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use function Livewire\trigger;

class FileInput extends Field
{
    use HasAcceptedTypes;
    use HasEmptyState;
    use HasHeadings;
    use HasMax;
    use HasRelationship;
    use HasRootDirectory;
    use HasSelectAction;
    use HasSidebarItems;
    use HasTooltip;

    protected string $view = 'cabinet-filament::components.forms.file-input';

    protected function setUp(): void
    {
        $this->emptyStateLabel(fn () =>
            trans_choice('cabinet::messages.no-files-selected', $this->getMax() ?? 9999)
        );

        $this->heading(fn () =>
            trans_choice('cabinet::actions.select-file', $this->getMax() ?? 9999)
        );

        $this->selectAction(fn () => $this->makeSelectAction());

        $this->registerListeners([
            'fileInput:select' => [
                function (FileInput $component, string $statePath, array $files) {
                    if ($component->getStatePath() !== $statePath) {
                        return;
                    }

//                    dd($component, $component->getStatePath(), $statePath, $files);

                    if ($component->isDisabled()) {
                        throw new AuthorizationException('Das Feld ist deaktiviert.');
                    }

                    try {
                        $component->validateAndSetFiles($files);
                    } catch (Exception $e) {
                        report($e);

                        Notification::make()
                            ->title(__('cabinet::messages.cannot-select-file'))
                            ->body(__('cabinet::messages.unknown-error'))
                            ->danger()
                            ->send();
                    }
                }
            ],
            'fileInput:reorder' => [
                function (FileInput $component, string $statePath, array $move) {
                    try {
                        $from = Arr::get($move, 'from');
                        $to = Arr::get($move, 'to');

                        if ($from === null || $to === null) {
                            return;
                        }

                        $fromIndex = (int) $from;
                        $toIndex = (int) $to;

                        if ($component->getStatePath() !== $statePath) {
                            return;
                        }

                        if ($component->isDisabled()) {
                            throw new AuthorizationException('Das Feld ist deaktiviert.');
                        }

                        $files = Collection::wrap($component->getState());

                        if ($fromIndex < 0 || $fromIndex >= $files->count() || $toIndex < 0 || $toIndex >= $files->count()) {
                            return;
                        }

                        $files->splice($toIndex, 0, $files->splice($fromIndex, 1));

                        $this->validateAndSetFiles($files->values()->all());
                    } catch (Exception $e) {
                        report($e);

                        Notification::make()
                            ->title(__('cabinet::messages.cannot-reorder-file'))
                            ->body(__('cabinet::messages.unknown-error'))
                            ->danger()
                            ->send();
                    }
                }
            ]
        ]);
    }

    public function validateAndSetFiles(array $files)
    {
        $sources = Cabinet::validSources()
            ->implode(',');

        $acceptedTypes = collect($this->getAcceptedTypes())
            ->map(fn (FileType $type) => $type->slug())
            ->join(',');

        $validator = validator($files, [
            '*.source' => ['required', "in:{$sources}"],
            '*.id' => ['required', 'string', 'max:255'],
            '*.type' => ['required', "in:{$acceptedTypes}"],
        ]);

        if ($validator->fails()) {
            // If a type error occurred
            if (collect($validator->failed())->keys()->contains(fn ($key) => str($key)->endsWith('.type'))) {
                $json = json_encode($validator->failed());
                throw new FileTypeNotAccepted("Unknown file type: {$json}");
            }

            throw new InvalidFileData('Error validating files: ' . json_encode($validator->errors()->toArray(), JSON_PRETTY_PRINT));
        }

        $files = collect($files)
            ->map(fn (array $file) => Cabinet::file($file['source'], $file['id']))
            ->filter()
            ->map(fn (File $file) => $file->toIdentifier());

        $max = $this->getMax();

        $livewire = $this->getLivewire();
        $statePath = $this->getStatePath();

        $finish = trigger('update', $livewire, $statePath, $files->first());

        if ($max === 1) {
            $this->state($files->first());
        } else if ($max <= 0 || $max === null) {
            $this->state($files->all());
        } else {
            $this->state($files->take($max)->all());
        }

        $finish();
    }

    protected function getFileIdentifiers(): Collection
    {
        $state = $this->getState();

        return collect()
            ->concat($state
                // If state is an array and does not have a source key
                // we assume it is a list of files
                ? is_array($state) && !isset($state['source'])
                    ? $state
                    : [$state]
                : []
            )
            ->filter()
            ->values();
    }

    public function getFiles(): Collection
    {
        return $this->getFileIdentifiers()
            ->map(fn (array $file) => Cabinet::file($file['source'], $file['id']))
            ->filter()
            ->values();
    }
}
