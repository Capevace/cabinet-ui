<?php

namespace Cabinet\Filament\Livewire\Finder\Actions;

use Cabinet\Cabinet;
use Cabinet\Filament\Livewire\Finder\Actions\Concerns\HasFolder;
use Cabinet\Sources\Contracts\AcceptsData;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
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
            Select::make('form')
                ->hiddenLabel()
                ->label('Art')
                ->default('spatie-media')
                ->live()
                ->options(fn (Cabinet $cabinet) => $cabinet->getSourceOptions())
                ->selectablePlaceholder(false)
                ->required(),
            Group::make([])
                ->schema(fn (Get $get, Cabinet $cabinet) => $get('form') && ($form = $cabinet->getSourceForm($get('form')))
                    ? match ($form) {
                        'upload' => [
                            FileUpload::make('files')
                                ->label('Files')
                                ->multiple()
                                ->saveUploadedFileUsing(fn (Cabinet $cabinet, TemporaryUploadedFile $file) =>
                                    $this->upload($cabinet, $file)
                                )
                                ->required()
                        ],
                        default => $form ?? []
                    }
                    : []
                )

//            match ($get('form')) {
//                    'spatie-media' => [
//                        FileUpload::make('files')
//                            ->label('Files')
//                            ->multiple()
//                            ->saveUploadedFileUsing(fn (Cabinet $cabinet, TemporaryUploadedFile $file) =>
//                                $this->upload($cabinet, $file)
//                            )
//                            ->required()
//                    ],
//                    'youtube' => [
//                        TextInput::make('url')
//                            ->label('YouTube URL')
//                            ->placeholder('https://www.youtube.com/watch?v=...')
//                            ->url()
//                            ->helperText('YouTube URL einfügen')
//                            ->required()
//                    ],
//                    'matterport' => [
//                        TextInput::make('name')
//                            ->label('Name')
//                            ->placeholder('z.B. 2. Stockwerk')
//                            ->maxLength(255)
//                            ->required(),
//                        TextInput::make('url')
//                            ->label('Matterport URL')
//                            ->placeholder('https://my.matterport.com/show/?m=...')
//                            ->url()
//                            ->helperText('Matterport URL einfügen')
//                            ->required()
//                    ],
//                    'camera-feed' => [
//                        TextInput::make('name')
//                            ->label('Name')
//                            ->placeholder('z.B. Kranansicht Osten')
//                            ->maxLength(255)
//                            ->required(),
//                        TextInput::make('url')
//                            ->label('Kamera-URL')
//                            ->placeholder('https://...')
//                            ->url()
//                            ->helperText('Kamera-URL einfügen')
//                            ->required()
//                    ],
//                }
        ]);

        $this->action(function (Cabinet $cabinet, array $data, UploadFile $action) {
            $form = $data['form'];
            $folder = $action->getParentFolder();

            if ($form === 'spatie-media') {
                $action->success();
                return;
            }

            $source = $cabinet->getSource($form);

            if (in_array(AcceptsData::class, class_implements($source))) {
                /**
                 * @var AcceptsData $source
                 */
                $source->add($folder, $data);
            }

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
