<?php

namespace Cabinet\Filament;

use Cabinet\Filament\Livewire\Finder;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
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
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('cabinet-filament')
            ->hasViews();
    }
}
