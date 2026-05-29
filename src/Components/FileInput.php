<?php

namespace Cabinet\Filament\Components;

use Cabinet\Exceptions\FileTypeNotAccepted;
use Cabinet\Exceptions\InvalidFileData;
use Cabinet\Facades\Cabinet;
use Cabinet\Filament\Components\Concerns\HasAcceptedTypes;
use Cabinet\Filament\Components\Concerns\HasEmptyState;
use Cabinet\Filament\Components\Concerns\HasHeadings;
use Cabinet\Filament\Components\Concerns\HasMax;
use Cabinet\Filament\Components\Concerns\HasRelationship;
use Cabinet\Filament\Components\Concerns\HasReorderAction;
use Cabinet\Filament\Components\Concerns\HasRootDirectory;
use Cabinet\Filament\Components\Concerns\HasSelectAction;
use Cabinet\Filament\Components\Concerns\HasSidebarItems;
use Cabinet\Filament\Components\Concerns\HasTooltip;
use Cabinet\Filament\Components\Concerns\HasTreeSidebar;
use Cabinet\File;
use Cabinet\FileType;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
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
    use HasReorderAction;
    use HasRootDirectory;
    use HasSelectAction;
    use HasSidebarItems;
    use HasTooltip;
    use HasTreeSidebar;

    protected string $view = 'cabinet-filament::components.forms.file-input';

    protected function setUp(): void
    {
        $this->emptyStateLabel(fn () => trans_choice('cabinet::messages.no-files-selected', $this->getMax() ?? 9999)
        );

        $this->heading(fn () => trans_choice('cabinet::actions.select-file', $this->getMax() ?? 9999)
        );

        $this->selectAction(fn () => $this->makeSelectAction());

        $this->registerActions([
            fn (FileInput $component) => $this->makeConfirmSelectionAction($component),
            fn (FileInput $component) => $this->makeReorderAction($component),
        ]);


    }

    public function makeConfirmSelectionAction(FileInput $component): Action
    {
        return Action::make('confirmSelection')
            ->extraAttributes([
                'class' => 'hidden',
            ])
            ->action(function (array $arguments) use ($component) {
                ['statePath' => $statePath, 'files' => $files] = $arguments;

                if ($component->getStatePath() !== $statePath || ! is_array($files)) {
                    return;
                }

                if ($component->isDisabled()) {
                    throw new AuthorizationException('Das Feld ist deaktiviert.');
                }

                $component->validateAndSetFiles($files);
            });
    }

    public function getSelectActionMountJS(string $filesVariable): string
    {
        $statePath = $this->getStatePath();

        $action = $this->makeConfirmSelectionAction($this);

        // Make sure to not escape $filesVariable, as we want Alpine to treat it as a JS variable, not a string
        $data = "{ statePath: '{$statePath}', files: {$filesVariable} }";

        $context = [
            'recordKey' => $this->getRecord()?->getKey(),
            'schemaComponent' => $this->getInheritanceKey(),
        ];
        $context_variables = \Illuminate\Support\Js::from($context)->toHtml();

        return "this.\$wire.mountAction('{$action->getName()}', {$data}, {$context_variables});";
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

            throw new InvalidFileData('Error validating files: '.json_encode($validator->errors()->toArray(), JSON_PRETTY_PRINT));
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
        } elseif ($max <= 0 || $max === null) {
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
                ? is_array($state) && ! isset($state['source'])
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
