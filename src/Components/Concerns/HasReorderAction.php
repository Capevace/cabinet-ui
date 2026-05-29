<?php

namespace Cabinet\Filament\Components\Concerns;

use Cabinet\Filament\Components\FileInput;
use Filament\Actions\Action;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

trait HasReorderAction
{
    public function makeReorderAction(FileInput $component): Action
    {
        return Action::make('reorder')
            ->extraAttributes([
                'class' => 'hidden',
            ])
            ->action(function (array $arguments) use ($component) {
                ['statePath' => $statePath, 'from' => $from, 'to' => $to] = $arguments;

                if ($component->getStatePath() !== $statePath) {
                    return;
                }

                if ($component->isDisabled()) {
                    throw new AuthorizationException('Das Feld ist deaktiviert.');
                }

                $fromIndex = (int) $from;
                $toIndex = (int) $to;

                $files = Collection::wrap($component->getState());

                if ($fromIndex < 0 || $fromIndex >= $files->count() || $toIndex < 0 || $toIndex >= $files->count()) {
                    return;
                }

                $files->splice($toIndex, 0, $files->splice($fromIndex, 1));
                $component->validateAndSetFiles($files->values()->all());
            });
    }

    public function getReorderActionMountJS(string $fromVariable, string $toVariable): string
    {
        $statePath = $this->getStatePath();

        $action = $this->makeReorderAction($this);

        // Make sure to not escape variables, as we want Alpine to treat them as JS variables
        $data = "{ statePath: '{$statePath}', from: {$fromVariable}, to: {$toVariable} }";

        $context = [
            'recordKey' => $this->getRecord()?->getKey(),
            'schemaComponent' => $this->getInheritanceKey(),
        ];
        $context_variables = \Illuminate\Support\Js::from($context)->toHtml();

        return "this.\$wire.mountAction('{$action->getName()}', {$data}, {$context_variables});";
    }
}
