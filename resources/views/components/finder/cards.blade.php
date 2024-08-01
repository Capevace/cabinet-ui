@props(['files', 'acceptedTypeChecker', 'previewAction' => null, 'hasSidebar' => false])

@php

@endphp

<div
    class="flex-1"
    @dragenter="
        if (!$event.dataTransfer.getData('application/cabinet-identifier') && draggingVirtualFile === false) {
            draggingFiles++;
        }
    "
    @dragleave="
        if (!$event.dataTransfer.getData('application/cabinet-identifier')) {
            draggingFiles--;
        }
    "
    @drop.prevent="
        if (!$event.dataTransfer.getData('application/cabinet-identifier')) {
            uploadDroppedFile($event);
            $event.preventDefault();
            draggingFiles = 0;
        }
    "
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

    @if (count($files) === 0)
        <x-filament-tables::empty-state
            heading="{{ __('cabinet::messages.empty-folder') }}"
            description="{{ __('cabinet::messages.drag-or-add-files') }}"
            icon="heroicon-o-folder"
            class="col-span-full opacity-75s"
            :actions="[
                $this->uploadFileAction
                    ->button()
                    ->color('gray')
            ]"
        />
    @endif
</div>
