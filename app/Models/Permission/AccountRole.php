<?php

namespace App\Models\Permission;

use App\Models\Permissions\Role;
use App\Models\RecruitmentAd;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AccountRole extends Model
{
    protected $table = 'account_role';

    /**
     * Update an existing role or create a new one for the user
     * This function is needed to accommodate the extra parameter, $recruitment_id
     *
     * @param $character_id
     * @param $role_id
     * @param null $recruitment_id
     */
    public static function insertOrUpdateRole($character_id, $role_id, $recruitment_id = null)
    {
        $role = AccountRole::where('character_id', $character_id)->where('role_id', $role_id);

        if ($recruitment_id)
            $role = $role->where('recruitment_id', $recruitment_id);

        $role = $role->first();

        if (!$role)
            $role = new AccountRole();

        $role->character_id = $character_id;
        $role->role_id = $role_id;
        $role->recruitment_id = $recruitment_id;

        $role->save();
    }

    /**
     * Get the list of corp ads a user can manage
     *
     * @return |null
     */
    public static function getAdsUserCanManage()
    {
        $account_id = Auth::user()->id;
        $director_role_ids = Role::where('slug', 'director')->get()->pluck('id')->toArray();

        // 1. Get the recruitment IDs a user can view
        $account_recruitments = AccountRole::whereIn('role_id', $director_role_ids)->where('account_id', $account_id)->get();

        if (!$account_recruitments)
            return null;

        // 2. Get the ad IDs
        $recruitment_ads = Role::whereIn('id', $account_recruitments->pluck('role_id')->toArray())->get();

        if (!$recruitment_ads)
            return null;

        // 3. Get the ads
        $ads = RecruitmentAd::whereIn('id', $recruitment_ads->pluck('recruitment_id')->toArray())->get();

        if (!$ads)
            return null;

        // 4. Get corp names
        foreach ($ads as $ad)
            $ad->corp_name = User::where('corporation_id', $ad->corp_id)->first()->corporation_name;

        return $ads;
    }

    /**
     * Get the ads that a user can view
     */
    public static function getAdsUserCanView()
    {
        $account_id = Auth::user()->id;
        $recruiter_role_ids = Role::where('slug', 'recruiter')->get()->pluck('id')->toArray();

        // 1. Get the recruitment IDs a user can view
        $account_recruitments = AccountRole::whereIn('role_id', $recruiter_role_ids)->where('account_id', $account_id)->get();

        if (!$account_recruitments)
            return null;

        // 2. Get the ad IDs
        $recruitment_ads = Role::whereIn('id', $account_recruitments->pluck('role_id')->toArray())->get();

        if (!$recruitment_ads)
            return null;

        // 3. Get the ads
        $ads = RecruitmentAd::whereIn('id', $recruitment_ads->pluck('recruitment_id')->toArray())->get();

        if (!$ads)
            return null;

        // 4. Get corp names, where necessary
        foreach ($ads as $ad)
            $ad->corp_name = ($ad->corp_id == null) ? null : User::where('corporation_id', $ad->corp_id)->first()->corporation_name;

        return $ads;
    }

    /**
     * Get the corporations a user can view
     * Used for the "Corp Members" dropdown
     */
    public static function getCorpsUserCanView()
    {
        $account_id = Auth::user()->id;
        $recruiter_role_ids = Role::where('slug', 'recruiter')->get()->pluck('id')->toArray();

        // 1. Get the recruitment IDs a user can view
        $account_recruitments = AccountRole::whereIn('role_id', $recruiter_role_ids)->where('account_id', $account_id)->get();

        if (!$account_recruitments)
            return null;

        // 2. Get the ad IDs
        $recruitment_ads = Role::whereIn('id', $account_recruitments->pluck('role_id')->toArray())->get();

        if (!$recruitment_ads)
            return null;

        $recruitment_ads = $recruitment_ads->pluck('recruitment_id')->toArray();

        // 3. Get the ads
        $ads = RecruitmentAd::whereIn('id', $recruitment_ads)->whereNotNull('corp_id')->get();

        if (!$ads)
            return null;

        // 4. Get corp names
        foreach ($ads as $ad)
            $ad->corp_name = User::where('corporation_id', $ad->corp_id)->first()->corporation_name;

        return $ads;
    }

    /**
     * Determine if a user can view a corporation's member listing
     *
     * @param $corp_id
     * @return bool
     */
    public static function canViewCorpMembers($corp_id)
    {
        $recruitment_id = RecruitmentAd::where('corp_id', $corp_id)->first();

        if (!$recruitment_id)
            return false;

        $recruitment_id = $recruitment_id->id;
        $recruiter_role_ids = Role::where('slug', 'recruiter')->where('recruitment_id', $recruitment_id)->first();
        return (bool) AccountRole::where('account_id', Auth::user()->id)->where('role_id', $recruiter_role_ids->id)->exists();
    }

    /**
     * Check if a user can view applications
     *
     * @param $ad_id
     * @return mixed
     */
    public static function canViewApplications($ad_id)
    {
        return AccountRole::where('role_id', Role::getIdForSlug('recruiter'))
            ->where('account_id', Auth::user()->id)
            ->where('recruitment_id', $ad_id)
            ->exists();
    }
}