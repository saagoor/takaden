<?php

namespace Takaden;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Takaden\Commands\TakadenCommand;

class TakadenServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('takaden')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_takaden_tables')
            ->hasRoute('web')
            ->hasCommand(TakadenCommand::class);
    }
}
