<?php

namespace TriQuang\LaravelSolidMaker;

use Illuminate\Support\ServiceProvider;
use TriQuang\LaravelSolidMaker\Commands\MakeSolidScaffoldCommand;

class LaravelSolidMakerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            // Register the command
            $this->commands([
                MakeSolidScaffoldCommand::class,
            ]);

            // Publish stubs for customization
            $this->publishes([
                __DIR__ . '/../stubs' => base_path('stubs/vendor/triquang/laravel-solid-maker'),
            ], 'solid-stubs');
        }
    }

    public function register() {}
}
