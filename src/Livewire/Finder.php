<?php

namespace Cabinet\Filament\Livewire;

use Cabinet\Filament\Livewire\Finder\SelectionMode;
use Cabinet\Facades\Cabinet;
use Cabinet\Filament\Livewire\Finder\AcceptableTypeChecker;
use Cabinet\Filament\Livewire\Finder\Actions\CreateFolder;
use Cabinet\Filament\Livewire\Finder\Actions\DeleteBulk;
use Cabinet\Filament\Livewire\Finder\Actions\DeleteFile;
use Cabinet\Filament\Livewire\Finder\Actions\DownloadBulk;
use Cabinet\Filament\Livewire\Finder\Actions\DownloadFile;
use Cabinet\Filament\Livewire\Finder\Actions\PreviewFile;
use Cabinet\Filament\Livewire\Finder\Actions\RefreshFile;
use Cabinet\Filament\Livewire\Finder\Actions\RenameFile;
use Cabinet\Filament\Livewire\Finder\Actions\ShareFile;
use Cabinet\Filament\Livewire\Finder\Actions\UploadFile;
use Cabinet\Filament\Livewire\Finder\Breadcrumb;
use Cabinet\Filament\Livewire\Finder\ContextMenuItem;
use Cabinet\Filament\Livewire\Finder\FileTypeDto;
use Cabinet\Filament\Livewire\Finder\SidebarItemDto;
use Cabinet\File;
use Cabinet\FileType;
use Cabinet\Sources\SpatieMediaSource;
use Cabinet\Types\Other;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;
use Illuminate\Support\Str;
use Cabinet\Folder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * @property-read Collection<File> $files
 * @property-read Collection<Breadcrumb> $breadcrumbs
 * @property-read Collection<SidebarItemDto> $sidebarItems
 * @property-read SidebarItemDto|null $selectedSidebarItem
 * @property-read Folder|null $folder
 * @property-read Folder|null $initialFolder
 * @property-read AcceptableTypeChecker $acceptableTypeChecker
 * @property-read int $totalFileCount
 */
