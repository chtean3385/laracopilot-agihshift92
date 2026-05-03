<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class HotelEmailConfig extends Model
{
    use BelongsToHotel;

    protected $table = 'hotel_email_configs';

    protected $fillable = [
        'hotel_id',
        'email_address',
        'email_password',
        'imap_host',
        'imap_port',
        'encryption',
        'folder_to_watch',
        'last_synced_at',
        'is_active',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_synced_at' => 'datetime',
        'imap_port'      => 'integer',
    ];

    /**
     * Encrypt the password automatically when set.
     */
    public function setEmailPasswordAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            return; // keep existing
        }
        $this->attributes['email_password'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt on access. If decryption fails (legacy plain text), return raw.
     */
    public function getDecryptedPassword(): ?string
    {
        $value = $this->attributes['email_password'] ?? null;
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}
