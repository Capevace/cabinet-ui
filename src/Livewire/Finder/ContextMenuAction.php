<?php

namespace Cabinet\Filament\Livewire\Finder;

use Cabinet\Filament\Livewire\Finder;
use Closure;
use Filament\Actions\Action;

/**
 * A context menu entry that an app adds to every finder, together with its
 * Filament action.
 *
 * Register it once, for example in a service provider:
 *
 * ```php
 * Finder::registerContextMenuAction(
 *     ContextMenuAction::make(fn (): Action => EditImageAction::make())
 *         ->menus(['image'])
 *         ->after('previewFile')
 *         ->visible(fn (Finder $finder): bool => $finder->selectionMode === null),
 * );
 * ```
 *
 * The finder creates the action again for each request and component, so
 * the action never keeps state between requests. The entry shows only when
 * the action is not hidden (`visible()`, `hidden()` and `authorize()` of the
 * action) and the condition of `visible()` here is true. To show the same
 * action at another position in another menu, register it a second time.
 *
 * The menus are built once per file type, not per file. A condition can use
 * the finder and the menu, but not the clicked file. The action itself must
 * check the file it gets in its arguments.
 */
class ContextMenuAction
{
    /** Slug of the menu of a multi selection. */
    public const BULK_MENU = 'bulk';

    /**
     * Slugs of the menus that show the entry: a file type slug such as
     * `image` or `folder`, or `bulk` for the menu of a multi selection.
     *
     * @var list<string>
     */
    protected array $menus = [];

    /**
     * Name of the action after which the entry shows. When the menu does not
     * contain this action, the entry shows at the end.
     */
    protected ?string $after = null;

    /** Whether the entry shows at the top of the menu. */
    protected bool $isFirst = false;

    /**
     * Whether the entry shows. A closure gets `$finder` and `$menu` by name.
     */
    protected bool|Closure $isVisible = true;

    /**
     * @param Closure(): Action $makeAction
     */
    final public function __construct(protected Closure $makeAction)
    {
    }

    /**
     * @param Closure(): Action $makeAction Creates the action, for example `fn () => MyAction::make()`.
     */
    public static function make(Closure $makeAction): static
    {
        return new static($makeAction);
    }

    /**
     * @param list<string> $menus
     */
    public function menus(array $menus): static
    {
        $this->menus = $menus;

        return $this;
    }

    public function after(string $actionName): static
    {
        $this->after = $actionName;
        $this->isFirst = false;

        return $this;
    }

    public function first(): static
    {
        $this->isFirst = true;
        $this->after = null;

        return $this;
    }

    /**
     * Like Filament's visible(): a bool, or a closure that gets `Finder $finder`
     * and `string $menu` by name.
     */
    public function visible(bool|Closure $condition = true): static
    {
        $this->isVisible = $condition;

        return $this;
    }

    public function makeAction(): Action
    {
        return ($this->makeAction)();
    }

    /**
     * @return list<string>
     */
    public function getMenus(): array
    {
        return $this->menus;
    }

    /**
     * Where the entry goes in the items of a menu.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function getPosition(array $items): int
    {
        if ($this->isFirst) {
            return 0;
        }

        $index = collect($items)->search(fn (array $item): bool => $this->after !== null && ($item['actionName'] ?? null) === $this->after);

        return $index === false ? count($items) : $index + 1;
    }

    public function isVisible(Finder $finder, string $menu): bool
    {
        if (!in_array($menu, $this->menus, true)) {
            return false;
        }

        if ($this->isVisible instanceof Closure) {
            return (bool) app()->call($this->isVisible, ['finder' => $finder, 'menu' => $menu]);
        }

        return $this->isVisible;
    }
}
