<?php

namespace Joemunapo\Whatsapp;

use Joemunapo\Whatsapp\Commands\WhatsappCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WhatsappServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('whatsapp-php')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_whatsapp-php_table')
            ->hasCommand(WhatsappCommand::class);
    }
}
