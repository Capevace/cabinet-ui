<?php

namespace Cabinet\Filament\Livewire\Finder\Concerns;

use Cabinet\Filament\Livewire\Finder\ContextMenuAction;
use Cabinet\Filament\Livewire\Finder\ContextMenuItem;
use Cabinet\Filament\Livewire\Finder\FinderActionRegistry;
use Closure;
use Filament\Actions\Action;
use LogicException;

/**
 * Adds the actions that the app registers for every finder: context menu
 * entries (registerContextMenuAction) and actions without an entry, for
 * example for a button in a render hook (registerAction).
 *
 * The actions are created again for every request, so they never keep state
 * between requests. They are cached in a Livewire "booted" hook of this trait,
 * which runs before the one of InteractsWithActions. A subclass of the finder
 * that defines its own booted() therefore keeps them. Use this trait before
 * InteractsWithActions.
 */
trait InteractsWithRegisteredActions
{
    /**
     * The registered entries with the names of their actions for this request.
     *
     * @var list<array{actionName: string, entry: ContextMenuAction}>
     */
    protected array $registeredContextMenuActions = [];

    /**
     * Add a context menu entry with its action to every finder.
     */
    public static function registerContextMenuAction(ContextMenuAction $contextMenuAction): void
    {
        app(FinderActionRegistry::class)->registerContextMenuAction($contextMenuAction);
    }

    /**
     * Add an action without a context menu entry to every finder, for example
     * for a button that a render hook shows. Mount it with
     * `$wire.mountAction(name, arguments)`.
     *
     * @param Closure(): Action $makeAction Creates the action, for example `fn () => MyAction::make()`.
     */
    public static function registerAction(Closure $makeAction): void
    {
        app(FinderActionRegistry::class)->registerAction($makeAction);
    }

    /**
     * Creates the registered actions for this request.
     *
     * @throws LogicException When a registered action has the name of a built-in finder action.
     */
    public function bootedInteractsWithRegisteredActions(): void
    {
        $registry = app(FinderActionRegistry::class);

        foreach ($registry->getActions() as $makeAction) {
            $this->cacheRegisteredAction($makeAction());
        }

        $this->registeredContextMenuActions = [];

        foreach ($registry->getContextMenuActions() as $contextMenuAction) {
            $action = $this->cacheRegisteredAction($contextMenuAction->makeAction());

            $this->registeredContextMenuActions[] = [
                'actionName' => $action->getName(),
                'entry' => $contextMenuAction,
            ];
        }
    }

    /**
     * Adds the registered entries that are visible in this menu.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    protected function addRegisteredContextMenuActions(array $items, string $menu): array
    {
        foreach ($this->registeredContextMenuActions as ['actionName' => $actionName, 'entry' => $entry]) {
            if (!$entry->isVisible($this, $menu)) {
                continue;
            }

            $action = $this->getAction($actionName, isMounting: false);

            if ($action === null || $action->isHidden()) {
                continue;
            }

            array_splice($items, $entry->getPosition($items), 0, [ContextMenuItem::fromAction($action)->toArray()]);
        }

        return $items;
    }

    /**
     * A registered action must not replace a built-in action of the finder.
     * The same registered action may come twice, for example for two menus.
     */
    protected function cacheRegisteredAction(Action $action): Action
    {
        $name = $action->getName();

        if (method_exists($this, "{$name}Action")) {
            throw new LogicException("The registered finder action [{$name}] has the name of a built-in finder action.");
        }

        return $this->cacheAction($action);
    }
}
