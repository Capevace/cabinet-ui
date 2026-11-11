<?php

namespace Cabinet\Filament\View;

/**
 * Render hooks of the finder, for Filament's `FilamentView::registerRenderHook()`.
 *
 * Each hook gets `$data['finder']`, the finder component that renders. Scopes
 * are the class of that component, so `scopes: Finder::class` limits a hook to
 * the finder itself.
 *
 * ```php
 * FilamentView::registerRenderHook(
 *     FinderRenderHook::GRID_ENTRIES_BEFORE,
 *     fn (array $data): View => view('files.placeholders', ['finder' => $data['finder']]),
 * );
 * ```
 */
class FinderRenderHook
{
    /**
     * Before the files of the folder in the grid view, inside the `<ul>`.
     * Render `<li>` cards.
     */
    public const GRID_ENTRIES_BEFORE = 'cabinet::finder.grid.entries.before';

    /**
     * Before the files of the folder in the list view, inside the `<tbody>`.
     * Render `<tr>` rows.
     */
    public const LIST_ENTRIES_BEFORE = 'cabinet::finder.list.entries.before';
}
