<?php

namespace Cabinet\Filament\Components\Concerns;

use Closure;

trait HasMax
{
    protected Closure|int|null $max = null;

    public function max(Closure|int|null $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function multiple(bool $condition = true): static
    {
        return $this->max($condition ? null : 1);
    }

    public function single(bool $condition = true): static
    {
        return $this->max($condition ? 1 : null);
    }

    public function getMax(): int|null
    {
        return $this->evaluate($this->max);
    }

    public function hasMultiple(): bool
    {
        return $this->getMax() === null || $this->getMax() > 1;
    }
}
