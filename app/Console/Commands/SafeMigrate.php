<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SafeMigrate extends Command
{
    protected $signature = 'app:safe-migrate';
    protected $description = 'Run migrations safely, handling pre-existing tables from failed deployments';

    public function handle(): int
    {
        $this->info('Checking database state...');

        try {
            // Ensure the migrations tracking table exists
            if (!Schema::hasTable('migrations')) {
                DB::statement('CREATE TABLE IF NOT EXISTS migrations (id serial primary key, migration varchar(255) not null, batch int not null)');
                $this->info('Created migrations tracking table.');
            }

            $trackedCount = DB::table('migrations')->count();
            $tablesExist  = Schema::hasTable('users');

            if ($trackedCount === 0 && $tablesExist) {
                // Orphaned state: tables exist from a failed/partial deployment
                // but none are tracked. Safest fix: wipe and start clean.
                $this->warn('Orphaned database state detected (tables exist but not tracked).');
                $this->warn('Dropping all tables and running fresh migrations...');
                $this->call('migrate:fresh', ['--force' => true]);
            } else {
                // Normal path: run only pending migrations
                $this->call('migrate', ['--force' => true]);
            }

            // Seed the platform superadmin if no users exist yet
            if (DB::table('users')->count() === 0) {
                $this->info('Seeding platform superadmin...');
                DB::table('users')->insert([
                    'name'           => 'Super Admin',
                    'email'          => 'superadmin@gmail.com',
                    'password'       => Hash::make('Super@#3385'),
                    'role'           => 'Super Admin',
                    'is_super_admin' => true,
                    'status'         => 'active',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
                $this->info('Superadmin created: superadmin@gmail.com');
            }

            // Seed platform plans if not seeded yet
            if (Schema::hasTable('platform_plans') && DB::table('platform_plans')->count() === 0) {
                $this->call('db:seed', ['--class' => 'PlatformPlanSeeder', '--force' => true]);
            }

        } catch (\Exception $e) {
            $this->error('SafeMigrate failed: ' . $e->getMessage());
            return 1;
        }

        $this->info('Database ready.');
        return 0;
    }
}
