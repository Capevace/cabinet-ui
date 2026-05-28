@props([
    'initialFolderId' => null,
    'rootFolderName' => '',
])

{{--
    Directory Tree Sidebar
    ──────────────────────
    A lazily-loaded, Alpine-driven folder tree.

    Architecture:
    - A shared `cabinetTree` Alpine store holds all node state:
        { [folderId]: { id, name, children: string[], loaded: bool, loading: bool, expanded: bool } }
    - The root node is bootstrapped server-side from `initialFolderId`.
    - Expanding a node calls `$wire.getSubfolders(folderId)` lazily.
    - Because Alpine doesn't support recursive x-for templates natively,
      we render up to MAX_DEPTH levels by nesting x-for loops.
      (In practice, file libraries are rarely deeper than 5–6 levels.)
--}}

<div
    x-data="{
        rootId: @js($initialFolderId),
        MAX_DEPTH: 8,

        store: Alpine.store('cabinetTree'),

        async init() {
            if (!this.rootId) return;

            Alpine.store('cabinetTree').ensure(this.rootId, @js($rootFolderName));
            Alpine.store('cabinetTree').setExpanded(this.rootId, true);

            await this.loadChildren(this.rootId);
        },

        async loadChildren(folderId) {
            const s = Alpine.store('cabinetTree');

            if (s.isLoaded(folderId)) return;

            s.setLoading(folderId, true);

            const children = await $wire.call('getSubfolders', folderId);

            children.forEach(child => {
                s.ensure(child.id, child.name);
            });

            s.setChildren(folderId, children.map(c => c.id));
            s.setLoaded(folderId, true);
            s.setLoading(folderId, false);
        },

        async toggle(folderId) {
            const s = Alpine.store('cabinetTree');
            const wasExpanded = s.isExpanded(folderId);

            s.setExpanded(folderId, !wasExpanded);

            if (!wasExpanded) {
                await this.loadChildren(folderId);
            }
        },

        openFolder(folderId) {
            $wire.call('openFolder', folderId);
        }
    }"
    x-init="init()"
>
    {{-- We render depth level by level. 6 levels covers almost all real-world hierarchies. --}}
    <template x-if="rootId">
        <ul class="space-y-0.5 text-sm">

            {{-- LEVEL 0: root --}}
            <template x-for="l0 in [rootId]" :key="l0">
                <li>
                    <x-cabinet-filament::finder.tree-node depth="0" />

                    {{-- LEVEL 1 --}}
                    <template x-if="Alpine.store('cabinetTree').isExpanded(l0)">
                        <ul class="pl-4 space-y-0.5 mt-0.5">
                            <template x-for="l1 in Alpine.store('cabinetTree').getChildren(l0)" :key="l1">
                                <li>
                                    <x-cabinet-filament::finder.tree-node depth="1" />

                                    {{-- LEVEL 2 --}}
                                    <template x-if="Alpine.store('cabinetTree').isExpanded(l1)">
                                        <ul class="pl-4 space-y-0.5 mt-0.5">
                                            <template x-for="l2 in Alpine.store('cabinetTree').getChildren(l1)" :key="l2">
                                                <li>
                                                    <x-cabinet-filament::finder.tree-node depth="2" />

                                                    {{-- LEVEL 3 --}}
                                                    <template x-if="Alpine.store('cabinetTree').isExpanded(l2)">
                                                        <ul class="pl-4 space-y-0.5 mt-0.5">
                                                            <template x-for="l3 in Alpine.store('cabinetTree').getChildren(l2)" :key="l3">
                                                                <li>
                                                                    <x-cabinet-filament::finder.tree-node depth="3" />

                                                                    {{-- LEVEL 4 --}}
                                                                    <template x-if="Alpine.store('cabinetTree').isExpanded(l3)">
                                                                        <ul class="pl-4 space-y-0.5 mt-0.5">
                                                                            <template x-for="l4 in Alpine.store('cabinetTree').getChildren(l3)" :key="l4">
                                                                                <li>
                                                                                    <x-cabinet-filament::finder.tree-node depth="4" />

                                                                                    {{-- LEVEL 5 --}}
                                                                                    <template x-if="Alpine.store('cabinetTree').isExpanded(l4)">
                                                                                        <ul class="pl-4 space-y-0.5 mt-0.5">
                                                                                            <template x-for="l5 in Alpine.store('cabinetTree').getChildren(l4)" :key="l5">
                                                                                                <li>
                                                                                                    <x-cabinet-filament::finder.tree-node depth="5" />
                                                                                                </li>
                                                                                            </template>
                                                                                        </ul>
                                                                                    </template>

                                                                                </li>
                                                                            </template>
                                                                        </ul>
                                                                    </template>

                                                                </li>
                                                            </template>
                                                        </ul>
                                                    </template>

                                                </li>
                                            </template>
                                        </ul>
                                    </template>

                                </li>
                            </template>
                        </ul>
                    </template>

                </li>
            </template>

        </ul>
    </template>
</div>
