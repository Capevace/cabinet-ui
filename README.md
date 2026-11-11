<div align="center">
	<h1>UI for Cabinet</h1>
    <h3>Pre-built Finder and file picker components for Cabinet, the streamlined file management solution for Laravel</h3><br />
	<div>
		<a 
			href="https://github.com/Capevace/cabinet/actions/workflows/test.yml"
		>
			<img
				src="https://github.com/Capevace/cabinet/actions/workflows/test.yml/badge.svg"
				alt="Run tests"
			/>
		</a>
		<a href="https://github.com/Capevace/cabinet/actions/workflows/test.yml">
			<img
				src="https://img.shields.io/badge/coverage-100%25-brightgreen"
				alt="Code coverage - 100%"
			/>
		</a>
		<img
			src="https://img.shields.io/github/v/release/capevace/cabinet?include_prereleases"
			alt="Latest release"
		/>
	</div>
	<h6><em>Made by <a href="https://mateffy.me">Lukas Mateffy</a></em></h6>

</div>
<br />


**Cabinet is a turn-key file management solution for Laravel, that enables attaching files to models. 
It integrates various file sources into a streamlined API and user interface (including disks, spatie/media-library, 
custom database tables, etc).**

> [!WARNING]  
> This package requires [capevace/cabinet](https://github.com/capevace/cabinet) to be installed.
> 
> See the [documentation](https://github.com/capevace/cabinet) for more information.

## Extending the finder

Apps can add their own actions to every finder from a service provider. A subclass of `Finder` is not necessary.

### Context menu entries

```php
use Cabinet\Filament\Livewire\Finder;
use Cabinet\Filament\Livewire\Finder\ContextMenuAction;

Finder::registerContextMenuAction(
    ContextMenuAction::make(fn (): Action => EditImageAction::make())
        ->menus(['image'])          // file type slugs, or ContextMenuAction::BULK_MENU
        ->after('previewFile')      // or ->first(); without it, the entry goes to the end
        ->visible(fn (Finder $finder): bool => $finder->selectionMode === null),
);
```

- The finder creates the action again for each request, so it keeps no state between requests.
- The entry shows only when the condition is true and the action is not hidden or unauthorized.
- The menus are built per file type, not per file. The action must check the file it gets in its arguments.
- The action name must not be the name of a built-in finder action.

### Actions without a menu entry

```php
Finder::registerAction(fn (): Action => CancelUploadAction::make());
```

A view (for example a render hook) mounts it with `$wire.mountAction('cancelUpload', { id: 1 })`.

### Render hooks

The finder renders Filament render hooks. Each hook gets the finder as `$data['finder']`.

```php
use Cabinet\Filament\View\FinderRenderHook;
use Filament\Support\Facades\FilamentView;

FilamentView::registerRenderHook(
    FinderRenderHook::GRID_ENTRIES_BEFORE, // <li> cards before the files; LIST_ENTRIES_BEFORE for <tr> rows
    fn (array $data): View => view('my-placeholders', ['finder' => $data['finder']]),
);
```
