<?php

namespace App\Providers;

use Core\Modules\PunctualService\Models\PunctualService;
use Core\Modules\PunctualService\Policies\PunctualServicePolicy;
use Core\Modules\RecurringService\Models\RecurringService;
use Core\Modules\RecurringService\Policies\RecurringServicePolicy;
use Core\Modules\User\Models\User;
use Core\Modules\User\UserPolicy;
// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [

    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
    }
}
