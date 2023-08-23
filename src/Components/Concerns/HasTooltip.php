<?php

namespace Cabinet\Filament\Components\Concerns;

use Closure;

trait HasTooltip
{
    protected Closure|string|null $tooltip = null;

    public function tooltip(Closure|string|null $tooltip): static
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    public function getTooltip(): ?string
    {
        return $this->evaluate($this->tooltip);
    }
}
