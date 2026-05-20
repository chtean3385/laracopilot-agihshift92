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
 *
 * guest_name regex note: always list "guest name" (compound) BEFORE bare
 * "guest" or "name" so "Guest Name: AMIT" captures "AMIT" not "Name".
 */
return [
    'parsers' => [

        /*
         * Custom forwarded format — emails forwarded/sent in the format:
         *   OTA: Goibibo
         *   Booking Ref: SDFSF-3385
         *   Guest Name: AMIT MAKWANA
         *   Check-In: 20 May 2026
         *   Check-Out: 21 May 2026
         *   Room: Double Room
         *   Amount: ₹2,500
         *   Guest Phone: +919725645543
         *
         * Matches any subject containing "booking confirmation" or "new booking"
         * from known forwarding addresses (configured in allowed_senders whitelist).
         * Also matches if body literally starts with "OTA:".
         */
        'custom_forwarded' => [
            'label'            => 'Custom',
            'subject_contains' => ['new booking confirmation', 'ota booking'],
            'from_contains'    => ['dreams-technology.com'],
            'regexes' => [
                'ota_source'  => '/^OTA\s*:\s*(.+)$/im',
                'booking_id'  => '/^Booking\s+Ref\s*:\s*([A-Za-z0-9\-]+)/im',
                'guest_name'  => '/^Guest\s+Name\s*:\s*([A-Za-z][A-Za-z \.\-\']{1,80})/im',
                'check_in'    => '/^Check[\s-]*In\s*:\s*([A-Za-z0-9 ,\/\-]{6,40})/im',
                'check_out'   => '/^Check[\s-]*Out\s*:\s*([A-Za-z0-9 ,\/\-]{6,40})/im',
                'room_type'   => '/^Room\s*:\s*([A-Za-z0-9 ,\/\-\(\)]{3,80})/im',
                'amount'      => '/^Amount\s*:\s*[₹Rs\.]*\s*([\d,]+(?:\.\d{1,2})?)/im',
                'guest_phone' => '/^Guest\s+Phone\s*:\s*\+?([0-9][\d\s\-]{6,20})/im',
                'guest_email' => '/^Guest\s+Email\s*:\s*([a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+)/im',
            ],
        ],

        'booking_com' => [
            'label'            => 'Booking.com',
            'subject_contains' => ['new booking', 'booking confirmation', 'booking.com'],
            'from_contains'    => ['booking.com', 'noreply@booking.com'],
            'regexes' => [
                'guest_name'  => '/(?:guest\s+name|guest|name)\s*:\s*([A-Za-z][A-Za-z \.\-\']{1,80})/i',
                'guest_email' => '/([a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+)/',
                'guest_phone' => '/(?:guest\s+phone|phone|mobile|tel)\s*:\s*\+?([0-9][\d\s\-]{6,20})/i',
                'check_in'    => '/(?:check[\s-]?in|arrival)\s*:\s*([A-Za-z0-9 ,\/\-]{6,40})/i',
                'check_out'   => '/(?:check[\s-]?out|departure)\s*:\s*([A-Za-z0-9 ,\/\-]{6,40})/i',
                'booking_id'  => '/(?:reservation|confirmation|booking)\s*(?:ref(?:erence)?|number|id|#|no\.?)\s*:\s*([A-Z0-9\-]{4,30})/i',
                'room_type'   => '/(?:room\s*type|room|accommodation)\s*:\s*([A-Za-z0-9 ,\/\-\(\)]{3,80})/i',
                'amount'      => '/(?:total|amount|price)\s*:\s*[€£$₹Rs\.]*\s*([\d,]+(?:\.\d{1,2})?)/i',
            ],
        ],

        'airbnb' => [
            'label'            => 'Airbnb',
            'subject_contains' => ['reservation confirmed', 'airbnb'],
            'from_contains'    => ['airbnb.com', 'automated@airbnb.com'],
            'regexes' => [
                'guest_name'  => '/(?:guest\s+name|guest|from)\s*:\s*([A-Za-z][A-Za-z \.\-\']{1,80})/i',
                'guest_email' => '/([a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+)/',
                'check_in'    => '/(?:check[\s-]?in|arrival)\s*:\s*([A-Za-z0-9 ,\/\-]{6,40})/i',
                'check_out'   => '/(?:check[\s-]?out|departure)\s*:\s*([A-Za-z0-9 ,\/\-]{6,40})/i',
                'booking_id'  => '/(?:confirmation\s*code|reservation\s*code)\s*:\s*([A-Z0-9]{6,20})/i',
                'room_type'   => '/(?:listing|room\s*type)\s*:\s*([A-Za-z0-9 ,\/\-\(\)]{3,80})/i',
                'amount'      => '/(?:total|amount|price)\s*:\s*[€£$₹Rs\.]*\s*([\d,]+(?:\.\d{1,2})?)/i',
            ],
        ],

        'makemytrip' => [
            'label'            => 'MakeMyTrip',
            'subject_contains' => ['mmt booking', 'makemytrip', 'booking confirmation - mmt'],
            'from_contains'    => ['makemytrip.com', 'mmt.com'],
            'regexes' => [
                'guest_name'  => '/(?:guest\s+name|guest|name)\s*:\s*([A-Za-z][A-Za-z \.\-\']{1,80})/i',
                'guest_email' => '/([a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+)/',
                'guest_phone' => '/(?:guest\s+phone|phone|mobile)\s*:\s*\+?([0-9][\d\s\-]{6,20})/i',
                'check_in'    => '/(?:check[\s-]?in|from)\s*:\s*([A-Za-z0-9 ,\/\-]{6,40})/i',
                'check_out'   => '/(?:check[\s-]?out|to)\s*:\s*([A-Za-z0-9 ,\/\-]{6,40})/i',
                'booking_id'  => '/(?:booking\s*id|reference)\s*:\s*(NHT?\w+|MMT\w+|[A-Z0-9]{6,20})/i',
                'room_type'   => '/(?:room\s*type|room)\s*:\s*([A-Za-z0-9 ,\/\-\(\)]{3,80})/i',
                'amount'      => '/(?:total|amount|price)\s*:\s*[₹Rs\.]*\s*([\d,]+(?:\.\d{1,2})?)/i',
            ],
        ],

        'goibibo' => [
            'label'            => 'Goibibo',
            'subject_contains' => ['goibibo booking', 'goibibo reservation', 'goibibo'],
            'from_contains'    => ['goibibo.com'],
            'regexes' => [
                'guest_name'  => '/(?:guest\s+name|guest|name)\s*:\s*([A-Za-z][A-Za-z \.\-\']{1,80})/i',
                'guest_email' => '/([a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+)/',
                'guest_phone' => '/(?:guest\s+phone|phone|mobile)\s*:\s*\+?([0-9][\d\s\-]{6,20})/i',
                'check_in'    => '/(?:check[\s-]?in|from)\s*:\s*([A-Za-z0-9 ,\/\-]{6,40})/i',
                'check_out'   => '/(?:check[\s-]?out|to)\s*:\s*([A-Za-z0-9 ,\/\-]{6,40})/i',
                'booking_id'  => '/(?:booking\s*id|booking\s*ref|reference)\s*:\s*([A-Za-z0-9\-]{4,30})/i',
                'room_type'   => '/(?:room\s*type|room)\s*:\s*([A-Za-z0-9 ,\/\-\(\)]{3,80})/i',
                'amount'      => '/(?:total|amount|price)\s*:\s*[₹Rs\.]*\s*([\d,]+(?:\.\d{1,2})?)/i',
            ],
        ],

        'agoda' => [
            'label'            => 'Agoda',
            'subject_contains' => ['agoda', 'agoda booking confirmation'],
            'from_contains'    => ['agoda.com'],
            'regexes' => [
                'guest_name'  => '/(?:guest\s+name|guest|name)\s*:\s*([A-Za-z][A-Za-z \.\-\']{1,80})/i',
                'guest_email' => '/([a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+)/',
                'check_in'    => '/(?:check[\s-]?in|arrival)\s*:\s*([A-Za-z0-9 ,\/\-]{6,40})/i',
                'check_out'   => '/(?:check[\s-]?out|departure)\s*:\s*([A-Za-z0-9 ,\/\-]{6,40})/i',
                'booking_id'  => '/(?:booking\s*id)\s*:\s*(\d{6,15})/i',
                'room_type'   => '/(?:room\s*type|room)\s*:\s*([A-Za-z0-9 ,\/\-\(\)]{3,80})/i',
                'amount'      => '/(?:total|amount|price)\s*:\s*[€£$₹Rs\.]*\s*([\d,]+(?:\.\d{1,2})?)/i',
            ],
        ],

        'expedia' => [
            'label'            => 'Expedia',
            'subject_contains' => ['expedia', 'expedia booking', 'itinerary'],
            'from_contains'    => ['expedia.com', 'expediapartnercentral.com'],
            'regexes' => [
                'guest_name'  => '/(?:guest\s+name|guest|name|traveler)\s*:\s*([A-Za-z][A-Za-z \.\-\']{1,80})/i',
                'guest_email' => '/([a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+)/',
                'check_in'    => '/(?:check[\s-]?in|arrival)\s*:\s*([A-Za-z0-9 ,\/\-]{6,40})/i',
                'check_out'   => '/(?:check[\s-]?out|departure)\s*:\s*([A-Za-z0-9 ,\/\-]{6,40})/i',
                'booking_id'  => '/(?:itinerary|confirmation|booking)\s*(?:number|id|#)\s*:\s*([A-Z0-9\-]{4,30})/i',
                'room_type'   => '/(?:room\s*type|room)\s*:\s*([A-Za-z0-9 ,\/\-\(\)]{3,80})/i',
                'amount'      => '/(?:total|amount|price)\s*:\s*[€£$₹Rs\.]*\s*([\d,]+(?:\.\d{1,2})?)/i',
            ],
        ],

    ],
];
