@props([
    'files' => [],
    'thumbnailUrls' => [],
    'fileUrls' => [],
    'previewUrls' => [],
])

{{--
    Detail panel — slides in from the right when a file is selected in browse mode.
    `selectedFiles` Alpine variable (on the parent article) drives this panel.

    We build a JS-accessible fileMap from all files in the current folder so metadata
    is available without a Livewire round-trip.
--}}

@php
    $fileMap = collect($files)
        ->filter(fn ($f) => $f instanceof \Cabinet\File)
        ->mapWithKeys(fn (\Cabinet\File $f) => [
            $f->source . ':' . $f->id => [
                'id'         => $f->id,
                'source'     => $f->source,
                'name'       => $f->name,
                'type'       => $f->type->name(),
                'typeSlug'   => $f->type->slug(),
                'icon'       => $f->icon ?? $f->type->icon(),
                'size'       => method_exists($f, 'humanSize') ? $f->humanSize() : null,
                'url'        => $f->url(),
                'previewUrl' => $f->previewUrl,
                'path'       => method_exists($f, 'path') ? $f->path() : null,
                'mimeType'   => method_exists($f, 'formattedMimeType') ? $f->formattedMimeType() : null,
                'createdAt'  => $f->createdAt ? $f->createdAt->translatedFormat(config('cabinet.date_format', 'd. F Y')) : null,
            ]
        ]);
@endphp

<aside
    {{ $attributes }}
    x-show="selectedFiles.length > 0"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-x-4"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-4"
    class="w-72 flex-shrink-0 border-l-2 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex flex-col overflow-y-auto"
    x-cloak
    x-data="{
        fileMap: @js($fileMap),

        get currentFile() {
            if (selectedFiles.length === 0) return null;
            return this.fileMap[selectedFiles[0].source + ':' + selectedFiles[0].id] ?? null;
        },

        get isMultiSelect() {
            return selectedFiles.length > 1;
        },

        mountAction(name, args = {}) {
            $wire.call('mountAction', name, args);
        },

        thumbnailUrls: @js($thumbnailUrls),
        fileUrls: @js($fileUrls),
        previewUrls: @js($previewUrls),

        makeThumbnailUrl(file) {
            return this.thumbnailUrls[file.source + ':' + file.id]?.normal ?? null;
        },

        makeTinyThumbnailUrl(file) {
            return this.thumbnailUrls[file.source + ':' + file.id]?.tiny ?? null;
        },

        makeFileUrl(file) {
            return this.fileUrls[file.source + ':' + file.id] ?? null;
        },

        makePreviewUrl(file) {
            return this.previewUrls[file.source + ':' + file.id] ?? null;
        },

        getBulkFile(fileId) {
            const parts = fileId.split(':');
            return this.fileMap[parts[0] + ':' + parts[1]] ?? null;
        }
    }"
