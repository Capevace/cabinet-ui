<?php

declare(strict_types=1);

namespace Cabinet\Filament\Components\Concerns;

use Cabinet\Filament\Components\FileInput;
use Filament\Actions\Action;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Js;

use function Livewire\trigger;

trait HasRemoveFileAction
{
    /**
     * A required field has to keep its file (it can still be replaced by
     * selecting another one) and a disabled field cannot be changed at all.
     */
    public function canRemoveFiles(): bool
    {
        return ! $this->isDisabled() && ! $this->isRequired();
    }

    public function makeRemoveFileAction(FileInput $component): Action
    {
        return Action::make('removeFile')
            ->extraAttributes([
                'class' => 'hidden',
            ])
            ->action(function (array $arguments) use ($component): void {
                ['statePath' => $statePath, 'source' => $source, 'id' => $id] = $arguments;

                if ($component->getStatePath() !== $statePath) {
                    return;
                }

                if (! $component->canRemoveFiles()) {
                    throw new AuthorizationException('The field cannot be emptied.');
                }

                $component->removeFileFromState((string) $source, (string) $id);
            });
    }

    /**
     * Only unlinks the file from this field: the file stays in its folder.
     */
    public function removeFileFromState(string $source, string $id): void
    {
        $files = $this->getFileIdentifiers()
            ->reject(fn (array $file) => $file['source'] === $source && $file['id'] === $id)
            ->values();

        $state = $this->getMax() === 1
            ? $files->first()
            : $files->all();

        $finish = trigger('update', $this->getLivewire(), $this->getStatePath(), $state);

        $this->state($state);

        $finish();
    }

    public function getRemoveFileActionMountJS(string $fileVariable): string
    {
        $statePath = $this->getStatePath();

        $action = $this->makeRemoveFileAction($this);

        // Make sure to not escape the variable, as we want Alpine to treat it as a JS variable
        $data = "{ statePath: '{$statePath}', source: {$fileVariable}.source, id: {$fileVariable}.id }";

        $context = [
            'recordKey' => $this->getRecord()?->getKey(),
            'schemaComponent' => $this->getInheritanceKey(),
        ];
        $context_variables = Js::from($context)->toHtml();

        return "this.\$wire.mountAction('{$action->getName()}', {$data}, {$context_variables});";
    }
}
