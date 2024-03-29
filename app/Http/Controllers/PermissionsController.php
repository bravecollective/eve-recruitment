<?php

namespace App\Http\Controllers;

use App\Models\CoreGroup;
use App\Models\Permission\AutoRole;
use App\Models\Permission\Role;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        if (!Auth::user()->hasRole('admin'))
            return redirect('/')->with('error', 'Unauthorized');

        $roles = Role::orderBy('name')->get();

        return view('permissions', ['roles' => $roles]);
    }

    /**
     * Ajax function for saving roles
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveAutoRoles(Request $r)
    {
        if (!Auth::user()->hasRole('admin'))
            return redirect('/')->with('error', 'Unauthorized');

        $roles = $r->input('roles');

        if (!$roles)
            die(json_encode(['success' => false, 'message' => 'No input fields can be blank']));

        // First, validate inputs
        foreach ($roles as $role)
        {
            $group_id = $role['group'];
            $role_id = $role['role'];

            $dbGroup = CoreGroup::where('id', $group_id)->first();
            $dbRole = Role::where('id', $role_id)->first();

            if (!$group_id || !$role_id)
                die(json_encode(['success' => false, 'message' => 'No input fields can be blank']));

            if (!$dbGroup)
                die(json_encode(['success' => false, 'message' => "Group '{$group_id}' does not exist"]));

            if (!$dbRole)
                die(json_encode(['success' => false, 'message' => "Role '{$role_id}' does not exist"]));
        }

        // Next, save inputs
        foreach ($roles as $role)
        {
            $group_id = $role['group'];
            $role_id = $role['role'];

            AutoRole::addOrUpdateAutoRole($group_id, $role_id);
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

        $autoRoles = $autoRoles->sortBy(function ($autoRole) {
            return strtolower($autoRole->role_name);
        });

        return view('auto_roles', ['roles' => $autoRoles]);
    }

    /**
     * Delete an auto role
     */
    public function deleteAutoRole(Request $r)
    {
        if (!Auth::user()->hasRole('admin'))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $group_id = $r->input('group_id');
        $role_id = $r->input('role_id');

        if (!$group_id || !$role_id)
            die(json_encode(['success' => false, 'message' => 'Invalid input']));

        $autoRole = AutoRole::getAutoRole($group_id, $role_id);

        if (!$autoRole)
            die(json_encode(['success' => false, 'message' => 'Invalid input']));

        $autoRole->delete();
        die(json_encode(['success' => true, 'message' => 'Role deleted']));
    }

    /**
     * Ajax route for getting auto role template dropdowns
     *
     * @throws \Throwable
     */
    public function getAutoRoleTemplate()
    {
        if (!Auth::user()->hasRole('admin'))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $roles = Role::orderBy('name')->get();
        $groups = CoreGroup::orderBy('name')->get();
        $view = view('parts/auto_role', ['roles' => $roles, 'groups' => $groups])->render();

        die(json_encode(['success' => true, 'message' => $view]));
    }

    /**
     * Get a user, filtering by the logged in user's scopes
     * @param $character_id int The character ID to lookup
     * @return Account|null User object, or null
     */
    private function getAccountWithScopes($character_id)
    {
        $user = User::where('character_id', $character_id)->first();
        return ($user == null) ? $user : $user->account;
    }

    /**
     * Ajax call to save user permissions and roles
     *
     * @param $role
     */
    public function saveUserRoles(Request $r, $role = 'admin')
    {
        if (!Auth::user()->hasRole($role))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $user_id = $r->input('userid');
        $roles = $r->input('roles');

        if (!$user_id)
            die(json_encode(['success' => false, 'message' => 'User ID is required']));

        $account = $this->getAccountWithScopes($user_id);

        if ($account == null)
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        foreach ($roles as $role)
        {
            $dbRole = Role::find($role['id']);
            $persistent = ($role['persistent'] === "true") ? 1 : 0;

            if ($role['active'] == "true" && !$account->hasRole($dbRole->name))
                $account->giveRoles($persistent, $dbRole->name);
            else if ($role['active'] == "false" && $account->hasRole($dbRole->name))
                $account->deleteRoles($dbRole->name);
        }

        die(json_encode(['success' => true, 'message' => 'Permissions updated']));
    }

    /**
     * Load user permissions
     */
    public function loadUserRoles(Request $r)
    {
        if (!Auth::user()->hasRole('admin'))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $user_id = $r->input('userid');

        $user = $this->getAccountWithScopes($user_id);

        if ($user == null)
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        die(json_encode(['success' => true, 'message' => $user->accountRolesWithSetParameter()]));
    }
}
