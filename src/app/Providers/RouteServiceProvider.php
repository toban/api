<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
      // In lumen this used to be in bootstrap....
      if( getenv( 'ROUTES_LOAD_WEB' ) == 1 ) {
        $this->mapApiRoutes();
      }
      if( getenv( 'ROUTES_LOAD_BACKEND' ) == 1 ) {
        $this->mapBackendRoutes();
      }
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        // Route::prefix('api')
        // throttle, 45 requests in 1 min
        Route::middleware(['cors', 'throttle:45,1'])
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

    protected function mapBackendRoutes()
    {
        Route::prefix('backend')
             ->middleware('backend.auth')
             ->namespace($this->namespace . '\Backend')
             ->group(base_path('routes/backend.php'));
    }
}
