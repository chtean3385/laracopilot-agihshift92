<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Fix "Specified key was too long" on MySQL < 5.7.7 / MariaDB < 10.2.2
        Schema::defaultStringLength(191);

        try {
            $settings = Setting::first();
            View::share('settings', $settings);
        } catch (\Exception $e) {
            View::share('settings', null);
        }

        Blade::directive('canDo', function (string $expression) {
            return "<?php if (\\App\\Services\\PermissionService::check({$expression})): ?>";
        });

        Blade::directive('endCanDo', function () {
            return '<?php endif; ?>';
        });
    }
}
