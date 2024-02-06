<?php

namespace Cabinet\Filament\Livewire;

use Cabinet\Facades\Cabinet;
use Cabinet\Filament\Livewire\Finder\AcceptableTypeChecker;
use Cabinet\Filament\Livewire\Finder\Actions\CreateFolder;
use Cabinet\Filament\Livewire\Finder\Actions\DeleteFile;
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
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

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
 */
class Finder extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    #[Locked]
    public bool $modal = true;

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

	public ?Finder\SelectionMode $selectionMode = null;

    public array $selectedFiles = [];

    public bool $showSidebar = true;

	public array $uploadedFiles = [];

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

        $this->sidebarItems = collect($sidebarItems)
            ->map(fn (array $item) => SidebarItemDto::fromLivewire($item))
            ->all();

        $this->acceptedTypes = collect($acceptedTypes)
            ->map(fn (array $type) => FileTypeDto::fromLivewire($type))
            ->filter()
            ->all();

        $this->selectionMode = Finder\SelectionMode::fromLivewire($mode);
        $this->selectedFiles = $selectedFiles;
    }

	public function updatedUploadedFiles()
	{
		$folder = $this->folder;
		$source = Cabinet::getSource(SpatieMediaSource::TYPE);
        $files = collect($this->uploadedFiles)
            // Make sure the file exists
            ->filter(fn (TemporaryUploadedFile $file) => $file->exists());

        if ($files->isEmpty()) {
            Notification::make()
                ->danger()
                ->title(__('cabinet::messages.no-files-uploaded'))
                ->send();

            return;
        }

        try {
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
				->each(function (TemporaryUploadedFile $file) use ($folder, $source) {
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

			unset($this->uploadedFiles);
			unset($this->folder);
			unset($this->files);
			unset($this->breadcrumbs);
        } catch (UnableToCheckFileExistence $exception) {
            Notification::make()
                ->danger()
                ->title(__('cabinet::messages.unknown-error'))
                ->body(app()->hasDebugModeEnabled() ? $$exception->getMessage() : null)
                ->send();
        }
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
    }

    public function confirmFileSelection()
    {
        if (!$this->selectionMode) {
            return;
        }

        $files = collect($this->selectedFiles)
            ->map(fn (array $file) => Cabinet::file($file['source'], $file['id']))
            ->filter(fn (File $file) => $this->acceptableTypeChecker->isAccepted($file->type))
            //->filter(/** TODO: fine-grained auth check */)
            ->filter()
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

    public function openFolder(string $id)
    {
        // only allow setting if the folder id is found in the current folder
        // or sidebar items

        if ($this->folder?->id === $id) {
            return;
        }

        $this->folderId = $id;
        unset($this->files);
        unset($this->folder);
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
        $files = $this->folder?->files() ?? collect();

        return $files;
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
        return $this->files
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
     * This URL can be used to load images in a more performant way, if you're using
     * S3 as your file backend. Normally signed URLs are used to load images, but
     * these will not be cached by the browser properly.
     *
     * All you need to do is create your own 'cabinet.files.thumbnail' route and
     * return the Image / URL (redirect to S3) from there. If you set Cache-Control
     * headers on this route properly, the redirect will be cached by the browser.
     */
    #[Computed]
    public function replaceableThumbnailUrl(): ?string
    {
        // check if the 'api.media.v1.cabinet.thumbnail' route exists
        // if it does, return the url

        if (app('router')->has('cabinet.files.thumbnail')) {
            return route('cabinet.files.thumbnail', [
                'source' => 'REPLACE_SOURCE',
                'id' => 'REPLACE_ID',
            ]);
        }

        return null;
    }

	public function render()
    {
        $view = $this->modal
            ? 'cabinet-filament::livewire.finder-modal'
            : 'cabinet-filament::livewire.finder-page';

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
            'replaceableThumbnailUrl' => $this->replaceableThumbnailUrl,
        ];

        return view($view, $data);
    }
}
