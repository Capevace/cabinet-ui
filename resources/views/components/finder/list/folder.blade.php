@props(['folder'])

<tr
    {{ $attributes->class(['group h-10 border-b border-gray-200 dark:border-gray-800 last:border-0 hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors cursor-pointer']) }}
    x-data="{ get upload() { return folderUploadState('{{ $folder->id }}'); } }"
    :class="{
        'bg-primary-50 dark:bg-primary-950/40': draggingOverFolder === '{{ $folder->id }}',
    }"
    x-show="!searchQuery || @js(strtolower($folder->name)).includes(searchQuery.toLowerCase())"

    data-drop-folder="{{ $folder->id }}"
    data-drop-folder-name="{{ $folder->name }}"
    @dragover="draggingOverFolder = '{{ $folder->id }}'"
    @dragleave.self="clearFolderDropTarget()"
    @drop.prevent="
        clearFolderDropTarget();
        const json = $event.dataTransfer.getData('application/cabinet-identifier');
        if (json) {
            const identifier = JSON.parse(json);
            $wire.moveFile(identifier.source, identifier.id, '{{ $folder->id }}');
        }
    "
    wire:click.prevent="openFolder('{{ $folder->id }}')"
    @contextmenu="openContextMenu('{{ $folder->type->slug() }}', $event, @js($folder->toIdentifier()))"
>
    {{-- Folder icon, or the upload progress when files are dropped onto it --}}
    <td class="py-1.5 px-2 w-8">
        <div class="w-7 h-7 rounded flex items-center justify-center flex-shrink-0">
            <template x-if="upload?.error">
                @svg('heroicon-o-exclamation-triangle', 'w-5 h-5 text-danger-500')
            </template>
            <template x-if="upload && ! upload.error">
                <x-filament::loading-indicator class="w-5 h-5 text-gray-500" />
            </template>
            <template x-if="! upload">
                @svg($folder->type->icon(), 'w-5 h-5 text-gray-500')
            </template>
        </div>
    </td>

    {{-- Name --}}
    <td class="py-1.5 px-2 whitespace-nowrap" colspan="2">
        <span class="font-medium text-sm text-gray-900 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
            {{ $folder->name }}
        </span>

        {{-- Files being uploaded into this folder --}}
        <span
            x-show="upload"
            x-cloak
            class="ml-2 text-xs"
            :class="upload?.error ? 'text-danger-600 dark:text-danger-400' : 'text-gray-500 dark:text-gray-400'"
            x-text="upload
                ? (upload.error
                    ? @js(__('cabinet::messages.unknown-error'))
                    : upload.count + ' · ' + Math.min(99, Math.round(upload.progress * 100)) + '%')
                : ''"
        ></span>
    </td>

    {{-- Type label / upload progress --}}
    <td class="py-1.5 px-2 hidden md:table-cell whitespace-nowrap">
        <template x-if="upload && ! upload.error">
            <div class="w-16 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <div
                    class="h-full rounded-full bg-primary-500 transition-all duration-200"
                    :style="'width: ' + Math.round(upload.progress * 100) + '%'"
                ></div>
            </div>
        </template>
        <template x-if="! upload">
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('cabinet::files.directory') }}</span>
        </template>
    </td>

    {{-- Context menu --}}
    <td class="py-1.5 px-2 text-right w-8 whitespace-nowrap">
        <button
            type="button"
            class="opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700"
            @click.stop="openContextMenu('{{ $folder->type->slug() }}', $event, @js($folder->toIdentifier()))"
            title="More actions"
        >
            @svg('heroicon-o-ellipsis-horizontal', 'w-4 h-4 text-gray-500')
        </button>
    </td>
</tr>
