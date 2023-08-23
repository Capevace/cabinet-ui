<?php

namespace Cabinet\Filament\Livewire\Finder;

use Illuminate\Contracts\Support\Arrayable;

class ContextMenuSeperator implements Arrayable
{
    public function toArray()
    {
        return [
            'seperator' => true,
        ];
    }
}
