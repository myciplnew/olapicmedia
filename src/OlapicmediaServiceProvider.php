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
            __DIR__.'/views' => base_path('resources/views/myciplnew/olapicmedia'),
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
