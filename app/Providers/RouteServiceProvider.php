<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->routes(function () {

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));


            Route::prefix('api/transactions')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('src/Modules/Transaction/TransactionRoutes.php'));

            Route::prefix('api/punctual/services')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('src/Modules/PunctualService/PunctualServiceRoutes.php'));

            Route::prefix('api/wallets')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('src/Modules/Wallet/WalletRoutes.php'));




            Route::prefix('api/trash')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('src/Modules/Trash/TrashRoutes.php'));


            Route::prefix('api/partners')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('src/Modules/Partners/PartnersRoute.php'));

            Route::prefix('api/notifications')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('src/Modules/Notification/NotificationRoutes.php'));

            Route::prefix('api/focal-points')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('src/Modules/FocalPoints/FocalPointsRoute.php'));


            Route::prefix('api/mail')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('src/Modules/Email/EmailRoutes.php'));


            Route::prefix('api/trainers')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('src/Modules/Trainers/TrainersRoutes.php'));

            Route::prefix('api/recurring/services')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('src/Modules/RecurringService/RecurringServiceRoutes.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
