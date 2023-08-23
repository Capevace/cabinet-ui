<?php

namespace Cabinet\Filament\Components\Concerns;

use Closure;

trait HasEmptyState
{
    public Closure|string $emptyStateIcon = 'heroicon-o-document';
    public Closure|string $emptyStateLabel;

    public function emptyStateIcon(Closure|string $icon): static
    {
        $this->emptyStateIcon = $icon;

        return $this;
    }

    public function emptyStateLabel(Closure|string $label): static
    {
        $this->emptyStateLabel = $label;

        return $this;
    }

    public function getEmptyStateIcon(): string
    {
        return $this->evaluate($this->emptyStateIcon);
    }

    public function getEmptyStateLabel(): string
    {
        return $this->evaluate($this->emptyStateLabel);
    }
}
