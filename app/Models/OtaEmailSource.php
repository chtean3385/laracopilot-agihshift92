<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtaEmailSource extends Model
{
    protected $table = 'ota_email_sources';

    protected $fillable = [
        'hotel_id',
        'inbound_email',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Find an active email source by the recipient "to" address.
     * Strips any Mailgun routing suffix (e.g. +tag) and lowercases for comparison.
     */
    public static function findByRecipient(string $toAddress): ?static
    {
        $normalised = strtolower(trim($toAddress));
        // Strip any sub-addressing like user+tag@domain → user@domain
        $normalised = preg_replace('/\+[^@]+@/', '@', $normalised);

        return static::where('is_active', true)
            ->whereRaw('LOWER(inbound_email) = ?', [$normalised])
            ->first();
    }
}
