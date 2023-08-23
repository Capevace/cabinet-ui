<?php

namespace Cabinet\Filament\Livewire\Finder;

use Filament\Actions\Action;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\HtmlString;

class ContextMenuItem implements Arrayable
{
    public function __construct(
        public string $label,
        public string|HtmlString $icon,
        public string $actionName,
    )
    {
    }


    public function toArray()
    {
        return [
            'label' => $this->label,
            'icon' => $this->icon instanceof HtmlString
                ? $this->icon
                : svg($this->icon, 'h-4 w-4')->toHtml(),
            'actionName' => $this->actionName,
        ];
    }

    public static function fromAction(Action $action): static
    {
        return new static(
            label: $action->getLabel(),
            icon: $action->getIcon(),
            actionName: $action->getName(),
        );
    }
}
