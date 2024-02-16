@props(['folder'])

<li
    {{ $attributes->class(['flex flex-col group border border-gray-200 dark:border-gray-800 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors rounded-md overflow-hidden']) }}
>
    <button
        wire:click.prevent="openFolder('{{ $folder->id }}')"
        type="button"
        class="flex flex-col flex-1 w-full text-left"
        @contextmenu="openContextMenu('{{ $folder->type->slug() }}', $event, @js($folder->toIdentifier()))"
    >
        <figure class="h-32 w-full flex items-center justify-center bg-gray-200 dark:bg-gray-800">
            @svg($folder->type->icon(), 'w-20 h-20 text-gray-500')
        </figure>

        <div class="px-2 py-1 flex flex-col justify-between w-full h-full flex-1">
            <p class="font-medium line-clamp-2">
                {{ $folder->name }}
            </p>

            <p class="text-gray-700 dark:text-gray-400 text-sm">
                {{ __('cabinet::files.directory') }}
            </p>
        </div>
    </button>
</li>
