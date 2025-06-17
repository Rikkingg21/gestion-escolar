<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot()
    {
        Gate::policy(User::class, UserPolicy::class);

        Blade::if('hasrole', function ($roles) {
            $currentRole = session('current_role');
            if (!$currentRole) return false;

            $rolesArray = is_array($roles) ? $roles : explode(',', $roles);
            return in_array($currentRole, $rolesArray);
        });
    }
}
