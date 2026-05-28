{{--
    File References Panel
    ─────────────────────
    Shows where the currently selected file is referenced in the application.

    Cabinet resolves its own file-references. Host applications can extend this
    by dispatching the `cabinet:detail-panel-file` browser event to inject
    additional context via the Livewire `loadFileReferences` method, or by
    registering a custom Livewire component via the `detailPanelComponents`
    extensibility hook (see `HasDetailPanelComponents` concern).

    The Livewire `loadFileReferences` method returns an array of references:
    [
        ['label' => 'Post: Hello World', 'url' => '/admin/posts/1', 'icon' => 'heroicon-o-document-text'],
        ...
    ]
--}}

<div
    class="px-3 py-3 flex-1"
    x-data="{
        references: null,
        loading: false,

        loadReferences() {
            if (!detailFile) {
                this.references = null;
                return;
            }

            this.loading = true;
            this.references = null;

            $wire.loadFileReferences(detailFile.source, detailFile.id)
                .then((refs) => {
                    this.references = refs ?? [];
                    this.loading = false;
                })
                .catch(() => {
                    this.references = [];
                    this.loading = false;
                });
        }
    }"
    x-init="$watch('detailFile', () => loadReferences())"
>
    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
        {{ __('cabinet::messages.file-references') }}
    </h4>

    {{-- Loading state --}}
    <div x-show="loading" class="flex items-center gap-2 text-xs text-gray-400 py-2">
        <x-filament::loading-indicator class="w-4 h-4" />
        <span>{{ __('cabinet::messages.loading-references') }}</span>
    </div>

    {{-- References list --}}
    <template x-if="!loading && references !== null && references.length > 0">
        <ul class="space-y-1">
            <template x-for="ref in references" :key="ref.label">
                <li>
                    <template x-if="ref.url">
                        <a
                            :href="ref.url"
                            target="_blank"
                            class="flex items-center gap-2 text-xs text-primary-600 dark:text-primary-400 hover:underline py-0.5 group"
                        >
                            <span class="w-4 h-4 flex-shrink-0 text-gray-400 group-hover:text-primary-500">
                                @svg('heroicon-o-arrow-top-right-on-square', 'w-3.5 h-3.5')
                            </span>
                            <span class="truncate" x-text="ref.label"></span>
                        </a>
                    </template>
                    <template x-if="!ref.url">
                        <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 py-0.5">
                            <span class="w-4 h-4 flex-shrink-0 text-gray-400">
                                @svg('heroicon-o-link', 'w-3.5 h-3.5')
                            </span>
                            <span class="truncate" x-text="ref.label"></span>
                        </div>
                    </template>
                </li>
            </template>
        </ul>
    </template>

    {{-- Empty state --}}
    <template x-if="!loading && references !== null && references.length === 0">
        <p class="text-xs text-gray-400 dark:text-gray-500 py-2">
            {{ __('cabinet::messages.no-references') }}
        </p>
    </template>

    {{-- Null state (before first file selected — should not be visible) --}}
    <template x-if="references === null && !loading">
        <p></p>
    </template>
</div>
