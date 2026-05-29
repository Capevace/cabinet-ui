@props(['file', 'previewAction' => null, 'disabled' => false, 'stableThumbnailUrl' => null])

@php
    $thumbnailUrl = $stableThumbnailUrl ?? $file->previewUrl;
@endphp

<li
    {{ $attributes->class(['flex flex-col group border border-gray-200 dark:border-gray-800 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors rounded-md overflow-hidden']) }}
    :class="{
        'opacity-60 cursor-not-allowed': selectionEnabled && (!canSelectMore && !isFileSelected(@js($file->toIdentifier())) || {{ $disabled ? 'true' : 'false' }}),
        'ring-2 ring-primary-500': isFileSelected(@js($file->toIdentifier())),
        'ring-2 ring-gray-400 dark:ring-gray-500': !selectionEnabled && isDetailFile(@js($file->toIdentifier())),
        'ring-2 ring-secondary-500': !selectionEnabled && isBulkSelected(@js($file->toIdentifier())),
        'opacity-50': draggingVirtualFile,
    }"
    x-on:dragend="
        draggingVirtualFile = false;
        draggingFiles = 0;
    "
    draggable="true"
    x-on:dragstart.self="
        draggingVirtualFile = true;
        draggingFiles = 0;

        const json = JSON.stringify(@js($file->toIdentifier()));

        $event.dataTransfer.setData('application/cabinet-identifier', json);
        $event.dataTransfer.effectAllowed='move';
        $event.dataTransfer.dropEffect='move';

        const thumbnail = document.createElement('img');
        thumbnail.src = '{{ $thumbnailUrl }}';
        thumbnail.style.width = '100%';
        thumbnail.style.height = '100%';
        thumbnail.style.objectFit = 'cover';

        const div = document.createElement('div');
        div.style.width = '200px';
        div.style.height = '150px';
        div.style.backgroundColor = '#fff';
        div.style.opacity = '0.1';
        div.style.borderRadius = '10px';
        div.style.overflow = 'hidden';
        div.appendChild(thumbnail);

        // Append element to body
        document.body.appendChild(div);

        // Set element to dataTransfer
        $event.dataTransfer.setDragImage(div, 200, 150);
    "
    @drop.prevent="
        const json = $event.dataTransfer.getData('application/cabinet-identifier');
        const identifier = JSON.parse(json);

        $wire.moveFile(identifier.source, identifier.id, draggingOverFolder);
        draggingOverFolder = null;
    "
>
    <button
        class="flex flex-col flex-1 w-full text-left"
        type="button"
        @click="
            if (!(selectionEnabled && (!canSelectMore && !isFileSelected(@js($file->toIdentifier())) || {{ $disabled ? 'true' : 'false' }}))) {
                handleFileClick(@js($file->toIdentifier()), $event);
            }
        "
        @contextmenu="openContextMenu(!selectionEnabled && isBulkSelected(@js($file->toIdentifier())) && bulkSelectedFiles.length > 1 ? 'bulk' : '{{ $file->type->slug() }}', $event, @js($file->toIdentifier()))"
    >
        <figure
			class="h-32 w-full flex items-center justify-center bg-gray-200 dark:bg-gray-800"
			x-data="{
				src: @js($thumbnailUrl),
				renderedSrc: null,
				pollTimer: null,
				attempts: 0,
				maxAttempts: 15,
				backoffMs: 1000,

				init() {
					if (!this.src) {
						return;
					}

					const img = new Image();
					img.src = this.src;

					img.addEventListener('load', () => {
						this.renderedSrc = this.src;
						this.stopPolling();
					});

					img.addEventListener('error', (event) => {
						// Don't retry on rate-limit (429) or unprocessable (422) errors
						// — the server is telling us to back off
						if (event?.target?.status === 429 || event?.target?.status === 422) {
							return;
						}

						this.startPolling();
					});
				},

				destroy() {
					this.stopPolling();
				},

				startPolling() {
					if (this.pollTimer) return;

					const tryLoad = () => {
						if (this.attempts >= this.maxAttempts) {
							this.stopPolling();
							return;
						}

						this.attempts++;
						const img = new Image();
						img.src = this.src + '?retry=' + this.attempts;

						img.addEventListener('load', () => {
							this.renderedSrc = this.src;
							this.stopPolling();
						});

						img.addEventListener('error', (e) => {
							if (e?.target?.status === 429 || e?.target?.status === 422) {
								this.stopPolling();
								return;
							}

							// Exponential backoff: 1s, 2s, 4s, 8s ... max 30s
							this.backoffMs = Math.min(this.backoffMs * 2, 30000);
							this.pollTimer = setTimeout(tryLoad, this.backoffMs);
						});
					};

					tryLoad();
				},

				stopPolling() {
					if (this.pollTimer) {
						clearTimeout(this.pollTimer);
						this.pollTimer = null;
					}
				}
			}"
		>
            @if(filled($thumbnailUrl) && in_array($file->type->slug(), ['image', 'video', 'pdf']))
                <img
					ref="image"
					x-show="renderedSrc"
                    :src="renderedSrc"
                    alt="{{ $file->name }}"
                    loading="lazy"
                    class="h-full w-full object-center object-cover"
                    draggable="false"
                />
				<x-filament::loading-indicator class="w-10 h-10 text-gray-500" x-show="!renderedSrc" />
            @else
                @svg($file->icon ?? $file->type->icon(), 'w-20 h-20 text-gray-500')
            @endif
        </figure>

        {{-- Bulk selection checkmark --}}
        <div
            x-show="!selectionEnabled && isBulkSelected(@js($file->toIdentifier()))"
            class="absolute top-2 right-2 bg-secondary-500 text-white rounded-full p-1 shadow-sm"
            x-cloak
        >
            @svg('heroicon-s-check', 'w-4 h-4')
        </div>

        <div class="px-2 py-1 flex flex-col justify-between w-full h-full flex-1">
            <p class="font-medium line-clamp-2">
                {{ $file->name }}
            </p>

            <div class="flex items-center justify-between">
                <p class="text-gray-700 dark:text-gray-400 text-sm">
                    {{ $file->type->name() }}
                </p>
                <p class="text-gray-500 dark:text-gray-500 text-xs">
                    {{ $file->formattedCreatedAt() }}
                </p>
            </div>
        </div>
    </button>
</li>
