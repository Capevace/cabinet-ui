<?php

namespace Cabinet\Filament\Components\Concerns;

use Closure;

trait HasTreeSidebar
{
    protected bool|Closure $treeSidebar = false;

    /**
     * Enable the directory tree sidebar mode.
     *
     * When enabled, the sidebar shows a lazy-loading folder tree instead of
     * the flat location shortcut buttons. This is most useful for full-page
     * browser instances where users need to navigate deep folder hierarchies.
     *
     * @param  bool|Closure  $condition
     */
    public function treeSidebar(bool|Closure $condition = true): static
    {
        $this->treeSidebar = $condition;

        return $this;
    }

    public function hasTreeSidebar(): bool
    {
        return (bool) $this->evaluate($this->treeSidebar);
    }
}
