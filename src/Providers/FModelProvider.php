<?php

namespace Roddy\FirestoreEloquent\Providers;

use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Roddy\FirestoreEloquent\Console\Commands\MakeModel;
use Roddy\FirestoreEloquent\FJavaScript;
use Roddy\FirestoreEloquent\FStyle;
use Roddy\FirestoreEloquent\Firestore\Url\SetLivewireUrl;

class FModelProvider extends ServiceProvider
{
    use FJavaScript;
    use FStyle;
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(\Illuminate\Routing\Router  $router, Kernel $kernel)
    {
        /**
         * Set Livewire Url
         */

        $kernel->appendMiddlewareToGroup('web', \Illuminate\Session\Middleware\StartSession::class);
        $kernel->appendMiddlewareToGroup('web', \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class);
        $kernel->appendMiddlewareToGroup('web', \Illuminate\View\Middleware\ShareErrorsFromSession::class);
        $kernel->appendMiddlewareToGroup('web', \Illuminate\Cookie\Middleware\EncryptCookies::class);
        $kernel->appendMiddlewareToGroup('web', \Illuminate\Routing\Middleware\SubstituteBindings::class);
        $kernel->appendMiddlewareToGroup('web', SetLivewireUrl::class);

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

        Blade::directive('fScriptsForLivewirePagination', function () {
            return $this->loadJavascriptForLivewirePagination();
        });

        Blade::directive('fStyleForLivewirePagination', function () {
            return $this->loadStylesForLivewirePagination();
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

        $this->app->bind('fmodel', function () {
            return $this->app->make('\Roddy\FirestoreEloquent\Firestore\Eloquent\FModel');
        });
    }
}
