<?php

namespace Cabinet\Filament\Livewire\Finder\Actions;

use Cabinet\Cabinet;
use Cabinet\Filament\Livewire\Finder\Actions\Concerns\HasFolder;
use Cabinet\Sources\Contracts\AcceptsData;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UploadFile extends \Filament\Actions\Action
{
    use HasFolder;

    protected Closure|string $uploadForm = 'spatie-media';

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

        $this->mountUsing(function (Form $form, self $action) {
            $form->fill([
                'form' => $action->getUploadForm()
            ]);
        });

        $this->form([
            Select::make('form')
                ->hiddenLabel()
                ->label('Art')
                ->live()
                ->options(fn (Cabinet $cabinet) => $cabinet->getSourceOptions())
                ->selectablePlaceholder(false)
                ->required(),
            Group::make([])
                ->schema(fn (Get $get, Cabinet $cabinet, Group $component) => $get('form')
                    ? $cabinet->getSourceForm(
                        $get('form'),
                        fn () => FileUpload::make('files')
                            ->label('Dateien')
                            ->multiple()
                            ->saveUploadedFileUsing(fn (Cabinet $cabinet, TemporaryUploadedFile $file, Get $get) =>
                                $this->upload($cabinet, $file, $component->getState(), $get('form'))
                            )
                            ->required()
                        )
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

    public function upload(Cabinet $cabinet, TemporaryUploadedFile $file, array $data, string $source)
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
            ->getSource($source)
            ->upload($folder, $file, Arr::except($data, ['form', 'files']));

        $file->delete();

        return $cabinetFile->slug;
    }

    public function uploadForm(Closure|string|null $form): static
    {
        $this->uploadForm = $form ?? $this->uploadForm;

        return $this;
    }

    public function getUploadForm(): string
    {
        return $this->evaluate($this->uploadForm);
    }
}
