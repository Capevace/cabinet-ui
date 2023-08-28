<?php

namespace Cabinet\Filament\Livewire\Finder;

use Livewire\Wireable;

class SidebarItemDto implements Wireable
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $icon,
    )
    {

    }

    public function toLivewire()
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'icon' => $this->icon,
        ];
    }

    public static function fromLivewire($value)
    {
        return new static(
            id: $value['id'],
            label: $value['label'],
            icon: $value['icon'],
        );
    }
}
