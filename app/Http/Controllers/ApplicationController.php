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
     * Load an application
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Swagger\Client\Eve\ApiException
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

        return view('application', [
            'alts' => $application->account->alts(),
            'character' => $application->account->main(),
            'application' => $application,
            'states' => Application::$state_names,
            'warnings' => $warnings,
            'sp' => $esi->getSkillpoints()
        ]);
    }

    /**
     * Load character overview (used on the application page)
     *
     * @param $char_id
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     * @throws \Swagger\Client\Eve\ApiException
     * @throws \Throwable
     */
    public function loadOverview($char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);

        $character_info = $esi->getCharacterInfo();
        $clones = $esi->getCloneInfo();
        $corp_history = $esi->getCorpHistory();
        $contacts = $esi->getContacts();
        $res = view('parts/application/overview', [
            'application' => true,
            'character_info' => $character_info,
            'clones' => $clones,
            'corp_history' => $corp_history,
            'contacts' => $contacts
        ])->render();

        die(json_encode(['success' => true, 'message' => $res]));
    }

    /**
     * Load user skills
     *
     * @param $char_id
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     * @throws \Swagger\Client\Eve\ApiException
     * @throws \Throwable
     */
    public function loadSkills($char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);

        $skill_groups = [
            ["Spaceship Command", "Subsystems", "Shield", "Armor", "Rigging", "Missiles", "Gunnery", "Drones"],
            ["Engineering", "Navigation", "Electronic Systems", "Targeting", "Fleet Support", "Scanning", "Neural Enhancement"],
            ["Science", "Resource Processing", "Production", "Planet Management", "Structure Management", "Social", "Trade", "Corporation Management"]
        ];

        $skills = $esi->getSkills();
        $res = view('parts/application/skills', ['skills' => $skills, 'skill_groups' => $skill_groups])->render();

        die(json_encode(['success' => true, 'message' => $res]));
    }

    /**
     * Load user mail
     *
     * @param $char_id
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     * @throws \Swagger\Client\Eve\ApiException
     * @throws \Throwable
     */
    public function loadMail($char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);

        $mail = $esi->getMail();
        $res = view('parts/application/mail', ['mails' => $mail])->render();

        die(json_encode(['success' => true, 'message' => $res]));
    }

    /**
     * Load assets and journal tab
     *
     * @param $char_id
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     * @throws \Swagger\Client\Eve\ApiException
     * @throws \Throwable
     */
    public function loadAssetsJournal($char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);

        $assets = $esi->getAssets();
        $journal = $esi->getJournal();
        $res = view('parts/application/assets_journal', ['assets' => $assets, 'journal' => $journal])->render();

        die(json_encode(['success' => true, 'message' => $res]));
    }

    /**
     * Load a user's market information
     *
     * @param $char_id
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Swagger\Client\Eve\ApiException
     * @throws \Throwable
     */
    public function loadMarket($char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);

        $transactions = $esi->getTransactions();
        $orders = $esi->getMarketOrders();
        $res = view('parts/application/market', ['transactions' => $transactions, 'orders' => $orders])->render();

        die(json_encode(['success' => true, 'message' => $res]));
    }

    /**
     * Load a user's notifications
     *
     * @param $char_id
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     * @throws \Swagger\Client\Eve\ApiException
     * @throws \Throwable
     */
    public function loadNotifications($char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);

        $notifications = $esi->getNotifications();
        $res = view('parts/application/notifications', ['notifications' => $notifications])->render();

        die(json_encode(['success' => true, 'message' => $res]));
    }

    /**
     * Load a user's contracts
     *
     * @param $char_id
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     * @throws \Swagger\Client\Eve\ApiException
     * @throws \Throwable
     */
    public function loadContracts($char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);

        $contracts = $esi->getContracts();
        $res = view('parts/application/contracts', ['contracts' => $contracts])->render();

        die(json_encode(['success' => true, 'message' => $res]));
    }

    /**
     * Check to make sure the recruiter can view the esi
     *
     * @param $char_id
     */
    private function checkPermissions($char_id)
    {
        $char = User::find($char_id);

        if (!$char)
            die(json_encode(['success' => false, 'message' => 'Invalid character ID']));

        if (!AccountRole::recruiterCanViewEsi($char_id) && (!Auth::user()->hasRole($char->corporation_name . ' recruiter') && !Auth::user()->hasRole($char->corporation_name . ' director')))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));
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
            'sp' => $esi->getSkillpoints()
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