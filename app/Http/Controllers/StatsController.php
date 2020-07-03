<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationChangelog;
use App\Models\Permission\AccountRole;
use App\Models\RecruitmentAd;
use App\Models\User;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function loadStatsPage($ad_id)
    {
        $ad = RecruitmentAd::where('id', $ad_id)->first();

        if (!$ad)
            return redirect('/')->with('error', 'Invalid ad ID');

        if (!AccountRole::userCanEditAd('group', $ad_id) && !AccountRole::userCanEditAd('corp', $ad->corp_id))
            return redirect('/')->with('error', 'Unauthorized');

        return view('stats', [
            'name' => $ad->group_name,
            'ad_id' => $ad_id,
            'states' => Application::$state_names
        ]);
    }

    public function lookupStats(Request $r)
    {
        $stats = [];
        $start_state = $r->get('start_state');
        $end_state = $r->get('end_state');
        $start_date = $r->get('start_date');
        $end_date = $r->get('end_date');
        $ad_id = $r->get('ad_id');
        $show_all = $r->get('show-all');

        $ad = RecruitmentAd::where('id', $ad_id)->first();

        if (!$ad)
            die(json_encode(['success'=> false, 'message' => 'Invalid ad ID']));

        if (!AccountRole::userCanEditAd('group', $ad_id) && !AccountRole::userCanEditAd('corp', $ad->corp_id))
            die(json_encode(['success'=> false, 'message' => 'Unauthorized']));

        if (!$start_state || !$end_state || !$start_date || !$end_date)
            die(json_encode(['success'=> false, 'message' => 'All fields are required']));

        $start_state_id = array_search($start_state, Application::$state_names);
        $end_state_id = array_search($end_state, Application::$state_names);

        $changes = ApplicationChangelog::join('account', 'account_id', '=', 'account.id')
            ->join('application', 'application.id', '=', 'application_id')
            ->where('recruitment_id', $ad_id)
            ->where('new_state', '<>', Application::OPEN);

        if (!$show_all)
            $changes = $changes->where('old_state', $start_state_id)->where('new_state', $end_state_id);
        else
            $changes = $changes->whereIn('new_state', [Application::ACCEPTED, Application::DENIED, Application::CLOSED, Application::TRIAL]);

        $changes = $changes->whereBetween('application_changelog.created_at', [$start_date, $end_date])
            ->get();

        foreach ($changes as $change)
        {
            $user = User::where('character_id', $change->main_user_id)->first()->name;

            if (!array_key_exists($user, $stats))
                $stats[$user] = 0;

            $stats[$user]++;
        }

        arsort($stats);

        die(json_encode(['success'=> true, 'message' => json_encode($stats)]));
    }
}
