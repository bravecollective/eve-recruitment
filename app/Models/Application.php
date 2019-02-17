<?php

namespace App\Models;

use App\Models\FormResponse;
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
    const BLACKLISTED = 7;
    const TRIAL = 8;

    // Map states to names
    public static $state_names = [
        self::OPEN => "Open",
        self::ON_HOLD => "On Hold",
        self::ACCEPTED => "Accepted",
        self::DENIED => "Denied",
        self::REVIEW_REQUESTED => "Review Requested",
        self::CLOSED => "Closed",
        self::BLACKLISTED => "Blacklisted",
        self::TRIAL => "Trial",
    ];

    // Override what the user sees
    public static $state_names_overrides = [
        self::BLACKLISTED => "Closed",
        self::REVIEW_REQUESTED => "Open",
    ];

    protected $table = 'application';

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

        if (!Auth::user()->hasRole('recruiter') && !Auth::user()->hasRole('director') && array_key_exists($state, self::$state_names_overrides))
            return self::$state_names_overrides[$state];

        return self::$state_names[$state];
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

        $dbApp->status = self::OPEN;
        $dbApp->save();

        return $dbApp;
    }

    public static function getWarnings($application)
    {
        $previously_denied = false;
        $warnings = [];
        $changes = $application->changelog;

        foreach ($changes as $change)
            if ($change->new_state == self::DENIED)
                $previously_denied = true;

        if ($previously_denied)
            $warnings[] = "User was previously denied";

        return $warnings;
    }

    /**
     * Get applications for currently logged in user
     *
     * @return mixed
     */
    public static function getUserApplications()
    {
        return self::where('account_id', Auth::user()->id)->get();
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
            self::BLACKLISTED,
            self::ON_HOLD,
            self::REVIEW_REQUESTED,
            self::ACCEPTED,
            self::OPEN
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
            ->get();
    }
}