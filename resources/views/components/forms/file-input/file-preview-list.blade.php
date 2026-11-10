@props(['file', 'disableDelete' => false, 'sortable' => false])

<div
    {{ $attributes->class("relative w-full flex gap-5 items-center") }}

    @if ($sortable)
    x-sortable-handle
    x-sortable-item="{{ $file->uniqueId() }}"
    @endif
>
    @if ($sortable)
        @svg('heroicon-o-bars-3', 'w-6 h-6 text-gray-500 dark:text-gray-400 cursor-pointer drag-handle')
    @endif

    <div
		class="w-20 flex items-center justify-center rounded-md overflow-hidden flex-shrink-0"
        style="aspect-ratio: 4/3"
	>
        @if(!filled($file->previewUrl))
            @svg($file->icon ?? $file->type->icon(), 'w-20 h-20 text-gray-500')
		@else
            <img
                src="{{ $file->previewUrl }}"
                alt="{{ $file->name }}"
                loading="lazy"
                class="w-full h-full object-center object-cover"
            />
        @endif
    </div>

    <div class="flex-1">
        <p class="mb-0.5 text-sm font-semibold text-gray-950 dark:text-gray-100 line-clamp-2">{{ $file->name }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
            <span>{{ $file->formattedMimeType() }}</span>
            –
            <span>{{ $file->humanSize() }}</span>
        </p>
    </div>

    @unless ($disableDelete)
        {{-- The whole row is a drag handle: a press on the button must not start a drag. --}}
        <div
            class="flex-shrink-0"
            x-on:pointerdown.stop
            x-on:mousedown.stop
        >
            <x-filament::loading-indicator
                x-show="removingFile === '{{ $file->source }}:{{ $file->id }}'"
                x-cloak
                class="w-5 h-5 text-gray-400"
            />

            <x-filament::icon-button
                icon="heroicon-m-x-mark"
                color="gray"
                size="sm"
                :label="__('cabinet::actions.clear-selection')"
                :tooltip="__('cabinet::actions.clear-selection')"
                x-show="removingFile !== '{{ $file->source }}:{{ $file->id }}'"
                x-bind:disabled="removingFile !== null"
                x-on:click.stop="removeFile({ source: '{{ $file->source }}', id: '{{ $file->id }}' })"
            />
        </div>
    @endunless
</div>
