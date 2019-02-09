<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $table = 'account';

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
    public function user()
    {
        return $this->hasMany('App\Models\User');
    }
}