<?php

namespace Cabinet\Filament\Livewire\Finder\Actions;

use Filament\Actions\Action;
use Cabinet\Cabinet;
use Cabinet\Filament\Livewire\Finder\Actions\Concerns\ValidatesFileAttributes;
use Cabinet\File;
use Cabinet\Folder;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Component;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RefreshFile extends Action
{
    use ValidatesFileAttributes;

    public static function getDefaultName(): ?string
    {
        return 'refreshFile';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('cabinet::actions.refresh-file'));
        $this->iconButton();
        $this->icon('heroicon-o-arrow-path-rounded-square');

        $this->requiresConfirmation();
//        $this->modalSubmitActionLabel(__('cabinet::actions.refresh-file'));

        $this->action(function (Cabinet $cabinet, array $data, array $arguments, self $action, FileManipulator $fileManipulator) {
            $action->verifyFileArguments($arguments);

            try {
                if ($arguments['type'] === (new \Cabinet\Types\Folder)->slug()) {
                    $folder = $cabinet->folder($arguments['id']);

                    $results = $this->traverseFolder($folder)
                        ->map(fn (File $file) => $file->model())
                        ->filter(fn (Model $model) => ($model instanceof Media))
                        ->map(fn (Media $media) => $this->processMedia($media));

                    if ($results->count() === 0) {
                        $this
                            ->failureNotificationTitle(trans_choice('cabinet::messages.artefact-file-count', 0))
                            ->failure();
                    } else {
                        $this
                            ->successNotification(
                                Notification::make()
                                    ->success()
                                    ->title(__('cabinet::messages.artefact-regeneration-confirmed'))
                                    ->body(trans_choice('cabinet::messages.artefact-file-count', 1))
                            )
                            ->success();
                    }
                } else {
                    $file = $cabinet->file($arguments['source'], $arguments['id']);
                    $media = $file->model();

                    if ($media === null || !($media instanceof Media)) {
                        $this
                            ->failureNotificationTitle(__('cabinet::messages.not-eligble-for-artifact-regeneration'))
                            ->failure();

                        return;
                    }

                    $this->processMedia($media);

                    $this
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('cabinet::messages.artefact-regeneration-confirmed'))
                                ->body(trans_choice('cabinet::messages.artefact-file-count', 1))
                        )
                        ->success();
                }
            } catch (Exception $exception) {
                $this
                    ->failureNotificationTitle(
                    app()->hasDebugModeEnabled()
                            ? $exception->getMessage()
                            : __('cabinet::messages.unknown-error')
                    )
                    ->failure();
            }
        });
    }

    protected function processMedia(Media $media): void
    {
        app(FileManipulator::class)->createDerivedFiles($media);
    }

    protected function traverseFolder(Folder $folder, bool $deep = true): Collection
    {
        return $folder
            ->files()
            ->dd()
            ->flatMap(fn (File|Folder $file) => $file instanceof Folder
                ? $this->traverseFolder($file)
                : $file
            );
    }
}
