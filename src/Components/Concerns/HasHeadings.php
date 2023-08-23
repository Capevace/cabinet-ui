<?php

namespace Cabinet\Filament\Components\Concerns;

use Closure;

trait HasHeadings
{
	protected Closure|string $heading;
    protected Closure|string|null $description = null;

    protected Closure|string|null $finderHeading = null;
    protected Closure|string|null $finderDescription = null;

	public function heading(Closure|string $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function description(Closure|string|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function finderHeading(Closure|string|null $finderHeading): static
    {
        $this->finderHeading = $finderHeading;

        return $this;
    }

    public function finderDescription(Closure|string|null $finderDescription): static
    {
        $this->finderDescription = $finderDescription;

        return $this;
    }

    public function getHeading(): string
    {
    	return $this->evaluate($this->heading);
    }

    public function getDescription(): ?string
    {
        return $this->evaluate($this->description);
    }

    public function getFinderHeading(): ?string
    {
        return $this->evaluate($this->finderHeading);
    }

    public function getFinderDescription(): ?string
    {
        return $this->evaluate($this->finderDescription);
    }
}
