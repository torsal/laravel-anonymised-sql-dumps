<?php

namespace torsal\AnonymisedSQLDumps;

use Illuminate\Support\ServiceProvider;
use torsal\AnonymisedSQLDumps\Commands\ExportAnonymisedDB;
use torsal\AnonymisedSQLDumps\Commands\ConfigGenerate;

class AnonymisedSQLDumpsServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/anonymised-sql-dumps.php' => config_path('anonymised-sql-dumps.php'),
            ], 'config');
        }

        $this->app->bind('command.snapshot:create', ExportAnonymisedDB::class);
        $this->app->bind('command.config:generate', ConfigGenerate::class);
        $this->commands([
            'command.snapshot:create'
        ]); 
         $this->commands([
            'command.config:generate'
        ]);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/anonymised-sql-dumps.php', 'anonymised-sql-dumps');
    }

}