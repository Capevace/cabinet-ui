@props([
	'modal' => false,
    'folder',
    'acceptedTypeChecker',
    'breadcrumbs' => [],
    'contextMenus' => [],
    'toolbarActions' => [],
    'files' => [],
    'selectionMode' => null,
    'sidebarItems' => collect(),
    'selectedSidebarItem' => null,
    'selectedFiles' => [],
    'treeSidebar' => false,
    'initialFolderId' => null,
    'rounded' => true,
    'thumbnailUrls' => [],
    'fileUrls' => [],
    'lazyLoad' => false,
    'hasMoreFiles' => false,
])

@php
    if(!isset($lazyLoad) && isset($hasMoreFiles)) {
        $lazyLoad = $hasMoreFiles;
    }
@endphp

<article
    wire:key="finder"
    x-data="{
        selectedFiles: @entangle('selectedFiles').live,
        selectionEnabled: @json($selectionMode !== null),
        max: @json($selectionMode?->max ?? null),
        showSidebar: @entangle('showSidebar'),
        previousBodyOverflow: null,

        draggingFiles: 0,
        draggingVirtualFile: null,
        draggingOverFolder: null,
        uploads: [],

        viewMode: @entangle('viewMode').live,

        // Search state (browse mode only)
        searchQuery: '',

        // Bulk selection state (browse mode only)
        bulkSelectedFiles: @entangle('bulkSelectedFiles').live,

        // Detail panel state (browse mode only)
        detailFile: null,

        init() {
            this.previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';

			document.addEventListener('dragover', (e) => {
				if (!this.draggingFiles) {
					return;
				}

				e.preventDefault();
			});

			document.addEventListener('drop', (e) => {
				if (!this.draggingFiles) {
					return;
				}

				e.preventDefault();
			});
        },

        destroy() {
            document.body.style.overflow = this.previousBodyOverflow;
        },

        setViewMode(mode) {
            this.viewMode = mode;
        },

        toggleFileSelection(file) {
        	const isSelected = this.isFileSelected(file);

            if (!this.canSelectMore && !isSelected) {
                return;
            }

            if (isSelected) {
                this.selectedFiles = this.selectedFiles.filter(f => f.id !== file.id || f.source !== file.source);
            } else {
                this.selectedFiles = [...this.selectedFiles, file];
            }
        },

        toggleBulkSelection(file) {
            const key = `${file.source}:${file.id}`;
            const index = this.bulkSelectedFiles.findIndex(f => `${f.source}:${f.id}` === key);

            if (index !== -1) {
                this.bulkSelectedFiles = this.bulkSelectedFiles.filter((_, i) => i !== index);
            } else {
                this.bulkSelectedFiles = [...this.bulkSelectedFiles, file];
            }
        },

        handleFileClick(file, event) {
            if (this.selectionEnabled) {
                this.toggleFileSelection(file);
            } else if (event.metaKey || event.ctrlKey) {
                // Meta+click toggles bulk selection
                this.toggleBulkSelection(file);
                this.detailFile = null;
            } else if (this.bulkSelectedFiles.length > 0) {
                // If bulk selection active and normal click, clear bulk and show detail
                this.bulkSelectedFiles = [];
                this.detailFile = file;
            } else {
                // Browse mode: open detail panel
                if (this.detailFile && this.detailFile.id === file.id && this.detailFile.source === file.source) {
                    this.detailFile = null; // clicking same file closes the panel
                } else {
                    this.detailFile = file;
                }
            }
        },

        isFileSelected(file) {
            return this.selectedFiles.some(f => f.id === file.id && f.source === file.source);
        },

        isBulkSelected(file) {
            return this.bulkSelectedFiles.some(f => f.id === file.id && f.source === file.source);
        },

        isDetailFile(file) {
            return this.detailFile && this.detailFile.id === file.id && this.detailFile.source === file.source;
        },

        confirmFileSelection() {
            this.$wire.confirmFileSelection(this.selectedFiles);
        },

        get canSelectMore() {
            return this.selectionEnabled && (this.max === null || this.selectedFiles.length < this.max);
        },



        availableContextMenus: @js($contextMenus),

        contextMenu: null,

        get contextMenuVisible() {
            return this.contextMenu && this.contextMenu.visible;
        },

        get contextMenuItems() {
            return this.contextMenu
                ? this.contextMenu.items
                : [];
        },

        openContextMenu(type, event, data) {
            event.preventDefault();

            this.$store.finderContextMenu.items = this.availableContextMenus[type];
            this.$store.finderContextMenu.data = data;
            this.$store.finderContextMenu.position = {
                top: event.clientY,
                left: event.clientX,
            };

            this.$store.finderContextMenu.visible = true;
        },

        closeContextMenu() {
            this.contextMenu = null;
        },

        calculateContextMenuPosition(clickEvent) {
            if (window.innerHeight < clickEvent.clientY + this.$refs.contextmenu.offsetHeight) {
                this.$refs.contextmenu.style.top = (window.innerHeight - this.$refs.contextmenu.offsetHeight) + 'px';
            } else {
                this.$refs.contextmenu.style.top = clickEvent.clientY + 'px';
            }
            if (window.innerWidth < clickEvent.clientX + this.$refs.contextmenu.offsetWidth) {
                this.$refs.contextmenu.style.left = (clickEvent.clientX - this.$refs.contextmenu.offsetWidth) + 'px';
            } else {
                this.$refs.contextmenu.style.left = clickEvent.clientX + 'px';
            }

            this.$refs.contextmenu.classList.remove('opacity-0');
            this.$refs.contextmenu.style.display = 'block';
        },
        calculateSubMenuPosition (clickEvent) {
            let submenus = document.querySelectorAll('[data-submenu]');
            let contextMenuWidth = this.$refs.contextmenu.offsetWidth;

            for(let i = 0; i < submenus.length; i++){
                if(window.innerWidth < (clickEvent.clientX + contextMenuWidth + submenus[i].offsetWidth)){
                    submenus[i].classList.add('left-0', '-translate-x-full');
                    submenus[i].classList.remove('right-0', 'translate-x-full');
                } else {
                    submenus[i].classList.remove('left-0', '-translate-x-full');
                    submenus[i].classList.add('right-0', 'translate-x-full');
                }
                if(window.innerHeight < (submenus[i].previousElementSibling.getBoundingClientRect().top + submenus[i].offsetHeight)){
                    let heightDifference = (window.innerHeight - submenus[i].previousElementSibling.getBoundingClientRect().top) - submenus[i].offsetHeight;
                    submenus[i].style.top = heightDifference + 'px';
                } else {
                    submenus[i].style.top = '';
                }
            }
        },

        selectButtonLabel() {
            let translation = null;

            if (this.selectedFiles.length === 0) {
                translation = @js(trans_choice('cabinet::actions.select-no-files', $selectionMode?->max === 1 ? 1 : 9999));
            }

            if (this.selectedFiles.length === 1) {
                translation = @js(trans_choice('cabinet::actions.select-x-files', 1));
            }

            if (this.selectedFiles.length > 1) {
                translation = @js(trans_choice('cabinet::actions.select-x-files', 9999));
            }

            {{-- We use :value instead of :count because we want to replace it in JS only --}}
            return translation.replaceAll(':value', this.selectedFiles.length);
        },

        uploadDroppedFile(event) {
			event.preventDefault();
			event.stopPropagation();

			this.draggingFiles = false;

			let files = event.dataTransfer.files;

			if (files.length === 0) {
				return;
			}

			this.uploadFiles(files);
		},

		uploadFiles(files) {
			const uploadId = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);

			for (const file of files) {
				this.uploads.push({
					id: uploadId,
					name: file.name,
					progress: 0.0,
					completed: false,
					error: null,
				});
			}

			this.$wire.uploadMultiple(
				'uploadedFiles',
				files,
				() => {
					this.uploads = this.uploads.filter(upload => upload.id !== uploadId);
				},
				(error) => {
					this.uploads = this.uploads.map(upload => {
						if (upload.id === uploadId) {
							upload.error = error;
						}

						return upload;
					});
				},
				(event) => {
					this.uploads = this.uploads.map(upload => {
						if (upload.id === uploadId) {
							upload.progress = event.detail.progress / 100.0;
						}

						return upload;
					});
				}
			);
		},

		moveFileInSelection(fromIndex, toIndex) {
            const file = this.selectedFiles[fromIndex];
            const files = [...this.selectedFiles];

            files.splice(fromIndex, 1);
            files.splice(toIndex, 0, file);

            this.selectedFiles = files;
        },
    }"
    @class([
        'border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 overflow-hidden flex flex-col flex-1',
        'hidden pointer-events-none' => $this->folderId === null,
        'pointer-events-auto' => $this->folderId !== null,
        'shadow-xl' => $modal,
        'h-full' => !$modal,
        'rounded-xl' => $rounded ?? false,
    ])
    @if (!$modal)
        @cabinet:url-state-changed.window="
            const url = new URL(window.location.href);
            if ($event.detail.folder !== null) {
                url.searchParams.set('folder', $event.detail.folder);
            } else {
                url.searchParams.delete('folder');
            }
            if ($event.detail.selected !== null) {
                url.searchParams.set('selected', $event.detail.selected);
            } else {
                url.searchParams.delete('selected');
            }
            history.pushState({}, '', url);
        "
    @endif
	@style(['min-height: 500px;', 'height: 90vh;' => $modal])
