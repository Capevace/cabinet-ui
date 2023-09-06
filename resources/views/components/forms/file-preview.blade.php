<?php
/** @var File $file */
$file = $getFile();
?>

@switch($file->type->slug())
    @case('image')
        <img
            src="{{ $file->url() }}"
            alt=""
        />
        @break

    @case('video')
        <video
            src="{{ $file->url() }}"
            controls
        ></video>
        @break
    @case('pdf')
        <iframe
            src="{{ $file->url() }}"
            class="w-full h-full border-none"
            style="min-height: 70vh;"
        ></iframe>
        @break

    @case('indoor-scan')
        <iframe
            src="{{ $file->url() }}"
            class="w-full h-full border-none"
            style="min-height: 70vh;"
        ></iframe>
        @break

    @case('external-video')
        <iframe
            src="{{ $file->url() }}"
            class="w-full border-none aspect-video"
        ></iframe>
        @break

    @case('camera-feed')
        <iframe
            src="{{ $file->url() }}"
            class="w-full border-none aspect-video"
        ></iframe>
        @break

    @case('energy-certificate')
        <div class="grid grid-cols-3 gap-10">
            <img
                class="col-span-2"
                src="{{ $file->url() }}"
                alt=""
            />

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Endenergieverbrauch Wärme
                </dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 mb-5">
                    {{ $file->attributes['heat_consumption'] }} kWh/(m²a)
                </dd>

                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Endenergieverbrauch Strom
                </dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 mb-5">
                    {{ $file->attributes['electricity_consumption'] }} kWh/(m²a)
                </dd>

                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Primärenergiebedarf
                </dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 mb-5">
                    {{ $file->attributes['primary_energy_consumption'] }} kWh/(m²a)
                </dd>
            </div>
        </div>
        @break

    @default
        <div class="flex flex-col space-y-5 items-center justify-center w-full h-full">
            <x-heroicon-o-document class="w-16 h-16 text-gray-400 dark:text-gray-600" />
            <p>{{ __('cabinet::messages.no-preview-available') }}</p>
        </div>
@endswitch


