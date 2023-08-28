@props(['files', 'acceptedTypeChecker', 'previewAction' => null])

<ul
    {{ $attributes->class(['grid grid-cols-4 px-4 py-4 gap-5']) }}
>
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
