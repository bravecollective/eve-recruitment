<?php

namespace App\Models;

use App\Connectors\CoreConnection;
use Illuminate\Database\Eloquent\Model;

class AccountGroup extends Model
{
    protected $table = 'account_group';
    public $timestamps = false;

    /**
     * Update groups in the database
     * @param $main_id int The main user ID
     */
    public static function updateGroupsForUser($main_id)
    {
        $account_id = User::getAccountIdForUserId($main_id);
        $core_groups = CoreConnection::getCharacterGroups($main_id);

        // TODO: There needs to be a better way than clearing user groups each time
        AccountGroup::where('account_id', $account_id)->delete();

        foreach ($core_groups as $group)
        {
            // Ensure the core group is in the database
            $dbGroup = CoreGroup::find($group->id);

            if (!$dbGroup)
            {
                $dbGroup = new CoreGroup();
                $dbGroup->id = $group->id;
                $dbGroup->name = $group->name;
                $dbGroup->save();
            }

            // Update user groups
            $userGroup = new AccountGroup();
            $userGroup->account_id = $account_id;
            $userGroup->group_id = $group->id;
            $userGroup->save();
        }
    }

    /**
     * Entity relationship
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}