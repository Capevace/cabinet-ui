{{--@teleport('body')--}}
<div
    @class([
        'fixed overscroll-contain inset-0 flex items-center justify-center z-40 px-10',
        'bg-gray-500/70 dark:bg-gray-900/70 backdrop-blur' => $this->folderId !== null,
        'pointer-events-none' => $this->folderId === null,
    ])
    @click.self="$wire.closeFinder()"
    x-show="open"
    x-transition:enter="transition ease-out duration-100"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-data="{
        folderId: @entangle('folderId'),
        open: false,

        init() {
            this.$watch('folderId', (value) => {
                this.open = value !== null;
            });
        }
    }"
>
    <script type="text/javascript">
        document.addEventListener('alpine:init', function () {
            Alpine.store('finderContextMenu', {
               visible: false,
               position: {
                   top: 0,
                   left: 0,
               },
               items: [],
               data: {},

               open() {
                   this.visible = true;
               },

               close() {
                   this.visible = false;
                   this.items = [];
                     this.data = {};
               }
           });
        });
    </script>
    <div
        class="max-w-7xl w-full"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"

    >
        @if($this->folderId)
            <x-cabinet-filament::finder
				:modal="true"
                :$folder
                :$files
                :$breadcrumbs
                :$toolbarActions
                :$contextMenus
                :$selectionMode
                :$sidebarItems
                :$acceptedTypeChecker
                :$selectedSidebarItem
            />
        @endif
    </div>
</div>

{{--@endteleport--}}
