<?php

/*
 * OTA Email Parser — rule-based detection.
 * Add a new OTA without touching code: just add a new entry below.
 *
 * Each parser entry has:
 *   subject_contains  — case-insensitive substrings; ANY match qualifies
 *   from_contains     — case-insensitive substrings of the sender address
 *   regexes           — named PCRE patterns whose first capture group
 *                       is stored in the parsed_data array
 *
 * Order matters: the first parser that matches subject OR sender wins.
 */
return [
    'parsers' => [

        'booking_com' => [
            'label'            => 'Booking.com',
            'subject_contains' => ['new booking', 'booking confirmation', 'booking.com'],
            'from_contains'    => ['booking.com', 'noreply@booking.com'],
            'regexes' => [
                'guest_name'       => '/(?:guest|name)[:\s]+([A-Za-z][A-Za-z \.\-\']{1,80})/i',
                'guest_email'      => '/([a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+)/',
                'guest_phone'      => '/(?:phone|mobile|tel)[:\s]+\+?([0-9][\d\s\-]{6,20})/i',
                'check_in'         => '/(?:check[\s-]?in|arrival)[:\s]+([A-Za-z0-9 ,\/\-]{6,40})/i',
                'check_out'        => '/(?:check[\s-]?out|departure)[:\s]+([A-Za-z0-9 ,\/\-]{6,40})/i',
                'booking_id'       => '/(?:reservation|confirmation|booking)\s*(?:ref(?:erence)?|number|id|#|no\.?)[:\s]*([A-Z0-9\-]{4,30})/i',
                'room_type'        => '/(?:room\s*type|room|accommodation)[:\s]+([A-Za-z0-9 ,\/\-\(\)]{3,80})/i',
                'amount'           => '/(?:total|amount|price)[:\s]*[€£$₹Rs\.]*\s*([\d,]+(?:\.\d{1,2})?)/i',
            ],
        ],

        'airbnb' => [
            'label'            => 'Airbnb',
            'subject_contains' => ['reservation confirmed', 'new booking', 'airbnb'],
            'from_contains'    => ['airbnb.com', 'automated@airbnb.com'],
            'regexes' => [
                'guest_name'  => '/(?:guest|from)[:\s]+([A-Za-z][A-Za-z \.\-\']{1,80})/i',
                'guest_email' => '/([a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+)/',
                'check_in'    => '/(?:check[\s-]?in|arrival)[:\s]+([A-Za-z0-9 ,\/\-]{6,40})/i',
                'check_out'   => '/(?:check[\s-]?out|departure)[:\s]+([A-Za-z0-9 ,\/\-]{6,40})/i',
                'booking_id'  => '/(?:confirmation\s*code|reservation\s*code)[:\s]*([A-Z0-9]{6,20})/i',
                'room_type'   => '/(?:listing|room\s*type)[:\s]+([A-Za-z0-9 ,\/\-\(\)]{3,80})/i',
                'amount'      => '/(?:total|amount|price)[:\s]*[€£$₹Rs\.]*\s*([\d,]+(?:\.\d{1,2})?)/i',
            ],
        ],

        'makemytrip' => [
            'label'            => 'MakeMyTrip',
            'subject_contains' => ['mmt booking', 'makemytrip', 'booking confirmation - mmt'],
            'from_contains'    => ['makemytrip.com', 'mmt.com'],
            'regexes' => [
                'guest_name'  => '/(?:guest|name)[:\s]+([A-Za-z][A-Za-z \.\-\']{1,80})/i',
                'guest_email' => '/([a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+)/',
                'guest_phone' => '/(?:phone|mobile)[:\s]+\+?([0-9][\d\s\-]{6,20})/i',
                'check_in'    => '/(?:check[\s-]?in|from)[:\s]+([A-Za-z0-9 ,\/\-]{6,40})/i',
                'check_out'   => '/(?:check[\s-]?out|to)[:\s]+([A-Za-z0-9 ,\/\-]{6,40})/i',
                'booking_id'  => '/(?:booking\s*id|reference)[:\s]*(NHT?\w+|MMT\w+|[A-Z0-9]{6,20})/i',
                'room_type'   => '/(?:room\s*type|room)[:\s]+([A-Za-z0-9 ,\/\-\(\)]{3,80})/i',
                'amount'      => '/(?:total|amount|price)[:\s]*[₹Rs\.]*\s*([\d,]+(?:\.\d{1,2})?)/i',
            ],
        ],

        'goibibo' => [
            'label'            => 'Goibibo',
            'subject_contains' => ['goibibo booking', 'goibibo reservation', 'goibibo'],
            'from_contains'    => ['goibibo.com'],
            'regexes' => [
                'guest_name'  => '/(?:guest|name)[:\s]+([A-Za-z][A-Za-z \.\-\']{1,80})/i',
                'guest_email' => '/([a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+)/',
                'guest_phone' => '/(?:phone|mobile)[:\s]+\+?([0-9][\d\s\-]{6,20})/i',
                'check_in'    => '/(?:check[\s-]?in|from)[:\s]+([A-Za-z0-9 ,\/\-]{6,40})/i',
                'check_out'   => '/(?:check[\s-]?out|to)[:\s]+([A-Za-z0-9 ,\/\-]{6,40})/i',
                'booking_id'  => '/(?:booking\s*id|reference)[:\s]*([A-Z0-9]{6,20})/i',
                'room_type'   => '/(?:room\s*type|room)[:\s]+([A-Za-z0-9 ,\/\-\(\)]{3,80})/i',
                'amount'      => '/(?:total|amount|price)[:\s]*[₹Rs\.]*\s*([\d,]+(?:\.\d{1,2})?)/i',
            ],
        ],

        'agoda' => [
            'label'            => 'Agoda',
            'subject_contains' => ['agoda', 'agoda booking confirmation'],
            'from_contains'    => ['agoda.com'],
            'regexes' => [
                'guest_name'  => '/(?:guest|name)[:\s]+([A-Za-z][A-Za-z \.\-\']{1,80})/i',
                'guest_email' => '/([a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+)/',
                'check_in'    => '/(?:check[\s-]?in|arrival)[:\s]+([A-Za-z0-9 ,\/\-]{6,40})/i',
                'check_out'   => '/(?:check[\s-]?out|departure)[:\s]+([A-Za-z0-9 ,\/\-]{6,40})/i',
                'booking_id'  => '/(?:booking\s*id)[:\s]*(\d{6,15})/i',
                'room_type'   => '/(?:room\s*type|room)[:\s]+([A-Za-z0-9 ,\/\-\(\)]{3,80})/i',
                'amount'      => '/(?:total|amount|price)[:\s]*[€£$₹Rs\.]*\s*([\d,]+(?:\.\d{1,2})?)/i',
            ],
        ],

        'expedia' => [
            'label'            => 'Expedia',
            'subject_contains' => ['expedia', 'expedia booking', 'itinerary'],
            'from_contains'    => ['expedia.com', 'expediapartnercentral.com'],
            'regexes' => [
                'guest_name'  => '/(?:guest|name|traveler)[:\s]+([A-Za-z][A-Za-z \.\-\']{1,80})/i',
                'guest_email' => '/([a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+)/',
                'check_in'    => '/(?:check[\s-]?in|arrival)[:\s]+([A-Za-z0-9 ,\/\-]{6,40})/i',
                'check_out'   => '/(?:check[\s-]?out|departure)[:\s]+([A-Za-z0-9 ,\/\-]{6,40})/i',
                'booking_id'  => '/(?:itinerary|confirmation|booking)\s*(?:number|id|#)[:\s]*([A-Z0-9\-]{4,30})/i',
                'room_type'   => '/(?:room\s*type|room)[:\s]+([A-Za-z0-9 ,\/\-\(\)]{3,80})/i',
                'amount'      => '/(?:total|amount|price)[:\s]*[€£$₹Rs\.]*\s*([\d,]+(?:\.\d{1,2})?)/i',
            ],
        ],

    ],
];
