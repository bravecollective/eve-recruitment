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
     * Get either:
     * 1. List of corp members a user can view
     * 2. List of corp ads a user can manage
     *
     * @param bool $corps
     * @return array|null
     */
    public static function getUserCorpMembersOrAdsListing($corps = false)
    {
        $account_id = Auth::user()->id;

        // Get role IDs. Either recruiter or director
        $role_ids = Role::where('slug', 'director');

        if ($corps === true)
            $role_ids = $role_ids->orWhere('slug', 'recruiter');

        $role_ids = $role_ids->get()->pluck('id')->toArray();

        // Get the account roles
        $account_recruitments = AccountRole::whereIn('role_id', $role_ids)->where('account_id', $account_id)->get();

        if (!$account_recruitments)
            return null;

        // Get the roles
        $roles = Role::whereIn('id', $account_recruitments->pluck('role_id')->toArray())->get();

        if (!$roles)
            return null;

        // 3. Get the corporations
        // TODO: Base this on ID instead of name.
        $ads = [];
        foreach ($roles as $role)
        {
            if ($role->name == "director" || $role->name == "recruiter")
                continue; // No corp associated with this one

            $corp = preg_split("/\s+(?=\S*+$)/", $role->name)[0]; // Split at last space. Everything before 'director' or 'recruiter'
            $corp_id = User::where('corporation_name', $corp)->first();

            if (!$corp_id)
                continue;

            $corp_id = $corp_id->corporation_id;
            $ads[] = (object) [ 'corp_name' => $corp, 'corp_id' => $corp_id ];
        }

        // array_unique is needed to avoid returning duplicates when the user has both director and recruiter permissions
        return array_unique($ads, SORT_REGULAR);
    }

    /**
     * Determine if a user can view a corporation's member listing
     *
     * @param $corp_id
     * @return bool
     */
    public static function canViewCorpMembers($corp_id)
    {
        $corp_name = User::where('corporation_id', $corp_id)->first()->corporation_name;
        $role = $corp_name . " recruiter";
        $dir_role = $corp_name . " director";

        return (Auth::user()->hasRole($role) || Auth::user()->hasRole($dir_role));
    }
}