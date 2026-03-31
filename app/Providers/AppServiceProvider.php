<?php

namespace App\Providers;

use App\Services\HotelContext;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register HotelContext as a singleton so it persists across the request
        $this->app->singleton(HotelContext::class);
    }

    public function boot(): void
    {
        // Fix "Specified key was too long" on MySQL < 5.7.7 / MariaDB < 10.2.2
        Schema::defaultStringLength(191);

        // Share hotel-scoped settings at view render time (after middleware has set context)
        View::composer('*', function ($view) {
            if (!$view->offsetExists('settings')) {
                try {
                    $view->with('settings', \App\Models\Setting::first());
                } catch (\Throwable $e) {
                    $view->with('settings', null);
                }
            }
        });

        Blade::directive('canDo', function (string $expression) {
            return "<?php if (\\App\\Services\\PermissionService::check({$expression})): ?>";
        });

        Blade::directive('endCanDo', function () {
            return '<?php endif; ?>';
        });
    }
}
