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

    @default
        <div class="flex flex-col space-y-5 items-center justify-center w-full h-full">
            <x-heroicon-o-document class="w-16 h-16 text-gray-400 dark:text-gray-600" />
            <p>{{ __('cabinet::messages.no-preview-available') }}</p>
        </div>
@endswitch


