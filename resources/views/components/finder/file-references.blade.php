{{--
    File References Panel
    ─────────────────────
    Shows where the currently selected file is referenced in the application.

    Cabinet resolves its own file-references. Host applications can describe
    how their models are shown here (label, type label, icon, url and per-usage
    labels) via `Cabinet::describeReferences()` — see the References service.
    References of models without a descriptor fall back to the simpler list at
    the bottom.

    The Livewire `loadFileReferences` method returns an array of references:
    [
        [
            'label'        => 'Post: Hello World',
            'url'          => '/admin/posts/1',
            'icon'         => 'heroicon-o-document-text',
            'iconHtml'     => '<svg …>',        // pre-rendered icon markup
            'typeLabel'    => 'Post',           // what kind of model it is
            'usageLabel'   => 'Header image',   // what the file is used as
            'thumbnailUrl' => null,
            'isDescribed'  => true,             // false: default UI
        ],
        ...
    ]
--}}

<div
    class="px-3 py-3 flex-1"
    x-data="{
        references: null,
        loading: false,

        loadReferences() {
            if (selectedFiles.length !== 1 || !selectedFiles[0]) {
                this.references = null;
                return;
            }

            this.loading = true;
            this.references = null;

            const promise = $wire.loadFileReferences(selectedFiles[0].source, selectedFiles[0].id);

            if (!promise || typeof promise.then !== 'function') {
                this.loading = false;
                this.references = [];
                return;
            }

            promise
                .then((refs) => {
                    this.references = refs ?? [];
                })
                .catch(() => {
                    this.references = [];
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        /* References with a descriptor, grouped by what kind of model they belong to. */
        get describedGroups() {
            const groups = {};

            (this.references ?? [])
                .filter((ref) => ref.isDescribed)
                .forEach((ref) => {
                    const key = ref.typeLabel ?? '';

                    groups[key] = [...(groups[key] ?? []), ref];
                });

            return Object.entries(groups).map(([label, refs]) => ({ label, refs }));
        },

        /* Everything the host application has not described: keep it plain. */
        get plainReferences() {
            return (this.references ?? []).filter((ref) => ! ref.isDescribed);
        },

        /* The lines under the label: what it is used as, falling back to the model type. */
        referenceSubtitle(ref) {
            return [ref.usageLabel, ref.isDescribed ? null : ref.typeLabel]
                .filter(Boolean)
                .join(' · ');
        }
    }"
    x-init="$watch('selectedFiles', () => loadReferences())"
>
    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
        {{ __('cabinet::messages.file-references') }}
    </h4>

    {{-- Loading state --}}
    <div x-show="loading" class="flex items-center gap-2 text-xs text-gray-400 py-2">
        <x-filament::loading-indicator class="w-4 h-4" />
        <span>{{ __('cabinet::messages.loading-references') }}</span>
    </div>

    <template x-if="!loading && references !== null && references.length > 0">
        <div class="flex flex-col gap-3">
            {{-- Described references, grouped per model type --}}
            <template x-for="group in describedGroups" :key="group.label">
                <div x-show="group.refs.length > 0" class="flex flex-col gap-1">
                    <p
                        class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                        x-text="group.label"
                    ></p>

                    <ul class="space-y-1">
                        <template x-for="ref in group.refs" :key="ref.label + (ref.usage ?? '')">
                            <li>
                                <template x-if="ref.url">
                                    <a
                                        :href="ref.url"
                                        target="_blank"
                                        class="flex items-start gap-2 rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1.5 hover:border-primary-300 dark:hover:border-primary-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group"
                                    >
                                        <x-cabinet-filament::finder.reference-icon />
                                    </a>
                                </template>

                                <template x-if="!ref.url">
                                    <div class="flex items-start gap-2 rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1.5">
                                        <x-cabinet-filament::finder.reference-icon />
                                    </div>
                                </template>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            {{-- References without a descriptor keep the simple one line look --}}
            <ul x-show="plainReferences.length > 0" class="space-y-1">
                <template x-for="ref in plainReferences" :key="ref.label + (ref.usage ?? '')">
                    <li>
                        <template x-if="ref.url">
                            <a
                                :href="ref.url"
                                target="_blank"
                                class="flex items-center gap-2 text-xs text-primary-600 dark:text-primary-400 hover:underline py-0.5 group"
                            >
                                <template x-if="ref.thumbnailUrl">
                                    <img :src="ref.thumbnailUrl" class="w-5 h-5 rounded object-cover flex-shrink-0" alt="" />
                                </template>
                                <template x-if="!ref.thumbnailUrl">
                                    <span class="w-4 h-4 flex-shrink-0 text-gray-400 group-hover:text-primary-500">
                                        @svg('heroicon-o-arrow-top-right-on-square', 'w-3.5 h-3.5')
                                    </span>
                                </template>
                                <span class="truncate" x-text="ref.label"></span>
                                <span
                                    x-show="referenceSubtitle(ref)"
                                    class="ml-auto text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 flex-shrink-0"
                                    x-text="referenceSubtitle(ref)"
                                ></span>
                            </a>
                        </template>
                        <template x-if="!ref.url">
                            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 py-0.5">
                                <template x-if="ref.thumbnailUrl">
                                    <img :src="ref.thumbnailUrl" class="w-5 h-5 rounded object-cover flex-shrink-0" alt="" />
                                </template>
                                <template x-if="!ref.thumbnailUrl">
                                    <span class="w-4 h-4 flex-shrink-0 text-gray-400">
                                        @svg('heroicon-o-link', 'w-3.5 h-3.5')
                                    </span>
                                </template>
                                <span class="truncate" x-text="ref.label"></span>
                                <span
                                    x-show="referenceSubtitle(ref)"
                                    class="ml-auto text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 flex-shrink-0"
                                    x-text="referenceSubtitle(ref)"
                                ></span>
                            </div>
                        </template>
                    </li>
                </template>
            </ul>
        </div>
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
