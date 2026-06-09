<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PlatformDisable2fa extends Command
{
    protected $signature = 'platform:disable-2fa {email : Email address of the super admin to clear 2FA for}';

    protected $description = 'Emergency: clear 2FA state for a platform super admin (allows password-only login + re-enrollment)';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = DB::table('users')
            ->where('email', $email)
            ->where('is_super_admin', 1)
            ->first();

        if (!$user) {
            $this->error("No super admin found with email: {$email}");
            return Command::FAILURE;
        }

        if (!$user->totp_enabled && !$user->totp_secret) {
            $this->warn("User {$email} does not have 2FA enabled — nothing to clear.");
            return Command::SUCCESS;
        }

        DB::table('users')->where('id', $user->id)->update([
            'totp_enabled' => false,
            'totp_secret'  => null,
            'updated_at'   => now(),
        ]);

        DB::table('platform_recovery_codes')->where('user_id', $user->id)->delete();

        $this->info("2FA cleared for {$email} (user ID: {$user->id}).");
        $this->info("They can now log in with their password only and re-enroll at /platform/settings/2fa.");

        return Command::SUCCESS;
    }
}
