<?php

namespace App\Http\Controllers;

use App\Connectors\CoreConnection;
use App\Models\Application;
use App\Models\ApplicationChangelog;
use App\Connectors\EsiConnection;
use App\Models\EveFittingEFTParser;
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
     */
    function viewApplication($id)
    {
        $application = Application::find($id);

        if (!$application)
            return redirect('/')->with('error', 'Invalid application ID');

        $ad = $application->recruitmentAd;

        if (!AccountRole::canViewApplications($ad))
            return redirect('/')->with('error', 'Unauthorized');

        #User::updateUsersOnApplicationLoad($application->account->main_user_id);
        $esi = new EsiConnection($application->account->main_user_id);

        try {
            $sp = $esi->getSkillpoints();
            $isk = $esi->getWalletBalance();
            $titles = $esi->getTitles();
            $warnings = Application::getWarnings($application);
        } catch(\Exception $e) {
            $sp = $isk = $titles = $warnings = null;
        }

        $tooltips = [];

        foreach (Application::$state_names as $id => $name)
        {
            if (!array_key_exists($id, Application::$tooltips))
                continue;

            $tooltip = Application::$tooltips[$id];
            $tooltips[] = "$name: $tooltip";
        }

        $tooltips = implode("<br>", $tooltips);

        return view('application', [
            'alts' => $application->account->alts(),
            'character' => $application->account->main(),
            'application' => $application,
            'states' => Application::$state_names,
            'warnings' => $warnings,
            'sp' => $sp,
            'isk' => $isk,
            'titles' => $titles,
            'state_tooltip' => $tooltips,
            'userApplications' => Application::getUserApplicationsForRecruiter($application->account->main()),
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
     */
    public function viewCharacterEsi($char_id)
    {
        $char = User::find($char_id);

        if (!$char)
            return redirect('/')->with('error', 'Invalid character ID');

        if (!AccountRole::recruiterCanViewEsi($char_id) && (!Auth::user()->hasRole($char->corporation_name . ' recruiter') && !Auth::user()->hasRole($char->corporation_name . ' director')))
            return redirect('/')->with('error', 'Unauthorized');

        #User::updateUsersOnApplicationLoad($char_id);
        $esi = new EsiConnection($char_id);

        $char_info = $esi->getCharacterInfo();
        $corp_history = $esi->getCorpHistory();
        $deleted_chars = CoreConnection::getRemovedCharacters($char_id);

        try {
            $clones = $esi->getCloneInfo();
            $contacts = $esi->getContacts();
            $sp = $esi->getSkillpoints();
            $isk = $esi->getWalletBalance();
            $titles = $esi->getTitles();
        } catch(\Exception $e) {
            $clones = $contacts = $sp = $isk = $titles = null;
        }

        return view('application', [
            'character' => $char,
            'character_info' => $char_info,
            'clones' => $clones,
            'corp_history' => $corp_history,
            'contacts' => $contacts,
            'sp' => $sp,
            'isk' => $isk,
            'deleted_characters' => $deleted_chars,
            'titles' => $titles,
            'userApplications' => Application::getUserApplicationsForRecruiter($char),
        ]);
    }

    /**
     * Load character overview (used on the application page)
     *
     * @param $char_id
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Throwable
     */
    public function loadOverview($char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);

        $char = User::find($char_id);

        $character_info = $esi->getCharacterInfo();
        $clones = $esi->getCloneInfo();
        $corp_history = $esi->getCorpHistory();
        $contacts = $esi->getContacts();
        $deleted_chars = CoreConnection::getRemovedCharacters($char_id);

        $res = view('parts/application/overview', [
            'application' => true,
            'character' => $char,
            'character_info' => $character_info,
            'clones' => $clones,
            'corp_history' => $corp_history,
            'contacts' => $contacts,
            'deleted_characters' => $deleted_chars
        ])->render();

        die(json_encode(['success' => true, 'message' => $res]));
    }

    /**
     * Load user skills
     *
     * @param $char_id
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
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
        $queue = $esi->getSkillQueue();
        $res = view('parts/application/skills', ['skills' => $skills, 'skill_groups' => $skill_groups, 'queue' => $queue])->render();

        die(json_encode(['success' => true, 'message' => $res]));
    }

    public function getSingleMail($char_id, $id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);

        $mail = $esi->getMailDetails($id);
        return view('parts/application/mail_body', ['mail' => $mail]);
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

        $char_name = User::where('character_id', $char_id)->first()->name;
        $mail = $esi->getMail();
        $res = view('parts/application/mail', ['mails' => $mail, 'name' => $char_name])->render();

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
     * Check if a user can fly a fit
     *
     * @param $char_id
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function checkFit($char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);
        $fit = Input::get('fit');

        if (!$fit)
            die(json_encode(['success' => false, 'message' => 'Invalid fit']));

        try {
            $itemIDs = EveFittingEFTParser::EveFittingRender($fit);
        } catch(\Exception $e) {
            die(json_encode(['success' => false, 'message' => 'Invalid fit']));
        }

        if ($itemIDs == null)
            die(json_encode(['success' => false, 'message' => 'Invalid fit']));

        foreach ($itemIDs as $itemID)
        {
            if ($itemID < 0)
                continue;
            if (!$esi->characterCanUseItem($itemID))
                die(json_encode(['success' => true, 'message' => 'Character can not fly ship']));
        }

        die(json_encode(['success' => true, 'message' => 'Character can fly ship']));
    }

    /**
     * Check if a character meets a skillplan
     *
     * @param $char_id
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function checkSkillplan($char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);
        $skillplan = Input::get('skillplan');
        $levels = [];

        if (!$skillplan)
            die(json_encode(['success' => false, 'message' => 'Invalid fit']));

        $skillplan = str_replace("\r\n", "\n", $skillplan);
        $skills = explode("\n", $skillplan);

        foreach ($skills as $skill)
        {
            $split = strrpos($skill, ' ');
            $name = substr($skill, 0, $split);
            $level = trim(substr($skill, $split));

            if (!$name || !$level)
                continue;

            switch ($level)
            {
                case 'I':
                    $level = 1;
                    break;
                case 'II':
                    $level = 2;
                    break;
                case 'III':
                    $level = 3;
                    break;
                case 'IV':
                    $level = 4;
                    break;
                case 'V':
                    $level = 5;
                    break;
                default:
                    die(json_encode(['success' => false, 'message' => 'Invalid level found']));
            }

            if (!array_key_exists($name, $levels) || $levels[$name] < $level)
                $levels[$name] = $level;
        }

        $res = $esi->checkSkillplan($levels);
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
        $review_requested_apps = Application::where('status', Application::REVIEW_REQUESTED)->where('recruitment_id', $id)->count();
        $on_hold_apps = Application::where('status', Application::ON_HOLD)->where('recruitment_id', $id)->count();
        $trial = Application::where('status', Application::TRIAL)->where('recruitment_id', $id)->count();
        $accepted_apps = Application::where('status', Application::ACCEPTED)->where('recruitment_id', $id)->count();
        $closed_apps = Application::whereIn('status', [Application::CLOSED, Application::DENIED])->where('recruitment_id', $id)->count();
        $in_progress = Application::where('status', Application::IN_PROGRESS)->where('recruitment_id', $id)->count();

        return view('applications',  ['ad' => $ad,
                                            'open_apps' => $open_apps,
                                            'review_requested_apps' => $review_requested_apps,
                                            'on_hold_apps' => $on_hold_apps,
                                            'trial_apps' => $trial,
                                            'accepted_apps' => $accepted_apps,
                                            'closed_apps' => $closed_apps,
                                            'in_progress_apps' => $in_progress]);
    }

    /**
     * Load applications via ajax on tab change
     * @param $id
     */
    function loadAjaxApplications($id)
    {
        $ad = RecruitmentAd::find($id);
        $type = substr(Input::get('type'), 1);
        $states = null;

        if (!$ad)
            die(json_encode(['success' => false, 'message' => 'Invalid ad ID']));

        if (!AccountRole::canViewApplications($ad))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        switch($type)
        {
            case 'open':
                $states = Application::OPEN;
                break;
            case 'review-requested':
                $states = Application::REVIEW_REQUESTED;
                break;
            case 'hold':
                $states = Application::ON_HOLD;
                break;
            case 'trial':
                $states = Application::TRIAL;
                break;
            case 'accepted':
                $states = Application::ACCEPTED;
                break;
            case 'closed':
                $states = [Application::CLOSED, Application::DENIED];
                break;
            case 'in-progress':
                $states = Application::IN_PROGRESS;
                break;
            default:
                break;
        }

        if ($states == null)
            die(json_encode(['success' => false, 'message' => 'Invalid state name']));

        $query = ($type == 'closed') ? Application::whereIn('status', $states) : Application::where('status', $states);
        $query = $query->where('recruitment_id', $id)->orderBy('updated_at', 'asc')->get();

        die(view('application_ajax_page', ['apps' => $query])->render());
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
            die(json_encode(['success' => false, 'message' => 'You have already applied to this recruitment ad. Please see the homepage for your application status.']));

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
