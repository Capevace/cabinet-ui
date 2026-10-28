@props(['folder'])

<li
    {{ $attributes->class(['flex flex-col transform group border border-gray-200 dark:border-gray-800 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 transition rounded-md overflow-hidden']) }}
    x-data="{ get upload() { return folderUploadState('{{ $folder->id }}'); } }"
    :class="{
        'ring-2 ring-primary-500 border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-950/40': draggingOverFolder === '{{ $folder->id }}',
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
>
    <button
        wire:click.prevent="openFolder('{{ $folder->id }}')"
        type="button"
        class="flex flex-col flex-1 w-full text-left"
        @contextmenu="openContextMenu('{{ $folder->type->slug() }}', $event, @js($folder->toIdentifier()))"
        :class="{
            'pointer-events-none': draggingOverFolder
        }"
    >
        <figure class="relative h-32 w-full flex items-center justify-center bg-gray-200 dark:bg-gray-800">
            <template x-if="upload">
                <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 px-4">
                    <template x-if="upload.error">
                        @svg('heroicon-o-exclamation-triangle', 'w-14 h-14 text-danger-500')
                    </template>
                    <template x-if="! upload.error">
                        <x-filament::loading-indicator class="w-14 h-14 text-gray-500" />
                    </template>

                    <div class="w-full h-1.5 rounded-full bg-gray-300 dark:bg-gray-700 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-200"
                            :class="upload.error ? 'bg-danger-500' : 'bg-primary-500'"
                            :style="'width: ' + Math.round(upload.progress * 100) + '%'"
                        ></div>
                    </div>
                </div>
            </template>

            <template x-if="! upload">
                @svg($folder->type->icon(), 'w-20 h-20 text-gray-500')
            </template>

            {{-- How many files are being uploaded into this folder --}}
            <span
                x-show="upload && upload.count > 1"
                x-cloak
                class="absolute top-2 right-2 min-w-5 h-5 px-1 rounded-full bg-primary-500 text-white text-[11px] font-semibold flex items-center justify-center shadow-sm"
                x-text="upload?.count"
            ></span>
        </figure>

        <div class="px-2 py-1 flex flex-col justify-between w-full h-full flex-1">
            <p class="font-medium line-clamp-2">
                {{ $folder->name }}
            </p>

            <p
                class="text-sm"
                :class="upload?.error ? 'text-danger-600 dark:text-danger-400' : 'text-gray-700 dark:text-gray-400'"
                x-text="upload
                    ? (upload.error ? @js(__('cabinet::messages.unknown-error')) : Math.min(99, Math.round(upload.progress * 100)) + '%')
                    : @js(__('cabinet::files.directory'))"
            ></p>
        </div>
    </button>
</li>
