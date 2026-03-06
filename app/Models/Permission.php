<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['slug', 'label', 'module', 'sort_order'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}
