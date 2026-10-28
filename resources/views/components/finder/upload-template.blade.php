<template x-for="(upload, index) of gridUploads()">
	<li
		:key="upload.id + index"
		class="flex flex-col group border bg-gray-100 dark:bg-gray-900 transition-colors rounded-md overflow-hidden"
		:class="upload.error
			? 'border-danger-300 dark:border-danger-700'
			: 'border-gray-200 dark:border-gray-800'"
	>
		<figure class="h-32 w-full flex items-center justify-center bg-gray-200 dark:bg-gray-800">
			<template x-if="upload.error">
				@svg('heroicon-o-exclamation-triangle', 'w-20 h-20 text-danger-500')
			</template>
			<template x-if="!upload.error">
				<x-filament::loading-indicator class="w-20 h-20 text-gray-500" />
			</template>
		</figure>

		<div class="px-2 py-1 flex flex-col justify-between w-full h-full flex-1">
			<p class="font-medium line-clamp-2" x-text="upload.name"></p>

			<p
				class="text-sm"
				:class="upload.error
					? 'text-danger-600 dark:text-danger-400'
					: 'text-gray-700 dark:text-gray-400'"
				x-text="upload.error
					? @js(__('cabinet::messages.unknown-error'))
					: Math.min(99, Math.round(upload.progress * 100)) + '%'"
			></p>
		</div>
	</li>
</template>