<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * App\Models\ApplicationChangelog
 *
 * @property int $id
 * @property int $application_id
 * @property int $account_id
 * @property int $old_state
 * @property int $new_state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account|null $account
 * @method static \Illuminate\Database\Eloquent\Builder|ApplicationChangelog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ApplicationChangelog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ApplicationChangelog query()
 * @method static \Illuminate\Database\Eloquent\Builder|ApplicationChangelog whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApplicationChangelog whereApplicationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApplicationChangelog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApplicationChangelog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApplicationChangelog whereNewState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApplicationChangelog whereOldState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApplicationChangelog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ApplicationChangelog extends Model
{
    protected $table = 'application_changelog';

    /**
     * Add a changelog entry
     *
     * @param $application_id
     * @param $old_state
     * @param $new_state
     */
    public static function addEntry($application_id, $old_state, $new_state, $account_id = null)
    {
        $account_id = ($account_id == null) ? Auth::user()->id : $account_id;

        $entry = new ApplicationChangelog();
        $entry->application_id = $application_id;
        $entry->account_id = $account_id;
        $entry->old_state = $old_state;
        $entry->new_state = $new_state;
        $entry->save();
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
}
