<?php

namespace Hanoivip\Epinkasa;

use Illuminate\Support\ServiceProvider;

class LibServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../lang' => resource_path('lang/vendor/hanoivip'),
            __DIR__.'/../config' => config_path(),
        ]);
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadTranslationsFrom( __DIR__.'/../lang', 'hanoivip');
        $this->mergeConfigFrom( __DIR__.'/../config/epinkasa.php', 'epinkasa');
    }
    
    public function register()
    {
        $this->commands([
        ]);
    }
}
