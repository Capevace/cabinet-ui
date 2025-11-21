@props([
    'breadcrumbs' => [],
    /** @var ?\Cabinet\Folder $folder */
    'folder' => null
])

@php
    $iconAlias = 'breadcrumbs.separator';
    $iconClasses = 'h-5 w-5 text-gray-400 dark:text-gray-500';
@endphp

<nav {{ $attributes->class(['fi-breadcrumbs']) }}>
    <ol class="flex items-center justify-start gap-x-2 w-full">
        @foreach ($breadcrumbs as $breadcrumb)
            <li
                @class([
                    'inline-flex gap-x-2 line-clamp-1',
                    'w-full flex-1' => $breadcrumb->folderId === $folder->id,
                    'flex-shrink w-min max-w-[5rem]' => $breadcrumb->folderId !== $folder->id
                ])
                wire:key="{{ $breadcrumb->folderId }}"
                @dragover.prevent="draggingOverFolder = '{{ $breadcrumb->folderId }}'"
                @dragleave.self="draggingOverFolder = null"
                @drop="
                    const json = $event.dataTransfer.getData('application/cabinet-identifier');
                    const identifier = JSON.parse(json);

                    $wire.moveFile(identifier.source, identifier.id, '{{ $breadcrumb->folderId }}');
                    draggingOverFolder = null;
                "
            >
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
                    class="line-clamp-1 text-sm font-medium text-gray-500 outline-none transition duration-75 hover:text-gray-700 focus:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 dark:focus:text-gray-200"
                    :class="{
                        'ring-2 ring-primary-500 scale-125 rounded-md p-1': draggingOverFolder === '{{ $breadcrumb->folderId }}',
                    }"
                >
                    {{ $breadcrumb->label }}
                </button>
            </li>
        @endforeach
    </ol>
</nav>
