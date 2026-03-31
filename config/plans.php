<?php

return [

    'basic' => [
        'label'            => 'Basic',
        'color'            => '#64748b',
        'badge_bg'         => '#f1f5f9',
        'badge_text'       => '#475569',
        'max_rooms'        => 50,
        'max_users'        => 10,
        'max_modules'      => 4,
        'features'         => [
            'Guest management',
            'Room management',
            'Booking & check-in/out',
            'Invoicing & payments',
            'Activity audit log',
            'WhatsApp messaging',
            'Basic reports',
        ],
        'limits_note'      => 'Up to 50 rooms, 10 staff users',
    ],

    'pro' => [
        'label'            => 'Pro',
        'color'            => '#0891b2',
        'badge_bg'         => '#cffafe',
        'badge_text'       => '#0e7490',
        'max_rooms'        => 150,
        'max_users'        => 25,
        'max_modules'      => PHP_INT_MAX,
        'features'         => [
            'Everything in Basic',
            'OTA Channel Manager',
            'Payment Links (QR / UPI)',
            'Pathik guest autofill',
            'Advanced reports & analytics',
            'Priority support',
        ],
        'limits_note'      => 'Up to 150 rooms, 25 staff users',
    ],

    'enterprise' => [
        'label'            => 'Enterprise',
        'color'            => '#7c3aed',
        'badge_bg'         => '#ede9fe',
        'badge_text'       => '#6d28d9',
        'max_rooms'        => PHP_INT_MAX,
        'max_users'        => PHP_INT_MAX,
        'max_modules'      => PHP_INT_MAX,
        'features'         => [
            'Everything in Pro',
            'Unlimited rooms & users',
            'Custom integrations',
            'Dedicated account manager',
            'SLA guarantee',
            'White-label branding',
        ],
        'limits_note'      => 'Unlimited rooms & users',
    ],

];
