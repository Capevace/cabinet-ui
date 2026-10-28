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
    'previewUrls' => [],
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
    x-on:keydown.window="trackModifiers($event)"
    x-on:keyup.window="trackModifiers($event)"
    x-on:blur.window="shiftKeyHeld = false"
    x-data="{
        selectedFiles: @entangle('selectedFiles').live,
        selectionEnabled: @json($selectionMode !== null),
        max: @json($selectionMode?->max ?? null),
        showSidebar: @entangle('showSidebar'),
        previousBodyOverflow: null,

        draggingFiles: 0,
        draggingVirtualFile: null,
        draggingOverFolder: null,
        draggingOverFolderName: null,
        dragHint: { x: 0, y: 0 },
        /** Upload queue: [{ id, folderId, folderName, files, progress, error, state }] */
        uploadQueue: [],
        /**
         * File objects per drop. Kept in a plain window object, not in the
         * reactive state: a proxied File cannot be appended to a FormData.
         */
        uploadFilesById: {},
        uploadsRunning: false,
        uploadCancelled: false,
        uploadRun: 0,
        uploadRefreshInterval: null,
        uploadRefreshUntil: 0,
        uploadChunkSize: @js($this->uploadChunkSize),

        viewMode: @entangle('viewMode').live,

        // Search state (browse mode only)
        searchQuery: '',

        init() {
            // `this.$el` inside a method is the element whose handler ran (a
            // card), not the finder: capture the root once.
            this.finderRoot = this.$el;

            this.previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';

            // A dropped file must never be opened by the browser: inside the
            // finder area the drop uploads, anywhere else inside the finder it
            // is at least neutralised. Outside of the finder the default is
            // left alone so other drop targets keep working.
            document.addEventListener('dragover', (event) => {
                if (this.isFileDrag(event) && this.$el.contains(event.target)) {
                    event.preventDefault();
                }
            });

            document.addEventListener('drop', (event) => {
                if (this.isFileDrag(event) && this.$el.contains(event.target)) {
                    event.preventDefault();
                }

                this.draggingFiles = 0;
            });

            // Dragging out of the window never fires a drop event.
            document.addEventListener('dragleave', (event) => {
                if (!event.relatedTarget) {
                    this.draggingFiles = 0;
                }
            });

            // Escape cancels the drag and any upload it started.
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    this.cancelDrop();
                }
            });

            // Reload the file list when the folder on screen received files.
            this.$el.addEventListener('cabinet:files-uploaded', (event) => {
                const uploadedFolderId = event.detail?.folderId;

                if (uploadedFolderId === null || uploadedFolderId === this.currentFolderId) {
                    this.$wire.refresh();
                }
            });
        },

        /** Folder the finder is showing right now. */
        get currentFolderId() {
            return this.$wire.folderId;
        },

        /** Name of the folder on screen, used to label its uploads. */
        get folderName() {
            return @js($folder?->name ?? '');
        },

        /**
         * Plain (non-reactive) store for the File objects of the queue: a
         * proxied File cannot be appended to a FormData. Keyed per finder, so
         * the modal and the page finder do not share their drops.
         */
        uploadFiles(reset = false) {
            const key = this.$wire.$id;

            window.cabinetUploadFiles ??= {};

            if (reset || ! window.cabinetUploadFiles[key]) {
                window.cabinetUploadFiles[key] = {};
            }

            return window.cabinetUploadFiles[key];
        },

        isFileDrag(event) {
            return Array.from(event.dataTransfer?.types ?? []).includes('Files');
        },

        handleFileDragOver(event) {
            this.draggingFiles = 1;
            this.resolveFolderDropTarget(event);
            this.trackDropHint(event);
        },

        /**
         * Keep the hint that names the drop target under the cursor, so it is
         * read where the user is looking instead of in the middle of the finder.
         */
        trackDropHint(event) {
            const area = this.$refs.dropArea?.getBoundingClientRect();

            if (! area) {
                return;
            }

            this.dragHint = {
                x: event.clientX - area.left,
                y: event.clientY - area.top,
            };
        },

        get dragHintStyle() {
            const hint = this.$refs.dropHint;
            const area = this.$refs.dropArea;

            if (! hint || ! area) {
                return null;
            }

            // Stay inside the drop area, also when dropping near its edges.
            const left = Math.max(12, Math.min(this.dragHint.x + 16, area.clientWidth - hint.offsetWidth - 12));
            const top = Math.max(12, Math.min(this.dragHint.y + 18, area.clientHeight - hint.offsetHeight - 12));

            return `left: ${left}px; top: ${top}px;`;
        },

        // Folder targets (cards, breadcrumbs) carry data-drop-folder attributes,
        // so the drop target is resolved from the element under the pointer.
        resolveFolderDropTarget(event) {
            const target = event.target?.closest?.('[data-drop-folder]');

            this.draggingOverFolder = target?.dataset.dropFolder ?? null;
            this.draggingOverFolderName = target?.dataset.dropFolderName ?? null;

            return this.draggingOverFolder;
        },

        clearFolderDropTarget() {
            this.draggingOverFolder = null;
            this.draggingOverFolderName = null;
        },

        /**
         * Escape: drop the drag state and abort the uploads it may have started.
         */
        cancelDrop() {
            const wasDragging = this.draggingVirtualFile || this.draggingFiles > 0 || !! this.draggingOverFolder;
            const wasUploading = this.uploadsRunning || this.uploadQueue.length > 0;

            this.draggingFiles = 0;
            this.draggingVirtualFile = false;
            this.clearFolderDropTarget();

            if (! wasUploading) {
                return wasDragging;
            }

            this.cancelAllUploads();

            return true;
        },

        destroy() {
            document.body.style.overflow = this.previousBodyOverflow;
        },

        setViewMode(mode) {
            this.viewMode = mode;
        },

        // Modifier keys that are currently held down. A click event does not
        // always carry them (automation, synthetic events), so the key state is
        // tracked as well.
        shiftKeyHeld: false,
        selectionHandledOnPress: null,

        trackModifiers(event) {
            this.shiftKeyHeld = event.shiftKey;
        },

        toggleFileSelection(file) {
            const isSelected = this.isFileSelected(file);

            if (isSelected) {
                this.selectedFiles = this.selectedFiles.filter(f => f.id !== file.id || f.source !== file.source);
            } else {
                this.selectedFiles = [...this.selectedFiles, file];
            }
        },

        handleFileClick(file, event) {
            // A modified press already handled the selection (see
            // handleFilePress), the click only follows it up.
            if (this.selectionHandledOnPress === this.fileKey(file)) {
                this.selectionHandledOnPress = null;

                return;
            }

            if (this.selectionEnabled && this.max === 1) {
                this.selectedFiles = this.isFileSelected(file) ? [] : [file];
                this.setSelectionAnchor(file);

                return;
            }

            // SHIFT extends the selection from the anchor to the clicked file
            // (and makes it the new anchor), CMD/CTRL toggles a single file and
            // makes it the anchor, a plain click selects just that file.
            if (event.shiftKey || this.shiftKeyHeld) {
                this.selectRangeTo(file);

                return;
            }
            if (event.metaKey || event.ctrlKey) {
                if (this.selectionEnabled && ! this.canSelectMore && ! this.isFileSelected(file)) {
                    return;
                }

                this.toggleFileSelection(file);
                this.setSelectionAnchor(file);

                return;
            }

            if (this.selectionEnabled) {
                if (! this.canSelectMore && ! this.isFileSelected(file)) {
                    return;
                }

                this.toggleFileSelection(file);
            } else if (this.selectedFiles.length === 1 && this.isFileSelected(file)) {
                this.selectedFiles = [];
            } else {
                this.selectedFiles = [file];
            }

            this.setSelectionAnchor(file);
        },

        fileKey(file) {
            return `${file.source}:${file.id}`;
        },

        /**
         * Anchor of the selection (the last clicked file). Kept outside the
         * component state: a re-render or an incoming request answer would
         * reset it, and the range would fall back to a single file.
         */
        selectionAnchorKey() {
            const key = this.$wire.$id;

            window.cabinetSelectionAnchors ??= {};

            return window.cabinetSelectionAnchors[key] ?? null;
        },

        setSelectionAnchor(file) {
            const key = this.$wire.$id;

            window.cabinetSelectionAnchors ??= {};
            window.cabinetSelectionAnchors[key] = this.fileKey(file);
        },

        /**
         * SHIFT and CMD/CTRL are applied on press, not on click: a click does
         * not reliably carry the modifier (and shift+drag or a text selection
         * can swallow it entirely). The plain selection stays on click.
         */
        handleFilePress(file, event) {
            if (event.button !== 0) {
                return;
            }

            const shift = event.shiftKey || this.shiftKeyHeld;
            const toggle = event.metaKey || event.ctrlKey;

            this.selectionHandledOnPress = shift || toggle ? this.fileKey(file) : null;

            if (! shift && ! toggle) {
                return;
            }

            event.preventDefault();

            if (this.selectionEnabled && this.max === 1) {
                this.selectedFiles = this.isFileSelected(file) ? [] : [file];
                this.setSelectionAnchor(file);

                return;
            }

            if (shift) {
                this.selectRangeTo(file);

                return;
            }

            if (this.selectionEnabled && ! this.canSelectMore && ! this.isFileSelected(file)) {
                return;
            }

            this.toggleFileSelection(file);
            this.setSelectionAnchor(file);
        },

        /**
         * The files in the order they are displayed. A range selection has to
         * follow the order the user sees, and the rendered items carry their
         * identifier.
         */
        displayedFiles() {
            const root = this.finderRoot ?? this.$el;

            return [...root.querySelectorAll('[data-file-select]')]
                .filter(el => el.checkVisibility ? el.checkVisibility() : el.getClientRects().length > 0)
                .map(el => this.parseFileKey(el.dataset.fileSelect))
                .filter(file => file !== null);
        },

        parseFileKey(key) {
            try {
                const file = JSON.parse(key ?? '');

                // The whole identifier: the click handlers pass the same shape,
                // and the detail panel needs name and type for its list.
                return file?.source && file?.id ? file : null;
            } catch {
                return null;
            }
        },

        /**
         * SHIFT+click: select everything between the anchor (the last clicked
         * file) and the clicked file, and make the clicked file the new anchor.
         *
         * This stays on the client: the rendered items are the displayed order,
         * and a roundtrip would show the range only after the request answered.
         */
        selectRangeTo(file) {
            const anchor = this.selectionAnchorKey();

            this.setSelectionAnchor(file);

            const files = this.displayedFiles();
            const from = files.findIndex(displayed => this.fileKey(displayed) === anchor);
            const to = files.findIndex(displayed => this.fileKey(displayed) === this.fileKey(file));

            // No anchor, or it is not displayed anymore (another folder, a
            // search): the click is a normal one.
            if (! anchor || from === -1 || to === -1) {
                this.selectedFiles = [file];

                return;
            }

            const [start, end] = from <= to ? [from, to] : [to, from];
            const range = files.slice(start, end + 1);

            // SHIFT adds to the selection instead of replacing it: a range that
            // was picked before stays selected.
            const keys = new Set(this.selectedFiles.map(selected => this.fileKey(selected)));
            const merged = [...this.selectedFiles, ...range.filter(file => ! keys.has(this.fileKey(file)))];

            this.selectedFiles = this.max === null ? merged : merged.slice(0, this.max);
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

        /**
         * Handle a drop of files from outside the browser.
         *
         * @param {string|null} targetFolderId folder the files were dropped on,
         *        null uploads into the folder that is currently open
         */
		uploadDroppedFile(event, targetFolderId = null) {
			event.preventDefault();
			event.stopPropagation();

			this.draggingFiles = 0;
			this.clearFolderDropTarget();

			// Read the files right away: a FileList is only valid during the event.
			const files = Array.from(event.dataTransfer.files);

			if (files.length === 0) {
				return;
			}

			this.queueUpload(files, targetFolderId, this.draggingOverFolderName);
		},

		/**
		 * Add a drop to the upload queue.
		 *
		 * The queue (and the popup) live here, in the finder, so that browsing and
		 * progress keep working; the chunks themselves are handed to the uploader
		 * component, whose Livewire requests are independent of the finder's.
		 *
		 * @param {string|null} targetFolderId folder the files were dropped on,
		 *        null uploads into the folder that is currently open
		 */
		queueUpload(files, targetFolderId = null, folderName = null) {
			const drop = {
				id: Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15),
				folderId: targetFolderId ?? this.currentFolderId,
				folderName: folderName ?? this.folderName,
				files: files.map(file => file.name),
				progress: 0.0,
				error: null,
				state: 'queued',
			};

			this.uploadFiles()[drop.id] = files;
			this.uploadQueue = [...this.uploadQueue, drop];

			this.watchUploadedFiles();
			this.runUploadQueue();
		},

		/**
		 * Upload one drop at a time: the target folder and the batch counters are
		 * announced per component, not per request, so two drops uploading at the
		 * same time would end up in each other's folder.
		 */
		async runUploadQueue() {
			if (this.uploadsRunning) {
				return;
			}

			// Runs are numbered so a cancelled run cannot pick up the drops of a
			// later one when its upload promise finally resolves.
			const run = ++this.uploadRun;

			this.uploadsRunning = true;
			this.uploadCancelled = false;

			while (this.uploadQueue.some(drop => drop.state === 'queued') && ! this.uploadCancelled && run === this.uploadRun) {
				const drop = this.uploadQueue.find(candidate => candidate.state === 'queued');

				this.patchUpload(drop.id, { state: 'uploading' });

				await this.uploadDrop(drop.id);
			}

			if (run === this.uploadRun) {
				this.uploadsRunning = false;
			}
		},

		async uploadDrop(dropId) {
			const files = this.uploadFiles()[dropId] ?? [];
			const drop = this.uploadQueue.find(candidate => candidate.id === dropId);

			if (! drop || files.length === 0) {
				this.removeUpload(dropId);
				return;
			}

			const chunkSize = Math.max(1, Number(this.uploadChunkSize) || 1);
			const chunks = [];

			for (let i = 0; i < files.length; i += chunkSize) {
				chunks.push(files.slice(i, i + chunkSize));
			}

			// Announced to the server with the upload requests (writing to $wire
			// sends the value with the next request): where the drop goes, how much
			// of it is left, and the running total for the notification.
			const uploader = this.$wire;

			uploader.uploadTargetFolderId = drop.folderId ?? null;
			uploader.uploadBatchId = drop.id;
			uploader.uploadedFilesInBatch = 0;

			let remaining = files.length;
			let uploaded = 0;

			for (const chunk of chunks) {
				if (this.uploadCancelled || ! this.uploadFiles()[dropId]) {
					return;
				}

				remaining -= chunk.length;
				uploader.uploadFilesRemaining = remaining;

				const failed = await this.uploadChunk(dropId, chunk, uploaded, files.length);

				if (failed) {
					this.patchUpload(dropId, { error: true, state: 'failed' });
					return;
				}

				uploaded += chunk.length;
			}

			this.removeUpload(dropId);
		},

		uploadChunk(dropId, chunk, uploaded, total) {
			return new Promise((resolve) => {
				this.$wire.uploadMultiple(
					'uploadedFiles',
					chunk,
					() => resolve(false),
					(error) => {
						this.patchUpload(dropId, { error: error || true });
						resolve(true);
					},
					(event) => {
						this.patchUpload(dropId, { progress: (uploaded + (event.detail.progress / 100) * chunk.length) / total });
					},
					() => resolve(false),
				);
			});
		},

		patchUpload(dropId, changes) {
			this.uploadQueue = this.uploadQueue.map(drop => drop.id === dropId ? { ...drop, ...changes } : drop);
		},

		removeUpload(dropId) {
			delete this.uploadFiles()[dropId];
			this.uploadQueue = this.uploadQueue.filter(drop => drop.id !== dropId);
		},

        /**
         * Files are stored in the background, so while a drop is being stored
         * the open folder is refreshed so the files pop in by themselves.
         */
        watchUploadedFiles() {
            clearInterval(this.uploadRefreshInterval);

            this.uploadRefreshUntil = Date.now() + 20000;

            this.uploadRefreshInterval = setInterval(() => {
                if (this.uploadQueue.length > 0) {
                    this.uploadRefreshUntil = Date.now() + 20000;
                }

                if (Date.now() > this.uploadRefreshUntil) {
                    clearInterval(this.uploadRefreshInterval);

                    return;
                }

                this.$wire.refresh();
            }, 2000);
        },

		/** Cancel one drop, whether it is running or still waiting. */
		cancelUpload(dropId) {
			this.$wire.abandonUploads(dropId);

			if (this.uploadQueue.find(candidate => candidate.id === dropId)?.state === 'uploading') {
				this.abortUploads();
			}

			this.removeUpload(dropId);
		},

		cancelAllUploads() {
			this.uploadQueue.forEach(drop => this.$wire.abandonUploads(drop.id));

			this.abortUploads();

			this.uploadFiles(true);
			this.uploadQueue = [];
		},


		/**
		 * Stop the running queue. The run counter makes sure a late upload promise
		 * cannot restart it with the drops of a newer run.
		 */
		abortUploads() {
			this.uploadRun++;
			this.uploadsRunning = false;
			this.uploadCancelled = true;

			this.$wire.cancelUpload('uploadedFiles');

			setTimeout(() => {
				this.uploadCancelled = false;
				this.runUploadQueue();
			}, 0);
		},

		/** Uploads into the folder that is currently open. */
		gridUploads() {
			return this.queuedFiles(this.currentFolderId);
		},

		queuedFiles(folderId) {
			return this.uploadQueue
				.filter(drop => (drop.folderId ?? null) === (folderId ?? null))
				.flatMap(drop => drop.files.map(name => ({ ...drop, id: drop.id + name, name })));
		},

		/**
		 * Progress of the uploads going into a folder card or breadcrumb, so they
		 * report themselves there instead of in the open folder's grid.
		 * Returns null when nothing is being uploaded into that folder.
		 */
		folderUploadState(folderId) {
			const files = this.queuedFiles(folderId);

			if (files.length === 0) {
				return null;
			}

			return {
				count: files.length,
				progress: files.reduce((total, file) => total + (file.error ? 0 : file.progress), 0) / files.length,
				error: files.find(file => file.error)?.error ?? null,
			};
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
        'relative border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 overflow-hidden flex flex-col flex-1',
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
                            class="w-48"
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
                            @else
                                <x-filament::input
                                    type="text"
                                    x-model.debounce.100ms="searchQuery"
                                    :placeholder="__('cabinet::messages.search-files')"
                                />
                            @endif
                        </x-filament::input.wrapper>
                    @endif

                    {{-- View mode toggle (always shown) --}}
                    <div class="flex items-center rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <button
                            type="button"
                            @click="setViewMode('grid')"
                            :class="viewMode === 'grid' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                            class="flex h-[34px] w-8 items-center justify-center transition-colors"
                            title="{{ __('cabinet::actions.view-grid') }}"
                        >
                            @svg('heroicon-o-squares-2x2', 'w-4 h-4')
                        </button>
                        <button
                            type="button"
                            @click="setViewMode('list')"
                            :class="viewMode === 'list' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                            class="flex h-[34px] w-8 items-center justify-center transition-colors"
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
                    x-ref="dropArea"
                    class="relative flex-1 overflow-y-auto transition-colors flex flex-col"
                    :class="draggingFiles > 0 && ! draggingOverFolder ? 'bg-primary-50/60 dark:bg-primary-950/60' : ''"
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

                    <template x-if="!draggingVirtualFile && (draggingFiles > 0 || draggingOverFolder)">

                        <div
                            class="z-10 absolute inset-0 pointer-events-none"
                            x-show="!draggingVirtualFile && (draggingFiles > 0 || draggingOverFolder)"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                        >
                            {{-- Light up the drop area without hiding the files behind it. --}}
                            <div
                                class="absolute inset-2 rounded-xl border-2 border-dashed border-primary-400 dark:border-primary-400 transition-opacity duration-150"
                                :class="draggingFiles > 0 && ! draggingOverFolder ? 'opacity-100' : 'opacity-0'"
                            ></div>

                            {{-- Names the folder the files would land in, right under the cursor. --}}
                            <div
                                x-ref="dropHint"
                                class="absolute flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-md px-2.5 py-1.5"
                                :style="dragHintStyle"
                            >
                                @svg('heroicon-o-cloud-arrow-up', 'w-5 h-5 text-primary-500 flex-shrink-0')
                                <div class="flex flex-col leading-tight">
                                    <span
                                        class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                        x-text="draggingOverFolderName || @js($folder?->name ?? '')"
                                    ></span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('cabinet::messages.drop-files-to-upload') }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
    {{-- Upload queue: keeps running while the finder is used. --}}
    <template x-if="uploadQueue.length > 0">
        <div
            x-ref="uploadQueue"
            class="pointer-events-auto fixed bottom-4 right-4 z-30 w-72 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
            <header class="flex items-center justify-between gap-2 border-b border-gray-100 px-3 py-2 dark:border-gray-700/60">
                <p class="min-w-0 truncate text-xs font-semibold text-gray-800 dark:text-gray-100">
                    <span>{{ __('cabinet::messages.upload-queue') }}</span>
                    <span
                        class="font-normal text-gray-500 dark:text-gray-400"
                        x-text="'(' + uploadQueue.reduce((total, drop) => total + drop.files.length, 0) + ' ' + @js(__('cabinet::files.files')) + ')'"
                    ></span>
                </p>

                <button
                    type="button"
                    x-on:click="cancelAllUploads()"
                    :aria-label="@js(__('cabinet::messages.cancel-all-uploads'))"
                    class="flex-shrink-0 rounded-md p-0.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                >
                    @svg('heroicon-o-x-mark', 'w-3.5 h-3.5')
                </button>
            </header>

            <ul class="max-h-64 divide-y divide-gray-100 overflow-y-auto dark:divide-gray-700/60">
                <template x-for="drop in uploadQueue" :key="drop.id">
                    <li class="px-3 py-2">
                        <div class="flex items-center gap-2">
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-xs font-medium text-gray-800 dark:text-gray-100"
                                    x-text="drop.folderName || @js($folder?->name ?? '')"
                                ></p>
                                <p
                                    class="truncate text-[11px]"
                                    :class="drop.error ? 'text-danger-600 dark:text-danger-400' : 'text-gray-500 dark:text-gray-400'"
                                    x-text="drop.error
                                        ? @js(__('cabinet::messages.unknown-error'))
                                        : drop.files.length === 1
                                            ? drop.files[0]
                                            : drop.files.length + ' ' + @js(__('cabinet::files.files'))"
                                ></p>
                            </div>

                            <span
                                class="flex-shrink-0 text-[11px] tabular-nums"
                                :class="drop.error ? 'text-danger-600 dark:text-danger-400' : 'text-gray-500 dark:text-gray-400'"
                                x-text="drop.error ? '' : Math.min(99, Math.round(drop.progress * 100)) + '%'"
                            ></span>

                            <button
                                type="button"
                                x-on:click="cancelUpload(drop.id)"
                                :aria-label="@js(__('cabinet::messages.cancel-upload'))"
                                class="flex-shrink-0 rounded p-0.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                            >
                                @svg('heroicon-o-x-mark', 'w-3.5 h-3.5')
                            </button>
                        </div>

                        <div class="mt-1.5 h-1 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div
                                class="h-full rounded-full transition-all duration-200"
                                :class="drop.error ? 'bg-danger-500' : 'bg-primary-500'"
                                :style="'width: ' + Math.round(drop.progress * 100) + '%'"
                            ></div>
                        </div>
                    </li>
                </template>
            </ul>
        </div>
    </template>
                </main>

                {{-- Detail sidebar — browse mode only, shown when a file is clicked --}}
                @if (!$selectionMode)
                    <x-cabinet-filament::finder.detail-panel
                        :$files
                        :thumbnail-urls="$thumbnailUrls"
                        :file-urls="$fileUrls"
                        :preview-urls="$previewUrls"
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