class Finder extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    #[Locked]
    public bool $modal = true;

    /**
     * Whether to sync folder/selection state to URL query params.
     * Enabled automatically in full-screen (non-modal) mode.
     */
    #[Locked]
    public bool $urlState = false;

    #[Locked]
	public ?string $initialFolderId = null;

    #[Locked]
	public ?string $folderId = null;

    /**
     * @var SidebarItemDto[]
     */
    #[Locked]
    public array $sidebarItems = [];

    /**
     * @var FileTypeDto[]
     */
    #[Locked]
    public array $acceptedTypes = [];

	public ?SelectionMode $selectionMode = null;

	public array $selectedFiles = [];

    #[Session]
    public bool $showSidebar = true;

    #[Session]
    public string $viewMode = 'grid';

    #[Session]
    public string $sortColumn = 'name';

    #[Session]
    public string $sortDirection = 'asc';

    /**
     * When true, files are loaded in batches of 100 with a "Load more" button.
     * When false, all files are shown immediately.
     * Search becomes server-side when lazy loading is enabled.
     */
    #[Locked]
    public bool $lazyLoad = true;

    public int $fileLimit = 100;

    public string $searchQuery = '';

    /**
     * When true, the sidebar shows a directory tree instead of sidebar item shortcuts.
     * Set this at component mount time: @livewire(Finder::class, ['treeSidebar' => true])
     * or by passing it through the `open` event (for modal usage, always false).
     */
    #[Locked]
    public bool $treeSidebar = false;

    /**
     * When true and no sidebar items are explicitly provided, the sidebar will
     * automatically be populated with all root-level (parentless) directories.
     */
    #[Locked]
    public bool $autoSidebar = false;

	public array $uploadedFiles = [];

    /**
     * Returns the direct child folders of a given folder for the tree sidebar.
     * Called via $wire.call('getSubfolders', folderId) from Alpine.
     *
     * @return array<array{id: string, name: string, source: string}>
     */
    public function getSubfolders(string $folderId): array
    {
        $folder = Cabinet::folder($folderId);

        if ($folder === null) {
            return [];
        }

        return $folder->files()
            ->filter(fn ($item) => $item instanceof Folder)
            ->sortBy(fn (Folder $f) => mb_strtolower($f->name))
            ->map(fn (Folder $f) => [
                'id'     => $f->id,
                'name'   => $f->name,
                'source' => $f->source ?? '',
            ])
            ->values()
            ->all();
    }

    #[On('open')]
    public function open(
        string $folderId,
        ?array $mode = null,
        array $sidebarItems = [],
        array $selectedFiles = [],
        array $acceptedTypes = [],
    )
    {
        $folder = Cabinet::folder($folderId);

        abort_if($folder === null, 404);

        $this->initialFolderId = $folderId;
        $this->folderId = $folderId;
        $this->fileLimit = 100;
        $this->searchQuery = '';

        $this->sidebarItems = collect($sidebarItems)
            ->map(fn (array $item) => SidebarItemDto::fromLivewire($item))
            ->all();

        $this->acceptedTypes = collect($acceptedTypes)
            ->map(fn (array $type) => FileTypeDto::fromLivewire($type))
            ->filter()
            ->all();

        if ($mode !== null) {
            $this->selectionMode = SelectionMode::fromLivewire($mode);
        }

        $this->selectedFiles = $selectedFiles;
    }

	public function updatedUploadedFiles()
	{
		$folder = $this->folder;
		$source = Cabinet::getSource(SpatieMediaSource::TYPE);
        $files = collect($this->uploadedFiles)
            // Make sure the file exists
            ->filter(fn (?TemporaryUploadedFile $file) => $file?->exists());

        if ($files->isEmpty()) {
            Notification::make()
                ->danger()
                ->title(__('cabinet::messages.no-files-uploaded'))
                ->send();

            return;
        }

        $invalidFiles = $files
            ->filter(function (TemporaryUploadedFile $file) {
                $type = Cabinet::determineFileTypeFromMime($file->getMimeType());

                return $this->globalAcceptableTypeChecker->isAccepted($type) === false;
            });

        $validFiles = $files->diff($invalidFiles);

        if ($invalidFiles->isNotEmpty()) {
            $names = $invalidFiles
                ->map(fn (TemporaryUploadedFile $file) => $file->getClientOriginalName());

            // If there are more than 3 files, only show the first 3 and add an ellipsis
            if ($names->count() > 3) {
                $names = $names->take(3)->push('...');
            }

            Notification::make()
                ->warning()
                ->title(trans_choice('cabinet::messages.invalid-file-types', $invalidFiles->count()))
                ->body($names->join(', '))
                ->send();

            // Delete the temporary files
            $invalidFiles->each->delete();
        }

        $validFiles
            // Upload the file
            ->each(function (TemporaryUploadedFile $file) use ($folder, $source, $invalidFiles) {
                $source->upload($folder, $file);

                // Delete the file from the uploads directory, now that it's been uploaded to destination
                $file->delete();
            });

        $skippedFilesText = $invalidFiles->count() > 0
            ? trans_choice('cabinet::messages.files-skipped', $invalidFiles->count())
            : null;

        Notification::make()
            ->success()
            ->title(trans_choice('cabinet::messages.files-uploaded-successfully', $validFiles->count()))
            ->body($skippedFilesText)
            ->send();

        $this->refresh();
	}

