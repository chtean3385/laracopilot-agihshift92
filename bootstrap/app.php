<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// When running CLI artisan commands (build or queue worker boot), check if Redis
// is actually reachable. If not (e.g. build container), force file/sync drivers
// BEFORE the bridging loop below — the loop skips keys already in $_ENV.
// ECONNREFUSED is instant so this adds zero perceptible latency.
if (PHP_SAPI === 'cli') {
    $_redis_sock = @fsockopen('127.0.0.1', 6379, $_redis_errno, $_redis_errstr, 0.5);
    if (!$_redis_sock) {
        foreach (['SESSION_DRIVER' => 'file', 'CACHE_STORE' => 'file',
                  'CACHE_DRIVER'   => 'file', 'QUEUE_CONNECTION' => 'sync'] as $_bk => $_bv) {
            putenv("{$_bk}={$_bv}");
            $_ENV[$_bk] = $_SERVER[$_bk] = $_bv;
        }
        unset($_bk, $_bv);
    } else {
        fclose($_redis_sock);
    }
    unset($_redis_sock, $_redis_errno, $_redis_errstr);
}

// Copy OS-level env vars (injected by Replit) into $_ENV before phpdotenv runs.
// phpdotenv (createImmutable) checks $_ENV for existing vars — not getenv() —
// so without this, it silently overwrites Replit's injected vars with .env values.
foreach ([
    // Database
    'DB_CONNECTION','DATABASE_URL','DB_HOST','DB_PORT','DB_DATABASE',
    'DB_USERNAME','DB_PASSWORD','DB_SSLMODE',
    // App
    'APP_ENV','APP_DEBUG','APP_URL','APP_KEY',
    'SESSION_DRIVER','CACHE_STORE','QUEUE_CONNECTION','REDIS_CLIENT',
    'REDIS_HOST','REDIS_PORT','REDIS_PASSWORD',
    // WhatsApp platform credentials (seeded into platform_whatsapp_settings on first boot)
    'WA_SAAS_TOKEN','WA_SAAS_PHONE_NUMBER_ID','WA_SAAS_WABA_ID',
    'WA_META_APP_ID','WA_META_APP_SECRET','WA_META_CONFIG_ID','WA_WEBHOOK_VERIFY_TOKEN',
    // Firebase credentials (seeded into platform_firebase_settings on first boot)
    'FIREBASE_PROJECT_ID','FIREBASE_API_KEY','FIREBASE_MESSAGING_SENDER_ID',
    'FIREBASE_APP_ID','FIREBASE_VAPID_KEY','FCM_SERVER_KEY','FIREBASE_SERVICE_ACCOUNT_JSON',
    // Mail
    'MAIL_PASSWORD',
    // Mailgun inbound parse webhook signing key (OTA email ingestion)
    'MAILGUN_WEBHOOK_SIGNING_KEY',
    // External cron trigger secret
    'CRON_SECRET',
] as $_k) {
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
        // Exclude all webhook endpoints and public booking widget from CSRF token validation
        $middleware->validateCsrfTokens(except: [
            'webhook/*',
            'book/*',
            'widget/*',
            'pricing/enquire',
            'g/*',
        ]);
        $middleware->alias([
            'permission'     => \App\Http\Middleware\PermissionMiddleware::class,
            'not.installed'  => \App\Http\Middleware\CheckNotInstalled::class,
            'platform.admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    })->create();
