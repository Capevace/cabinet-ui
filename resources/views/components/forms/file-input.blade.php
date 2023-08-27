@php
    $statePath = $getStatePath();
    $max = $getMax();
    $multiple = $max !== 1;
    $acceptedTypes = []; //$getAcceptedTypes();

    $heading = $getHeading();
    $description = $getDescription();
    $finderHeading = '';
    $finderDescription = '';

    $selectAction = $getSelectAction();

    $emptyStateIcon = $getEmptyStateIcon();
    $emptyStateLabel = $getEmptyStateLabel();

    $canEdit = false;
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <section
		class="@container rounded-lg ring-1 ring-gray-950/10 bg-white dark:ring-white/20 dark:bg-white/5"
		x-data="{
            state: @entangle($statePath).live,
            loading: false,

            confirmSelection(data) {
                if (data.statePath !== @js($statePath)) {
                    return;
                }

                console.log(data.statePath);

                this.$wire.dispatchFormEvent('fileInput:select', '{{ $statePath }}', data.files);
            },

            openFinder() {
                @unless ($isDisabled())
                    try {
                        this.$wire.dispatchTo('modals', 'modals:open', 'finder', {
                            statePath: @js($statePath),
                            selection: this.files,
                            max: @js($max),
                            acceptedTypes: @js($acceptedTypes),
                            heading: @js($finderHeading),
                        });
                    } catch (e) {
                        console.error(e);
                    }
                @endunless
            },

            removeFile(file) {
                @if (!$multiple)
                    this.state = null;
                @else
                    this.state = this.state.filter(f => f.id !== file.id);
                @endif
            },

            // Helper so we can use the same template for single and multiple files
            get files() {

                @if ($multiple)
                    return this.state ?? [];
                @else
                    return this.state ? [this.state] : [];
                @endif

            }
        }"
        @cabinet:file-input:{{  $getLivewire()->getId() }}:confirm.window="confirmSelection($event.detail)"
    >
		<header
			class="flex items-center border-b px-2 py-2 dark:border-gray-700"
		>
			<div class="pl-3">
				<h2 class="font-semibold">{{ $heading }}</h2>

                @if($description)
                    <p class="text-sm dark:text-gray-300">
                        {{ $description }}
                    </p>
                @endif
			</div>
			<div class="flex-1"></div>

            <div
                class="ml-4"
                @if($getTooltip() || !$canEdit)
                    x-data="{}"

                    @if($tooltip = $getTooltip())
                        x-tooltip.raw="{{ $tooltip }}"
                    @endif
                @endif
            >
                @foreach($getActions() as $action)
                    {{ $action }}
                @endforeach
            </div>
		</header>

        <div
            @class([
                'grid gap-4 p-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 grid-cols-2 @xs:!grid-cols-2 @sm:!grid-cols-3 @md:!grid-cols-3 @lg:!grid-cols-4 @2xl:!grid-cols-5 @4xl:!grid-cols-6 @5xl:!grid-cols-7',
            ])
        >
            @foreach($getFiles() as $file)
                <x-cabinet-filament::file-input.file-preview
                    :wire:key="$file->uniqueId()"
                    :file="$file"
                    :disable-delete="!$canEdit"
                />
            @endforeach
        </div>

        {{-- No files selected --}}
        <template x-if="files.length === 0">
            <div class="flex flex-col items-center justify-center min-h-40 py-5">
                <x-icon :name="$getEmptyStateIcon()" class="w-20 h-20 text-gray-400" />

                <p class="mt-4 text-gray-400">
                    {{ $getEmptyStateLabel() }}
                </p>
            </div>
        </template>
	</section>
</x-dynamic-component>
