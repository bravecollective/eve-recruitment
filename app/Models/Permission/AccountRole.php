<?php

namespace App\Models\Permission;

use App\Models\Account;
use App\Models\Application;
use App\Models\Permissions\Role;
use App\Models\RecruitmentAd;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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
     * Mark a permission as a persistent value
     *
     * @param $account_id
     * @param $role_id
     * @param $persistent
     */
    public static function setPersistent($account_id, $role_id, $persistent)
    {
        $role = AccountRole::getAccountRole($account_id, $role_id);

        if ($role)
        {
            $role->set = $persistent;
            $role->save();
        }
    }

    /**
     * Get the ads that a recruiter can view
     */
    public static function getAdsUserCanView()
    {
        $account_id = Auth::user()->id;
        $recruiter_role_ids = Role::where('slug', 'recruiter')->orWhere('slug', 'director')->get()->pluck('id')->toArray();

        // 1. Get the recruitment IDs a user can view
        $account_roles = AccountRole::whereIn('role_id', $recruiter_role_ids)->where('account_id', $account_id)->get();

        if (!$account_roles)
            return null;

        // 2. Get the ad IDs
        $recruitment_ads = Role::whereIn('id', $account_roles->pluck('role_id')->toArray())->get();

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
        $role_ids = Role::where('slug', 'LIKE', '%director');

        if ($corps === true)
            $role_ids = $role_ids->orWhere('slug', 'LIKE', '%recruiter');

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

    /**
     * Check if the currently logged in user can see applications for a group
     *
     * @param $ad
     * @return mixed
     */
    public static function canViewApplications($ad)
    {
        $role = Role::where('name', $ad->group_name . ' recruiter');

        if ($ad->corp_id != null)
        {
            $role = $role->orWhere('name', $ad->group_name . ' director')->get()->pluck('id')->toArray();
            return AccountRole::where('account_id', Auth::user()->id)->whereIn('role_id', $role)->exists();
        }
        else
            return AccountRole::where('account_id', Auth::user()->id)->where('role_id', $role->first()->id)->exists();
    }

    /**
     * Delete permissions that aren't persistent
     *
     * @param $account_id
     */
    public static function deleteNotPersistentRoles($account_id)
    {
        AccountRole::where('account_id', $account_id)->where('set', 0)->delete();
    }

    /**
     * Get an account role
     *
     * @param $account_id
     * @param $role_id
     * @return mixed
     */
    public static function getAccountRole($account_id, $role_id)
    {
        return AccountRole::where('account_id', $account_id)->where('role_id', $role_id)->first();
    }

    /**
     * Determine if a user can view an ESI. This checks if the character has an open application
     *
     * @param $character_id
     * @return bool
     */
    public static function recruiterCanViewEsi($character_id)
    {
        $account = User::where('character_id', $character_id)->first()->account;
        $open_ad_ids = Application::where('account_id', $account->id)->get();

        if (!$open_ad_ids)
            return false;

        $open_ad_ids = $open_ad_ids->pluck('recruitment_id')->toArray();
        $role_ids = Role::whereIn('recruitment_id', $open_ad_ids)->get();

        if (!$role_ids)
            return false;

        return AccountRole::where('account_id', Auth::user()->id)->whereIn('role_id', $role_ids->pluck('id')->toArray())->exists();
    }

    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where('account_id', $this->getAttribute('account_id'))
            ->where('role_id', $this->getAttribute('role_id'));

        return $query;
    }
}