<?php

namespace Cabinet\Filament;

use Cabinet\Filament\Livewire\Finder;
use Filament\Support\Facades\FilamentView;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CabinetFilamentServiceProvider extends PackageServiceProvider
{
    public function register()
    {
        parent::register();
    }

    public function boot()
    {
        parent::boot();

        Livewire::component('cabinet::finder', Finder::class);

        FilamentView::registerRenderHook(
            'panels::body.end',
            fn () => view('cabinet-filament::global-finder')
        );
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('cabinet-filament')
            ->hasViews();
    }
}
