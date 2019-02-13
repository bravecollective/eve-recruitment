<?php

namespace App\Http\Controllers;

use App\Models\Permissions\Role;
use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;

class PermissionsController extends Controller
{
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
            if ($role['active'] == "true" && !$account->hasRole($role['slug']))
                $account->giveRoles($role['slug']);
            else if ($role['active'] == "false" && $account->hasRole($role['slug']))
                $account->deleteRoles($role['slug']);
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