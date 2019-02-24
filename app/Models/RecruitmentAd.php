<?php

namespace App\Models;

use App\Models\Permission\AccountRole;
use App\Models\Permissions\Role;
use Illuminate\Database\Eloquent\Model;

class RecruitmentAd extends Model
{
    protected $table = 'recruitment_ad';

    /**
     * Get the recruiters for a recruitment ad
     *
     * @param $ad_id
     * @return mixed
     */
    public static function getRecruiters($ad_id)
    {
        $ad = RecruitmentAd::find($ad_id);
        $role_id = Role::where('name', $ad->group_name . ' recruiter')->first()->id;

        return AccountRole::join('account', 'account_role.account_id', '=', 'account.id')
            ->join('user', 'user.character_id', '=', 'account.main_user_id')
            ->where('role_id', $role_id)->get();
    }

    /**
     * Requirements relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requirements()
    {
        return $this->hasMany('App\Models\RecruitmentRequirement', 'recruitment_id');
    }
}