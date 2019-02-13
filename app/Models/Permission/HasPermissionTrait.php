<?php

namespace App\Models;

use App\Models\Permissions\Permission;
use App\Models\Permissions\Role;
use Illuminate\Support\Facades\Auth;

trait HasPermissionTrait
{

    /**
     * Check if a account has permission to do something, through roles or direct permissions
     *
     * @param $permission
     * @return bool
     */
    public function hasPermissionTo($permission)
    {
        return $this->hasPermissionThroughRole($permission);
    }

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
     * Check if a account is granted permission implicitly through a role
     *
     * @param $permission
     * @return bool
     **/
    public function hasPermissionThroughRole($permission)
    {
        foreach (Auth::user()->roles as $role)
            if ($role->permissions->contains(Permission::where('slug', $permission)->first()))
                return true;

        return false;
    }

    /**
     * Check if a account has a permission through direct permissions
     *
     * @param $permission
     * @return bool
     *
    protected function hasPermission($permission)
    {
        return (bool) Auth::user()->permissions->where('slug', $permission->slug)->count();
    }*/

    /**
     * Give an accounts permissions
     *
     * @param mixed ...$permissions
     * @return $this
     *
    public function givePermissionTo(...$permissions)
    {
        $permissions = $this->getAllPermissions($permissions);

        if ($permissions === null)
            return $this;

        $this->permissions()->saveMany($permissions);

        return $this;
    }*/

    /**
     * Remove permissions from an account
     *
     * @param mixed ...$permissions
     * @return $this
     *
    public function deletePermissions(...$permissions)
    {
        $permissions = $this->getAllPermissions($permissions);
        $this->permissions()->detach($permissions);

        return $this;
    }*/

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