<?php

namespace App\Models\Permissions;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permission';

    /**
     * Roles relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id');
    }
}