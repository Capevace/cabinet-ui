@props(['file', 'previewAction' => null, 'disabled' => false])

<li
    {{ $attributes->class(['flex flex-col group border border-gray-200 dark:border-gray-800 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors rounded-md overflow-hidden']) }}
    :class="{
        'opacity-60': selectionEnabled && (!canSelectMore && !isFileSelected(@js($file->toIdentifier())) || {{ $disabled ? 'true' : 'false' }}),
        'ring-2 ring-primary-500': isFileSelected(@js($file->toIdentifier())),
{{--        'pointer-events-none': draggingFiles > 0,--}}
    }"
	@contextmenu="console.log('context menu')"
>
    <button
        class="flex flex-col flex-1 w-full text-left"
        type="button"
{{--        x-bind:disabled="(!canSelectMore && !isFileSelected(@js($file->toIdentifier()))) || {{ $disabled ? 'true' : 'false' }}"--}}
        @click="toggleFileSelection(@js($file->toIdentifier()))"
        @contextmenu="openContextMenu('{{ $file->type->slug() }}', $event, @js($file->toIdentifier()))"
    >
        <figure
			class="h-32 w-full flex items-center justify-center bg-gray-200 dark:bg-gray-800"
			x-data="{
				src: @js($file->previewUrl),
				renderedSrc: null,
				polling: null,

				init() {
					if (!this.src) {
						return;
					}

					const img = new Image();
					img.src = this.src;

					img.addEventListener('load', () => {
						this.renderedSrc = this.src;

						if (this.polling) {
							clearInterval(this.polling.interval);
							this.polling = null;
						}
					});

					img.addEventListener('error', () => {
						if (this.polling) {
							return;
						}

						const checker = () => {
							// reload the image
							img.src = this.src + '?' + this.polling.count;

							this.polling.count++;
						};

						this.polling = {
							count: 0,
							interval: setInterval(() => {
								if (this.polling.count >= 10) {
									clearInterval(this.polling.interval);

									this.polling.interval = setInterval(checker, 10000);
								}

								checker();
							}, 1000)
						};
					});
				}
			}"
		>
            @if(filled($file->previewUrl))
                <img
					ref="image"
					x-show="renderedSrc"
                    :src="renderedSrc"
                    alt="{{ $file->name }}"
                    loading="lazy"
                    class="h-full w-full object-center object-cover"
                />
				<x-filament::loading-indicator class="w-10 h-10 text-gray-500" x-show="!renderedSrc" />
            @else
                @svg($file->icon ?? $file->type->icon(), 'w-20 h-20 text-gray-500')
            @endif
        </figure>

        <div class="px-2 py-1 flex flex-col justify-between w-full h-full flex-1">
            <p class="font-medium line-clamp-2">
                {{ $file->name }}
            </p>

            <p class="text-gray-700 dark:text-gray-400 text-sm">
                {{ $file->type->name() }}
            </p>
        </div>
    </button>
</li>
