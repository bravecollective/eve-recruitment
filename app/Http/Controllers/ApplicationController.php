<?php

namespace App\Http\Controllers;

use App\Connectors\CoreConnection;
use App\Connectors\SlackClient;
use App\Models\Account;
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
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Seat\Eseye\Exceptions\EsiScopeAccessDeniedException;
use Seat\Eseye\Exceptions\InvalidAuthenticationException;
use Seat\Eseye\Exceptions\InvalidContainerDataException;
use Seat\Eseye\Exceptions\RequestFailedException;
use Seat\Eseye\Exceptions\UriDataMissingException;
use Swagger\Client\Eve\ApiException;
use Throwable;

class ApplicationController extends Controller
{

    /**
     * Load an application
     *
     * @param $id
     * @return Factory|RedirectResponse|View
     * @throws InvalidContainerDataException
     */
    function viewApplication($id)
    {
        $application = Application::find($id);

        if (!$application || $application->status == Application::REVOKED)
            return redirect('/')->with('error', 'Invalid application ID');

        $ad = $application->recruitmentAd;

        if (!AccountRole::canViewApplications($ad))
            return redirect('/')->with('error', 'Unauthorized');

        User::updateUsersOnApplicationLoad($application->account->main_user_id);
        $esi = new EsiConnection($application->account->main_user_id);

        try {
            $sp = $esi->getSkillpoints();
            $isk = $esi->getWalletBalance();
            $titles = $esi->getTitles();
        } catch(Exception) {
            $sp = $isk = $titles = null;
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
     * @return Factory|RedirectResponse|View
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws InvalidAuthenticationException
     * @throws RequestFailedException
     * @throws UriDataMissingException
     * @throws ApiException
     */
    public function viewCharacterEsi($char_id)
    {
        $char = User::find($char_id);

        if (!$char)
            return redirect('/')->with('error', 'Invalid character ID');

        if (!AccountRole::recruiterCanViewEsi($char_id) && (!Auth::user()->hasRole($char->corporation_name . ' recruiter') && !Auth::user()->hasRole($char->corporation_name . ' director')))
            return redirect('/')->with('error', 'Unauthorized');

        User::updateUsersOnApplicationLoad($char_id);

        if (!$char->has_valid_token)
            return view('application', [
                'character' => $char,
                'userApplications' => Application::getUserApplicationsForRecruiter($char),
            ]);

        $esi = new EsiConnection($char_id);

        $char_info = $esi->getCharacterInfo();
        $corp_history = $esi->getCorpHistory();
        $deleted_chars = CoreConnection::getRemovedCharacters($char_id);
        $added_chars = CoreConnection::getAddedCharacters($char_id);
        $contacts = $esi->getContacts();
        $login_details = $esi->getLoginDetails();

        try {
            $sp = $esi->getSkillpoints();
            $isk = $esi->getWalletBalance();
            $titles = $esi->getTitles();
        } catch(Exception) {
            $sp = $isk = $titles = null;
        }

        try {
            $clones = $esi->getCloneInfo();
        } catch(Exception) {
            $clones = null;
        }

        return view('application', [
            'character' => $char,
            'account' => Account::find($char->account_id),
            'character_info' => $char_info,
            'clones' => $clones,
            'corp_history' => $corp_history,
            'contacts' => $contacts,
            'sp' => $sp,
            'isk' => $isk,
            'deleted_characters' => $deleted_chars,
            'added_characters' => $added_chars,
            'titles' => $titles,
            'login_details' => $login_details,
            'userApplications' => Application::getUserApplicationsForRecruiter($char),
        ]);
    }

    /**
     * Load application warnings
     *
     * @param $id
     * @return RedirectResponse
     * @throws InvalidContainerDataException
     * @throws ApiException
     */
    public function loadWarnings($id)
    {
        $application = Application::find($id);

        if (!$application)
            return redirect('/')->with('error', 'Invalid application ID');

        $ad = $application->recruitmentAd;

        if (!AccountRole::canViewApplications($ad))
            return redirect('/')->with('error', 'Unauthorized');

        $warnings = Application::getWarnings($application);
        die(view('parts/application/warnings', ['warnings' => $warnings]));
    }

    /**
     * Load character overview (used on the application page)
     *
     * @param $char_id
     * @throws InvalidContainerDataException
     * @throws Throwable
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
        $added_chars = CoreConnection::getAddedCharacters($char_id);
        $login_details = $esi->getLoginDetails();

        $res = view('parts/application/overview', [
            'application' => true,
            'account' => Account::find($char->account_id),
            'character' => $char,
            'character_info' => $character_info,
            'clones' => $clones,
            'corp_history' => $corp_history,
            'contacts' => $contacts,
            'deleted_characters' => $deleted_chars,
            'login_details' => $login_details,
            'added_characters' => $added_chars,
        ])->render();

        die(json_encode(['success' => true, 'message' => $res]));
    }

    /**
     * Load user skills
     *
     * @param $char_id
     * @throws InvalidContainerDataException
     * @throws ApiException
     * @throws Throwable
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
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     * @throws ApiException
     * @throws Throwable
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
     * Load assets tab
     *
     * @param $char_id
     * @throws Throwable
     */
    public function loadAssets($char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);

        $assets = $esi->getAssets();
        $res = view('parts/application/assets', ['assets' => $assets])->render();

        die(json_encode(['success' => true, 'message' => $res]));
    }

    /**
     * Load assets journal tab
     *
     * @param $char_id
     * @throws Throwable
     */
    public function loadJournal($char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);

        $journal = $esi->getJournal();
        $res = view('parts/application/journal', ['journal' => $journal])->render();

        die(json_encode(['success' => true, 'message' => $res]));
    }

