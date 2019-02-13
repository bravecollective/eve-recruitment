<?php

namespace App\Models\Permissions;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'role';

    /**
     * Permissions relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id');
    }
}