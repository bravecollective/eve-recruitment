<?php

namespace App\Http\Controllers;

use App\Models\CoreGroup;
use App\Models\Permission\AutoRole;
use App\Models\Permissions\Role;
use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;

class PermissionsController extends Controller
{
    public function listCoreGroups()
    {
        if (!Auth::user()->hasRole('admin'))
            return redirect('/')->with('error', 'Unauthorized');

        $core_groups = CoreGroup::all();

        return view('core_groups', ['groups' => $core_groups]);
    }

    /**
     * Render the global permissions page
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function globalRoles()
    {
        if (!Auth::user()->hasPermissionTo(Config::get('constants.permissions')['MANAGE_GLOBAL_PERMISSIONS']))
            return redirect('/')->with('error', 'Unauthorized');

        $roles = Role::all();

        return view('permissions', ['roles' => $roles]);
    }

    /**
     * Ajax function for saving roles
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveAutoRoles()
    {
        if (!Auth::user()->hasRole('admin'))
            return redirect('/')->with('error', 'Unauthorized');

        $roles = Input::get('roles');

        if (!$roles)
            die(json_encode(['success' => false, 'message' => 'No input fields can be blank']));

        // First, validate inputs
        foreach ($roles as $role)
        {
            $group_name = $role['group'];
            $role_name = $role['role'];

            $dbGroup = CoreGroup::where('name', $group_name)->first();
            $dbRole = Role::where('name', $role_name)->first();

            if (!$group_name || !$role_name)
                die(json_encode(['success' => false, 'message' => 'No input fields can be blank']));

            if (!$dbGroup)
                die(json_encode(['success' => false, 'message' => "Group '{$group_name}' does not exist"]));

            if (!$dbRole)
                die(json_encode(['success' => false, 'message' => "Role '{$role_name}' does not exist"]));
        }

        // Next, save inputs
        foreach ($roles as $role)
        {
            $group_name = $role['group'];
            $role_name = $role['role'];

            $dbGroup = CoreGroup::where('name', $group_name)->first();
            $dbRole = Role::where('name', $role_name)->first();

            AutoRole::addOrUpdateAutoRole($dbGroup->id, $dbRole->id);
        }

        die(json_encode(['success' => true, 'message' => "Auto roles updated"]));
    }

    /**
     * Load the autoRoles view
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function autoRoles()
    {
        if (!Auth::user()->hasRole('admin'))
            return redirect('/')->with('error', 'Unauthorized');

        $autoRoles = AutoRole::all();

        foreach ($autoRoles as $autoRole)
        {
            $autoRole->role_name = Role::find($autoRole->role_id)->name;
            $autoRole->group_name = CoreGroup::find($autoRole->core_group_id)->name;
        }

        return view('auto_roles', ['roles' => $autoRoles]);
    }

    /**
     * Get a user, filtering by the logged in user's scopes
     * @param $character_id int The character ID to lookup
     * @return Account|null User object, or null
     */
    private function getAccountWithScopes($character_id)
    {
        if (Auth::user()->hasPermissionTo(Config::get('constants.permissions')['MANAGE_GLOBAL_PERMISSIONS']))
            $scope = 'global';
        else if (Auth::user()->hasPermissionTo(Config::get('constants.permissions')['MANAGE_CORP_PERMISSIONS']))
            $scope = 'corp';
        else
            $scope = null;

        $user = User::where('character_id', $character_id);

        switch($scope)
        {
            case 'corp':
                $user = $user->where('corporation_id', Auth::user()->getMainUser()->corporation_id);
                break;

            case 'global':
                break;

            default:
                $user = null;
                break;
        }

        $user = $user->first();

        return ($user == null) ? $user : $user->account;
    }

    /**
     * Ajax call to save user permissions and roles
     */
    public function saveUserRoles()
    {
        $user_id = Input::get('userid');
        $roles = Input::get('roles');

        if (!$user_id)
            die(json_encode(['success' => false, 'message' => 'User ID is required']));

        $account = $this->getAccountWithScopes($user_id);

        if ($account == null)
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        foreach ($roles as $role)
        {
            $dbRole = Role::find($role['id']);
            if ($role['active'] == "true" && !$account->hasRole($dbRole->name))
                $account->giveRoles($dbRole->name);
            else if ($role['active'] == "false" && $account->hasRole($dbRole->name))
                $account->deleteRoles($dbRole->name);
        }

        die(json_encode(['success' => true, 'message' => 'Permissions updated']));
    }

    /**
     * Load user permissions
     */
    public function loadUserRoles()
    {
        $user_id = Input::get('userid');

        $user = $this->getAccountWithScopes($user_id);

        if ($user == null)
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        die(json_encode(['success' => true, 'message' => $user->roles]));
    }
}