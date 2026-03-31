<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use BelongsToHotel;

    protected $fillable = ['hotel_id', 'name', 'description', 'is_system'];

    protected $casts = ['is_system' => 'boolean'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function permissionSlugs(): array
    {
        return $this->permissions->pluck('slug')->toArray();
    }
}
