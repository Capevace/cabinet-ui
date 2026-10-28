<?php

namespace Cabinet\Filament\Livewire\Finder\Actions;

use Filament\Actions\Action;
use Cabinet\Facades\Cabinet;
use Cabinet\Filament\Livewire\Finder;
use Cabinet\Filament\Livewire\Finder\Actions\Concerns\BuildsDownloadExpression;

class DownloadBulk extends Action
{
    use BuildsDownloadExpression;

    public static function getDefaultName(): ?string
    {
        return 'download-bulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('cabinet::actions.download-bulk'));
        $this->icon('heroicon-o-arrow-down-tray');

        $this->action(function (array $arguments, Finder $livewire) {
            $downloads = [];

            foreach ($livewire->bulkActionFiles($arguments) as $identifier) {
                $file = Cabinet::file($identifier['source'], $identifier['id']);

                if ($file === null) {
                    continue;
                }

                $ext = str($file->path())->afterLast('.')->toString();

                $downloads[] = [
                    'url' => Cabinet::generateDownloadUrl($file),
                    'name' => str($file->name)
                        ->replace('\'', '')
                        ->append(".{$ext}")
                        ->slug()
                        ->toString(),
                ];
            }

            if ($downloads !== []) {
                $livewire->js(expression: $this->downloadExpression($downloads));
            }
        });
    }
}
