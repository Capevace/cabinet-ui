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
    'replaceableThumbnailUrl' => null,
    'selectedFiles' => [],
])

<article
    wire:key="finder"
    x-data="{
        selectedFiles: @entangle('selectedFiles').live,
        selectionEnabled: @json($selectionMode !== null),
        max: @json($selectionMode?->max ?? null),
        showSidebar: @entangle('showSidebar'),
        previousBodyOverflow: null,

        draggingFiles: 0,
        uploads: [],

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

				console.log(e.dataTransfer.files);
			});
        },

        destroy() {
            document.body.style.overflow = this.previousBodyOverflow;
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

        isFileSelected(file) {
            return this.selectedFiles.some(f => f.id === file.id && f.source === file.source);
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

		moveFileInSelection(event, fromIndex, toIndex) {
            setTimeout(() => {
                    const file = this.selectedFiles[fromIndex];
                    const files = [...this.selectedFiles];

                    files.splice(fromIndex, 1);
                    files.splice(toIndex, 0, file);

                    this.selectedFiles = files;
                    this.selectedFiles = files;
            }, 1000);
        },
    }"
    @class([
        'border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 rounded-xl overflow-hidden flex flex-col flex-1',
        'hidden pointer-events-none' => $this->folderId === null,
        'pointer-events-auto' => $this->folderId !== null,
        'shadow-xl' => $modal,
        'h-full' => !$modal,
    ])
	@style(['min-height: 500px;', 'height: 90vh;' => $modal])
>
    @if ($selectionMode)
        <header class="w-full bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-200 dark:border-gray-800 flex items-center justify-between px-4 py-2">
            <div>
                <h3 class="font-medium text-lg">{{ trans_choice('cabinet::actions.select-file', $selectionMode?->max === 1 ? 1 : 9999) }}</h3>
    {{--            <p class="text-sm text-gray-700 dark:text-gray-400">Select a file to get started with Cabinet</p>--}}
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
        @if(count($sidebarItems) > 0)
            <aside
                wire:key="sidebar"
                class="bg-gray-100 dark:bg-gray-900 border-r-2 border-gray-200 dark:border-gray-800 px-2 py-2"
                :class="{
                    'w-64': showSidebar,
                    'w-16': !showSidebar,
                }"
            >
                <header
                    class="flex items-center mb-1.5"
                    :class="{
                        'space-x-2 justify-between': showSidebar,
                        'justify-center': !showSidebar,
                    }"
                >
                    <p class="text-xs text-gray-500" x-show="showSidebar">Orte</p>
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

                <ul class="grid gap-2">
                    @foreach($sidebarItems as $item)
                        <x-cabinet-filament::finder.sidebar-item
                            wire:key="{{ $item->id }}"
                            :active="$selectedSidebarItem?->id === $item->id"
                            :$item
                        />
                    @endforeach
                </ul>
            </aside>
        @endif


        <section class="flex-1 min-h-64 flex flex-col overflow-hidden">
            <nav class="bg-gray-100 dark:bg-gray-900 px-4 py-2 h-12 flex items-start justify-between md:items-center flex-col md:flex-row">
                <x-cabinet-filament::finder.breadcrumbs :$breadcrumbs />

                <div
                    class="flex items-center w-full md:w-auto"
                    :class="{
                        'justify-end': selectedFiles.length === 0,
                        'justify-between': selectedFiles.length > 0,
                    }"
                >
                    <div
                        class="flex items-center space-x-4 text-xs mr-5"
                    >
                        <x-filament::dropdown>
                            <x-slot:trigger>
                                <x-filament::link url="#">
                                    <span x-text="selectedFiles.length"></span> ausgewählt
                                </x-filament::link>
{{--                                <pre x-html="JSON.stringify(selectedFiles, null, 2)"></pre>--}}
                            </x-slot:trigger>

                            <x-filament::dropdown.list>
                                <div
                                    x-show="selectedFiles.length > 0"
                                    x-data="{
                                        makeThumbnailUrl(file) {
                                            return '{{ $replaceableThumbnailUrl }}'
                                                .replaceAll('REPLACE_SOURCE', file.source)
                                                .replaceAll('REPLACE_ID', file.id);
                                        }
                                    }"
                                    x-sortable
                                    x-on:end="moveFileInSelection($event.oldIndex, $event.newIndex)"
                                    wire:ignore
                                >
                                    <template wire:ignore x-for="selectedFile in selectedFiles">
                                        <div
                                            class="flex items-center gap-3 px-1 py-1"
{{--                                            wire:key="{{ $selectedFile->source }}-{{ $selectedFile->id }}"--}}
{{--                                            key="{{ $selectedFile->source }}-{{ $selectedFile->id }}"--}}
                                            :wire:key="`${selectedFile.source}-${selectedFile.id}`"
                                            :id="selectedFile.id"
                                            :key="selectedFile.id"
                                            :x-sortable-item="selectedFile.id"
                                            x-sortable-handle
                                        >
                                            <img
                                                :src="makeThumbnailUrl(selectedFile)"
                                                class="w-6 aspect-square object-cover object-center rounded flex-shrink-0"
                                            />
                                            <p class="block flex-1 truncate" x-text="selectedFile.name"></p>

                                            <x-filament::icon-button
                                                icon="heroicon-o-x-mark"
                                                class="flex-shrink-0 mx-0"
                                                size="xs"
                                                color="gray"
                                                @click.prevent="toggleFileSelection(selectedFile)"
                                            />
                                        </div>
{{--                                    @endforeach--}}
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
                            tooltip="Auswahl aufheben"
                        />
                        <x-filament::button
                            color="gray"
                            icon="heroicon-o-x-circle"
                            icon-position="after"
                            size="sm"
                            @click="selectedFiles = []"
                            class="hidden lg:flex"
                        >
                            Auswahl aufheben
                        </x-filament::button>
                    </div>

                    <nav class="flex items-center gap-3">
                        @foreach($toolbarActions as $action)
                            {{ $action }}
                        @endforeach
                    </nav>
                </div>
            </nav>
            <main
				class="relative flex-1 overflow-y-auto transition-colors flex flex-col"
			>
                <x-cabinet-filament::finder.cards
                    :$acceptedTypeChecker
                    :has-sidebar="count($sidebarItems) > 0"
                    :max="$selectionMode?->max"
                    :$files
                    :preview-action="$this->previewFileAction"
                />

                <div
					class="z-10 absolute inset-0 flex flex-col items-center justify-center pointer-events-none bg-gray-200/50 dark:bg-gray-800/50 backdrop-blur font-medium"
					x-show="draggingFiles > 0"
					x-transition:enter="transition ease-out duration-150"
					x-transition:enter-start="opacity-0"
					x-transition:enter-end="opacity-100"
					x-transition:leave="transition ease-in duration-150"
					x-transition:leave-start="opacity-100"
					x-transition:leave-end="opacity-0"
				>
					@svg('heroicon-o-cloud-arrow-up', 'w-20 h-20 text-gray-500 mb-5 bg-white border shadow-inner border-gray-200 rounded-full p-2')
					<p class="filter text-gray-700 bg-gray-50 border shadow-inner border-gray-200 rounded-xl px-3 py-1">Dateien hier ablegen, um sie hochzuladen</p>
				</div>
            </main>
        </section>
    </div>

    <x-filament-actions::modals/>

    @teleport('body')
        <x-cabinet-filament::finder.contextmenu />
    @endteleport

</article>
