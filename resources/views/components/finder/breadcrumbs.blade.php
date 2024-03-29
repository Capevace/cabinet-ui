@props([
    'breadcrumbs' => [],
])

@php
    $iconAlias = 'breadcrumbs.separator';
    $iconClasses = 'h-5 w-5 text-gray-400 dark:text-gray-500';
@endphp

<nav {{ $attributes->class(['fi-breadcrumbs']) }}>
    <ol class="flex flex-wrap items-center gap-x-2">
        @foreach ($breadcrumbs as $breadcrumb)
            <li class="flex gap-x-2" wire:key="{{ $breadcrumb->folderId }}">
                @if (! $loop->first)
                    <x-filament::icon
                        :alias="$iconAlias"
                        icon="heroicon-m-chevron-right"
                        @class([
                            $iconClasses,
                            'rtl:hidden',
                        ])
                    />

{{--                    <x-filament::icon--}}
{{--                        :alias="$iconAlias"--}}
{{--                        icon="heroicon-m-chevron-left"--}}
{{--                        @class([--}}
{{--                            $iconClasses,--}}
{{--                            'ltr:hidden',--}}
{{--                        ])--}}
{{--                    />--}}
                @endif

                <button
                    wire:click.prevent="{{ $breadcrumb->action }}"
                    type="button"
                    {{-- wire:navigate --}}
                    class="text-sm font-medium text-gray-500 outline-none transition duration-75 hover:text-gray-700 focus:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 dark:focus:text-gray-200"
                >
                    {{ $breadcrumb->label }}
                </button>
            </li>
        @endforeach
    </ol>
</nav>
