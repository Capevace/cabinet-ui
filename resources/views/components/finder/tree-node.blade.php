@props(['depth' => 0])

@php
    // The current folder id variable at this depth, e.g. 'l0', 'l1', etc.
    $varName = 'l' . $depth;
@endphp

{{--
    A single tree node row. The current folder id is in the Alpine variable
    named `l{depth}` (e.g. l0, l1, l2...) injected by the x-for loop in tree-sidebar.blade.php.
--}}
<button
    type="button"
    class="w-full flex items-center gap-1.5 px-2 py-1 rounded-md text-sm font-medium text-left transition-colors group"
    :class="{
        'bg-primary-50 dark:bg-primary-950/30 text-primary-700 dark:text-primary-300': $wire.folderId === {{ $varName }},
        'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800': $wire.folderId !== {{ $varName }},
    }"
    @click.prevent="
        openFolder({{ $varName }});
    "
>
    {{-- Expand/collapse chevron --}}
    <span
        class="w-4 h-4 flex-shrink-0 flex items-center justify-center text-gray-400"
        @click.stop="toggle({{ $varName }})"
        :class="{
            'text-primary-500': Alpine.store('cabinetTree').isLoading({{ $varName }})
        }"
    >
        <template x-if="Alpine.store('cabinetTree').isLoading({{ $varName }})">
            <span>
                @svg('heroicon-o-arrow-path', 'w-3 h-3 animate-spin')
            </span>
        </template>
        <template x-if="!Alpine.store('cabinetTree').isLoading({{ $varName }}) && Alpine.store('cabinetTree').isExpanded({{ $varName }})">
            <span>
                @svg('heroicon-s-chevron-down', 'w-3 h-3')
            </span>
        </template>
        <template x-if="!Alpine.store('cabinetTree').isLoading({{ $varName }}) && !Alpine.store('cabinetTree').isExpanded({{ $varName }})">
            <span>
                @svg('heroicon-s-chevron-right', 'w-3 h-3')
            </span>
        </template>
    </span>

    {{-- Folder icon --}}
    <span class="flex-shrink-0">
        <template x-if="Alpine.store('cabinetTree').isExpanded({{ $varName }})">
            <span>@svg('heroicon-s-folder-open', 'w-4 h-4 text-gray-400 group-hover:text-gray-500')</span>
        </template>
        <template x-if="!Alpine.store('cabinetTree').isExpanded({{ $varName }})">
            <span>@svg('heroicon-s-folder', 'w-4 h-4 text-gray-400 group-hover:text-gray-500')</span>
        </template>
    </span>

    {{-- Folder name --}}
    <span
        class="flex-1 truncate text-xs"
        x-show="showSidebar"
        x-text="Alpine.store('cabinetTree').getName({{ $varName }})"
    ></span>
</button>
