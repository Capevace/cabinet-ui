<?php

namespace Cabinet\Filament\Livewire\Finder\Actions;

use Cabinet\Cabinet;
use Cabinet\Filament\Components\FilePreview;
use Cabinet\Filament\Livewire\Finder\Actions\Concerns\ValidatesFileAttributes;
use Cabinet\Folder;
use Closure;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Support\HtmlString;

class PreviewFile extends \Filament\Actions\Action
{
    use ValidatesFileAttributes;

    public static function getDefaultName(): ?string
    {
        return 'preview-file';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('cabinet::actions.preview'));
        $this->iconButton();
        $this->icon('heroicon-o-eye');

        $this->modalAlignment('center');

        $this->mountUsing(function (array $arguments, Cabinet $cabinet, Form $form, PreviewFile $action) {


//            $form->fill([
//                'url' => $file->url(),
//            ]);
        });

        $this->form(function (array $arguments, Cabinet $cabinet, PreviewFile $action) {
            $action->verifyFileArguments($arguments);

            if ($arguments['type'] === (new \Cabinet\Types\Folder)->slug()) {
                throw new \Exception('Cannot preview folders.');
            } else {
                $file = $cabinet->file($arguments['source'], $arguments['id']);
            }

            abort_if($file === null, 404);

            return [
                FilePreview::make($file)
            ];
        });

        $this->action(function (Cabinet $cabinet, array $data, array $arguments, PreviewFile $action) {

        });

        $this->modalSubmitAction(fn ($action) => $action->hidden());
        $this->modalCancelActionLabel(__('cabinet::actions.close'));
    }
}
