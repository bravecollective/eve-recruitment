<?php

namespace App\Models\Permission;

use App\Models\AccountGroup;
use App\Models\Permissions\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AutoRole extends Model
{
    protected $table = 'auto_role';

    /**
     * Add or update auto roles in the database
     *
     * @param $group
     * @param $role
     */
    public static function addOrUpdateAutoRole($group, $role)
    {
        $dbRole = AutoRole::getAutoRole($group, $role);

        if (!$dbRole)
            $dbRole = new AutoRole();

        $dbRole->core_group_id = $group;
        $dbRole->role_id = $role;
        $dbRole->save();
    }

    /**
     * Assign auto roles to a user
     *
     * @param $account
     */
    public static function assignAutoRoles($account)
    {
       $account_groups = AccountGroup::where('account_id', $account->id)->get()->pluck('group_id')->toArray();
       $auto_roles = AutoRole::whereIn('core_group_id', $account_groups)->get()->pluck('role_id')->toArray();
       $roles_to_assign = Role::whereIn('id', $auto_roles)->get()->pluck('name')->toArray();

       $account->giveRoles(0, ...$roles_to_assign);
    }

    /**
     * Get an auto role from the database
     *
     * @param $group_id
     * @param $role_id
     * @return mixed
     */
    public static function getAutoRole($group_id, $role_id)
    {
        return AutoRole::where('core_group_id', $group_id)->where('role_id', $role_id)->first();
    }

    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where('core_group_id', $this->getAttribute('core_group_id'))
            ->where('role_id', $this->getAttribute('role_id'));

        return $query;
    }
}