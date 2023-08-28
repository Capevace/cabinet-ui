<?php

namespace Cabinet\Filament\Components\Concerns;

use Cabinet\Filament\Livewire\Finder\SidebarItem;
use Closure;
use Illuminate\Support\Collection;

trait HasSidebarItems
{
    protected Closure|array $sidebarItems = [];

    public function sidebarItems(Closure|array|Collection $sidebarItems): static
    {
        if ($sidebarItems instanceof Collection) {
            $this->sidebarItems = $sidebarItems->all();
        } else {
            $this->sidebarItems = $sidebarItems;
        }

        return $this;
    }

    public function getSidebarItems(): array
    {
        // Convert the sidebar items to the DTOs that will be passed to the
        // Finder Livewire component.
        return array_map(
            fn (SidebarItem $item) => $item->dto(),
            $this->evaluate($this->sidebarItems)
        );
    }
}
