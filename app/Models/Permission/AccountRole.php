<?php

namespace App\Models\Permission;

use App\Connectors\EsiConnection;
use App\Models\Application;
use App\Models\RecruitmentAd;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * App\Models\Permission\AccountRole
 *
 * @property int $account_id
 * @property int $role_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $set
 * @method static Builder|AccountRole newModelQuery()
 * @method static Builder|AccountRole newQuery()
 * @method static Builder|AccountRole query()
 * @method static Builder|AccountRole whereAccountId($value)
 * @method static Builder|AccountRole whereCreatedAt($value)
 * @method static Builder|AccountRole whereRoleId($value)
 * @method static Builder|AccountRole whereSet($value)
 * @method static Builder|AccountRole whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
     *
     * @return RecruitmentAd[]
     */
    public static function getAdsUserCanView($managerOnly = false)
    {
        $account_id = Auth::user()->id;
        $recruiter_role_ids = Role::where('slug', 'director')->orWhere('slug', 'manager');

        if (!$managerOnly)
            $recruiter_role_ids = $recruiter_role_ids->orWhere('slug', 'recruiter');

        $recruiter_role_ids = $recruiter_role_ids->get()->pluck('id')->toArray();

        // 1. Get the recruitment IDs a user can view
        $account_roles = AccountRole::whereIn('role_id', $recruiter_role_ids)->where('account_id', $account_id)->get();

        if (!$account_roles)
            return [];

        // 2. Get the ad IDs
        $recruitment_ads = Role::whereIn('id', $account_roles->pluck('role_id')->toArray())->get();

        if (!$recruitment_ads)
            return [];

        // 3. Get the ads
        $ads = RecruitmentAd::whereIn('id', $recruitment_ads->pluck('recruitment_id')->toArray())->get();

        if (!$ads)
            return [];

        // 4. Get corp names, where necessary
        foreach ($ads as $ad)
            $ad->corp_name = ($ad->corp_id == null) ? null : (new EsiConnection($account_id))->getCorporationName($ad->corp_id);

        return $ads->all();
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
        $ads = [];

        foreach ($roles as $role)
        {
            if ($role->name == "recruiter")
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

    public static function getGroupAdsUsercanView()
    {
        $account_id = Auth::user()->id;

        // Get role IDs. Either recruiter or director
        $role_ids = Role::where('slug', 'recruiter')->orWhere('slug', 'manager');
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
        $ads = [];

        foreach ($roles as $role)
        {
            if ($role->name == "recruiter")
                continue; // No group associated with this one

            $corp = preg_split("/\s+(?=\S*+$)/", $role->name)[0]; // Split at last space. Everything before 'director' or 'recruiter'
            $ad = RecruitmentAd::where('group_name', $corp)->where('corp_id', null)->first();

            if (!$ad)
                continue;

            $ads[] = $ad;
        }

        // array_unique is needed to avoid returning duplicates when the user has both director and recruiter permissions
        return array_unique($ads, SORT_REGULAR);
    }

    public static function userCanEditAd($type, $id)
    {
        switch ($type)
        {
            case 'corp':
                $corp_name = User::where('corporation_id', $id)->first();

                if (!$corp_name)
                    return false;

                $corp_name = $corp_name->corporation_name;
                $role_id = Role::where('name', $corp_name . ' director')->first();

                if (!$role_id)
                    return false;

                $role_id = $role_id->id;

                return AccountRole::where('account_id', Auth::user()->id)->where('role_id', $role_id)->exists();
            case 'group':
                if ($id == 0)
                    return Auth::user()->hasRole('group admin');

                $group_ad = RecruitmentAd::where('id', $id)->first();
                $group_name = $group_ad->group_name;
                $role_id = Role::where('name', $group_name . ' manager')->first();

                if (!$role_id)
                    return false;

                $role_id = $role_id->id;
                $role = AccountRole::where('account_id', Auth::user()->id)->where('role_id', $role_id)->first();
                return !!$role;
            default:
                break;
        }

        return false;
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
            $role = $role->orWhere('name', $ad->group_name . ' director')->get()->pluck('id')->toArray();
        else
            $role = $role->orWhere('name', $ad->group_name . ' manager')->get()->pluck('id')->toArray();

        return AccountRole::where('account_id', Auth::user()->id)->whereIn('role_id', $role)->exists();
    }

    /**
     * Delete permissions that aren't persistent and roles that are currently auto-assigned.
     *
     * @param $account_id
     */
    public static function deleteNotPersistentRoles($account_id)
    {
        AccountRole::where('account_id', $account_id)->where('set', 0)->delete();

        $roleIds = AutoRole::select('role_id')->distinct()->get()->pluck('role_id')->toArray();
        AccountRole::where('account_id', $account_id)->whereIn('role_id', $roleIds)->delete();
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
        $open_ad_ids = Application::where('account_id', $account->id)->where('status', '<>', Application::REVOKED)->get();

        if (!$open_ad_ids)
            return false;

        $open_ad_ids = $open_ad_ids->pluck('recruitment_id')->toArray();
        $role_ids = Role::whereIn('recruitment_id', $open_ad_ids)->get();

        if (!$role_ids)
            return false;

        return AccountRole::where('account_id', Auth::user()->id)->whereIn('role_id', $role_ids->pluck('id')->toArray())->exists();
    }

    /**
     * Delete all roles from an account
     *
     * @param int $account_id Database account ID
     */
    public static function clearAccountRoles($account_id)
    {
        AccountRole::where('account_id', $account_id)->delete();
    }

    protected function setKeysForSaveQuery($query)
    {
        $query->where('account_id', $this->getAttribute('account_id'))
            ->where('role_id', $this->getAttribute('role_id'));

        return $query;
    }
}
