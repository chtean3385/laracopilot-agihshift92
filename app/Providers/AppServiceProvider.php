<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        \URL::forceScheme('https');

        try {
            $settings = Setting::first();
            View::share('settings', $settings);
        } catch (\Exception $e) {
            View::share('settings', null);
        }

        Blade::if('canDo', function (string $permission) {
            return PermissionService::check($permission);
        });
    }
}
