<?php

namespace App\Models;

use App\Connectors\EsiConnection;
use App\Connectors\SlackClient;
use App\Models\Permission\AccountRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * App\Models\Application
 *
 * @property int $id
 * @property int $account_id
 * @property int $recruitment_id
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account|null $account
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ApplicationChangelog[] $changelog
 * @property-read int|null $changelog_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[] $comments
 * @property-read int|null $comments_count
 * @property-read \App\Models\RecruitmentAd $recruitmentAd
 * @method static \Illuminate\Database\Eloquent\Builder|Application newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Application newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Application query()
 * @method static \Illuminate\Database\Eloquent\Builder|Application whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Application whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Application whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Application whereRecruitmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Application whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Application whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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

        $last_state = ApplicationChangelog::where('application_id', $application->id)->orderBy('created_at', 'DESC')->first();
        if ($last_state && $last_state->new_state == Application::DENIED)
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

        if ($dbApp->recruitmentAd->application_notification_url !== null)
        {
            try {
                $client = new SlackClient($dbApp->recruitmentAd->application_notification_url);
                $client->send("*New Application* - " . $dbApp->recruitmentAd->group_name . "\nCharacter: {$dbApp->account->main()->name}\nURL: " . env('APP_URL', '') . "/application/{$dbApp->id}");
            } catch (\Exception $e) { }
        }

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

        foreach ($users as $user)
        {
            $esi = new EsiConnection($user->character_id);
            $skills = $esi->getSearchableSkills();

            if (isset($skills["Heavy Interdiction Cruisers"])) {
                $warnings[] = ["type" => "HIC Pilot", "character" => $user->name];
            }

            if (isset($skills["Cynosural Field Theory"]) and $skills["Cynosural Field Theory"]["level"] == 5) {
                $warnings[] = ["type" => "Covert Cyno Pilot", "character" => $user->name];
            }
            elseif (isset($skills["Cynosural Field Theory"])) {
                $warnings[] = ["type" => "Cyno Pilot", "character" => $user->name];
            }

        }

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
