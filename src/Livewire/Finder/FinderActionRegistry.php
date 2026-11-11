<?php

namespace Cabinet\Filament\Livewire\Finder;

use Closure;
use Filament\Actions\Action;

/**
 * The actions that the app adds to every finder: context menu entries, and
 * actions without an entry, for example for a button in a render hook. Bound
 * as a singleton, so each application instance (and each test) starts empty.
 *
 * Register them with `Finder::registerContextMenuAction()` and
 * `Finder::registerAction()`.
 */
class FinderActionRegistry
{
    /**
     * @var list<ContextMenuAction>
     */
    protected array $contextMenuActions = [];

    /**
     * @var list<Closure(): Action>
     */
    protected array $actions = [];

    public function registerContextMenuAction(ContextMenuAction $contextMenuAction): void
    {
        $this->contextMenuActions[] = $contextMenuAction;
    }

    /**
     * @param Closure(): Action $makeAction
     */
    public function registerAction(Closure $makeAction): void
    {
        $this->actions[] = $makeAction;
    }

    /**
     * @return list<ContextMenuAction>
     */
    public function getContextMenuActions(): array
    {
        return $this->contextMenuActions;
    }

    /**
     * @return list<Closure(): Action>
     */
    public function getActions(): array
    {
        return $this->actions;
    }
}
