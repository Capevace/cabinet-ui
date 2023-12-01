<?php

namespace Cabinet\Filament\Livewire\Finder\Actions;

use Cabinet\Cabinet;
use Cabinet\Filament\Livewire\Finder\Actions\Concerns\ValidatesFileAttributes;
use Cabinet\Folder;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

class DownloadFile extends \Filament\Actions\Action
{
    use ValidatesFileAttributes;

    public static function getDefaultName(): ?string
    {
        return 'download-file';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('cabinet::actions.download'));
        $this->iconButton();
        $this->icon('heroicon-o-arrow-down-tray');

        $this->action(function (Cabinet $cabinet, array $data, array $arguments, DownloadFile $action) {
//            $action->verifyFileArguments($arguments);

            if ($arguments['type'] === (new \Cabinet\Types\Folder)->slug()) {
                $action
                    ->failureNotificationTitle(__('cabinet::messages.cannot-download-folder'))
                    ->failure();
            } else {
                $file = $cabinet->file($arguments['source'], $arguments['id']);
            }

            abort_if($file === null, 404);

            return redirect($file->url());
        });
    }
}
