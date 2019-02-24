<?php

namespace App\Models\Permissions;

use App\Models\RecruitmentAd;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

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
    }

    /**
     * Create director roles that aren't in the database already for the alliance whitelist
     *
     * @param $characters
     */
    public static function createDirectorRoles($account)
    {
        $alliace_whitelist = explode(',', env('ALLIANCE_WHITELIST'));
        $characters = $account->characters;

        foreach ($characters as $character)
        {
            if ($character->alliance_id === null || !in_array($character->alliance_id, $alliace_whitelist))
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
    public static function createRoleForAd($ad)
    {
        $role = Role::where('recruitment_id', $ad->id)->first();

        if (!$role)
            $role = new Role();

        $name = ($ad->corp_id === null) ? $ad->group_name : User::where('corporation_id', $ad->corp_id)->first()->corporation_name;

        $role->slug = 'recruiter';
        $role->name = $name . ' recruiter';
        $role->recruitment_id = $ad->id;
        $role->save();
    }

    /**
     * Permissions relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id');
    }
}