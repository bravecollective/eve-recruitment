<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentRequirement extends Model
{
    protected $table = 'recruitment_requirement';

    // Requirement types
    const CORPORATION = 1;
    const CORE_GROUP = 2;
    const ALLIANCE = 3;

    // If a new type is created, add it here and to the recruitment_requirement blade view
    const TYPES = [self::CORPORATION, self::CORE_GROUP, self::ALLIANCE];

    /**
     * Check if a user meets a set of requirements
     *
     * @param $account
     * @param $requirements
     * @return bool
     */
    public static function accountMeetsRequirements($account, $requirements)
    {
        $meets_requirements = true;

        foreach ($requirements as $requirement)
        {
            switch ($requirement->type)
            {
                case self::CORPORATION:
                    $meets_requirements = User::where('account_id', $account->id)->where('corporation_id', $requirement->requirement_id)->exists();
                    break;

                case self::CORE_GROUP:
                    $meets_requirements = AccountGroup::where('account_id', $account->id)->where('group_id', $requirement->requirement_id)->exists();
                    break;

                case self::ALLIANCE:
                    $meets_requirements = User::where('account_id', $account->id)->where('alliance_id', $requirement->requirement_id)->exists();
                    break;

                default:
                    break;
            }

            if (!$meets_requirements)
                break;
        }

        return $meets_requirements;
    }

    /**
     * Get all requirements, with their corresponding names
     *
     * @return RecruitmentRequirement[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getAllWithNames()
    {
        $requirements = RecruitmentRequirement::all();

        foreach ($requirements as $requirement)
        {
            switch ($requirement->type)
            {
                case RecruitmentRequirement::CORPORATION:
                    $requirement->name = User::where('corporation_id', $requirement->requirement_id)->first()->corporation_name;
                    break;

                case RecruitmentRequirement::CORE_GROUP:
                    $requirement->name = CoreGroup::where('id', $requirement->requirement_id)->first()->name;
                    break;

                case RecruitmentRequirement::ALLIANCE:
                    $requirement->name = User::where('alliance_id', $requirement->requirement_id)->first()->alliance_name;
                    break;

                default:
                    $requirement->name = null;
                    break;
            }
        }

        return $requirements;
    }

    /**
     * Get all possible application requirements
     * Filters by alliance whitelist for corps/alliances
     *
     * @param $setId
     * @return object
     */
    public static function getPossibleRequirements($id = 0)
    {
        $output = [];
        $alliance_whitelist = explode(',', env('ALLIANCE_WHITELIST'));
        $core_groups = CoreGroup::all();
        $alliances = User::select(['alliance_id', 'alliance_name'])->whereIn('alliance_id', $alliance_whitelist)->groupBy(['alliance_id', 'alliance_name'])->get();
        $corporations = User::select(['corporation_id', 'corporation_name'])->whereIn('alliance_id', $alliance_whitelist)->groupBy(['corporation_id', 'corporation_name'])->get();

        foreach ($core_groups as $group)
            $output[] = self::createObject($group->name, $group->id, RecruitmentRequirement::CORE_GROUP);

        foreach ($corporations as $corporation)
            $output[] = self::createObject($corporation->corporation_name, $corporation->corporation_id, RecruitmentRequirement::CORPORATION);

        foreach ($alliances as $alliance)
            $output[] = self::createObject($alliance->alliance_name, $alliance->alliance_id, RecruitmentRequirement::ALLIANCE);

        $output['id'] = $id;

        return (object) $output;
    }

    private static function createObject($name, $id, $type)
    {
        $obj = new \stdClass();
        $obj->name = $name;
        $obj->id = $id;
        $obj->type = $type;

        return $obj;
    }

    /**
     * Recruitment ad relationship
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function recruitmentAd()
    {
        return $this->belongsTo('App\Models\RecruitmentAd');
    }
}
