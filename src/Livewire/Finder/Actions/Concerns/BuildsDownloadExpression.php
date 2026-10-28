<?php

declare(strict_types=1);

namespace Cabinet\Filament\Livewire\Finder\Actions\Concerns;

/**
 * Builds the JavaScript that downloads files from the browser.
 */
trait BuildsDownloadExpression
{
    /**
     * The expression that downloads every given file.
     *
     * Livewire evaluates an effect as the right side of an assignment, and it
     * only wraps it in a function when it starts with `let` or `const`: a bare
     * block would be parsed as an object literal. The loop gives each download
     * its own scope, so any number of files downloads from one expression.
     *
     * @param  array<int, array{url: string, name: string}>  $downloads
     */
    protected function downloadExpression(array $downloads): string
    {
        $entries = collect($downloads)
            ->map(fn (array $download) => "['{$download['url']}', '{$download['name']}']")
            ->join(', ');

        return <<<JS
            const downloads = [{$entries}];

            for (const [url, name] of downloads) {
                const a = document.createElement('a');
                a.href = url;
                a.download = name;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        JS;
    }
}
