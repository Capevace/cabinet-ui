<?php

namespace Cabinet\Filament\Livewire\Finder\Actions;

use Cabinet\Cabinet;
use Cabinet\Filament\Livewire\Finder\Actions\Concerns\HasFolder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UploadFile extends \Filament\Actions\Action
{
    use HasFolder;
    public static function getDefaultName(): ?string
    {
        return 'uploadFile';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('cabinet::actions.upload-file'));
        $this->tooltip(__('cabinet::actions.upload-file'));
        $this->iconButton();
        $this->icon('heroicon-o-arrow-up-tray');

        $this->modalWidth('sm');
        $this->modalAlignment('center');
        $this->extraAttributes([
            'x-on:click' => new HtmlString("setTimeout(() => document.getElementById('mountedActionsData.0.name').focus(), 200)")
        ]);

        $this->form([
            FileUpload::make('files')
                ->label('Files')
                ->multiple()
                ->saveUploadedFileUsing(fn (Cabinet $cabinet, TemporaryUploadedFile $file) =>
                    $this->upload($cabinet, $file)
                )
                ->required()
        ]);

        $this->action(function (Cabinet $cabinet, array $data, UploadFile $action) {
            $action->success();
        });
    }

    public function upload(Cabinet $cabinet, TemporaryUploadedFile $file)
    {
        $folder = $this->getParentFolder();

        if (!$folder) {
            $file->delete();

            return null;
        }

        try {
            if (!$file->exists()) {
                return null;
            }
        } catch (UnableToCheckFileExistence $exception) {
            return null;
        }

        $cabinetFile = $cabinet
            ->getSource('spatie-media')
            ->upload($folder, $file);

        $file->delete();

        return $cabinetFile->slug;
    }
}
