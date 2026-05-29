@props(['file', 'previewAction' => null, 'disabled' => false, 'stableThumbnailUrl' => null])

@php
    $thumbnailUrl = $stableThumbnailUrl ?? $file->previewUrl;
@endphp

<tr
    {{ $attributes->class(['group border-b border-gray-200 dark:border-gray-800 last:border-0 hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors cursor-pointer']) }}
    :class="{
        'opacity-60 cursor-not-allowed': selectionEnabled && (!canSelectMore && !isFileSelected(@js($file->toIdentifier())) || {{ $disabled ? 'true' : 'false' }}),
        'bg-primary-50 dark:bg-primary-950/30': isFileSelected(@js($file->toIdentifier())),
        'bg-gray-100 dark:bg-gray-900': !selectionEnabled && isDetailFile(@js($file->toIdentifier())),
        'bg-secondary-50 dark:bg-secondary-950/20': !selectionEnabled && isBulkSelected(@js($file->toIdentifier())),
    }"
    draggable="true"
    x-on:dragstart="
        draggingVirtualFile = true;
        draggingFiles = 0;
        const json = JSON.stringify(@js($file->toIdentifier()));
        $event.dataTransfer.setData('application/cabinet-identifier', json);
        $event.dataTransfer.effectAllowed = 'move';
    "
    x-on:dragend="
        draggingVirtualFile = false;
        draggingFiles = 0;
    "
    @click="
        if (!(selectionEnabled && (!canSelectMore && !isFileSelected(@js($file->toIdentifier())) || {{ $disabled ? 'true' : 'false' }}))) {
            handleFileClick(@js($file->toIdentifier()), $event);
        }
    "
    @contextmenu="openContextMenu('{{ $file->type->slug() }}', $event, @js($file->toIdentifier()))"
>
    {{-- Type icon / thumbnail --}}
    <td class="py-1.5 px-2 w-8">
        <div class="w-7 h-7 rounded overflow-hidden flex items-center justify-center bg-gray-200 dark:bg-gray-700 flex-shrink-0">
            @if(filled($thumbnailUrl) && in_array($file->type->slug(), ['image', 'video', 'pdf']))
                <img
                    src="{{ $thumbnailUrl }}"
                    alt="{{ $file->name }}"
                    loading="lazy"
                    class="w-full h-full object-cover object-center"
                    draggable="false"
                />
            @else
                @svg($file->icon ?? $file->type->icon(), 'w-4 h-4 text-gray-500')
            @endif
        </div>
    </td>

    {{-- Name --}}
    <td class="py-1.5 px-2">
        <div class="flex items-center gap-2">
            <span
                class="font-medium text-sm truncate max-w-xs text-gray-900 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"
                :class="{
                    'text-primary-600 dark:text-primary-400': isFileSelected(@js($file->toIdentifier())),
                }"
            >
                {{ $file->name }}
            </span>
            {{-- Selection checkmark --}}
            <span
                x-show="isFileSelected(@js($file->toIdentifier()))"
                class="flex-shrink-0"
            >
                @svg('heroicon-s-check-circle', 'w-4 h-4 text-primary-500')
            </span>
            {{-- Bulk selection checkmark --}}
            <span
                x-show="!selectionEnabled && isBulkSelected(@js($file->toIdentifier()))"
                class="flex-shrink-0"
                x-cloak
            >
                @svg('heroicon-s-check-circle', 'w-4 h-4 text-secondary-500')
            </span>
        </div>
    </td>

    {{-- Type --}}
    <td class="py-1.5 px-2 hidden sm:table-cell">
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $file->type->name() }}</span>
    </td>

    {{-- Size --}}
    <td class="py-1.5 px-2 hidden md:table-cell">
        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
            @if(method_exists($file, 'humanSize'))
                {{ $file->humanSize() }}
            @endif
        </span>
    </td>

    {{-- Created --}}
    <td class="py-1.5 px-2 hidden md:table-cell">
        <span class="text-xs text-gray-500 dark:text-gray-400">
            {{ $file->formattedCreatedAt() }}
        </span>
    </td>

    {{-- Context menu trigger --}}
    <td class="py-1.5 px-2 text-right w-8">
        <button
            type="button"
            class="opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700"
            @click.stop="openContextMenu(!selectionEnabled && isBulkSelected(@js($file->toIdentifier())) && bulkSelectedFiles.length > 1 ? 'bulk' : '{{ $file->type->slug() }}', $event, @js($file->toIdentifier()))"
            title="More actions"
        >
            @svg('heroicon-o-ellipsis-horizontal', 'w-4 h-4 text-gray-500')
        </button>
    </td>
</tr>
