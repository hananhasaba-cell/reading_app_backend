<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;



class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
   {
    //تحديد الصلاحيات
    Gate::define('manage-properties', function ($user) {
        return in_array($user->role, ['مالك شقة','مدير']);
    });

    Gate::define('admin-only', function ($user) {
        return $user->role === 'مدير';
    });
   // $this->authorize('manage-properties');
}
}