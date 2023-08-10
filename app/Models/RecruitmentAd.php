<?php

namespace App\Models;

use App\Models\Permission\AccountRole;
use App\Models\Permission\AutoRole;
use App\Models\Permission\Role;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\RecruitmentAd
 *
 * @property int $id
 * @property int|null $corp_id
 * @property string $slug
 * @property string $text
 * @property int $created_by
 * @property string|null $group_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $allow_listing
 * @property string|null $application_notification_url
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\RecruitmentRequirement[] $requirements
 * @property-read int|null $requirements_count
 * @method static \Illuminate\Database\Eloquent\Builder|RecruitmentAd newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecruitmentAd newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecruitmentAd query()
 * @method static \Illuminate\Database\Eloquent\Builder|RecruitmentAd whereAllowListing($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecruitmentAd whereApplicationNotificationUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecruitmentAd whereCorpId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecruitmentAd whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecruitmentAd whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecruitmentAd whereGroupName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecruitmentAd whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecruitmentAd whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecruitmentAd whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecruitmentAd whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RecruitmentAd extends Model
{
    protected $table = 'recruitment_ad';

    public static function getRecruiters($ad_id)
    {
        return self::getAdminsAccounts($ad_id, 'recruiter');
    }

    public static function getManagers($ad_id)
    {
        return self::getAdminsAccounts($ad_id, 'manager');
    }

    public static function getDirectors($ad_id)
    {
        return self::getAdminsAccounts($ad_id, 'director');
    }

    /**
     * Returns all roles for the advert with Neucore groups for auto-assignment.
     */
    public static function getAutoRoles($adId)
    {
        $data = [];

        $roleIds = [];
        foreach (Role::where('recruitment_id', $adId)->get() as $role) {
            $roleIds[] = $role['id'];
            $data[$role['id']] = [
                'roleId' => $role['id'],
                'roleName' => $role['name'],
                'coreGroups' => [],
            ];
        }

        $coreGroupIds = [];
        foreach (AutoRole::whereIn('role_id', $roleIds)->get() as $autoRole) {
            $coreGroupIds[] = $autoRole['core_group_id'];
            $data[$autoRole['role_id']]['coreGroups'][$autoRole['core_group_id']] = [
                'id' => $autoRole['core_group_id'],
                'name' => null,
            ];
        }

        foreach (CoreGroup::whereIn('id', $coreGroupIds)->get() as $coreGroup) {
            foreach ($data as $roleId => $roleData) {
                if (isset($roleData['coreGroups'][$coreGroup['id']])) {
                    $data[$roleId]['coreGroups'][$coreGroup['id']]['name'] = $coreGroup['name'];
                }
            }
        }

        // remove keys and return
        return array_values(array_map(function ($role) {
            $role['coreGroups'] = array_values($role['coreGroups']);
            return $role;
        }, $data));
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

    /**
     * Get the recruiters for a recruitment ad
     *
     * @param $ad_id
     * @return mixed
     */
    private static function getAdminsAccounts($ad_id, string $role)
    {
        $ad = RecruitmentAd::find($ad_id);
        $role_id = Role::where('name', "$ad->group_name $role")->first()->id;

        return AccountRole::join('account', 'account_role.account_id', '=', 'account.id')
            ->join('user', 'user.character_id', '=', 'account.main_user_id')
            ->where('role_id', $role_id)->get();
    }
}
