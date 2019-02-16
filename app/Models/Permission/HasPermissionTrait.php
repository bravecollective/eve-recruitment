<?php

namespace App\Models;

use App\Models\Permissions\Permission;
use App\Models\Permissions\Role;

trait HasPermissionTrait
{

    /**
     * Get all roles, given a string array of slugs
     *
     * @return mixed
     */
    public function giveAllRoles()
    {
        return $this->giveRoles(...Role::all()->pluck('name')->toArray());
    }

    /**
     * Get all roles, given a string array of slugs
     *
     * @param string[] $roles
     * @return mixed
     */
    public function getAllRoles(array $roles)
    {
        return Role::whereIn('name', $roles)->get();
    }

    /**
     * Check if a user has a role
     *
     * @param $role
     * @return mixed
     */
    public function hasRole($role)
    {
        return $this->roles->contains(Role::where('name', $role)->first());
    }

    /**
     * Give an account roles
     *
     * @param mixed ...$roles
     * @return $this
     */
    public function giveRoles(...$roles)
    {
        $roles = $this->getAllRoles($roles);

        if ($roles === null)
            return $this;

        foreach ($roles as $role)
        {
            if (!$this->hasRole($role->name))
                $this->roles()->attach($role);
        }

        return $this;
    }

    /**
     * Remove roles from an account
     *
     * @param mixed ...$roles
     * @return $this
     */
    public function deleteRoles(...$roles)
    {
        $roles = $this->getAllRoles($roles);

        if ($roles === null)
            return $this;

        foreach ($roles as $role)
        {
            if ($this->hasRole($role->name))
                $this->roles()->detach($role);
        }

        return $this;
    }

    /**
     * Roles relationship
     *
     * @return mixed
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'account_role', 'account_id');
    }
}