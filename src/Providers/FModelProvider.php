<?php
namespace Roddy\FirestoreEloquent\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Roddy\FirestoreEloquent\Console\Commands\MakeModel;
use Roddy\FirestoreEloquent\FJavaScript;
use Roddy\FirestoreEloquent\FStyle;

class FModelProvider extends ServiceProvider
{
    use FJavaScript;
    use FStyle;
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(\Illuminate\Routing\Router  $router)
    {
        /**
         * FILEPATH: /vendor/roddy/firestore-eloquent/src/Providers/FModelProvider.php
         *
         * Registers middleware aliases for F_Authentication and F_RedirectIfAuthenticated.
         * Publishes the firebase.php config file to the application's config directory.
         * Registers the MakeModel command.
         * Defines a Blade directive for checking if the user is authenticated with fauth().
         */
        $router->middlewareGroup('web', [
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Roddy\FirestoreEloquent\Firestore\Url\SetLivewireUrl::class,
        ]);

        app('router')->aliasMiddleware('f.auth', \Roddy\FirestoreEloquent\Middleware\F_Authentication::class);
        app('router')->aliasMiddleware('f.guest', \Roddy\FirestoreEloquent\Middleware\F_RedirectIfAuthenticated::class);

        $this->publishes([
            __DIR__.'/../config/firebase.php' => config_path('firebase.php'),
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
            __DIR__.'/../config/firebase.php', 'firebase'
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
