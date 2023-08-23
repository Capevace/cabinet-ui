<?php

namespace Cabinet\Filament\Components\Concerns;

use Cabinet\Facades\Cabinet;
use Cabinet\Types\Image;
use Cabinet\Types\PDF;
use Cabinet\Types\Video;
use Closure;

trait HasAcceptedTypes
{
	public Closure|array|null $acceptedTypes = null;

	public function acceptedTypes(Closure|array|null $acceptedTypes): static
	{
		$this->acceptedTypes = $acceptedTypes;

		return $this;
	}

    public function image(): static
    {
        $this->acceptedTypes([
            new Image,
        ]);

        return $this;
    }

    public function video(): static
    {
        $this->acceptedTypes([
            new Video,
        ]);

        return $this;
    }

    public function pdf(): static
    {
        $this->acceptedTypes([
            new PDF,
        ]);

        return $this;
    }

	public function getAcceptedTypes(): array
	{
		return $this->evaluate($this->acceptedTypes) ?? Cabinet::validFileTypes()->all();
	}
}
