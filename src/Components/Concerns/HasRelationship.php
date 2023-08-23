<?php

namespace Cabinet\Filament\Components\Concerns;

use Cabinet\Facades\Cabinet;
use Cabinet\Filament\Components\FileInput;
use Cabinet\HasFiles;
use Cabinet\Models\FileRef;
use Closure;
use Illuminate\Database\Eloquent\Collection;

trait HasRelationship
{
    protected Closure|string|null $relationship = null;

    public function relationship(Closure|string|null $relationship = null): static
    {
        $this->relationship = $relationship ?? $this->getName();

        $this->loadStateFromRelationshipsUsing(function (FileInput $component, HasFiles $record) {
            $relationship = $component->getRelationship();

            if($relationship === null)
                return;

            if ($component->hasMultiple()) {
                $files = $record->{$relationship}()
                    ->get()
                    ->map(fn (FileRef $ref) => $ref->file()->toIdentifier())
                    ->all();

                $component->state($files);
            } else {
                $file = $record->{$relationship}()
                    ->first()
                    ?->file()
                    ->toIdentifier();

                $component->state($file);
            }
        });

        $this->saveRelationshipsUsing(function (FileInput $component, HasFiles $record, ?array $state) {
            $relationship = $component->getRelationship();

            if($relationship === null)
                return;

            if ($component->hasMultiple()) {
                $files = collect($state)
                    ->map(fn (array $file) => Cabinet::file($file['source'], $file['id']))
                    ->filter()
                    ->values();

                Cabinet::syncMany($record, $relationship, $files);
            } else {
                $file = $state
                    ? Cabinet::file($state['source'], $state['id'])
                    : null;

                // Morphone
                Cabinet::syncOne($record, $relationship, $file);
            }
        });

        return $this;
    }

    public function getRelationship(): ?string
    {
        return $this->evaluate($this->relationship);
    }
}
