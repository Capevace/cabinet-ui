<?php

declare(strict_types=1);

namespace Cabinet\Filament\Exceptions;

use Exception;

/**
 * Thrown when a folder is unknown or outside of the folders a finder can reach.
 */
class FolderNotAllowed extends Exception
{
    public function __construct(string $folderId)
    {
        parent::__construct("Folder [{$folderId}] is unknown or outside of the allowed folders.");
    }
}