    /**
     * Load a user's market information
     *
     * @param $char_id
     * @throws InvalidContainerDataException
     * @throws ApiException
     * @throws Throwable
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
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     * @throws ApiException
     * @throws Throwable
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
     * @throws InvalidContainerDataException
     */
    public function loadKillmails($char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);

        $killmails = $esi->getKillmails();
        $res = view('parts/application/killmails', ['killmails' => $killmails])->render();

        die(json_encode(['success' => true, 'message' => $res]));
    }

    /**
     * Load a user's contracts
     *
     * @param $char_id
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException
     * @throws ApiException
     * @throws Throwable
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
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException|ApiException
     */
    public function checkFit(Request $r, $char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);
        $fit = $r->input('fit');

        if (!$fit)
            die(json_encode(['success' => false, 'message' => 'Invalid fit']));

        try {
            $itemIDs = EveFittingEFTParser::EveFittingRender($fit);
        } catch(Exception) {
            die(json_encode(['success' => false, 'message' => 'Invalid fit']));
        }

        if ($itemIDs == null)
            die(json_encode(['success' => false, 'message' => 'Invalid fit']));

        foreach ($itemIDs as $itemID)
        {
            if ($itemID < 0)
                continue;
            if (!$esi->characterCanUseItem($itemID))
                die(json_encode(['success' => true, 'message' => '<div class="text-danger">Character can not fly ship</div>']));
        }

        die(json_encode(['success' => true, 'message' => '<div class="text-success">Character can fly ship</div>']));
    }

    /**
     * Check if a character meets a skillplan
     *
     * @param Request $r
     * @param $char_id
     * @throws ApiException
     * @throws InvalidContainerDataException
     */
    public function checkSkillplan(Request $r, $char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);
        $skillplan = $r->input('skillplan');
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
     * Check if a character has a set of assets
     *
     * @param $char_id
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidContainerDataException
     * @throws UriDataMissingException|ApiException
     */
    public function checkAssets(Request $r, $char_id)
    {
        $this->checkPermissions($char_id);
        $esi = new EsiConnection($char_id);
        $assets = $r->input('assets');

        if (!$assets)
            die(json_encode(['success' => false, 'message' => 'Invalid Assets Listing']));

        $assets_list = explode("\n", $assets);

        $found_assets = $esi->getTypeIDs($assets_list);

        if (empty($found_assets))
            die(json_encode(['success' => false, 'message' => 'Invalid Assets Listing']));

        $character_assets = $esi->getUniqueAssets();

        $output = [];

        foreach ($found_assets as $type_id => $type_name) {
            $output[] = [
                "Name" => htmlspecialchars($type_name), 
                "Found" => isset($character_assets[$type_id])
            ];
        }

        die(json_encode(['success' => true, 'data' => $output]));
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
     * @param Request $r
     * @param $id
     */
    function updateState(Request $r, $id)
    {
        $application = Application::find($id);
        $newState = $r->input('state');

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
     * @return Factory|RedirectResponse|View
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
     * @param Request $r
     * @param $id
     */
    function loadAjaxApplications(Request $r, $id)
    {
        $ad = RecruitmentAd::find($id);
        $type = substr($r->input('type'), 1);
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
     * @return Factory|RedirectResponse|View
     */
    public function loadAdBySlug($slug)
    {
        $ad = RecruitmentAd::where('slug', $slug)->first();

        if (!$ad)
            return redirect('/')->with('error', 'Recruitment ad does not exist');

        $requirements = $ad->requirements;

        if (!RecruitmentRequirement::accountMeetsRequirements(Auth::user(), $requirements))
            return redirect('/')->with('error', 'Unauthorized');


        $name = ($ad->corp_id == null) ? $ad->group_name : (new EsiConnection(Auth::user()->id))->getCorporationName($ad->corp_id);
        $form = FormQuestion::where('recruitment_id', $ad->id)->get();

        return view('view_ad', ['id' => $ad->id, 'name' => $name, 'text' => $ad->text, 'questions' => $form]);
    }

    /**
     * Apply to a recruitment ad
     *
     * @param $recruitment_id
     */
    public function apply(Request $r, $recruitment_id)
    {
        $ad = RecruitmentAd::find($recruitment_id);

        if (!$ad)
            die(json_encode(['success' => false, 'message' => 'Recruitment ad does not exist']));

        $requirements = $ad->requirements;

        if (!RecruitmentRequirement::accountMeetsRequirements(Auth::user(), $requirements))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        if (!Application::canApply(Auth::user(), $ad))
            die(json_encode(['success' => false, 'message' => 'You have already applied to this recruitment ad. Please see the homepage for your application status.']));

        $questions = $r->input('questions');

        if ($questions)
        {
            foreach ($questions as $question)
            {
                if (trim($question['response']) === '')
                    die(json_encode(['success' => false, 'message' => 'All question responses are required']));
            }
        }

        $application = Application::apply(Auth::user()->id, $recruitment_id);
        FormResponse::saveResponse($application->id, $questions);
        die(json_encode(['success' => true, 'message' => 'Application submitted']));
    }

    public function getAvailableApplications()
    {
        $ads = RecruitmentAd::where('allow_listing', 1)->orderBy('group_name')->get();
        foreach ($ads as $idx => $ad)
            if (!RecruitmentRequirement::accountMeetsRequirements(Auth::user(), $ad->requirements))
                unset($ads[$idx]);

        return view('available_ads', ['ads' => $ads]);
    }

    public function revokeApplication($application_id)
    {
        $app = Application::find($application_id);

        if (!Application::canBeRevoked($app))
            return redirect('/')->with('error', 'Unauthorized');

        $app->status = Application::REVOKED;
        $app->save();

        if ($app->recruitmentAd->application_notification_url !== null)
        {
            try {
                $client = new SlackClient($app->recruitmentAd->application_notification_url);
                $client->send("*Revoked Application* - " . $app->recruitmentAd->group_name . " \nCharacter: {$app->account->main()->name}\nURL: " . env('APP_URL', '') . "/application/{$app->id}");
            } catch (Exception $e) { }
        }

        return redirect('/')->with('info', 'Application revoked');
    }

    public function deleteApplication($application_id)
    {
        $app = Application::find($application_id);
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('supervisor'))
            return redirect('/')->with('error', 'Unauthorized');
        else if (!$app)
            return redirect('/')->with('error', 'Invalid application ID');

        $app->delete();
        return redirect('/')->with('info', 'Application deleted');
    }

    public function applicationGenerator()
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('supervisor'))
            return redirect('/')->with('error', 'Unauthorized');

        return view('application_generator', ['groups' => RecruitmentAd::all()->sortBy('group_name')]);
    }

    public function createApplication(Request $r) {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('supervisor'))
            return redirect('/')->with('error', 'Unauthorized');

        $user_id = $r->input('char_id');
        $group = $r->input('group');

        $user = User::where('character_id', $user_id)->first();
        $ad = RecruitmentAd::find($group);

        if (!$user)
            return redirect('/')->with('error', 'User does not exist in database');
        if (!$ad)
            return redirect('/')->with('error', 'Invalid ad ID');

        $app = Application::where('account_id', $user->account_id)->where('recruitment_id', $ad->id)->first();
        if (!$app)
            $app = new Application();

        $app->account_id = $user->account_id;
        $app->recruitment_id = $ad->id;
        $app->status = Application::OPEN;
        $app->save();

        return redirect('/application/' . $app->id);
    }
}
