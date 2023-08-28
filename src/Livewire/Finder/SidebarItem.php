<?php

namespace Cabinet\Filament\Livewire\Finder;

use Cabinet\Folder;
use Cabinet\Models\Directory;
use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Support\Collection;

class SidebarItem
{
    use EvaluatesClosures;

    protected Closure|string $label;
    protected Closure|string|null $icon = null;

    protected Closure|string|null $folderId = null;
    protected Closure|null $filter = null;

    public function __construct(public readonly string $id, Closure|string|null $label = null)
    {
        if ($label)
            $this->label = $label;
    }

    public static function make(string $id)
    {
        $static = app(static::class, ['id' => $id]);

        return $static;
    }

    public function label(Closure|string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function icon(Closure|string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function folder(Closure|Directory|Folder|string|null $folderId): static
    {
        $this->folderId = $folderId;

        return $this;
    }

    public function filterUsing(Closure|null $filter): static
    {
        $this->filter = $filter;

        return $this;
    }

    public function filterFiles(Collection $files): Collection
    {
        if ($this->filter === null)
            return $files;

        return $this->evaluate($this->filter, ['files' => $files]);
    }

    public function getLabel(): string
    {
        return $this->evaluate($this->label);
    }

    public function getIcon(): ?string
    {
        return $this->evaluate($this->icon);
    }

    public function getFolderId(): ?string
    {
        $folder = $this->evaluate($this->folderId);

        if ($folder instanceof Directory || $folder instanceof Folder) {
            return $folder->id;
        }

        return $folder;
    }

    public function hasFilter(): bool
    {
        return $this->filter !== null;
    }

    public function dto(): SidebarItemDto
    {
        return new SidebarItemDto(
            id: $this->getFolderId(),
            label: $this->getLabel(),
            icon: $this->getIcon(),
        );
    }
}
