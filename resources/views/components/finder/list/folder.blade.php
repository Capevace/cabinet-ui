@props(['folder'])

<tr
    {{ $attributes->class(['group border-b border-gray-200 dark:border-gray-800 last:border-0 hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors cursor-pointer']) }}
    :class="{
        'ring-2 ring-primary-500 scale-105': draggingOverFolder === '{{ $folder->id }}',
    }"
    x-show="!searchQuery || @json(strtolower($folder->name)).includes(searchQuery.toLowerCase())"

    @dragover="draggingOverFolder = '{{ $folder->id }}'"
    @dragleave.self="draggingOverFolder = null"
    @drop.prevent="
        draggingOverFolder = null;
        const json = $event.dataTransfer.getData('application/cabinet-identifier');
        if (json) {
            const identifier = JSON.parse(json);
            $wire.moveFile(identifier.source, identifier.id, '{{ $folder->id }}');
        }
    "
    wire:click.prevent="openFolder('{{ $folder->id }}')"
    @contextmenu="openContextMenu('{{ $folder->type->slug() }}', $event, @js($folder->toIdentifier()))"
>
    {{-- Folder icon --}}
    <td class="py-1.5 px-2 w-8">
        <div class="w-7 h-7 rounded flex items-center justify-center flex-shrink-0">
            @svg($folder->type->icon(), 'w-5 h-5 text-gray-500')
        </div>
    </td>

    {{-- Name --}}
    <td class="py-1.5 px-2" colspan="2">
        <span class="font-medium text-sm text-gray-900 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
            {{ $folder->name }}
        </span>
    </td>

    {{-- Type label --}}
    <td class="py-1.5 px-2 hidden md:table-cell">
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('cabinet::files.directory') }}</span>
    </td>

    {{-- Context menu --}}
    <td class="py-1.5 px-2 text-right w-8">
        <button
            type="button"
            class="opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700"
            @click.stop="openContextMenu('{{ $folder->type->slug() }}', $event, @js($folder->toIdentifier()))"
            title="More actions"
        >
            @svg('heroicon-o-ellipsis-horizontal', 'w-4 h-4 text-gray-500')
        </button>
    </td>
</tr>
