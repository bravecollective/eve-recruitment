<?php

namespace App\Models\Permission;

trait HasPermissionTrait
{

    /**
     * Get all roles, given a string array of slugs
     *
     * @return mixed
     */
    public function giveAllRoles()
    {
        return $this->giveRoles(0, ...Role::all()->pluck('name')->toArray());
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
     * Check if a user has a role using the SQL LIKE syntax
     * @param $role
     * @return mixed
     */
    public function hasRoleLike($role)
    {
        $roles = Role::where('name', 'LIKE', $role)->get();

        foreach ($roles as $role)
        {
            if ($this->roles->contains($role))
                return true;
        }

        return false;
    }

    /**
     * Give an account roles
     *
     * @param int $set If the role should be persistent
     * @param mixed ...$roles
     * @return $this
     */
    public function giveRoles($set, ...$roles)
    {
        $roles = $this->getAllRoles($roles);

        if ($roles === null)
            return $this;

        foreach ($roles as $role)
        {
            if (!$this->hasRole($role->name))
                $this->roles()->attach($role, ['set' => $set]);
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

    public function accountRolesWithSetParameter()
    {
        return AccountRole::join('role', 'account_role.role_id', '=', 'role.id')
            ->where('account_id', $this->id)->get();
    }
}
