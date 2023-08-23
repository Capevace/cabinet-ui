<?php

namespace Cabinet\Filament\Livewire\Finder;

use Cabinet\FileType;
use Livewire\Wireable;
use Cabinet\Facades\Cabinet;

class SelectionMode implements Wireable
{
	public function __construct(
        public string $livewireId,
		public string $statePath,
		public ?array $acceptedTypes = null,
		public ?int $max = null
	)
	{
	}

	public function toLivewire()
    {
        return [
            'livewireId' => $this->livewireId,
        	'statePath' => $this->statePath,
        	'acceptedTypes' => collect($this->acceptedTypes)
                ->map(fn (FileType $type) => $type->slug())
                ->all(),
        	'max' => $this->max
        ];
    }

    public static function fromLivewire($data)
    {
    	$validFileTypes = Cabinet::validFileTypes();

    	$typeSlugs = $validFileTypes
    		->map(fn (FileType $type) => $type->slug())
    		->join(',');

        validator($data, [
            'livewireId' => ['required', 'string', 'max:255'],
        	'statePath' => ['required', 'string', 'max:255'],
        	'acceptedTypes' => ['present', 'nullable', 'array'],
        	'acceptedTypes.*' => ['required', 'string', 'in:' . $typeSlugs],
        	'max' => ['present', 'nullable', 'numeric', 'min:1']
        ])->validate();

        $selectedTypeSlugs = collect($data['acceptedTypes'] ?? []);

        return new self(
            livewireId: $data['livewireId'],
        	statePath: $data['statePath'],
        	acceptedTypes: $validFileTypes
        		->filter(fn (FileType $type) => $selectedTypeSlugs->contains($type->slug()))
        		->all(),
        	max: $data['max']
        );
    }
}
