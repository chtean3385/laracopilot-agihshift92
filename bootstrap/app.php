<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Copy OS-level env vars (injected by Replit) into $_ENV before phpdotenv runs.
// phpdotenv (createImmutable) checks $_ENV for existing vars — not getenv() —
// so without this, it silently overwrites Replit's injected vars with .env values.
foreach (['DB_CONNECTION','DATABASE_URL','DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD','DB_SSLMODE','APP_ENV','APP_DEBUG','APP_URL','APP_KEY'] as $_k) {
    if (($v = getenv($_k)) !== false && !isset($_ENV[$_k])) {
        $_ENV[$_k] = $_SERVER[$_k] = $v;
    }
}
unset($_k, $v);

// Force file-based sessions/cache for installer routes so the install page
// works on a fresh server with no database tables yet (runs before anything else).
if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/install')) {
    putenv('SESSION_DRIVER=file');
    putenv('CACHE_STORE=file');
    $_ENV['SESSION_DRIVER'] = $_SERVER['SESSION_DRIVER'] = 'file';
    $_ENV['CACHE_STORE']    = $_SERVER['CACHE_STORE']    = 'file';
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PREFIX);
        $middleware->web(append: [
            \App\Http\Middleware\SetHotelContext::class,
            \App\Http\Middleware\CheckTrialStatus::class,
        ]);
        $middleware->alias([
            'permission'     => \App\Http\Middleware\PermissionMiddleware::class,
            'not.installed'  => \App\Http\Middleware\CheckNotInstalled::class,
            'platform.admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    })->create();
