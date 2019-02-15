<?php

namespace App\Models\Permission;

use App\Models\AccountGroup;
use App\Models\Permissions\Role;
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
        $dbRole = AutoRole::where('core_group_id', $group)->where('role_id', $role)->first();

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
       $roles_to_assign = Role::whereIn('id', $auto_roles)->get();

       $account->giveRoles(...$roles_to_assign);
    }
}