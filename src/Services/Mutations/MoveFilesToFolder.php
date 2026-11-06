<?php

declare(strict_types=1);

namespace Cabinet\Filament\Services\Mutations;

use Cabinet\Facades\Cabinet;
use Cabinet\Filament\DTO\FileIdentifier;
use Cabinet\Filament\Exceptions\FolderNotAllowed;
use Cabinet\File;
use Cabinet\Folder;
use Cabinet\Models\Directory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Moves files of one folder into another folder.
 *
 * The finder calls this when files are dropped on a folder, so the ids come
 * from the browser. Only files that are in the source folder move, and both
 * folders must be inside of the allowed folders. A sidebar item can lie outside
 * of the root folder, which is why the caller passes the allowed folders. All
 * files move in one transaction: a failure moves none of them.
 *
 * @example
 * $movedFiles = app(MoveFilesToFolder::class)->run(
 *     sourceFolder: $folder,
 *     targetFolderId: $estate->documents_directory_id,
 *     fileIdentifiers: [new FileIdentifier(source: 'spatie-media', id: $media->uuid)],
 *     allowedFolderIds: [$estate->directory_id],
 * );
 */
class MoveFilesToFolder
{
    /**
     * Run the move.
     *
     * @param  Folder  $sourceFolder  The folder the files are in. Files of other folders stay where they are.
     * @param  string  $targetFolderId  The folder to move to. Its own id as the source folder moves nothing.
     * @param  FileIdentifier[]  $fileIdentifiers  The files to move. Unknown files are ignored.
     * @param  string[]  $allowedFolderIds  The folders that can be reached, including everything below them.
     * @return File[] The files that moved. Empty when nothing had to move.
     *
     * @throws FolderNotAllowed When a folder is unknown or outside of the allowed folders.
     */
    public function run(Folder $sourceFolder, string $targetFolderId, array $fileIdentifiers, array $allowedFolderIds): array
    {
        $target = $this->findAllowedDirectory(folderId: $targetFolderId, allowedFolderIds: $allowedFolderIds);

        $this->findAllowedDirectory(folderId: $sourceFolder->id, allowedFolderIds: $allowedFolderIds);

        if ($target->id === $sourceFolder->id) {
            return [];
        }

        $files = $sourceFolder->files()
            ->filter(fn (File|Folder $file): bool => $file instanceof File && $this->isRequested(file: $file, fileIdentifiers: $fileIdentifiers))
            ->values();

        DB::transaction(fn () => $files->each(fn (File $file) => Cabinet::move($file, $target)));

        return $files->all();
    }

    /**
     * @param  FileIdentifier[]  $fileIdentifiers
     */
    protected function isRequested(File $file, array $fileIdentifiers): bool
    {
        foreach ($fileIdentifiers as $fileIdentifier) {
            if ($fileIdentifier->isFile($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string[]  $allowedFolderIds
     *
     * @throws FolderNotAllowed When the folder is unknown or outside of the allowed folders.
     */
    protected function findAllowedDirectory(string $folderId, array $allowedFolderIds): Directory
    {
        $directory = Str::isUuid($folderId)
            ? Cabinet::findCabinetDirectory($folderId)
            : null;

        for ($current = $directory; $current !== null; $current = $current->parentDirectory) {
            if (in_array($current->id, $allowedFolderIds, true)) {
                return $directory;
            }
        }

        throw new FolderNotAllowed($folderId);
    }
}
