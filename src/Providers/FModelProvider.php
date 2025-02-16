<?php

namespace Roddy\FirestoreEloquent\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Roddy\FirestoreEloquent\Console\Commands\MakeModel;

class FModelProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(\Illuminate\Routing\Router  $router, \Illuminate\Foundation\Http\Kernel $kernel)
    {
        app('router')->aliasMiddleware('f.auth', \Roddy\FirestoreEloquent\Middleware\F_Authentication::class);
        app('router')->aliasMiddleware('f.guest', \Roddy\FirestoreEloquent\Middleware\F_RedirectIfAuthenticated::class);

        $this->publishes([
            __DIR__ . '/../config/firebase.php' => config_path('firebase.php'),
        ]);

        $this->commands(
            MakeModel::class
        );

        Blade::if('fauth', function () {
            return fauth()->check();
        });
    }

    /**
     * Register any application services.
     * @return void
     */
    public function register(): void
    {
        /**
         * Merge the configuration from firebase.php file with the 'firebase' key.
         */
        $this->mergeConfigFrom(
            __DIR__ . '/../config/firebase.php',
            'firebase'
        );

        $this->app->config["filesystems.disks.firestore"] = [
            'driver' => 'local',
            'root' => storage_path('firestore'),
        ];

        $this->app->bind('fauth', function () {
            return new \Roddy\FirestoreEloquent\Auth\FAuth();
        });

        // Bind 'fmodel' to the service container to create a singleton instance of FModel
        // This allows accessing the FModel class through the service container or facade
        // The binding is used to resolve dependencies and create new instances when needed
        $this->app->bind('fmodel', function () {
            return $this->app->make('\Roddy\FirestoreEloquent\Firestore\Eloquent\FModel');
        });
    }
}
