{{--
    Icon (or thumbnail) plus the label of a single file reference, used inside
    the reference rows of the detail panel. Expects `ref` in the Alpine scope.
--}}
<figure class="w-6 h-6 rounded flex items-center justify-center flex-shrink-0 overflow-hidden bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
    <template x-if="ref.thumbnailUrl">
        <img :src="ref.thumbnailUrl" class="w-full h-full object-cover" alt="" />
    </template>
    <template x-if="!ref.thumbnailUrl && ref.iconHtml">
        <span class="w-4 h-4" x-html="ref.iconHtml"></span>
    </template>
    <template x-if="!ref.thumbnailUrl && !ref.iconHtml">
        @svg('heroicon-o-document', 'w-4 h-4')
    </template>
</figure>

<div class="min-w-0 flex-1">
    <p class="text-xs font-medium text-gray-800 dark:text-gray-100 truncate" x-text="ref.label"></p>
    <p
        x-show="referenceSubtitle(ref)"
        class="text-[11px] text-gray-500 dark:text-gray-400 truncate"
        x-text="referenceSubtitle(ref)"
    ></p>
</div>

<figure x-show="ref.url" class="flex-shrink-0 text-gray-300 dark:text-gray-600 group-hover:text-primary-500">
    @svg('heroicon-o-arrow-top-right-on-square', 'w-3.5 h-3.5')
</figure>
