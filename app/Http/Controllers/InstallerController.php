<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class InstallerController extends Controller
{
    public function __construct()
    {
        // Force file-based sessions and cache for ALL installer requests.
        // This allows the installer to work on a fresh server with no DB yet.
        config([
            'session.driver' => 'file',
            'cache.default'  => 'file',
        ]);
    }

    public function index()
    {
        return view('installer.index');
    }

    public function testDb(Request $request)
    {
        $host     = $request->input('db_host', '127.0.0.1');
        $port     = $request->input('db_port', '3306');
        $database = $request->input('db_database', '');
        $username = $request->input('db_username', '');
        $password = $request->input('db_password', '');

        if (!$database || !$username) {
            return response()->json(['ok' => false, 'message' => 'Database name and username are required.']);
        }

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);
            return response()->json(['ok' => true, 'message' => 'Connection successful! MySQL ' . $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION)]);
        } catch (\PDOException $e) {
            return response()->json(['ok' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
        }
    }

    public function run(Request $request)
    {
        $steps = [];

        $dbHost     = $request->input('db_host', '127.0.0.1');
        $dbPort     = $request->input('db_port', '3306');
        $dbDatabase = $request->input('db_database', '');
        $dbUsername = $request->input('db_username', '');
        $dbPassword = $request->input('db_password', '');
        $appName    = $request->input('app_name', 'Hotel CRM');
        $appUrl     = rtrim($request->input('app_url', 'http://localhost'), '/');
        $adminName  = $request->input('admin_name', '');
        $adminEmail = $request->input('admin_email', '');
        $adminPass  = $request->input('admin_password', '');

        // Step 1 — Write .env
        try {
            $this->writeEnv($dbHost, $dbPort, $dbDatabase, $dbUsername, $dbPassword, $appName, $appUrl);
            $steps[] = ['label' => 'Write configuration (.env)', 'ok' => true];
        } catch (\Throwable $e) {
            return $this->fail($steps, 'Write configuration (.env)', $e->getMessage());
        }

        // Reload env so artisan commands pick up new DB
        $this->reloadEnv();

        // Step 2 — Generate app key
        try {
            Artisan::call('key:generate', ['--force' => true]);
            $steps[] = ['label' => 'Generate application key', 'ok' => true];
        } catch (\Throwable $e) {
            return $this->fail($steps, 'Generate application key', $e->getMessage());
        }

        // Step 3 — Run migrations
        try {
            Artisan::call('migrate', ['--force' => true]);
            $steps[] = ['label' => 'Run database migrations', 'ok' => true];
        } catch (\Throwable $e) {
            return $this->fail($steps, 'Run database migrations', $e->getMessage());
        }

        // Step 3.5 — Create hotel record (must happen BEFORE seeds)
        try {
            $slug    = \Illuminate\Support\Str::slug($appName, '-') ?: 'hotel';
            $hotelId = \DB::table('hotels')->insertGetId([
                'name'       => $appName,
                'slug'       => $slug,
                'status'     => 'active',
                'plan'       => 'basic',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $steps[] = ['label' => 'Create hotel record', 'ok' => true];
        } catch (\Throwable $e) {
            return $this->fail($steps, 'Create hotel record', $e->getMessage());
        }

        // Step 4 — Seed modules
        try {
            Artisan::call('db:seed', ['--class' => 'ModuleSeeder', '--force' => true]);
            $steps[] = ['label' => 'Seed modules', 'ok' => true];
        } catch (\Throwable $e) {
            return $this->fail($steps, 'Seed modules', $e->getMessage());
        }

        // Step 5 — Seed roles & permissions
        try {
            Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder', '--force' => true]);
            $steps[] = ['label' => 'Seed roles & permissions', 'ok' => true];
        } catch (\Throwable $e) {
            return $this->fail($steps, 'Seed roles & permissions', $e->getMessage());
        }

        // Step 6 — Seed settings
        try {
            Artisan::call('db:seed', ['--class' => 'SettingSeeder', '--force' => true]);
            $steps[] = ['label' => 'Seed default settings', 'ok' => true];
        } catch (\Throwable $e) {
            return $this->fail($steps, 'Seed default settings', $e->getMessage());
        }

        // Step 7 — Seed WhatsApp templates
        try {
            Artisan::call('db:seed', ['--class' => 'WhatsAppTemplateSeeder', '--force' => true]);
            $steps[] = ['label' => 'Seed WhatsApp templates', 'ok' => true];
        } catch (\Throwable $e) {
            return $this->fail($steps, 'Seed WhatsApp templates', $e->getMessage());
        }

        // Step 8 — Create admin user
        try {
            $adminUser = User::updateOrCreate(
                ['email' => $adminEmail],
                [
                    'name'           => $adminName,
                    'password'       => Hash::make($adminPass),
                    'role'           => 'Admin',
                    'is_super_admin' => false,
                    'status'         => 'active',
                ]
            );
            $steps[] = ['label' => 'Create admin account', 'ok' => true];
        } catch (\Throwable $e) {
            return $this->fail($steps, 'Create admin account', $e->getMessage());
        }

        // Step 8.5 — Assign admin to hotel
        try {
            \App\Models\HotelUser::firstOrCreate(
                ['hotel_id' => $hotelId, 'user_id' => $adminUser->id],
                [
                    'hotel_id'       => $hotelId,
                    'user_id'        => $adminUser->id,
                    'role'           => 'Admin',
                    'is_hotel_admin' => true,
                    'status'         => 'active',
                ]
            );
            $steps[] = ['label' => 'Assign admin to hotel', 'ok' => true];
        } catch (\Throwable $e) {
            return $this->fail($steps, 'Assign admin to hotel', $e->getMessage());
        }

        // Step 9 — Storage link
        try {
            Artisan::call('storage:link');
            $steps[] = ['label' => 'Create storage symlink', 'ok' => true];
        } catch (\Throwable $e) {
            // Non-fatal — symlink may already exist
            $steps[] = ['label' => 'Create storage symlink', 'ok' => true, 'note' => 'Already exists'];
        }

        // Step 10 — Write lock file
        file_put_contents(storage_path('installed.lock'), date('Y-m-d H:i:s'));
        $steps[] = ['label' => 'Write installation lock', 'ok' => true];

        return response()->json(['ok' => true, 'steps' => $steps]);
    }

    private function fail(array $steps, string $label, string $error): \Illuminate\Http\JsonResponse
    {
        $steps[] = ['label' => $label, 'ok' => false, 'error' => $error];
        return response()->json(['ok' => false, 'steps' => $steps, 'error' => "{$label}: {$error}"]);
    }

    private function writeEnv(string $host, string $port, string $db, string $user, string $pass, string $appName, string $appUrl): void
    {
        $envPath = base_path('.env');
        $template = base_path('.env.example');

        $content = file_exists($envPath)
            ? file_get_contents($envPath)
            : (file_exists($template) ? file_get_contents($template) : '');

        $replacements = [
            'DB_CONNECTION'  => 'mysql',
            'DB_HOST'        => $host,
            'DB_PORT'        => $port,
            'DB_DATABASE'    => $db,
            'DB_USERNAME'    => $user,
            'DB_PASSWORD'    => $pass,
            'APP_NAME'       => '"' . addslashes($appName) . '"',
            'APP_URL'        => $appUrl,
            'APP_ENV'        => 'production',
            'APP_DEBUG'      => 'false',
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE'    => 'file',
        ];

        foreach ($replacements as $key => $value) {
            $pattern = '/^' . preg_quote($key, '/') . '=.*/m';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$value}", $content);
            } else {
                // Uncomment if commented out
                $commentPattern = '/^#\s*' . preg_quote($key, '/') . '=.*/m';
                if (preg_match($commentPattern, $content)) {
                    $content = preg_replace($commentPattern, "{$key}={$value}", $content);
                } else {
                    $content .= "\n{$key}={$value}";
                }
            }
        }

        file_put_contents($envPath, $content);
    }

    private function reloadEnv(): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            putenv("{$key}={$value}");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }

        // Reconfigure DB connection
        config([
            'database.default'                       => 'mysql',
            'database.connections.mysql.host'        => env('DB_HOST', '127.0.0.1'),
            'database.connections.mysql.port'        => env('DB_PORT', '3306'),
            'database.connections.mysql.database'    => env('DB_DATABASE', ''),
            'database.connections.mysql.username'    => env('DB_USERNAME', ''),
            'database.connections.mysql.password'    => env('DB_PASSWORD', ''),
        ]);
    }
}
