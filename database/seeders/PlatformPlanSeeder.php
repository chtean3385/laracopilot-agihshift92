<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug'          => 'basic',
                'label'         => 'Basic',
                'color'         => '#64748b',
                'monthly_price' => 999,
                'yearly_price'  => 9999,
                'max_rooms'     => 50,
                'max_users'     => 10,
                'features'      => json_encode([
                    'Guest management',
                    'Room management',
                    'Booking & check-in/out',
                    'Invoicing & payments',
                    'Activity audit log',
                    'WhatsApp messaging',
                    'Basic reports',
                ]),
                'is_active'     => true,
                'sort_order'    => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'slug'          => 'standard',
                'label'         => 'Standard',
                'color'         => '#0891b2',
                'monthly_price' => 1999,
                'yearly_price'  => 19999,
                'max_rooms'     => 100,
                'max_users'     => 20,
                'features'      => json_encode([
                    'Everything in Basic',
                    'WhatsApp Automation',
                    'Payment Links (QR / UPI)',
                    'Advanced reports',
                    'Email support',
                ]),
                'is_active'     => true,
                'sort_order'    => 2,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'slug'          => 'premium',
                'label'         => 'Premium',
                'color'         => '#7c3aed',
                'monthly_price' => 2999,
                'yearly_price'  => 29999,
                'max_rooms'     => 200,
                'max_users'     => 50,
                'features'      => json_encode([
                    'Everything in Standard',
                    'OTA Channel Manager',
                    'Pathik guest autofill',
                    'Priority support',
                    'Custom branding',
                ]),
                'is_active'     => true,
                'sort_order'    => 3,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'slug'          => 'pro_ai',
                'label'         => 'Pro AI',
                'color'         => '#d97706',
                'monthly_price' => 7999,
                'yearly_price'  => 79999,
                'max_rooms'     => 9999,
                'max_users'     => 9999,
                'features'      => json_encode([
                    'Everything in Premium',
                    'AI-powered analytics',
                    'Unlimited rooms & users',
                    'Dedicated account manager',
                    'SLA guarantee',
                    'White-label branding',
                ]),
                'is_active'     => true,
                'sort_order'    => 4,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('platform_plans')->updateOrInsert(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
