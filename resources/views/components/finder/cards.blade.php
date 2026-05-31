@props(['files', 'acceptedTypeChecker', 'previewAction' => null, 'hasSidebar' => false, 'viewMode' => 'grid', 'showSidebar' => true, 'thumbnailUrls' => [], 'lazyLoad' => false, 'hasMoreFiles' => false])

<div
    class="flex-1 flex flex-col"
    @dragenter="
        if (!$event.dataTransfer.getData('application/cabinet-identifier') && draggingVirtualFile === false) {
            draggingFiles++;
        }
    "
    @dragleave="
        if (!$event.dataTransfer.getData('application/cabinet-identifier')) {
            draggingFiles--;
        }
    "
    @drop.prevent="
        if (!$event.dataTransfer.getData('application/cabinet-identifier')) {
            uploadDroppedFile($event);
            $event.preventDefault();
            draggingFiles = 0;
        }
    "
>
    {{-- Grid view --}}
    <ul
        {{ $attributes->class([
            'grid px-4 py-4 gap-5 overflow-y-auto',
            'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5' => !$showSidebar,
            'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4' => $showSidebar,
        ]) }}
        x-show="viewMode === 'grid'"
        @if ($viewMode !== 'grid') x-cloak @endif
    >
        {{-- <x-cabinet-filament::finder.upload-template /> --}}

        @foreach($files as $file)
            @if ($file instanceof \Cabinet\File)
                <x-cabinet-filament::finder.cards.file
                    wire:key="file-{{ $file->source }}-{{ $file->id }}"
                    :$file
                    :preview-action="$previewAction($file->toIdentifier())"
                    :disabled="!$acceptedTypeChecker->isAccepted($file->type)"
                    :stable-thumbnail-url="$thumbnailUrls[$file->source . ':' . $file->id]['normal'] ?? null"
                />
            @elseif ($file instanceof \Cabinet\Folder)
                <x-cabinet-filament::finder.cards.folder
                    :folder="$file"
                    wire:key="folder-{{ $file->source }}-{{ $file->id }}"
                />
            @endif
        @endforeach
    </ul>

    {{-- List view --}}
    <div
        class="px-4 py-2"
        x-show="viewMode === 'list'"
        @if ($viewMode !== 'list') x-cloak @endif
    >
        {{-- Upload placeholders in list mode --}}
        <template x-for="(upload, index) of uploads">
            <div
                :key="upload.id + index"
                class="flex items-center gap-3 px-3 py-2 border-b border-gray-200 dark:border-gray-800 last:border-0 text-sm text-gray-500"
            >
                <x-filament::loading-indicator class="w-5 h-5 text-gray-400 flex-shrink-0" />
                <span class="flex-1 truncate" x-text="upload.name"></span>
                <span class="text-xs text-gray-400" x-text="Math.round(upload.progress * 100) + '%'"></span>
            </div>
        </template>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-800">
                    <th class="text-left py-2 px-2 font-medium text-xs text-gray-500 dark:text-gray-400 w-8"></th>
                    <th wire:click="toggleSort('name')" class="text-left py-2 px-2 font-medium text-xs text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none">
                        {{ __('cabinet::messages.file-name') }}
                        @if ($this->sortColumn === 'name')
                            @svg($this->sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 inline-block ml-1')
                        @endif
                    </th>
                    <th wire:click="toggleSort('type')" class="text-left py-2 px-2 font-medium text-xs text-gray-500 dark:text-gray-400 hidden sm:table-cell cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none">
                        {{ __('cabinet::messages.file-type') }}
                        @if ($this->sortColumn === 'type')
                            @svg($this->sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 inline-block ml-1')
                        @endif
                    </th>
                    <th wire:click="toggleSort('size')" class="text-left py-2 px-2 font-medium text-xs text-gray-500 dark:text-gray-400 hidden md:table-cell cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none">
                        {{ __('cabinet::messages.file-size') }}
                        @if ($this->sortColumn === 'size')
                            @svg($this->sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 inline-block ml-1')
                        @endif
                    </th>
                    <th wire:click="toggleSort('created')" class="text-left py-2 px-2 font-medium text-xs text-gray-500 dark:text-gray-400 hidden md:table-cell cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none">
                        {{ __('cabinet::messages.file-created') }}
                        @if ($this->sortColumn === 'created')
                            @svg($this->sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 inline-block ml-1')
                        @endif
                    </th>
                    <th class="text-right py-2 px-2 font-medium text-xs text-gray-500 dark:text-gray-400 w-8"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($files as $file)
                    @if ($file instanceof \Cabinet\File)
                        <x-cabinet-filament::finder.list.file
                            wire:key="list-file-{{ $file->source }}-{{ $file->id }}"
                            :$file
                            :preview-action="$previewAction($file->toIdentifier())"
                            :disabled="!$acceptedTypeChecker->isAccepted($file->type)"
                            :stable-thumbnail-url="$thumbnailUrls[$file->source . ':' . $file->id]['tiny'] ?? null"
                        />
                    @elseif ($file instanceof \Cabinet\Folder)
                        <x-cabinet-filament::finder.list.folder
                            :folder="$file"
                            wire:key="list-folder-{{ $file->source }}-{{ $file->id }}"
                        />
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($hasMoreFiles)
        <div class="flex justify-center py-4">
            <x-filament::button
                wire:click="loadMore"
                wire:loading.attr="disabled"
                color="gray"
                size="sm"
            >
                {{ __('cabinet::actions.load-more') }}
            </x-filament::button>
        </div>
    @endif

    @if (count($files) === 0)
        <x-filament::empty-state
            heading="{{ __('cabinet::messages.empty-folder') }}"
            description="{{ __('cabinet::messages.drag-or-add-files') }}"
            icon="heroicon-o-folder"
            class="col-span-full opacity-75s bg-transparent !inset-ring-0 !border-0 !ring-0 !shadow-none"
        >
            <x-slot:after-heading>
                {{
                    $this->uploadFileAction
                        ->button()
                        ->color('gray')
                }}
            </x-slot:after-heading>
        </x-filament::empty-state>
    @endif
</div>
