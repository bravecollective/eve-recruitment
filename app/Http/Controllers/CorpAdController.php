<?php

namespace App\Http\Controllers;

use App\Models\FormQuestion;
use App\Models\Permission\AccountRole;
use App\Models\Permission\Role;
use App\Models\RecruitmentAd;
use App\Models\RecruitmentRequirement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CorpAdController extends Controller
{

    /**
     * Manage corp roles
     * @param $corp_id
     */
    public function manageRoles($corp_id)
    {
        $ad = RecruitmentAd::where('corp_id', $corp_id)->first();

        if (!$ad || !Auth::user()->hasRole(User::where('corporation_id', $corp_id)->first()->corporation_name . " director"))
            return redirect('/')->with('error', 'Unauthorized');

        if (!$ad)
            return redirect('/')->with('error', 'Invalid corp ID');

        return view('ad_permissions', [
            'ad' => $ad,
            'recruiters' => RecruitmentAd::getRecruiters($ad->id),
            'adminAccounts' => RecruitmentAd::getDirectors($ad->id),
            'roles' => Role::where('recruitment_id', $ad->id)->get(),
            'autoRoles' => RecruitmentAd::getAutoRoles($ad->id),
        ]);
    }

    public function savePermissions(Request $r)
    {
        $ad_id = $r->input('ad_id');
        $ad = RecruitmentAd::find($ad_id);

        if (!$ad)
            die(json_encode(['success' => false, 'message' => 'Invalid ad ID']));

        if (!AccountRole::userCanEditAd('corp', $ad->corp_id))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        (new PermissionsController())->saveUserRoles($r, $ad->group_name . ' director');
        die(json_encode(['success' => true, 'message' => 'Roles updated']));
    }

    /**
     * Get a user's permissions
     */
    public function loadPermissions(Request $r)
    {
        $ad_id = $r->input('ad_id');
        $ad = RecruitmentAd::find($ad_id);

        if (!$ad)
            die(json_encode(['success' => false, 'message' => 'Invalid ad ID']));

        if (!AccountRole::userCanEditAd('corp', $ad->corp_id))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $user_id = $r->input('user_id');
        $user = User::where('character_id', $user_id)->first();

        if (!$user)
            die(json_encode(['success' => false, 'message' => 'Invalid user ID']));

        $roles = Role::where('recruitment_id', $ad_id)->get();
        $user_roles = AccountRole::where('account_id', $user->account_id)->whereIn('role_id', $roles->pluck('id')->toArray())->get();

        die(json_encode(['success' => true, 'message' => $user_roles]));
    }

    public function listCorpsForRoles()
    {
        if (!Auth::user()->hasRoleLike('%director'))
            return redirect('/')->with('error', 'Unauthorized');

        $corpIDs = array_map(function ($e) { return $e->corp_id; }, AccountRole::getUserCorpMembersOrAdsListing());
        $ads = RecruitmentAd::whereIn('corp_id', $corpIDs)->get();

        return view('list_ads', [
            'title' => 'Corporation',
            'ads' => $ads,
            'permissions' => true
        ]);
    }

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

        $ad = RecruitmentAd::where('corp_id', $corp_id)->first();
        $ad = ($ad == null) ? new RecruitmentAd() : $ad;

        $questions = FormQuestion::where('recruitment_id', $ad->id)->get();
        $requirements = (new RecruitmentRequirementController())->getApplicationRequirements($ad->id);

        return view('edit_ad', ['title' => User::where('corporation_id', $corp_id)->first()->corporation_name, 'ad' => $ad, 'questions' => $questions, 'corp_id' => $corp_id, 'requirements' => $requirements]);
    }

    /**
     * Delete a recruitment ad
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteAd($id)
    {
        $dbAd = RecruitmentAd::find($id);

        if (!$dbAd)
            die(json_encode(['success' => false, 'message' => 'Invalid question ID']));

        if ($dbAd->corp_id == null && $dbAd->created_by != Auth::user()->id)
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $corp_name = ($dbAd->corp_id != null) ? User::where('corporation_id', $dbAd->corp_id)->first()->coropration_name : null;

        if ($corp_name != null && !Auth::user()->hasRole($corp_name . ' director'))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        if ($dbAd->corp_id == null)
            Role::where('recruitment_id', $id)->delete();

        $dbAd->delete();

        return redirect('/')->with('info', 'Ad deleted');
    }

    /**
     * Save a corporation recruitment ad
     *
     * @param Request $r
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveAd($corp_id, Request $r)
    {
        if (!Auth::user()->hasRole(User::where('corporation_id', $corp_id)->first()->corporation_name . " director"))
            return redirect('/')->with('error', 'Unauthorized');

        $slug = $r->input('slug');
        $text = $r->input('text');
        $ad_id = $r->input('ad_id');
        $questions = $r->input('questions');
        $requirements = $r->input('requirements');
        $allow_listing = ($r->input('allow_listing') === null) ? 0 : 1;
        $webhook = $r->input('webhook_url');

        if (!$slug || !$text)
            die(json_encode(['success' => false, 'message' => 'Slug and text are both required']));

        if (strpos($slug, ' ') !== false)
            die(json_encode(['success' => false, 'message' => 'Slug cannot contain spaces']));

        if (!$ad_id)
            $ad = new RecruitmentAd();
        else
            $ad = RecruitmentAd::find($ad_id);

        if (RecruitmentAd::where('slug', $slug)->exists() && $slug != $ad->slug)
            die(json_encode(['success' => false, 'message' => 'Slug already exists']));

        $ad->created_by = Auth::user()->id;
        $ad->slug = $slug;
        $ad->text = $text;
        $ad->corp_id = $corp_id;
        $ad->group_name = User::where('corporation_id', $corp_id)->first()->corporation_name;
        $ad->allow_listing = $allow_listing;

        if (filter_var($webhook, FILTER_VALIDATE_URL))
            $ad->application_notification_url = $webhook;

        $ad->save();

        Role::createRoleForAd($ad);

        if ($questions)
        {
            // Outer loop iterates through the different ID sets
            // Should be one of two: question ID, or 0 for new question
            foreach ($questions as $id => $q)
            {
                if ($q === null)
                    continue;

                // Inner loop iterates through questions in that ID set
                foreach ($q as $question)
                {
                    $q = ($id == 0) ? new FormQuestion() : FormQuestion::find($id);

                    if (!$question)
                        continue;

                    $q->recruitment_id = $ad->id;
                    $q->question = $question;
                    $q->save();
                }
            }
        }

        if ($requirements)
        {
            foreach ($requirements as $id => $requirement)
            {
                if ($requirement === null)
                    continue;

                foreach ($requirement as $r)
                {
                    $data = explode('-', $r);

                    if (sizeof($data) !== 2)
                        continue;

                    $rec_id = $data[0];
                    $type = $data[1];

                    $dbRequirement = ($id == -1) ? new RecruitmentRequirement() : RecruitmentRequirement::find($id);
                    $dbRequirement->type = $type;
                    $dbRequirement->requirement_id = $rec_id;
                    $dbRequirement->recruitment_id = $ad->id;
                    $dbRequirement->save();
                }
            }
        }

        die(json_encode(['success' => true, 'message' => 'Ad updated', 'data' => $ad->id]));
    }
}