//    #[On('openFinder')]
//	public function openFinder(Folder $folder, ?Finder\SelectionMode $selectionMode = null)
//	{
//        dd('wat');
//		$this->folder = $folder;
//		$this->selectionMode = $selectionMode;
//	}

	public function closeFinder()
	{
        $this->initialFolderId = null;
		$this->folderId = null;
        $this->sidebarItems = [];
        $this->acceptedTypes = [];

		$this->selectionMode = null;
        $this->selectedFiles = [];

        $this->dispatch('cabinet:finder-closed');
    }

    public function mount(): void
    {
        // URL state is only meaningful in full-screen (non-modal) mode
        if (!$this->modal) {
            $this->urlState = true;

            // Restore folder from URL if not already set
            if ($this->folderId === null) {
                $urlFolderId = request()->query('folder');
                if ($urlFolderId && Cabinet::folder($urlFolderId)) {
                    $this->folderId = $urlFolderId;
                    $this->initialFolderId = $urlFolderId;
                }
            }

            // Restore selection from URL
            $urlSelected = request()->query('selected');
            if ($urlSelected) {
                $this->selectedFiles = $this->parseUrlSelection($urlSelected);
            }
        }
    }

    /**
     * Serialize bulk selection array to a URL-safe string.
     * Format: "source:id,source:id"
     */
    protected function serializeUrlSelection(): ?string
    {
        if (empty($this->selectedFiles)) {
            return null;
        }

        return collect($this->selectedFiles)
            ->map(fn (array $file) => "{$file['source']}:{$file['id']}")
            ->join(',');
    }

    /**
     * Parse a URL selection string back into file identifier arrays.
     */
    protected function parseUrlSelection(string $value): array
    {
        return collect(explode(',', $value))
            ->map(function (string $pair) {
                $parts = explode(':', $pair, 2);
                if (count($parts) !== 2) {
                    return null;
                }

                $file = Cabinet::file($parts[0], $parts[1]);
                if ($file === null) {
                    return null;
                }

                return $file->toIdentifier();
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Push current folder/selection state to the browser URL.
     * Only dispatches in full-screen mode.
     */
    protected function syncUrlState(): void
    {
        if (!$this->urlState) {
            return;
        }

        $this->dispatch('cabinet:url-state-changed', [
            'folder' => $this->folderId,
            'selected' => $this->serializeUrlSelection(),
        ]);
    }

    public function loadMore(): void
    {
        if (!$this->lazyLoad) {
            return;
        }

        $this->fileLimit += 100;
        $this->refresh();
    }

    public function updatedSearchQuery(): void
    {
        if (!$this->lazyLoad) {
            return;
        }

        $this->fileLimit = 100;
        $this->refresh();
    }

    public function clearSelection(): void
    {
        $this->selectedFiles = [];
        $this->syncUrlState();
    }

    public function refresh()
    {
        unset($this->uploadedFiles);
        unset($this->folder);
        unset($this->files);
        unset($this->breadcrumbs);
    }

    #[On('deselectFile')]
    public function deselectFile(string $source, string $id): void
    {
        $this->selectedFiles = collect($this->selectedFiles)
            ->filter(fn (array $file) => $file['source'] !== $source || $file['id'] !== $id)
            ->values()
            ->all();
    }

    public function confirmFileSelection()
    {
        if (!$this->selectionMode) {
            return;
        }

        $files = collect($this->selectedFiles)
            ->map(fn (array $file) => Cabinet::file($file['source'], $file['id']))
            ->filter() // Remove null values (files not found)
            ->filter(fn (File $file) => $this->acceptableTypeChecker->isAccepted($file->type))
            //->filter(/** TODO: fine-grained auth check */)
            ->map(fn (File $file) => $file->toIdentifier());

        $livewireId = str($this->selectionMode->livewireId)
            ->lower();

        $this->dispatch(
            "cabinet:file-input:{$livewireId}:confirm",
            statePath: $this->selectionMode->statePath,
            files: $files->all(),
        );

		$this->dispatch(
            "cabinet:file-input:confirm",
			livewireId: $livewireId,
            statePath: $this->selectionMode->statePath,
            files: $files->all(),
        );

        $this->closeFinder();
    }

    public function toggleSort(string $column)
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }

        $this->refresh();
    }

    public function openFolder(string $id)
    {
        // only allow setting if the folder id is found in the current folder
        // or sidebar items

        if ($this->folder?->id === $id) {
            return;
        }

        $this->folderId = $id;

        // Keep selection across folders in selection mode; reset in browse mode
        if ($this->selectionMode === null) {
            $this->selectedFiles = [];
        }

        $this->fileLimit = 100;
        $this->searchQuery = '';

        $this->refresh();
        $this->dispatch('cabinet:folder-opened');
        $this->syncUrlState();
    }

    public function moveFile(string $source, string $id, ?string $folderId)
    {
        if ($folderId === null) {
            return;
        }

        $validFolderId = $this->files
            ->filter(fn (File|Folder $file) => $file instanceof Folder)
            ->first(fn (Folder $folder) => $folder->id === $folderId)
            ?->id;

        if ($validFolderId === null) {
            $validFolderId = $this->breadcrumbs
                ->filter(fn (Breadcrumb $breadcrumb) => $breadcrumb->folderId === $folderId)
                ->first()
                ?->folderId;
        }

        if ($validFolderId !== null && $folder = Cabinet::findCabinetDirectory($validFolderId)) {
            $file = Cabinet::file($source, $id);

            Cabinet::move($file, $folder);

            $this->refresh();
        }
    }

    #[Computed]
    public function initialFolder(): ?Folder
    {
        return $this->initialFolderId
            ? Cabinet::folder($this->initialFolderId)
            : null;
    }

    #[Computed]
    public function folder(): ?Folder
    {
        return $this->folderId
            ? Cabinet::folder($this->folderId)
            : null;
    }

    #[Computed]
    public function selectedSidebarItem(): ?SidebarItemDto
    {
        $breadcrumbs = $this->breadcrumbs->reverse();

        $selectedItem = null;
        $closeness = null;

        // Go through the breadcrumbs and find item that's the closest to the current folder
        // or the current folder itself
        foreach ($this->sidebarItems as $item) {
            $folderId = $item->id;

            if ($folderId === null) {
                continue;
            }

            // If the folder is the current folder, return it immediately
            if ($folderId === $this->folderId) {
                return $item;
            }

            // Find the closest sidebar item to the current folder
            foreach ($breadcrumbs as $index => $breadcrumb) {
                // If the item's folder is in the breadcrumbs and it's closer than the current closest item,
                // set it as the closest item
                if ($breadcrumb->folderId === $folderId && ($closeness === null || $index < $closeness)) {
                    $closeness = $index;
                    $selectedItem = $item;

                    // We don't need to check the rest of the breadcrumbs
                    break;
                }
            }
        }

        return $selectedItem;
    }

    /**
     * @return Collection<File>
     */
    #[Computed]
    public function files(): Collection
    {
        return $this->allFiles()->take($this->lazyLoad ? $this->fileLimit : null);
    }

    /**
     * Total file count in the current folder (after search/sort, before limit).
     */
    #[Computed]
    public function totalFileCount(): int
    {
        return $this->allFiles()->count();
    }

    /**
     * All files in the current folder, filtered and sorted but not limited.
     *
     * @return Collection<File>
     */
    protected function allFiles(): Collection
    {
        $files = $this->folder?->files() ?? collect();

        // Server-side search filter (lazy load mode only)
        if ($this->lazyLoad && filled($this->searchQuery)) {
            $query = Str::lower($this->searchQuery);
            $files = $files->filter(fn ($fileOrFolder) =>
                Str::contains(Str::lower($fileOrFolder->name), $query)
            );
        }

        return $files
            ->sortBy(function ($fileOrFolder) {
                $isFolder = $fileOrFolder instanceof Folder;
                $folderPrefix = $isFolder ? 0 : 1;

                if ($isFolder) {
                    return [$folderPrefix, Str::lower($fileOrFolder->name)];
                }

                $value = match ($this->sortColumn) {
                    'name' => Str::lower($fileOrFolder->name),
                    'type' => Str::lower($fileOrFolder->type->name()),
                    'size' => $fileOrFolder->size,
                    'created' => $fileOrFolder->createdAt?->getTimestamp() ?? 0,
                    default => Str::lower($fileOrFolder->name),
                };

                return [$folderPrefix, $value];
            }, SORT_REGULAR, $this->sortDirection === 'desc');
    }

    #[Computed]
    public function breadcrumbs(): Collection
    {
        $directory = $this->folder?->findDirectoryOrFail();

        if ($directory === null) {
            return collect();
        }

        $breadcrumbs = collect([
            new Breadcrumb(
                folderId: $directory->id,
                label: $directory->asFolder()->name,
            )
        ]);

        $directory = $directory->parentDirectory;

        while ($directory !== null) {
            $breadcrumbs->push(new Breadcrumb(
                folderId: $directory->id,
                label: $directory->asFolder()->name,
            ));

            $directory = $directory->parentDirectory;
        }

        return $breadcrumbs->reverse();
    }

    public function createFolderAction(): Action
    {
        return CreateFolder::make('createFolder')
            ->parentFolder($this->folder);
    }

    public function uploadFileAction(): Action
    {
        return UploadFile::make('uploadFile')
            ->parentFolder($this->folder)
            ->uploadForm($this->selectedSidebarItem?->uploadForm);
    }

    public function selectFileAction(): Action
    {
        return Action::make('selectFile')
            ->icon('heroicon-o-check-circle')
            ->action(fn () => $this->confirmSelection());
    }

    public function renameAction(): Action
    {
        return RenameFile::make('rename');
    }

    public function deleteAction(): Action
    {
        return DeleteFile::make('delete');
    }

    public function downloadFileAction(): Action
    {
        return DownloadFile::make('downloadFile');
    }

    public function shareFileAction(): Action
    {
        return ShareFile::make('shareFile');
    }

    public function previewFileAction(): Action
    {
        return PreviewFile::make('previewFile');
    }

    public function refreshFileAction(): Action
    {
        return RefreshFile::make('refreshFile');
    }

    public function deleteBulkAction(): Action
    {
        return DeleteBulk::make('deleteBulk');
    }

    public function downloadBulkAction(): Action
    {
        return DownloadBulk::make('downloadBulk');
    }

    public function deselectAllAction(): Action
    {
        return Action::make('deselectAll')
            ->label(__('cabinet::actions.deselect-all'))
            ->icon('heroicon-o-x-mark')
            ->color('gray')
            ->action(fn () => $this->clearSelection());
    }

    public function deselectAction(): Action
    {
        return Action::make('deselect')
            ->label(__('cabinet::actions.deselect'))
            ->icon('heroicon-o-x-mark')
            ->color('gray')
            ->action(function (array $arguments) {
                $this->deselectFile($arguments['source'], $arguments['id']);
                $this->syncUrlState();
            });
    }

    /**
     * Load file references for the detail panel.
     *
     * Cabinet resolves its own filerefs. Host applications can listen to the
     * `cabinet:file-references-loaded` browser event and inject additional
     * references by dispatching `cabinet:extra-references` with their data,
     * OR they can override this method by extending the Finder component.
     *
     * @return array<array{label: string, url: string|null, icon: string|null, typeLabel: string|null, thumbnailUrl: string|null}>
     */
    public function loadFileReferences(string $source, string $id): array
    {
        $file = Cabinet::file($source, $id);

        if ($file === null) {
            return [];
        }

        return \Cabinet\Facades\Cabinet::resolveFileReferences($file);
    }

    public function moveFileInSelection(int $from, int $to)
    {
        // As PHP:
        $file = $this->selectedFiles[$from];

        $filesWithoutMovedFile = collect($this->selectedFiles)
            ->filter(fn ($file, $index) => $index !== $from)
            ->values();

        $filesWithoutMovedFile->splice($to, 0, [$file]);

        $this->selectedFiles = $filesWithoutMovedFile->all();

        $this->skipRender();
    }

    /**
     * @return Action[]
     */
    public function getToolbarActions(): array
    {
        return [
            $this->uploadFileAction,
            $this->createFolderAction
        ];
    }

    #[Computed]
    public function contextMenus(): Collection
    {
        $menus = $this->files
            ->unique('type')
            ->mapWithKeys(fn (File|Folder $file) => [
                $file->type->slug() => match ($file->type::class) {
                    \Cabinet\Types\Folder::class => [
                        ContextMenuItem::fromAction($this->renameAction),
                        ContextMenuItem::fromAction($this->deleteAction)
                    ],
                    default => [
                        ContextMenuItem::fromAction($this->previewFileAction),
                        ContextMenuItem::fromAction($this->renameAction),
                        ContextMenuItem::fromAction($this->downloadFileAction),
                        ContextMenuItem::fromAction($this->shareFileAction),
                        ContextMenuItem::fromAction($this->refreshFileAction),
                        ContextMenuItem::fromAction($this->deleteAction)
                    ]
                }
            ])
            ->mapWithKeys(fn (array $items, string $type) => [
                $type => collect($items)
                    ->map(fn (Arrayable $item) => $item->toArray())
                    ->toArray()
            ]);

        // Add bulk context menu when files are selected in browse mode
        if (!$this->selectionMode && !empty($this->selectedFiles)) {
            $menus['bulk'] = [
                ContextMenuItem::fromAction($this->deselectAction)->toArray(),
                [
                    'label' => '',
                    'seperator' => true,
                ],
                ContextMenuItem::fromAction($this->downloadBulkAction)->toArray(),
                ContextMenuItem::fromAction($this->deleteBulkAction)->toArray(),
                ContextMenuItem::fromAction($this->deselectAllAction)->toArray(),
            ];
        }

        return $menus;
    }

    #[Computed]
    public function acceptableTypeChecker(): AcceptableTypeChecker
    {
        return new AcceptableTypeChecker(
            acceptableTypes: collect($this->acceptedTypes)
                ->map(fn (FileTypeDto $type) => $type->toFileType())
        );
    }

    #[Computed]
    public function globalAcceptableTypeChecker(): AcceptableTypeChecker
    {
        $types = Cabinet::validFileTypes()
            ->filter(fn (FileType $type) => !($type instanceof Other));

        return new AcceptableTypeChecker($types);
    }

    /**
     * Map internal source names to public URL-friendly slugs.
     * This keeps implementation details (like "spatie-media") out of URLs.
     */
    protected function publicSourceSlug(string $source): string
    {
        return match ($source) {
            'spatie-media' => 'media',
            default => $source,
        };
    }

    /**
     * Generate a stable signed thumbnail URL for a specific file.
     * Returns null if the cabinet.files.thumbnail route is not registered.
     *
     * @param string $variant 'normal' or 'tiny'
     */
    public function stableThumbnailUrl(string $source, string $id, ?string $variant = null): ?string
    {
        if (!app('router')->has('cabinet.files.thumbnail')) {
            return null;
        }

        $url = route('cabinet.files.thumbnail', [
            'source' => $this->publicSourceSlug($source),
            'id' => $id,
        ]);

        if ($variant !== null) {
            $url .= '?variant=' . $variant;
        }

        return \Cabinet\RollingSignature\Signature::url($url)->signedUrl();
    }

    /**
     * Generate a stable signed original file URL for a specific file.
     * Returns null if the cabinet.files.original route is not registered.
     */
    public function stableFileUrl(string $source, string $id): ?string
    {
        if (!app('router')->has('cabinet.files.original')) {
            return null;
        }

        return \Cabinet\RollingSignature\Signature::route('cabinet.files.original', [
            'source' => $this->publicSourceSlug($source),
            'id' => $id,
        ])->signedUrl();
    }

    /**
     * Generate a stable signed inline preview URL for a specific file.
     * Returns null if the cabinet.files.preview route is not registered.
     */
    public function stablePreviewUrl(string $source, string $id): ?string
    {
        if (!app('router')->has('cabinet.files.preview')) {
            return null;
        }

        return \Cabinet\RollingSignature\Signature::route('cabinet.files.preview', [
            'source' => $this->publicSourceSlug($source),
            'id' => $id,
        ])->signedUrl();
    }

	public function render()
    {
        // Auto-populate sidebar with root-level directories when enabled
        // and no explicit sidebar items have been provided.
        if ($this->autoSidebar && empty($this->sidebarItems)) {
            $directoryClass = config('cabinet.directory_model', \Cabinet\Models\Directory::class);

            $this->sidebarItems = $directoryClass::whereNull('parent_directory_id')
                ->get()
                ->map(fn ($directory) => new SidebarItemDto(
                    id: $directory->id,
                    label: $directory->translation_key
                        ? trans_choice($directory->translation_key, 9999)
                        : $directory->name,
                    icon: 'heroicon-o-folder',
                ))
                ->all();
        }

        $view = $this->modal
            ? 'cabinet-filament::livewire.finder-modal'
            : 'cabinet-filament::livewire.finder-page';

        $thumbnailUrls = [];
        $fileUrls = [];
        $previewUrls = [];

        foreach ($this->files as $fileOrFolder) {
            if ($fileOrFolder instanceof File) {
                $key = $fileOrFolder->source . ':' . $fileOrFolder->id;
                $thumbnailUrls[$key] = [
                    'normal' => $this->stableThumbnailUrl($fileOrFolder->source, $fileOrFolder->id, 'normal'),
                    'tiny' => $this->stableThumbnailUrl($fileOrFolder->source, $fileOrFolder->id, 'tiny'),
                ];
                $fileUrls[$key] = $this->stableFileUrl($fileOrFolder->source, $fileOrFolder->id);
                $previewUrls[$key] = $this->stablePreviewUrl($fileOrFolder->source, $fileOrFolder->id);
            }
        }

        $data = [
            'folder' => $this->folder,
            'acceptedTypeChecker' => $this->acceptableTypeChecker,
            'breadcrumbs' => $this->breadcrumbs,
            'files' => $this->files,
            'toolbarActions' => $this->getToolbarActions(),
            'contextMenus' => $this->contextMenus,
            'selectionMode' => $this->selectionMode,
            'sidebarItems' => collect($this->sidebarItems),
            'selectedSidebarItem' => $this->selectedSidebarItem,
            'thumbnailUrls' => $thumbnailUrls,
            'fileUrls' => $fileUrls,
            'previewUrls' => $previewUrls,
            'selectedFiles' => $this->selectedFiles,
            'treeSidebar' => $this->treeSidebar,
            'initialFolderId' => $this->initialFolderId,
            'lazyLoad' => $this->lazyLoad,
            'hasMoreFiles' => $this->lazyLoad && $this->totalFileCount > $this->fileLimit,
        ];

        return view($view, $data);
    }
}
