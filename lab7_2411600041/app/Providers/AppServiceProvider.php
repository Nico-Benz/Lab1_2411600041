<?php

namespace App\Providers;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Policies\PurchaseOrderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gate: only admins can use admin-only features (suppliers CRUD, users, adjustments...)
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        // Policy: all the purchase order rules (view, update, delete, approve, receive...) live in one class
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
    }
}