>
    {{-- SINGLE FILE MODE --}}
    <template x-if="!isMultiSelect">
        <div class="flex flex-col flex-1 min-h-0">
            {{-- Panel header --}}
            <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 flex-shrink-0">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 truncate min-w-0" x-text="currentFile?.name ?? ''">&nbsp;</h3>
                <button
                    type="button"
                    @click="selectedFiles = []"
                    class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex-shrink-0 ml-2"
                    title="{{ __('cabinet::actions.close-details') }}"
                >
                    @svg('heroicon-o-x-mark', 'w-4 h-4 text-gray-500')
                </button>
            </div>

            <template x-if="currentFile !== null">
                <div class="flex flex-col flex-1 min-h-0">

                    {{-- Preview area --}}
                    <div
                        class="bg-gray-100 dark:bg-gray-800 flex items-center justify-center overflow-hidden flex-shrink-0 relative group cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                        style="min-height: 160px; max-height: 220px;"
                        @click="mountAction('previewFile', selectedFiles[0])"
                        title="{{ __('cabinet::actions.preview') }}"
                    >
                        {{-- Click overlay for video/iframe elements that capture pointer events --}}
                        <div
                            class="absolute inset-0 z-10"
                            :class="{
                                'hidden': currentFile.typeSlug !== 'video' && currentFile.typeSlug !== 'pdf'
                            }"
                        ></div>

                        {{-- Image --}}
                        <template x-if="currentFile.typeSlug === 'image' && currentFile.previewUrl">
                            <img
                                :src="makeThumbnailUrl(currentFile) ?? currentFile.previewUrl"
                                :alt="currentFile.name"
                                class="max-w-full max-h-56 object-contain"
                                loading="lazy"
                            />
                        </template>

                        {{-- Video --}}
                        <template x-if="currentFile.typeSlug === 'video' && currentFile.url">
                            <video
                                :src="makeFileUrl(currentFile) ?? currentFile.url"
                                controls
                                class="max-w-full"
                                style="max-height: 200px;"
                            ></video>
                        </template>

                        {{-- PDF --}}
                        <template x-if="currentFile.typeSlug === 'pdf' && currentFile.previewUrl">
                            <img
                                :src="makeThumbnailUrl(currentFile) ?? currentFile.previewUrl"
                                :alt="currentFile.name"
                                class="max-w-full max-h-56 object-contain"
                                loading="lazy"
                            />
                        </template>

                        {{-- Generic icon fallback --}}
                        <template x-if="currentFile.typeSlug !== 'image' && currentFile.typeSlug !== 'video' && currentFile.typeSlug !== 'pdf'">
                            <div class="flex flex-col items-center justify-center py-8 text-gray-400 dark:text-gray-600 gap-2">
                                @svg('heroicon-o-document', 'w-16 h-16')
                                <span class="text-xs" x-text="currentFile.type"></span>
                            </div>
                        </template>

                        {{-- Hover overlay hint --}}
                        <div class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover:bg-black/10 transition-colors pointer-events-none z-20">
                            @svg('heroicon-o-eye', 'w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity drop-shadow-md')
                        </div>
                    </div>

                    {{-- Action buttons strip --}}
                    <div class="flex items-center justify-center gap-0.5 px-3 py-2 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 flex-shrink-0">
                        <button
                            type="button"
                            @click="mountAction('previewFile', selectedFiles[0])"
                            class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
                            title="{{ __('cabinet::actions.preview') }}"
                        >
                            @svg('heroicon-o-eye', 'w-4 h-4')
                        </button>
                        <button
                            type="button"
                            @click="mountAction('downloadFile', selectedFiles[0])"
                            class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
                            title="{{ __('cabinet::actions.download') }}"
                        >
                            @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                        </button>
                        <button
                            type="button"
                            @click="mountAction('shareFile', selectedFiles[0])"
                            class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
                            title="{{ __('cabinet::actions.share') }}"
                        >
                            @svg('heroicon-o-link', 'w-4 h-4')
                        </button>
                        <button
                            type="button"
                            @click="mountAction('rename', selectedFiles[0])"
                            class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
                            title="{{ __('cabinet::actions.rename') }}"
                        >
                            @svg('heroicon-o-pencil', 'w-4 h-4')
                        </button>
                        <button
                            type="button"
                            @click="mountAction('refreshFile', selectedFiles[0])"
                            class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
                            title="{{ __('cabinet::actions.refresh-file') }}"
                        >
                            @svg('heroicon-o-arrow-path', 'w-4 h-4')
                        </button>
                        <div class="flex-1"></div>
                        <button
                            type="button"
                            @click="mountAction('delete', selectedFiles[0])"
                            class="p-1.5 rounded hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                            title="{{ __('cabinet::actions.delete') }}"
                        >
                            @svg('heroicon-o-trash', 'w-4 h-4')
                        </button>
                    </div>

                    {{-- Metadata --}}
                    <div class="px-3 py-3 space-y-1.5 border-b border-gray-200 dark:border-gray-800 flex-shrink-0">
                        <dl class="space-y-1.5">
                            <div class="flex justify-between gap-2 items-baseline">
                                <dt class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">{{ __('cabinet::messages.file-type') }}</dt>
                                <dd class="text-xs text-gray-900 dark:text-gray-100 font-medium text-right truncate" x-text="currentFile?.type ?? '—'"></dd>
                            </div>
                            <template x-if="currentFile?.mimeType">
                                <div class="flex justify-between gap-2 items-baseline">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">MIME</dt>
                                    <dd class="text-xs text-gray-700 dark:text-gray-300 font-mono text-right truncate" x-text="currentFile.mimeType"></dd>
                                </div>
                            </template>
                            <template x-if="currentFile?.size">
                                <div class="flex justify-between gap-2 items-baseline">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">{{ __('cabinet::messages.file-size') }}</dt>
                                    <dd class="text-xs text-gray-900 dark:text-gray-100 font-medium text-right" x-text="currentFile.size"></dd>
                                </div>
                            </template>
                            <template x-if="currentFile?.createdAt">
                                <div class="flex justify-between gap-2 items-baseline">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">{{ __('cabinet::messages.file-created') }}</dt>
                                    <dd class="text-xs text-gray-900 dark:text-gray-100 font-medium text-right" x-text="currentFile.createdAt"></dd>
                                </div>
                            </template>
                            <div class="flex justify-between gap-2 items-baseline">
                                <dt class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">{{ __('cabinet::messages.file-source') }}</dt>
                                <dd class="text-xs text-gray-700 dark:text-gray-300 font-mono text-right truncate" x-text="currentFile?.source ?? '—'"></dd>
                            </div>
                            <template x-if="currentFile?.path">
                                <div class="flex flex-col gap-0.5">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('cabinet::messages.file-path') }}</dt>
                                    <dd class="text-xs text-gray-700 dark:text-gray-300 font-mono break-all leading-relaxed" x-text="currentFile.path"></dd>
                                </div>
                            </template>
                        </dl>
                    </div>

                    {{-- File References section --}}
                    <x-cabinet-filament::finder.file-references />

                </div>
            </template>
        </div>
    </template>

    {{-- BULK SELECTION MODE --}}
    <template x-if="isMultiSelect">
        <div class="flex flex-col flex-1 min-h-0">
            {{-- Panel header --}}
            <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 flex-shrink-0">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    <span x-text="selectedFiles.length"></span> {{ __('cabinet::messages.selected') }}
                </h3>
                <button
                    type="button"
                    @click="selectedFiles = []"
                    class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex-shrink-0 ml-2"
                    title="{{ __('cabinet::actions.deselect-all') }}"
                >
                    @svg('heroicon-o-x-mark', 'w-4 h-4 text-gray-500')
                </button>
            </div>

            {{-- Bulk action buttons --}}
            <div class="flex items-center justify-center gap-0.5 px-3 py-2 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 flex-shrink-0">
                <button
                    type="button"
                    @click="mountAction('downloadBulk', { files: selectedFiles })"
                    class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
                    title="{{ __('cabinet::actions.download-bulk') }}"
                >
                    @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                </button>
                <div class="flex-1"></div>
                <button
                    type="button"
                    @click="mountAction('deleteBulk', { files: selectedFiles })"
                    class="p-1.5 rounded hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                    title="{{ __('cabinet::actions.delete-bulk') }}"
                >
                    @svg('heroicon-o-trash', 'w-4 h-4')
                </button>
            </div>

            {{-- Selected files list --}}
            <div class="flex-1 overflow-y-auto">
                <template x-for="selectedFile in selectedFiles" :key="selectedFile.source + ':' + selectedFile.id">
                    <div class="flex items-center gap-2 px-3 py-2 border-b border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="w-8 h-8 rounded overflow-hidden flex items-center justify-center bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                        <template x-if="getBulkFile(selectedFile.source + ':' + selectedFile.id)?.previewUrl && ['image', 'video', 'pdf'].includes(getBulkFile(selectedFile.source + ':' + selectedFile.id)?.typeSlug)">
                            <img
                                :src="makeTinyThumbnailUrl(getBulkFile(selectedFile.source + ':' + selectedFile.id)) ?? getBulkFile(selectedFile.source + ':' + selectedFile.id).previewUrl"
                                class="w-full h-full object-cover object-center"
                                loading="lazy"
                            />
                        </template>
                            <template x-if="!getBulkFile(selectedFile.source + ':' + selectedFile.id)?.previewUrl || !['image', 'video', 'pdf'].includes(getBulkFile(selectedFile.source + ':' + selectedFile.id)?.typeSlug)">
                                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0h5.25m-5.25 0v2.25m2.25-2.25v2.25m0 13.5h5.25m-5.25 0v2.25m2.25-2.25v2.25m0 13.5h5.25m-5.25 0v2.25m2.25-2.25v2.25"/>
                                </svg>
                            </template>
                        </div>
                        <p class="text-xs text-gray-700 dark:text-gray-300 truncate flex-1" x-text="selectedFile.name"></p>
                        <button
                            type="button"
                            @click="toggleFileSelection(selectedFile)"
                            class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex-shrink-0"
                            title="{{ __('cabinet::actions.deselect') }}"
                        >
                            @svg('heroicon-o-x-mark', 'w-3 h-3 text-gray-500')
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </template>
</aside>
