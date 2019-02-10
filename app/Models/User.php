<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    protected $table = 'user';
    protected $primaryKey = 'character_id';
    public $incrementing = false;

    /**
     * Insert or update users in the database
     * @param $users array JSON array of users
     * @param $main User Main user on the account
     */
    public static function addUsersToDatabase($users, $main)
    {
        // Find the account ID
        $account_id = Account::getAccountIdForUsers($users);

        if ($account_id == null)
            $account = new Account();
        else
            $account = Account::find($account_id);

        $account->main_user_id = $main->id;
        $account->save();

        $first_admin = (env('FIRST_ACCOUNT_ADMIN', false) == true && $account->id == 1) ? true : false;

        foreach ($users as $user)
        {
            $dbUser = User::where('character_id', $user->id)->first();

            if (!$dbUser)
                $dbUser = new User(); // New character

            $dbUser->account_id = $account->id;
            $dbUser->name = $user->name;
            $dbUser->character_id = $user->id;
            $dbUser->corporation_id = $user->corporation->id;
            $dbUser->corporation_name = $user->corporation->name;

            if ($user->corporation->alliance !== null)
            {
                $dbUser->alliance_id = $user->corporation->alliance->id;
                $dbUser->alliance_name = $user->corporation->alliance->name;
            } // Don't need an else since the default values for alliance are null

            $dbUser->save();

            if ($first_admin && $dbUser->character_id == $main->id)
                $dbUser->assignRole('admin');
        }
    }

    /**
     * Get users on an account
     * @param $accountId int The account ID
     * @return User[]|null Found users or null
     */
    public static function getUsers($accountId)
    {
        return User::where('account_id', $accountId)->get();
    }

    /**
     * Given a character ID, get the account id
     * @param $userId int the user id
     * @return User|null User object, or null if doesn't exist
     */
    public static function getAccountIdForUserId($userId)
    {
        $user = User::where('character_id', $userId)->first();

        if (!$user)
            return null;
        else
            return $user->account_id;
    }

    /**
     * Get the members of a corporation given the corporation's ID
     * @param $corpId int Corp ID to get members of
     * @return User[]|null Corporation members
     */
    public static function getCorpMembers($corpId)
    {
        return User::where('corporation_id', $corpId)->get();
    }

    /**
     * Entity relationship
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function groups()
    {
        return $this->hasMany('App\Models\AccountGroup');
    }

    /**
     * Entity relationship
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function account()
    {
        return $this->hasOne('App\Models\Account', 'id', 'account_id');
    }

    /**
     * Overrides the method to ignore the remember token.
     */
    public function setAttribute($key, $value)
    {
        $isRememberTokenAttribute = $key == $this->getRememberTokenName();

        if (!$isRememberTokenAttribute)
            parent::setAttribute($key, $value);
    }

}
