<?php

namespace Cabinet\Filament\Livewire;

use App\Services\Cabinet\IndoorScan;
use Cabinet\Facades\Cabinet;
use Cabinet\Filament\Livewire\Finder\Actions\CreateFolder;
use Cabinet\Filament\Livewire\Finder\Actions\DeleteFile;
use Cabinet\Filament\Livewire\Finder\Actions\DownloadFile;
use Cabinet\Filament\Livewire\Finder\Actions\PreviewFile;
use Cabinet\Filament\Livewire\Finder\Actions\RenameFile;
use Cabinet\Filament\Livewire\Finder\Actions\ShareFile;
use Cabinet\Filament\Livewire\Finder\Actions\UploadFile;
use Cabinet\Filament\Livewire\Finder\Breadcrumb;
use Cabinet\Filament\Livewire\Finder\ContextMenuItem;
use Cabinet\Filament\Livewire\Finder\SidebarItem;
use Cabinet\File;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

use Cabinet\Folder;

/**
 * @property-read Collection<File> $files
 * @property-read array $breadcrumbs
 * @property-read Collection<SidebarItem> $sidebarItems
 * @property-read SidebarItem|null $selectedSidebarItem
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

	public ?Finder\SelectionMode $selectionMode = null;

    public array $selectedFiles = [];

    #[On('open')]
    public function open(string $folderId, ?array $mode = null)
    {
        $folder = Cabinet::folder($folderId);

        abort_if($folder === null, 404);

//        abort_if(! $folder->canViewFiles(), 403);

        $this->initialFolderId = $folderId;
        $this->folderId = $folderId;

        $this->selectionMode = Finder\SelectionMode::fromLivewire($mode);
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
		$this->selectionMode = null;
        $this->selectedSidebarItemId = null;
        $this->selectedFiles = [];
	}

    public function confirmFileSelection()
    {
        if (!$this->selectionMode) {
            return;
        }

        $files = collect($this->selectedFiles)
            ->map(fn (array $file) => Cabinet::file($file['source'], $file['id']))
            //->filter(/** auth check */)
            ->filter()
            ->map(fn (File $file) => $file->toIdentifier());

        $livewireId = str($this->selectionMode->livewireId)
            ->lower();

        $this->dispatch(
            "cabinet:file-input:{$livewireId}:confirm",
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
    public function sidebarItems(): Collection
    {
        return collect([
//            SidebarItem::make('images')
//                ->label('Fotos')
//                ->icon('heroicon-o-photo')
//                ->folder('2534e05c-ea53-4e01-b4bf-fcf6ba76b3fd')
//                ->filterUsing(fn (Collection $files) => $files
//                    ->filter(fn (File|Folder $file) => $file->type->slug() === 'image' || $file->type instanceof \Cabinet\Types\Folder)
//                ),
//
//            SidebarItem::make('videos')
//                ->label('Videos')
//                ->icon('heroicon-o-video-camera')
//                ->filterUsing(fn (Collection $files) => $files
//                    ->filter(fn (File|Folder $file) => $file->type->slug() === 'external-video' || $file->type instanceof \Cabinet\Types\Folder)
//                ),
//
//            SidebarItem::make('floorplans')
//                ->label('Grundrisse')
//                ->icon('heroicon-o-map')
//                ->filterUsing(fn (Collection $files) => $files
//                    ->filter(fn (File|Folder $file) => $file->type->slug() === 'image' || $file->type instanceof \Cabinet\Types\Folder)
//                ),
//
//            SidebarItem::make('documents')
//                ->label('Dokumente & Zertifikate')
//                ->icon('heroicon-o-document-text')
//                ->filterUsing(fn (Collection $files) => $files
//                    ->filter(fn (File|Folder $file) => $file->type->slug() === 'image' || $file->type instanceof \Cabinet\Types\Folder)
//                ),
//
//            SidebarItem::make('3d-scans')
//                ->label('3D Scans')
//                ->icon('heroicon-o-cube-transparent')
//                ->filterUsing(fn (Collection $files) => $files
//                    ->filter(fn (File|Folder $file) => $file->type->slug() === 'indoor-scan' || $file->type instanceof \Cabinet\Types\Folder)
//                ),
//
//            SidebarItem::make('live-cams')
//                ->label('Baustellenkameras')
//                ->icon('heroicon-o-camera')
//                ->filterUsing(fn (Collection $files) => $files
//                    ->filter(fn (File|Folder $file) => $file->type->slug() === 'camera-feed' || $file->type instanceof \Cabinet\Types\Folder)
//                ),

            SidebarItem::make('test')
                ->label(fn () => 'test')
                ->icon('heroicon-o-building-office')
                ->folder('65235782-9771-4294-a5f2-314fd137ae61'),

            SidebarItem::make('estate')
                ->label(fn () => $this->initialFolder?->name)
                ->icon('heroicon-o-building-office')
                ->folder($this->initialFolderId),

            SidebarItem::make('my-storage')
                ->label('Meine Ablage')
                ->icon('heroicon-o-user')
                ->folder('5d877f36-db9b-4efc-9799-dc068cc41d2a')
        ]);
    }

    #[Computed]
    public function selectedSidebarItem(): ?SidebarItem
    {
        $breadcrumbs = array_reverse($this->breadcrumbs);

        $selectedItem = null;
        $closeness = null;

        // Go through the breadcrumbs and find item that's the closest to the current folder
        // or the current folder itself
        foreach ($this->sidebarItems as $item) {
            $folderId = $item->getFolderId();

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
    public function breadcrumbs(): array
    {
        $directory = $this->folder?->findDirectoryOrFail();

        if ($directory === null) {
            return [];
        }

        $breadcrumbs = [
            new Breadcrumb(
                folderId: $directory->id,
                label: $directory->asFolder()->name,
            )
        ];

        $directory = $directory->parentDirectory;

        while ($directory !== null) {
            $breadcrumbs[] = new Breadcrumb(
                folderId: $directory->id,
                label: $directory->asFolder()->name,
            );

            $directory = $directory->parentDirectory;
        }

        return array_reverse($breadcrumbs);
    }

    public function createFolderAction(): Action
    {
        return CreateFolder::make('createFolder')
            ->parentFolder($this->folder);
    }

    public function uploadFileAction(): Action
    {
        return UploadFile::make('uploadFile')
            ->parentFolder($this->folder);
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
                        ContextMenuItem::fromAction($this->selectFileAction),
                        ContextMenuItem::fromAction($this->previewFileAction),
                        ContextMenuItem::fromAction($this->renameAction),
                        ContextMenuItem::fromAction($this->downloadFileAction),
                        ContextMenuItem::fromAction($this->shareFileAction),
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

	public function render()
    {
        $view = $this->modal
            ? 'cabinet-filament::livewire.finder-modal'
            : 'cabinet-filament::livewire.finder-page';

        $data = [
            'folder' => $this->folder,
            'breadcrumbs' => $this->breadcrumbs,
            'files' => $this->files,
            'toolbarActions' => $this->getToolbarActions(),
            'contextMenus' => $this->contextMenus,
            'selectionMode' => $this->selectionMode,
            'sidebarItems' => $this->sidebarItems,
            'sidebarFilters' => $this->sidebarItems
                ->filter(fn (SidebarItem $item) => $item->hasFilter()),
            'sidebarLinks' => $this->sidebarItems
                ->filter(fn (SidebarItem $item) => !$item->hasFilter()),
            'selectedSidebarItem' => $this->selectedSidebarItem,
        ];

        return view($view, $data);
    }
}
