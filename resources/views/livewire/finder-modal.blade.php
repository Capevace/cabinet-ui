<div wire:key="finder-modal">
	<div
		@class([
			'fixed overscroll-contain inset-0 flex items-center justify-center z-40 px-10',
		])
		@click.self="
			open = false;
			$wire.closeFinder();
		"
		x-show="open || loading"
		x-transition:enter="transition ease-out duration-100"
		x-transition:enter-start="opacity-0"
		x-transition:enter-end="opacity-100"
		x-transition:leave="transition ease-in duration-100"
		x-transition:leave-start="opacity-100"
		x-transition:leave-end="opacity-0"
		x-data="{
			folderId: @entangle('folderId'),
			open: false,
			loading: false,

			init() {
				this.$watch('folderId', (value) => {
					this.open = value !== null;
					this.loading = false;
				});

				this.$wire.on('open', () => {
					this.loading = true;
				});
			}
		}"
		:class="{
			'bg-gray-500/70 dark:bg-gray-900/70 backdrop-blur cursor-pointer': open || loading,
			'pointer-events-none': !open && !loading
		}"
		x-cloak
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

		<x-filament::loading-indicator
			class="absolute z-30 w-16"
			x-show="loading"
		/>

		<div
			class="max-w-7xl w-full relative z-40 cursor-auto"
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
					:$replaceableThumbnailUrl
				/>
			@endif
		</div>
	</div>
</div>
