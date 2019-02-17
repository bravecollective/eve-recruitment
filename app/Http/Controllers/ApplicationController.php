<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\FormQuestion;
use App\Models\RecruitmentAd;
use App\Models\RecruitmentRequirement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

class ApplicationController extends Controller
{

    /**
     * Load an ad from the slug
     *
     * @param $slug
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function loadAdBySlug($slug)
    {
        $ad = RecruitmentAd::where('slug', $slug)->first();

        if (!$ad)
            return redirect('/')->with('error', 'Recruitment ad does not exist');

        $requirements = $ad->requirements;

        if (!RecruitmentRequirement::accountMeetsRequirements(Auth::user(), $requirements))
            return redirect('/')->with('error', 'Unauthorized');

        $name = ($ad->corp_id == null) ? $ad->group_name : User::where('corporation_id', $ad->corp_id)->first()->corporation_name;
        $form = FormQuestion::where('recruitment_id', $ad->id)->get();

        return view('view_ad', ['id' => $ad->id, 'name' => $name, 'text' => $ad->text, 'questions' => $form]);
    }

    /**
     * Apply to a recruitment ad
     *
     * @param $recruitment_id
     */
    public function apply($recruitment_id)
    {
        $ad = RecruitmentAd::find($recruitment_id);

        if (!$ad)
            die(json_encode(['success' => false, 'message' => 'Recruitment ad does not exist']));

        $requirements = $ad->requirements;

        if (!RecruitmentRequirement::accountMeetsRequirements(Auth::user(), $requirements))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        if (!Application::canApply(Auth::user(), $ad))
            die(json_encode(['success' => false, 'message' => 'You cannot apply to this recruitment ad. Please contact a recruiter for further information.']));

        $questions = Input::get('questions');

        if ($questions)
        {
            foreach ($questions as $question)
            {
                if (!$question['response'])
                    die(json_encode(['success' => false, 'message' => 'All question responses are required']));
            }
        }

        $application = Application::apply(Auth::user()->id, $recruitment_id);
        FormResponse::saveResponse($application->id, $questions);
        die(json_encode(['success' => true, 'message' => 'Application submitted']));
    }
}