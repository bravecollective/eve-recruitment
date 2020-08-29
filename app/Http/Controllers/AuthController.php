<?php
namespace App\Http\Controllers;

use App\Connectors\CoreConnection;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Permission\AccountRole;
use App\Models\Permission\AutoRole;
use App\Models\Permissions\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Socialite;

class AuthController extends Controller
{
    /**
     * Redirect the user to the Eve Online authentication page
     *
     * @return Response
     */
    public function redirectToProvider()
    {
        return Socialite::driver('eveonline')->redirect();
    }

    /**
     * Obtain the user information from Eve Online and core
     *
     * @return Response
     */
    public function handleProviderCallback()
    {
        // Retrieve the user info from Eve
        $user = Socialite::driver('eveonline')->user();
        $core_users = CoreConnection::getCharactersForUser($user->id);
        $main = null;

        if ($core_users == null)
            return redirect('/')->with('error', 'User does not exist in core');

        foreach ($core_users as $user)
        {
            if ($user->main == true)
                $main = $user;

            if ($user->validToken === false)
                return redirect('/')->with('error', 'One or more of your characters has an invalid ESI token in Core. Please re-authorize all of your characters.');
        }

        if ($main == null)
            return redirect('/')->with('error', 'Cannot determine main character. Ensure your main is properly selected in Core.');

        $groups = CoreConnection::getCharacterGroups($main->id);

        foreach ($groups as $group)
            if ($group->name == "banned")
                return redirect('/')->with('error', 'You are not permitted to login. If you believe this is an error, please contact a recruiter.');

        // Insert/update users in database
        User::addUsersToDatabase($core_users, $main);

        // Insert/update core groups in database
        AccountGroup::updateGroupsForUser($main->id, $groups);

        $coreAccountID = CoreConnection::getCharacterAccount($main->id);
        $dbAccount = Account::where('core_account_id', $coreAccountID)->first();

        // Delete not persistent roles
        AccountRole::deleteNotPersistentRoles($dbAccount->id);

        // Create director roles
        Role::createDirectorRoles($dbAccount);

        // Assign auto roles
        AutoRole::assignAutoRoles($dbAccount);

        Auth::login($dbAccount);

        return redirect()->intended('/');
    }
}
