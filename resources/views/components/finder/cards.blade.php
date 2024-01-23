@props(['files', 'acceptedTypeChecker', 'previewAction' => null, 'hasSidebar' => false])

@php

@endphp

<div
    class="flex-1"
    @dragenter="draggingFiles++; console.info('dragging files')"
    @dragleave="draggingFiles--; console.info('not dragging files')"
    @drop.prevent="uploadDroppedFile"
>
    <ul
        {{ $attributes->class([
            'grid px-4 py-4 gap-5 overflow-y-auto',
        ]) }}
        :class="{
            'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 asd': !showSidebar,
            'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4': showSidebar
        }"
    >
        <x-cabinet-filament::finder.upload-template />

        @foreach($files as $file)
            @if ($file instanceof \Cabinet\File)
                <x-cabinet-filament::finder.cards.file
                    wire:key="file-{{ $file->source }}-{{ $file->id }}"
                    :$file
                    :preview-action="$previewAction($file->toIdentifier())"
                    :disabled="!$acceptedTypeChecker->isAccepted($file->type)"
                />
            @elseif ($file instanceof \Cabinet\Folder)
                <x-cabinet-filament::finder.cards.folder
                    :folder="$file"
                    wire:key="folder-{{ $file->source }}-{{ $file->id }}"
                />
            @endif
        @endforeach
    </ul>
</div>
