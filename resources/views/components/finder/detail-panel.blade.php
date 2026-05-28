@props([
    'files' => [],
])

{{--
    Detail panel — slides in from the right when a file is clicked in browse mode.
    `detailFile` Alpine variable (on the parent article) drives this panel.

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
            ]
        ]);
@endphp

<aside
    {{ $attributes }}
    x-show="detailFile !== null && currentFile !== null"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-x-4"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-4"
    class="w-72 flex-shrink-0 border-l-2 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex flex-col overflow-y-auto"
    x-cloak
    @cabinet:folder-opened.window="detailFile = null"
    @cabinet:finder-closed.window="detailFile = null"
    x-data="{
        fileMap: @js($fileMap),

        get currentFile() {
            if (!detailFile) return null;
            return this.fileMap[detailFile.source + ':' + detailFile.id] ?? null;
        },

        mountAction(name) {
            if (!detailFile) return;
            $wire.call('mountAction', name, detailFile);
        }
    }"
>
    {{-- Panel header --}}
    <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 flex-shrink-0">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 truncate min-w-0" x-text="currentFile?.name ?? ''">&nbsp;</h3>
        <button
            type="button"
            @click="detailFile = null"
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
                class="bg-gray-100 dark:bg-gray-800 flex items-center justify-center overflow-hidden flex-shrink-0"
                style="min-height: 160px; max-height: 220px;"
            >
                {{-- Image --}}
                <template x-if="currentFile.typeSlug === 'image' && currentFile.previewUrl">
                    <img
                        :src="currentFile.previewUrl"
                        :alt="currentFile.name"
                        class="max-w-full max-h-56 object-contain"
                        loading="lazy"
                    />
                </template>

                {{-- Video --}}
                <template x-if="currentFile.typeSlug === 'video' && currentFile.url">
                    <video
                        :src="currentFile.url"
                        controls
                        class="max-w-full"
                        style="max-height: 200px;"
                    ></video>
                </template>

                {{-- PDF --}}
                <template x-if="currentFile.typeSlug === 'pdf' && currentFile.url">
                    <iframe
                        :src="currentFile.url"
                        class="w-full border-none"
                        style="height: 200px;"
                    ></iframe>
                </template>

                {{-- Generic icon fallback --}}
                <template x-if="currentFile.typeSlug !== 'image' && currentFile.typeSlug !== 'video' && currentFile.typeSlug !== 'pdf'">
                    <div class="flex flex-col items-center justify-center py-8 text-gray-400 dark:text-gray-600 gap-2">
                        @svg('heroicon-o-document', 'w-16 h-16')
                        <span class="text-xs" x-text="currentFile.type"></span>
                    </div>
                </template>
            </div>

            {{-- Action buttons strip --}}
            <div class="flex items-center justify-center gap-0.5 px-3 py-2 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 flex-shrink-0">
                <button
                    type="button"
                    @click="mountAction('previewFile')"
                    class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
                    title="{{ __('cabinet::actions.preview') }}"
                >
                    @svg('heroicon-o-eye', 'w-4 h-4')
                </button>
                <button
                    type="button"
                    @click="mountAction('downloadFile')"
                    class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
                    title="{{ __('cabinet::actions.download') }}"
                >
                    @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                </button>
                <button
                    type="button"
                    @click="mountAction('shareFile')"
                    class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
                    title="{{ __('cabinet::actions.share') }}"
                >
                    @svg('heroicon-o-link', 'w-4 h-4')
                </button>
                <button
                    type="button"
                    @click="mountAction('rename')"
                    class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
                    title="{{ __('cabinet::actions.rename') }}"
                >
                    @svg('heroicon-o-pencil', 'w-4 h-4')
                </button>
                <button
                    type="button"
                    @click="mountAction('refreshFile')"
                    class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
                    title="{{ __('cabinet::actions.refresh-file') }}"
                >
                    @svg('heroicon-o-arrow-path', 'w-4 h-4')
                </button>
                <div class="flex-1"></div>
                <button
                    type="button"
                    @click="mountAction('delete')"
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
</aside>
