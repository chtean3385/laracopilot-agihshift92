<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['slug' => 'guests.view',       'label' => 'View Guests',           'module' => 'Guests',     'sort_order' => 1],
            ['slug' => 'guests.create',     'label' => 'Add Guests',            'module' => 'Guests',     'sort_order' => 2],
            ['slug' => 'guests.edit',       'label' => 'Edit Guests',           'module' => 'Guests',     'sort_order' => 3],
            ['slug' => 'guests.delete',     'label' => 'Delete Guests',         'module' => 'Guests',     'sort_order' => 4],

            ['slug' => 'rooms.view',        'label' => 'View Rooms',            'module' => 'Rooms',      'sort_order' => 5],
            ['slug' => 'rooms.create',      'label' => 'Add Rooms',             'module' => 'Rooms',      'sort_order' => 6],
            ['slug' => 'rooms.edit',        'label' => 'Edit Rooms',            'module' => 'Rooms',      'sort_order' => 7],
            ['slug' => 'rooms.delete',      'label' => 'Delete Rooms',          'module' => 'Rooms',      'sort_order' => 8],

            ['slug' => 'bookings.view',     'label' => 'View Bookings',         'module' => 'Bookings',   'sort_order' => 9],
            ['slug' => 'bookings.create',   'label' => 'Create Bookings',       'module' => 'Bookings',   'sort_order' => 10],
            ['slug' => 'bookings.edit',     'label' => 'Edit Bookings',         'module' => 'Bookings',   'sort_order' => 11],
            ['slug' => 'bookings.delete',   'label' => 'Delete Bookings',       'module' => 'Bookings',   'sort_order' => 12],

            ['slug' => 'checkin.process',   'label' => 'Process Check-In',      'module' => 'Operations', 'sort_order' => 13],
            ['slug' => 'checkout.process',  'label' => 'Process Check-Out',     'module' => 'Operations', 'sort_order' => 14],

            ['slug' => 'payments.view',     'label' => 'View Payments',         'module' => 'Payments',   'sort_order' => 15],
            ['slug' => 'payments.create',   'label' => 'Record Payments',       'module' => 'Payments',   'sort_order' => 16],
            ['slug' => 'payments.delete',   'label' => 'Delete Payments',       'module' => 'Payments',   'sort_order' => 17],

            ['slug' => 'invoices.view',     'label' => 'View Invoices',         'module' => 'Invoices',   'sort_order' => 18],
            ['slug' => 'invoices.delete',   'label' => 'Delete Invoices',       'module' => 'Invoices',   'sort_order' => 19],

            ['slug' => 'reports.view',      'label' => 'View Reports',          'module' => 'Reports',    'sort_order' => 20],

            ['slug' => 'settings.view',     'label' => 'View Settings',         'module' => 'Settings',   'sort_order' => 21],
            ['slug' => 'settings.edit',     'label' => 'Edit Settings',         'module' => 'Settings',   'sort_order' => 22],

            ['slug' => 'activity_log.view', 'label' => 'View Activity Log',     'module' => 'System',     'sort_order' => 23],

            ['slug' => 'roles.view',        'label' => 'View Roles & Permissions', 'module' => 'System',  'sort_order' => 24],
            ['slug' => 'roles.edit',        'label' => 'Edit Roles & Permissions', 'module' => 'System',  'sort_order' => 25],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        $roleMatrix = [
            'Admin' => [
                'description' => 'Full access except Roles & Permissions management',
                'is_system'   => true,
                'permissions' => [
                    'guests.view', 'guests.create', 'guests.edit', 'guests.delete',
                    'rooms.view', 'rooms.create', 'rooms.edit', 'rooms.delete',
                    'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.delete',
                    'checkin.process', 'checkout.process',
                    'payments.view', 'payments.create', 'payments.delete',
                    'invoices.view', 'invoices.delete',
                    'reports.view',
                    'settings.view', 'settings.edit',
                    'activity_log.view',
                ],
            ],
            'Manager' => [
                'description' => 'Operational access with reports, no delete or settings',
                'is_system'   => true,
                'permissions' => [
                    'guests.view', 'guests.create', 'guests.edit',
                    'rooms.view', 'rooms.create', 'rooms.edit',
                    'bookings.view', 'bookings.create', 'bookings.edit',
                    'checkin.process', 'checkout.process',
                    'payments.view', 'payments.create',
                    'invoices.view',
                    'reports.view',
                ],
            ],
            'Receptionist' => [
                'description' => 'Day-to-day front desk operations only',
                'is_system'   => true,
                'permissions' => [
                    'guests.view', 'guests.create',
                    'rooms.view',
                    'bookings.view', 'bookings.create',
                    'checkin.process', 'checkout.process',
                    'payments.view', 'payments.create',
                    'invoices.view',
                ],
            ],
        ];

        foreach ($roleMatrix as $name => $config) {
            $role = Role::firstOrCreate(
                ['name' => $name],
                ['description' => $config['description'], 'is_system' => $config['is_system']]
            );

            $permIds = Permission::whereIn('slug', $config['permissions'])->pluck('id');
            $role->permissions()->sync($permIds);
        }
    }
}
