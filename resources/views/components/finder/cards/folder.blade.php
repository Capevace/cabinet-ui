@props(['folder'])

<li
    {{ $attributes->class(['flex flex-col transform group border border-gray-200 dark:border-gray-800 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 transition rounded-md overflow-hidden']) }}
    :class="{
        'ring-2 ring-primary-500 scale-105': draggingOverFolder === '{{ $folder->id }}',
    }"
    @dragover="draggingOverFolder = '{{ $folder->id }}'"
    @dragleave.self="draggingOverFolder = null"
    @drop.prevent="
        draggingOverFolder = null;
        const json = $event.dataTransfer.getData('application/cabinet-identifier');
        const identifier = JSON.parse(json);

        $wire.moveFile(identifier.source, identifier.id, '{{ $folder->id }}');
    "
>
    <button
        wire:click.prevent="openFolder('{{ $folder->id }}')"
        type="button"
        class="flex flex-col flex-1 w-full text-left"
        @contextmenu="openContextMenu('{{ $folder->type->slug() }}', $event, @js($folder->toIdentifier()))"
        :class="{
            'pointer-events-none': draggingOverFolder
        }"
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
