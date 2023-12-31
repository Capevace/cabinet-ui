@props(['file', 'disableDelete' => false])

<div
    {{ $attributes->class("relative w-32 h-32 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 aspect-square rounded-lg overflow-hidden group") }}
>
    @unless ($disableDelete)
        <button
            type="button"
            class="absolute top-0 right-0 m-1 p-1 text-white bg-gray-800/50 hover:bg-gray-700/50 rounded-full"
            @click.prevent="removeFile(file)"
        >
            @svg('bi-x', 'w-6 h-6')
        </button>
    @endunless


    <div
		class="w-full h-full flex items-center justify-center bg-cover bg-center"
		style="background-image: url('{{ $file->previewUrl }}');"
	>
        @if(!filled($file->previewUrl))
{{--            <img--}}
{{--                src="{{ $file->previewUrl }}"--}}
{{--                alt="{{ $file->name }}"--}}
{{--                loading="lazy"--}}
{{--                class="w-full h-full object-center object-contain"--}}
{{--            />--}}
            @svg($file->icon ?? $file->type->icon(), 'w-20 h-20 text-gray-500')
        @endif
    </div>

    <div class="absolute bottom-0 -left-1 -right-1 bg-gray-100/90 dark:bg-gray-900/90 px-3 py-2 transition transform translate-y-1 opacity-0 group-hover:opacity-100 group-hover:translate-y-0">
        <p class="mb-0.5 text-xs font-semibold text-gray-950 dark:text-gray-100 line-clamp-2">{{ $file->name }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
            <span>{{ $file->mimeType }}</span>
            –
            <span>{{ $file->humanSize() }}</span>
        </p>
    </div>
</div>
