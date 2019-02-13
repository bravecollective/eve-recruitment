<?php

namespace App\Http\Controllers;

use App\Models\FormQuestion;
use App\Models\Permissions\Role;
use App\Models\RecruitmentAd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class GroupAdController extends Controller
{
    /**
     * List group ads belonging to a user
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function listAds()
    {
        if (!Auth::user()->hasPermissionTo(Config::get('constants.permissions')['MANAGE_GROUP_AD']))
            return redirect('/')->with('error', 'Unauthorized');

        $ads = RecruitmentAd::where('created_by', Auth::user()->main_user_id)->where('corp_id', null)->get();

        return view('group_ads', ['ads' => $ads]);
    }

    /**
     * Create a new group ad
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function createAd()
    {
        return $this->manageAd(0);
    }

    /**
     * View ad by ID
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function manageAd($id)
    {
        if (!Auth::user()->hasPermissionTo(Config::get('constants.permissions')['MANAGE_CORP_AD']))
            return redirect('/')->with('error', 'Unauthorized');

        $ad = RecruitmentAd::find($id);

        if ($ad == null && $id > 0)
            return redirect('/group/ads')->with('error', 'Invalid ad ID');

        $ad = ($ad == null) ? new RecruitmentAd() : $ad;

        $questions = FormQuestion::where('recruitment_id', $ad->id)->get();

        return view('edit_ad', ['title' => 'Group', 'ad' => $ad, 'questions' => $questions]);
    }

    public function saveAd(Request $r)
    {
        if (!Auth::user()->hasPermissionTo(Config::get('constants.permissions')['MANAGE_CORP_AD']))
            return redirect('/')->with('error', 'Unauthorized');

        $slug = $r->input('slug');
        $text = $r->input('text');
        $ad_id = $r->input('ad_id');
        $questions = $r->input('questions');

        // TODO: No colons or underscores allowed in the name
        $name = $r->input('name');

        if (!$slug || !$text || !$name)
            return redirect('/group/ad/create')->with('error', 'Slug, text, and name are all required');

        if (!$ad_id) {
            $ad = new RecruitmentAd();
            $ad->created_by = Auth::user()->main_user_id;
        } else
            $ad = RecruitmentAd::find($ad_id);

        $ad->slug = $slug;
        $ad->text = $text;
        $ad->group_name = $name;
        $ad->save();

        Role::createRoleForAd($ad);

        if ($questions)
        {
            // Outer loop iterates through the different ID sets
            // Should be one of two: question ID, or 0 for new question
            foreach ($questions as $id => $q) {
                // Inner loop iterates through questions in that ID set
                foreach ($q as $question) {
                    if ($id == 0)
                        $q = new FormQuestion();
                    else
                        $q = FormQuestion::find($id);

                    $q->recruitment_id = $ad->id;
                    $q->question = $question;
                    $q->save();
                }
            }
        }

        return redirect('/group/ad/' . $ad->id)->with('info', 'Ad updated');
    }
}