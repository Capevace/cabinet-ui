<div class="flex flex-col h-full" wire:key="finder-page">
    <script>
        document.addEventListener('alpine:init', function () {
            // Only register if not already registered (modal may have done it)
            if (!Alpine.store('finderContextMenu')) {
                Alpine.store('finderContextMenu', {
                    visible: false,
                    position: { top: 0, left: 0 },
                    items: [],
                    data: {},
                    open() { this.visible = true; },
                    close() { this.visible = false; this.items = []; this.data = {}; }
                });
            }

            if (!Alpine.store('cabinetTree')) {
                Alpine.store('cabinetTree', {
                    nodes: {},
                    ensure(id, name) {
                        if (!this.nodes[id]) {
                            this.nodes[id] = { id, name, children: [], loaded: false, loading: false, expanded: false };
                        }
                    },
                    getName(id)      { return this.nodes[id]?.name ?? id; },
                    getChildren(id)  { return this.nodes[id]?.children ?? []; },
                    isLoaded(id)     { return this.nodes[id]?.loaded ?? false; },
                    isLoading(id)    { return this.nodes[id]?.loading ?? false; },
                    isExpanded(id)   { return this.nodes[id]?.expanded ?? false; },
                    setExpanded(id, val) { this.ensure(id, id); this.nodes[id] = { ...this.nodes[id], expanded: val }; },
                    setLoading(id, val)  { this.ensure(id, id); this.nodes[id] = { ...this.nodes[id], loading: val }; },
                    setLoaded(id, val)   { this.ensure(id, id); this.nodes[id] = { ...this.nodes[id], loaded: val }; },
                    setChildren(id, children) { this.ensure(id, id); this.nodes[id] = { ...this.nodes[id], children }; },
                    reset() { this.nodes = {}; }
                });
            }
        });
    </script>

    <x-cabinet-filament::finder
        :modal="false"
        :$folder
        :$files
        :$breadcrumbs
        :$toolbarActions
        :$contextMenus
        :$selectionMode
        :$sidebarItems
        :$acceptedTypeChecker
        :$selectedSidebarItem
        :$selectedFiles
        :$treeSidebar
        :$initialFolderId
        :thumbnail-urls="$thumbnailUrls"
        :file-urls="$fileUrls"
        :lazy-load="$lazyLoad"
        :has-more-files="$hasMoreFiles"
    />
</div>
