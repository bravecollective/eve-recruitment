<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationChangelog;
use App\Connectors\EsiConnection;
use App\Models\FormQuestion;
use App\Models\FormResponse;
use App\Models\Permission\AccountRole;
use App\Models\RecruitmentAd;
use App\Models\RecruitmentRequirement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

class ApplicationController extends Controller
{

    /**
     * View a single application
     * @param $id
     * @return
     */
    function viewApplication($id)
    {
        $application = Application::find($id);

        if (!$application)
            return redirect('/')->with('error', 'Invalid application ID');

        $ad = $application->recruitmentAd;

        if (!AccountRole::canViewApplications($ad))
            return redirect('/')->with('error', 'Unauthorized');

        $warnings = Application::getWarnings($application);
        $esi = new EsiConnection($application->account->main_user_id);

        $charInfo = $esi->getCharacterInfo();
        $corpHistory = $esi->getCorpHistory();
        $contacts = $esi->getContacts();
        $mail = $esi->getMail();
        $clones = $esi->getCloneInfo();

        return view('application', [
            'alts' => $application->account->alts(),
            'character' => $application->account->main(),
            'application' => $application,
            'states' => Application::$state_names,
            'warnings' => $warnings,
            'character_info' => $charInfo,
            'clones' => $clones,
            'corp_history' => $corpHistory,
            'contacts' => $contacts,
            'mails' => $mail
        ]);
    }

    /**
     * View ESI for a corp member. The return value must be updated as viewApplication's return values are
     *
     * @param $char_id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     * @throws \Swagger\Client\Eve\ApiException
     */
    public function viewCharacterEsi($char_id)
    {
        $char = User::find($char_id);

        if (!$char)
            return redirect('/')->with('error', 'Invalid character ID');

        if (!AccountRole::recruiterCanViewEsi($char_id) && (!Auth::user()->hasRole($char->corporation_name . ' recruiter') && !Auth::user()->hasRole($char->corporation_name . ' director')))
            return redirect('/')->with('error', 'Unauthorized');

        $esi = new EsiConnection($char_id);

        $clones = $esi->getCloneInfo();

        return view('application', [
            'character' => $char,
            'character_info' => $esi->getCharacterInfo(),
            'clones' => $clones,
            'corp_history' => $esi->getCorpHistory(),
            'contacts' => $esi->getContacts(),
            'mails' => $esi->getMail()
        ]);
    }

    /**
     * Update the state of an application
     *
     * @param $id
     */
    function updateState($id)
    {
        $application = Application::find($id);
        $newState = Input::get('state');

        if (!$application)
            die(json_encode(['success' => false, 'message' => 'Invalid application ID']));

        $ad = $application->recruitmentAd;
        $oldState = $application->status;

        if (!AccountRole::canViewApplications($ad))
            return die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        ApplicationChangelog::addEntry($application->id, $oldState, $newState);
        $application->status = $newState;
        $application->save();

        die(json_encode(['success' => true, 'message' => 'Application state updated']));
    }

    /**
     * View applications to a recruitment ad
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    function viewApplications($id)
    {
        $ad = RecruitmentAd::find($id);

        if (!$ad)
            return redirect('/')->with('error', 'Invalid recruitment ID');

        if (!AccountRole::canViewApplications($ad))
            return redirect('/')->with('error', 'Unauthorized');

        $open_apps = Application::where('status', Application::OPEN)->where('recruitment_id', $id)->get();
        $on_hold_apps = Application::where('status', Application::ON_HOLD)->where('recruitment_id', $id)->get();
        $accepted_apps = Application::where('status', Application::ACCEPTED)->where('recruitment_id', $id)->get();
        $closed_apps = Application::whereIn('status', [Application::CLOSED, Application::DENIED])->where('recruitment_id', $id)->get();

        return view('applications',  ['ad' => $ad,
                                            'open_apps' => $open_apps,
                                            'on_hold_apps' => $on_hold_apps,
                                            'accepted_apps' => $accepted_apps,
                                            'closed_apps' => $closed_apps]);
    }

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

    public function getAvailableApplications()
    {
        $ads = RecruitmentAd::where('allow_listing', 1)->get();
        foreach ($ads as $idx => $ad)
            if (!RecruitmentRequirement::accountMeetsRequirements(Auth::user(), $ad->requirements))
                unset($ads[$idx]);

        return view('available_ads', ['ads' => $ads]);
    }
}