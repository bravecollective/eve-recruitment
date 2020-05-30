<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
