<?php

namespace Myciplnew\Olapicmedia;

use Illuminate\Support\ServiceProvider;

class OlapicmediaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__.'/routes/web.php';
        $this->loadViewsFrom(__DIR__.'/views', 'olapicmedia');
        $this->publishes([
            __DIR__.'/Commands' => base_path('app/Console/Commands'),
            __DIR__.'/Database/Migrations' => base_path('Database/Migrations'),
            __DIR__.'/Config' => base_path('Config'),
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Myciplnew\Olapicmedia\OlapicmediaController');
    }
}
