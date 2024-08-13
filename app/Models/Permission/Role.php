<?php

namespace App\Models\Permission;

use App\Models\Account;
use App\Models\RecruitmentAd;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Permission\Role
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int|null $recruitment_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereRecruitmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Role extends Model
{
    protected $table = 'role';

    /**
     * Given a slug, return the role ID
     *
     * @param $slug
     * @return |null
     */
    public static function getIdForSlug($slug)
    {
        $role = Role::where('slug', $slug)->first();

        return ($role) ? $role->id : null;
    }

    /**
     * Update the name of a recruiter role
     *
     * @param $old_name
     * @param $new_name
     */
    public static function updateGroupRoleName($old_name, $new_name)
    {
        $role = Role::where('name', $old_name . ' recruiter')->first();

        if (!$role)
            return;

        $role->name = $new_name . ' recruiter';
        $role->save();

        $role = Role::where('name', $old_name . ' manager')->first();

        if (!$role)
            return;

        $role->name = $new_name . ' manager';
        $role->save();
    }

    /**
     * Create director roles that aren't in the database already for the alliance whitelist
     *
     * @param $characters
     */
    public static function createDirectorRoles($account)
    {
        $alliance_whitelist = explode(',', config('eve-recruitment.alliance_whitelist'));
        $corporation_whitelist = explode(',', config('eve-recruitment.corporation_whitelist'));
        $characters = $account->characters;

        foreach ($characters as $character)
        {
            if (!in_array($character->alliance_id, $alliance_whitelist) && !in_array($character->corporation_id, $corporation_whitelist))
                continue;

            $role = Role::where('name', $character->corporation_name . " director")->where('slug', 'director')->first();

            if ($role)
                continue;

            $role = new Role();
            $role->slug = 'director';
            $role->name = $character->corporation_name . ' director';
            $role->save();
        }
    }

    /**
     * Create the role for a recruitment ad
     *
     * @param RecruitmentAd $ad_id The ad to create a role for
     */
    public static function createRoleForAd($ad, $type = 'corp')
    {
        $name = ($ad->corp_id === null) ? $ad->group_name : User::where('corporation_id', $ad->corp_id)->first()->corporation_name;

        $role = Role::where('name', $name . ' recruiter')->first();

        if (!$role)
            $role = new Role();

        $role->slug = 'recruiter';
        $role->name = $name . ' recruiter';
        $role->recruitment_id = $ad->id;
        $role->save();

        $slug = ($type == 'group') ? 'manager' : 'director';

        $role = Role::where('name', $name . ' ' . $slug)->first();

        if (!$role)
            $role = new Role();

        $name = ($ad->corp_id === null) ? $ad->group_name : User::where('corporation_id', $ad->corp_id)->first()->corporation_name;

        $role->slug = $slug;
        $role->name = $name . " $slug";
        $role->recruitment_id = $ad->id;
        $role->save();
    }
}
