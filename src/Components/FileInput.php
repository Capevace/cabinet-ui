<?php

namespace Cabinet\Filament\Components;

use Cabinet\Exceptions\UnknownFileType;
use Cabinet\Facades\Cabinet;
use Cabinet\File;
use Cabinet\FileType;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use function Livewire\trigger;

class FileInput extends \Filament\Forms\Components\Field
{
    use Concerns\HasAcceptedTypes;
    use Concerns\HasEmptyState;
    use Concerns\HasHeadings;
    use Concerns\HasMax;
    use Concerns\HasRelationship;
    use Concerns\HasRootDirectory;
    use Concerns\HasSelectAction;
    use Concerns\HasTooltip;

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

                    $component->validateAndSetFiles($files);
                    try {
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        $component->getLivewire()->addError($statePath, $e->getMessage());
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
                throw new UnknownFileType("Unknown file type: {$json}");
            }

            report(new Exception('Error validating files: ' . json_encode($validator->errors()->toArray(), JSON_PRETTY_PRINT)));

            return Notification::make()
                ->title(__('cabinet::messages.cannot-select-file'))
                ->body(__('cabinet::messages.unknown-error'))
                ->danger()
                ->send();
        }

        $files = collect($files)
            ->map(fn (array $file) => Cabinet::file($file['source'], $file['id']))
            ->filter()
            ->map(fn (File $file) => $file->toIdentifier());

        $max = $this->getMax();

        $livewire = $this->getLivewire();
        $statePath = $this->getStatePath();

        $finish = trigger('update', $livewire, $statePath, $files->first());
//        dd($livewire, $statePath, $files, $max);
        if ($max === 1) {
            $this->state($files->first());
        } else if ($max <= 0 || $max === null) {
            $this->state($files->all());
        } else {
            $this->state($files->take($max)->all());
        }

        $finish();
    }

    public function getFiles(): Collection
    {
        $state = $this->getState();

        $files = collect()
            ->concat($state
                ? is_array($state) && !isset($state['source'])
                    ? $state
                    : [$state]
                : []
            )
            ->filter()
            ->map(fn (array $file) => Cabinet::file($file['source'], $file['id']))
            ->filter();

        return $files;
    }
}
