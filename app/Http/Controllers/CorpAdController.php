<?php

namespace App\Http\Controllers;

use App\Models\FormQuestion;
use App\Models\Permissions\Role;
use App\Models\RecruitmentAd;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

class CorpAdController extends Controller
{
    /**
     * View the corp ad edit page
     *
     * Route: /corp/ad
     *
     * @param Request $r
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function manageAd($corp_id)
    {
        if (!Auth::user()->hasRole(User::where('corporation_id', $corp_id)->first()->corporation_name . " director"))
            return redirect('/')->with('error', 'Unauthorized');

        $ad = RecruitmentAd::where('corp_id', Auth::user()->getMainUser()->corporation_id)->first();
        $ad = ($ad == null) ? new RecruitmentAd() : $ad;

        $questions = FormQuestion::where('recruitment_id', $ad->id)->get();

        return view('edit_ad', ['title' => Auth::user()->getMainUser()->corporation_name, 'ad' => $ad, 'questions' => $questions, 'corp_id' => $corp_id]);
    }

    /**
     * Save a corporation recruitment ad
     *
     * Route: /corp/ad/save
     *
     * @param Request $r
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveAd(Request $r)
    {
        if (!Auth::user()->hasPermissionTo(Config::get('constants.permissions')['MANAGE_CORP_AD']))
            return redirect('/')->with('error', 'Unauthorized');

        $slug = $r->input('slug');
        $text = $r->input('text');
        $ad_id = $r->input('ad_id');
        $questions = $r->input('questions');

        if (!$slug || !$text)
            return redirect('/corp/ad')->with('error', 'Slug and text are both required');

        if (!$ad_id)
            $ad = new RecruitmentAd();
        else
            $ad = RecruitmentAd::find($ad_id);

        $ad->created_by = Auth::user()->main_user_id;
        $ad->slug = $slug;
        $ad->text = $text;
        $ad->corp_id = Auth::user()->getMainUser()->corporation_id;
        $ad->save();

        Role::createRoleForAd($ad);

        if ($questions)
        {
            // Outer loop iterates through the different ID sets
            // Should be one of two: question ID, or 0 for new question
            foreach ($questions as $id => $q)
            {
                // Inner loop iterates through questions in that ID set
                foreach ($q as $question)
                {
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

        return redirect('/corp/ad')->with('info', 'Ad updated');
    }
}