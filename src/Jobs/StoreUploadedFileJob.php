<?php

namespace Cabinet\Filament\Jobs;

use Cabinet\Facades\Cabinet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Stores one uploaded file in the source of the folder it was dropped on.
 *
 * The finder queues this instead of storing files inside the Livewire request:
 * writing to a bucket can take longer than the request timeout, and while such a
 * request runs it occupies the finder's request queue — which made the finder
 * unusable (and the upload impossible to cancel) until every file was stored.
 */
class StoreUploadedFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $disk,
        public string $path,
        public string $name,
        public ?string $mimeType,
        public string $sourceName,
        public ?string $folderId,
        public ?string $batchId = null,
    ) {
    }

    /**
     * Cache key marking a drop as cancelled, checked before a file is stored.
     */
    public static function cancelledKey(string $batchId): string
    {
        return "cabinet:upload-cancelled:{$batchId}";
    }

    public function handle(): void
    {
        $disk = Storage::disk($this->disk);

        if (! $disk->exists($this->path)) {
            return;
        }

        // The drop was cancelled while this file was waiting in the queue.
        if ($this->batchId !== null && Cache::has(self::cancelledKey($this->batchId))) {
            $disk->delete($this->path);

            return;
        }

        $folder = $this->folderId !== null ? Cabinet::folder($this->folderId) : null;

        if ($folder === null) {
            $disk->delete($this->path);

            return;
        }

        $file = new UploadedFile(
            path: $disk->path($this->path),
            originalName: $this->name,
            mimeType: $this->mimeType,
            error: null,
            test: true,
        );

        Cabinet::getSource($this->sourceName)->upload($folder, $file);

        $disk->delete($this->path);
    }
}
