<?php

namespace App\Models;

use App\Connectors\EsiConnection;
use App\Models\Permission\AccountRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Application extends Model
{
    // Application states
    const OPEN = 1;
    const ON_HOLD = 2;
    const ACCEPTED = 3;
    const DENIED = 4;
    const REVIEW_REQUESTED = 5;
    const CLOSED = 6;
    const TRIAL = 8;
    const IN_PROGRESS = 9;
    const REVOKED = 10;

    /**
     * NOTE: $state_names is the base for the tooltips
     * If a key exists in $state_names that doesn't exist in $tooltips (and vice versa), it will be
     * ignored.
     */

    // Map states to names
    public static $state_names = [
        self::ACCEPTED => "Accepted",
        self::ON_HOLD => "Awaiting Information",
        self::CLOSED => "Closed",
        self::DENIED => "Denied",
        self::IN_PROGRESS => "In Progress",
        self::OPEN => "Open",
        self::REVIEW_REQUESTED => "Review Requested",
        self::TRIAL => "Trial",
        self::REVOKED => "Revoked",
    ];

    // Tooltips to show on the application page
    public static $tooltips = [
        self::ACCEPTED => "Accept the application (can re-apply)",
        self::ON_HOLD => "Tell the applicant that they need to provide more information (cannot re-apply)",
        self::CLOSED => "Close the application (can re-apply)",
        self::DENIED => "Deny the application (cannot re-apply)",
        self::IN_PROGRESS => "Indicates someone is working on the application (cannot re-apply)",
        self::OPEN => "New application (cannot re-apply)",
        self::REVIEW_REQUESTED => "Request review from another recruiter (cannot re-apply)",
        self::TRIAL => "Trial applicant (cannot re-apply)",
    ];

    // Override what the user sees
    public static $state_names_overrides = [
        self::REVIEW_REQUESTED => "Open",
        self::TRIAL => "Accepted",
    ];

    protected $table = 'application';

    /**
     * Determine if an application can be revoked or not
     *
     * @param $application
     * @return bool
     */
    public static function canBeRevoked($application)
    {
        if ($application == null || $application->account_id != Auth::user()->id)
            return false;

        $appliedCorp = $application->recruitmentAd->corp_id;

        if ($appliedCorp == null)
            return true;

        $characters = $application->account->characters;
        $canRevoke = true;

        foreach ($characters as $character)
        {
            if ($character->corporation_id == $appliedCorp)
            {
                $canRevoke = false;
                break;
            }
        }

        return $canRevoke;
    }

    /**
     * Given the state ID, return the string representation
     *
     * @param $state
     * @return mixed|string
     */
    public static function getStringForState($state)
    {
        if (!array_key_exists($state, self::$state_names))
            return "UNKNOWN STATE";

        return array_key_exists($state, self::$state_names_overrides) ? self::$state_names_overrides[$state] : self::$state_names[$state];
    }

    /**
     * Apply to a recruitment ad
     *
     * @param $account_id
     * @param $recruitment_id
     * @return Application
     */
    public static function apply($account_id, $recruitment_id)
    {
        $dbApp = Application::where('account_id', $account_id)->where('recruitment_id', $recruitment_id)->first();

        if (!$dbApp)
        {
            $dbApp = new Application();
            $dbApp->account_id = $account_id;
            $dbApp->recruitment_id = $recruitment_id;
        }
        else
            ApplicationChangelog::addEntry($dbApp->id, $dbApp->status, self::OPEN, $account_id);

        $dbApp->status = self::OPEN;
        $dbApp->save();

        return $dbApp;
    }

    /**
     * Get an applicant's warnings
     *
     * @param $application
     * @return array
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Swagger\Client\Eve\ApiException
     */
    public static function getWarnings($application)
    {
        $previously_denied = false;
        $warnings = [];
        $changes = $application->changelog;

        foreach ($changes as $change)
        {
            if ($change->new_state == self::DENIED)
            {
                $previously_denied = true;
                break;
            }
        }

        if ($previously_denied)
            $warnings[] = "User was previously denied";

        $users = User::where('account_id', $application->account_id)->get();
        $hic = $cyno = $cyno5 = false;

        foreach ($users as $user)
        {
            $esi = new EsiConnection($user->character_id);
            $skills = $esi->getSkills();

            foreach ($skills as $catName => $category)
            {
                if ($catName == "Spaceship Command" && !$hic)
                {
                    foreach ($category as $name => $skill)
                    {
                        if ($name == "Heavy Interdiction Cruisers")
                            $hic = true;
                    }
                }
                else if ($catName == "Navigation" && !$cyno)
                {
                    foreach ($category as $name => $skill)
                    {
                        if ($name == "Cynosural Field Theory")
                        {
                            $cyno = true;
                            if ($skill['trained'] == 5)
                                $cyno5 = true;
                        }
                    }
                }
            }

            if ($hic && $cyno && $cyno5)
                break; // This can only break once all 3 are triggered, since the rest of the chars won't change anything
        }

        if ($cyno == true)
            $warnings[] = "Cyno trained";

        if ($cyno5 == true)
            $warnings[] = "Cyno V trained";

        if ($hic == true)
            $warnings[] = "A character in this app can fly a HIC";

        return $warnings;
    }

    /**
     * Get applications for currently logged in user
     *
     * @return mixed
     */
    public static function getUserApplications()
    {
        $applications = self::where('account_id', Auth::user()->id)->get();

        foreach ($applications as $application)
        {
            $last_update = ApplicationChangelog::where('application_id', $application->id)->latest('updated_at')->first();
            $application->last_update = ($last_update) ? $last_update->updated_at : $application->updated_at;
        }

        return $applications;
    }

    /**
     * Get applications for a user that the currently logged in user may see.
     *
     * @param User $character
     * @return Application[]
     */
    public static function getUserApplicationsForRecruiter(User $character)
    {
        $allowedAddIds = [];
        foreach (AccountRole::getAdsUserCanView() as $ad) {
            $allowedAddIds[] = $ad->id;
        }

        $result = [];
        foreach (self::whereAccountId($character->account_id)->get() as $application)
        {
            if (in_array($application->recruitment_id, $allowedAddIds))
            {
                $result[] = $application;
            }
        }

        return $result;
    }

    /**
     * Check if a user can apply to a recruitment ad
     *
     * @param $account
     * @param $ad
     * @return bool
     */
    public static function canApply($account, $ad)
    {
        // States that prohibit the user from re-applying
        $cantReapplyStates = [
            self::DENIED,
            self::ON_HOLD,
            self::REVIEW_REQUESTED,
            self::TRIAL,
            self::OPEN,
            self::IN_PROGRESS,
        ];

        if (Application::where('account_id', $account->id)
            ->where('recruitment_id', $ad->id)
            ->whereIn('status', $cantReapplyStates)
            ->exists())
            return false;

        return true;
    }

    /**
     * Recruitment ad relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function recruitmentAd()
    {
        return $this->belongsTo('App\Models\RecruitmentAd', 'recruitment_id', 'id');
    }

    /**
     * Account relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function account()
    {
        return $this->hasOne('App\Models\Account', 'id', 'account_id');
    }

    /**
     * Changelog relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function changelog()
    {
        return $this->hasMany('App\Models\ApplicationChangelog');
    }

    /**
     * Comments relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany('App\Models\Comment');
    }

    /**
     * Questions relation
     *
     * @return mixed
     */
    public function questions()
    {
        return FormResponse::join('form', 'form.id', '=', 'form_response.question_id')
            ->where('application_id', $this->id)->where('account_id', $this->account_id)
            ->get(['response', 'question', 'form_response.created_at']);
    }
}
