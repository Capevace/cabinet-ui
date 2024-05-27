@props([
    'item',
    'active' => false
])
@php
    $badge = null;
@endphp



<button
    {{ $attributes->class([
        'truncate relative font-semibold text-left flex items-center justify-center gap-x-3 rounded-lg px-2 py-2 text-sm text-gray-700 outline-none transition duration-75 hover:bg-gray-100 focus:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/5 dark:focus:bg-white/5',
        'bg-gray-100 text-primary-600 dark:bg-white/5 dark:text-primary-400' => $active,
    ]) }}


    x-data="{}"
    type="button"
    wire:click="openFolder('{{ $item->id }}')"
>
    <x-filament::loading-indicator
        wire:loading
        wire:target="openFolder('{{ $item->id }}')"
        class="w-6 h-6 text-primary-600 dark:text-primary-400"
    />

    <x-filament::icon
        :icon="$item->icon"
        wire:loading.remove
        wire:target="openFolder('{{ $item->id }}')"

        @class([
            'fi-sidebar-item-icon h-6 w-6',
            'text-gray-400 dark:text-gray-500' => ! $active,
            'text-primary-600 dark:text-primary-400' => $active,
        ])
    />

    <span
        class="flex-1 truncate"
        x-show="showSidebar"
    >
        {{ $item->label }}
    </span>

    @if (filled($badge))
        <span>
            <x-filament::badge :color="$badgeColor">
                {{ $badge }}
            </x-filament::badge>
        </span>
    @endif
</button>
