<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name', 'label', 'description', 'is_locked'];

    protected $casts = ['is_locked' => 'boolean'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'role_admin', 'role_id', 'admin_id');
    }
}
