@props([
    'folder',
    'breadcrumbs' => [],
    'contextMenus' => [],
    'toolbarActions' => [],
    'files' => [],
    'selectionMode' => null,
    'sidebarItems' => collect(),
    'selectedSidebarItem' => null,
])

<article
    wire:key="finder"
    x-data="{
        selectedFiles: @entangle('selectedFiles'),
        max: @json($selectionMode?->max ?? null),
        previousBodyOverflow: null,

        init() {
            this.previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
        },

        destroy() {
            document.body.style.overflow = this.previousBodyOverflow;
        },

        toggleFileSelection(file) {
            if (@json($selectionMode?->max !== null) && this.selectedFiles.length >= {{ $selectionMode?->max ?? 0 }} && !this.isFileSelected(file)) {
                return;
            }

            if (this.isFileSelected(file)) {
                this.selectedFiles = this.selectedFiles.filter(f => f.id !== file.id && f.source !== file.source);
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
            return this.max === null || this.selectedFiles.length < this.max;
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
        }
    }"
    @class([
        'border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 rounded-xl overflow-hidden flex flex-col shadow-xl',
        'hidden pointer-events-none' => $this->folderId === null,
        'pointer-events-auto' => $this->folderId !== null,
    ])
    style="min-height: 500px;"
>
    <header class="w-full bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-200 dark:border-gray-800 flex items-center justify-between px-4 py-2">
        <div>
            <h3 class="font-medium text-xl">Select file</h3>
            <p class="text-sm text-gray-700 dark:text-gray-400">Select a file to get started with Cabinet</p>
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
                x-bind:disabled="selectedFiles.length === 0"
            >
                <span x-show="selectedFiles.length === 0">
                    No files selected
                </span>
                <span x-show="selectedFiles.length > 0">
                    Select <span x-text="selectedFiles.length"></span> files
                </span>
            </x-filament::button>
        </nav>
    </header>

    <div class="flex flex-1">
        <aside class="bg-gray-100 dark:bg-gray-900 w-64 border-r-2 border-gray-200 dark:border-gray-800 px-2 py-2">
            @if($sidebarItems->isNotEmpty())
                <p class="text-xs text-gray-500 mb-2">Orte</p>
                <ul class="grid gap-1">
                    @foreach($sidebarItems as $item)
                        @if($folder === null)
                            @dd($sidebarItems)
                        @endif

                        <x-cabinet-filament::finder.sidebar-item
                            wire:key="{{ $item->id }}"
                            :active="$selectedSidebarItem?->id === $item->id"
                            :$item
                        />
                    @endforeach
                </ul>
            @endif
        </aside>


        <section class="flex-1 min-h-64 flex flex-col">
            <nav class="bg-gray-100 dark:bg-gray-900 px-4 py-2 flex justify-between items-center">
                <x-cabinet-filament::finder.breadcrumbs
                    :$breadcrumbs
                />

                <div class="flex items-center">
                    <div class="flex items-center space-x-4 text-xs mr-5" x-show="selectedFiles.length > 0">
                        <p>
                            <span x-text="selectedFiles.length"></span> files selected
                        </p>

                        <x-filament::button
                            color="gray"
                            icon="heroicon-o-x-circle"
                            icon-position="after"
                            size="sm"
                            @click="selectedFiles = []"
                        >
                            Auswahl aufheben
                        </x-filament::button>
                    </div>

                    @foreach($toolbarActions as $action)
                        {{ $action }}
                    @endforeach
                </div>
            </nav>
            <main class="flex-1">
                <x-cabinet-filament::finder.cards
                    :max="$selectionMode?->max"
                    :$files
                    :preview-action="$this->previewFileAction"
                />
            </main>
        </section>
    </div>

    <x-filament-actions::modals/>

    @teleport('body')
        <x-cabinet-filament::finder.contextmenu />
    @endteleport

</article>
