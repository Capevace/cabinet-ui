<template x-for="(upload, index) of uploads">
	<li
		:key="upload.id + index"
		class="flex flex-col group border border-gray-200 dark:border-gray-800 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors rounded-md overflow-hidden"
	>
		<figure class="h-32 w-full flex items-center justify-center bg-gray-200 dark:bg-gray-800">
            <x-filament::loading-indicator class="w-20 h-20 text-gray-500" />
        </figure>

		<div
			class="px-2 py-1 flex flex-col justify-between w-full h-full flex-1"
			x-data="{
				formatProgress() {
					return Math.round(upload.progress * 100) + '%';
				}
			}"
		>
			<p class="font-medium line-clamp-2" x-text="upload.name"></p>
			<p class="text-gray-700 dark:text-gray-400 text-sm" x-text="formatProgress"></p>
		</div>
	</li>
</template>