>
    {{-- Selection mode header — only shown in selection mode (modal) --}}
    @if ($selectionMode)
        <header class="w-full bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-200 dark:border-gray-800 flex items-center justify-between px-4 py-2">
            <div>
                <h3 class="font-medium text-lg">{{ trans_choice('cabinet::actions.select-file', $selectionMode?->max === 1 ? 1 : 9999) }}</h3>
            </div>

            <nav class="flex items-center space-x-5">
                @if($this->selectionMode?->max)
                    <div class="text-xs flex items-center">
                        Maximal: {{ $this->selectionMode?->max }}
                    </div>
                @endif

                <x-filament::button
                    icon="heroicon-o-check"
                    icon-position="after"
                    @click="confirmFileSelection"
                    wire:target="confirmFileSelection"
                    wire:loading.attr="disabled"
                >
                    <span x-text="selectButtonLabel()"></span>
                </x-filament::button>
            </nav>
        </header>
    @endif

    <div class="flex flex-1 overflow-hidden">
        {{-- Left sidebar: location shortcuts OR directory tree --}}
        @if(count($sidebarItems) > 0 || $treeSidebar)
            <aside
                wire:key="sidebar"
                class="bg-gray-100 dark:bg-gray-900 border-r-2 border-gray-200 dark:border-gray-800 px-2 py-2 flex-shrink-0 transition-all duration-200 flex flex-col overflow-y-auto"
                :class="{
                    'w-64': showSidebar,
                    'w-16': !showSidebar,
                }"
            >
                <header
                    class="flex items-center mb-1.5 flex-shrink-0"
                    :class="{
                        'space-x-2 justify-between': showSidebar,
                        'justify-center': !showSidebar,
                    }"
                >
                    <p class="text-xs text-gray-500" x-show="showSidebar">{{ __('cabinet::messages.locations') }}</p>
                    <figure>
                        <x-filament::icon-button
                            x-show="!showSidebar"
                            icon="heroicon-o-chevron-double-right"
                            color="gray"
                            size="sm"
                            @click="$wire.showSidebar = !$wire.showSidebar"
                        />
                        <x-filament::icon-button
                            x-show="showSidebar"
                            icon="heroicon-o-chevron-double-left"
                            color="gray"
                            size="sm"
                            @click="$wire.showSidebar = !$wire.showSidebar"
                        />
                    </figure>
                </header>

                @if($treeSidebar)
                    {{-- Tree sidebar --}}
                    <div x-show="showSidebar" class="flex-1 overflow-y-auto">
                        <x-cabinet-filament::finder.tree-sidebar
                            :initial-folder-id="$initialFolderId"
                            :root-folder-name="$folder?->name ?? ''"
                        />
                    </div>
                @else
                    {{-- Shortcuts sidebar --}}
                    <ul class="grid gap-2">
                        @foreach($sidebarItems as $item)
                            <x-cabinet-filament::finder.sidebar-item
                                wire:key="{{ $item->id }}"
                                :active="$selectedSidebarItem?->id === $item->id"
                                :$item
                            />
                        @endforeach
                    </ul>
                @endif
            </aside>
        @endif


        <section class="flex-1 min-h-64 flex flex-col overflow-hidden">
            {{-- Toolbar: breadcrumbs + actions --}}
            <nav class="bg-gray-100 dark:bg-gray-900 px-4 py-2 min-h-12 flex items-center justify-between gap-x-5">
                <x-cabinet-filament::finder.breadcrumbs
                    :$breadcrumbs
                    :$folder
                    class="flex-1 min-w-0"
                />

                <div class="flex items-center gap-2 flex-shrink-0">
                    {{-- Selection summary (selection mode only) --}}
                    @if ($selectionMode)
                        <div
                            class="flex items-center space-x-2 text-xs"
                            x-show="selectedFiles.length > 0"
                        >
                            <x-filament::dropdown>
                                <x-slot:trigger>
                                    <x-filament::link url="#">
                                        <span x-text="selectedFiles.length"></span>&nbsp;{{ __('cabinet::messages.selected') }}
                                    </x-filament::link>
                                </x-slot:trigger>

                                <x-filament::dropdown.list>
                                    <div
                                        x-show="selectedFiles.length > 0"
                                        x-sortable
                                        x-on:end="moveFileInSelection($event.oldIndex, $event.newIndex)"
                                        wire:ignore
                                    >
                                        <template wire:ignore x-for="selectedFile in selectedFiles" :key="selectedFile.id">
                                            <div
                                                class="flex items-center gap-3 px-1 py-1"
                                                :id="selectedFile.id"
                                                :key="selectedFile.id"
                                                :x-sortable-item="selectedFile.id"
                                                x-sortable-handle
                                            >
                                                @svg('heroicon-o-document', 'w-5 h-5 text-gray-400 flex-shrink-0')
                                                <p class="block flex-1 truncate" x-text="selectedFile.name"></p>

                                                <x-filament::icon-button
                                                    icon="heroicon-o-x-mark"
                                                    class="flex-shrink-0 mx-0"
                                                    size="xs"
                                                    color="gray"
                                                    @click.prevent="toggleFileSelection(selectedFile)"
                                                />
                                            </div>
                                        </template>
                                    </div>
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>

                            <x-filament::icon-button
                                color="gray"
                                icon="heroicon-o-x-circle"
                                size="sm"
                                @click="selectedFiles = []"
                                class="block lg:hidden"
                                :tooltip="__('cabinet::actions.clear-selection')"
                            />
                            <x-filament::button
                                color="gray"
                                icon="heroicon-o-x-circle"
                                icon-position="after"
                                size="sm"
                                @click="selectedFiles = []"
                                class="hidden lg:flex"
                            >
                                {{ __('cabinet::actions.clear-selection') }}
                            </x-filament::button>
                        </div>
                    @endif

                    {{-- Search input (browse mode only) --}}
                    @if (!$selectionMode)
                        <x-filament::input.wrapper
                            inline-prefix
                            class="w-44"
                        >
                            <x-slot name="prefix">
                                @svg('heroicon-o-magnifying-glass', 'w-4 h-4 text-gray-400')
                            </x-slot>
                            @if ($lazyLoad)
                                <x-filament::input
                                    type="text"
                                    wire:model.live.debounce.100ms="searchQuery"
                                    :placeholder="__('cabinet::messages.search-files')"
                                />
                                <x-slot name="suffix">
                                    <button
                                        type="button"
                                        wire:click="$set('searchQuery', '')"
                                        class="fi-input-wrp-suffix p-1 -mr-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        @svg('heroicon-o-x-mark', 'w-4 h-4 text-gray-400')
                                    </button>
                                </x-slot>
                            @else
                                <x-filament::input
                                    type="text"
                                    x-model.debounce.100ms="searchQuery"
                                    :placeholder="__('cabinet::messages.search-files')"
                                />
                                <x-slot name="suffix">
                                    <button
                                        type="button"
                                        x-show="searchQuery"
                                        x-cloak
                                        @click="searchQuery = ''"
                                        class="fi-input-wrp-suffix p-1 -mr-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        @svg('heroicon-o-x-mark', 'w-4 h-4 text-gray-400')
                                    </button>
                                </x-slot>
                            @endif
                        </x-filament::input.wrapper>
                    @endif

                    {{-- View mode toggle (always shown) --}}
                    <div class="flex items-center rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <button
                            type="button"
                            @click="setViewMode('grid')"
                            :class="viewMode === 'grid' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                            class="px-2 py-1.5 transition-colors"
                            title="{{ __('cabinet::actions.view-grid') }}"
                        >
                            @svg('heroicon-o-squares-2x2', 'w-4 h-4')
                        </button>
                        <button
                            type="button"
                            @click="setViewMode('list')"
                            :class="viewMode === 'list' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                            class="px-2 py-1.5 transition-colors"
                            title="{{ __('cabinet::actions.view-list') }}"
                        >
                            @svg('heroicon-o-list-bullet', 'w-4 h-4')
                        </button>
                    </div>

                    {{-- Toolbar actions (upload, create folder) --}}
                    <nav class="flex items-center gap-3">
                        @foreach($toolbarActions as $action)
                            {{ $action }}
                        @endforeach
                    </nav>
                </div>
            </nav>

            {{-- Main content area: file grid/list + optional detail sidebar --}}
            <div class="flex flex-1 overflow-hidden">
                <main
                    class="relative flex-1 overflow-y-auto transition-colors flex flex-col"
                >
                    <x-cabinet-filament::finder.cards
                        :$acceptedTypeChecker
                        :has-sidebar="count($sidebarItems) > 0"
                        :max="$selectionMode?->max"
                        :$files
                        :preview-action="$this->previewFileAction"
                        :view-mode="$this->viewMode"
                        :show-sidebar="$this->showSidebar"
                        :thumbnail-urls="$thumbnailUrls"
                        :lazy-load="$lazyLoad"
                        :has-more-files="$hasMoreFiles"
                    />

                    <template x-if="!draggingVirtualFile && draggingFiles > 0">

                        <div
                            class="z-10 absolute inset-0 flex flex-col items-center justify-center pointer-events-none bg-gray-200/50 dark:bg-gray-800/50 backdrop-blur font-medium"
                            x-show="!draggingVirtualFile && draggingFiles > 0"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                        >
                            @svg('heroicon-o-cloud-arrow-up', 'w-20 h-20 text-gray-500 mb-5 bg-white border shadow-inner border-gray-200 rounded-full p-2')
                            <p class="filter text-gray-700 bg-gray-50 border shadow-inner border-gray-200 rounded-xl px-3 py-1">{{ __('cabinet::messages.drop-files-to-upload') }}</p>
                        </div>
                    </template>
                </main>

                {{-- Detail sidebar — browse mode only, shown when a file is clicked --}}
                @if (!$selectionMode)
                    <x-cabinet-filament::finder.detail-panel
                        :$files
                        :thumbnail-urls="$thumbnailUrls"
                        :file-urls="$fileUrls"
                        wire:key="detail-panel-{{ $folder?->id }}"
                    />
                @endif
            </div>
        </section>
    </div>

    <x-filament-actions::modals/>

    @teleport('body')
        <x-cabinet-filament::finder.contextmenu />
    @endteleport

</article>
