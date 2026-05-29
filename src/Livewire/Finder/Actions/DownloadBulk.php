<?php

namespace Cabinet\Filament\Livewire\Finder\Actions;

use Filament\Actions\Action;
use Cabinet\Facades\Cabinet;
use Cabinet\Filament\Livewire\Finder;
use Livewire\Component;

class DownloadBulk extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'download-bulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('cabinet::actions.download-bulk'));
        $this->icon('heroicon-o-arrow-down-tray');

        $this->action(function (array $arguments, Component $livewire) {
            $files = $arguments['files'] ?? [];
            $jsExpressions = [];

            foreach ($files as $identifier) {
                $file = Cabinet::file($identifier['source'], $identifier['id']);

                if ($file === null) {
                    continue;
                }

                $ext = str($file->path())->afterLast('.')->toString();
                $url = Cabinet::generateDownloadUrl($file);

                $name = str($file->name)
                    ->replace('\'', '')
                    ->append(".{$ext}")
                    ->slug()
                    ->toString();

                $jsExpressions[] = <<<JS
                    browser.downloads.download({
                        url: '{$url}',
                        filename: '{$name}',
                    });
                JS;
            }

            if (!empty($jsExpressions)) {
                $livewire->js(expression: implode("\n", $jsExpressions));
            }
        });
    }
}
