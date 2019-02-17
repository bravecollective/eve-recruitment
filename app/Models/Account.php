<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Account extends Authenticatable
{
    use HasPermissionTrait;

    protected $table = 'account';
    /**
     * given an array of users, get the account ID
     *
     * @param $users
     * @return User|null
     */
    public static function getAccountIdForUsers($users)
    {
        $account_id = null;

        foreach ($users as $user)
        {
            $id = User::getAccountIdForUserId($user->id);

            if ($id != null)
            {
                $account_id = $id;
                break;
            }
        }

        return $account_id;
    }

    /**
     * Entity relationship
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function characters()
    {
        return $this->hasMany('App\Models\User', 'account_id');
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

    /**
     * Get the main user name
     *
     * @return mixed
     */
    public function main()
    {
        return User::find($this->main_user_id);
    }
}