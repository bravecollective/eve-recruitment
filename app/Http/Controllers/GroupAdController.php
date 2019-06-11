<?php

namespace App\Http\Controllers;

use App\Models\FormQuestion;
use App\Models\Permission\AccountRole;
use App\Models\Permissions\Role;
use App\Models\RecruitmentAd;
use App\Models\RecruitmentRequirement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

class GroupAdController extends Controller
{

    /**
     * Delete a question. This is for both group and corp ads
     *
     * @param $ad_id
     * @param $question_id
     */
    public function deleteQuestion($ad_id, $question_id)
    {
        $dbAd = RecruitmentAd::find($ad_id);

        if ($dbAd->corp_id == null && !AccountRole::userCanEditAd('group', $dbAd->id))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $corp_name = ($dbAd->corp_id != null) ? User::where('corporation_id', $dbAd->corp_id)->first()->coropration_name : null;

        if ($corp_name != null && !Auth::user()->hasRole($corp_name . ' director'))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $question = FormQuestion::find($question_id);

        if (!$question)
            die(json_encode(['success' => false, 'message' => 'Invalid question ID']));

        $question->delete();

        die(json_encode(['success' => true, 'message' => 'Question deleted']));
    }

    /**
     * Delete a question. This is for both group and corp ads
     *
     * @param $ad_id
     * @param $requirement_id
     */
    public function deleteRequirement($ad_id, $requirement_id)
    {
        $dbAd = RecruitmentAd::find($ad_id);

        if ($dbAd->corp_id == null && !AccountRole::userCanEditAd('group', $ad_id))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $corp_name = ($dbAd->corp_id != null) ? User::where('corporation_id', $dbAd->corp_id)->first()->coropration_name : null;

        if ($corp_name != null && !Auth::user()->hasRole($corp_name . ' director'))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $requirement = RecruitmentRequirement::find($requirement_id);

        if (!$requirement && $requirement_id >= 0)
            die(json_encode(['success' => false, 'message' => 'Invalid requirement ID']));

        if ($requirement_id >= 0)
            $requirement->delete();

        die(json_encode(['success' => true, 'message' => 'Requirement deleted']));
    }

    /**
     * List group ads belonging to a user
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function listAds()
    {
        if (!Auth::user()->hasRole('group admin') && !Auth::user()->hasRoleLike('%manager'))
            return redirect('/')->with('error', 'Unauthorized');

        $ads = AccountRole::getGroupAdsUsercanView();

        return view('group_ads', ['ads' => $ads]);
    }

    /**
     * Get the ad listing for permission management
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function listAdsForPermissions()
    {
        if (!Auth::user()->hasRole('group admin') && !Auth::user()->hasRoleLike('%manager'))
            return redirect('/')->with('error', 'Unauthorized');

        $ads = AccountRole::getGroupAdsUsercanView();

        return view('group_ads', ['ads' => $ads, 'permissions' => true]);
    }

    /**
     * Render the group permissions page
     *
     * @param $ad_id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function groupPermissions($ad_id)
    {
        $ad = RecruitmentAd::find($ad_id);

        if (!$ad || $ad->corp_id != null)
            return redirect('/')->with('error', 'Invalid ad ID');

        if (!AccountRole::userCanEditAd('group', $ad->id))
            return redirect('/')->with('error', 'Unauthorized');

        return view('group_permissions', [
            'ad' => $ad,
            'recruiters' => RecruitmentAd::getRecruiters($ad_id),
            'roles' => Role::where('recruitment_id', $ad_id)->get()
        ]);
    }

    public function savePermissions()
    {
        $ad_id = Input::get('ad_id');
        $ad = RecruitmentAd::find($ad_id);

        if (!$ad)
            die(json_encode(['success' => false, 'message' => 'Invalid ad ID']));

        if (!AccountRole::userCanEditAd('group', $ad->id))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        (new PermissionsController())->saveUserRoles($ad->group_name . ' manager');
        die(json_encode(['success' => true, 'message' => 'Roles updated']));
    }

    /**
     * Get a user's permissions
     */
    public function loadPermissions()
    {
        $ad_id = Input::get('ad_id');
        $ad = RecruitmentAd::find($ad_id);

        if (!$ad)
            die(json_encode(['success' => false, 'message' => 'Invalid ad ID']));

        if (!AccountRole::userCanEditAd('group', $ad_id))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $user_id = Input::get('user_id');
        $user = User::where('character_id', $user_id)->first();

        if (!$user)
            die(json_encode(['success' => false, 'message' => 'Invalid user ID']));

        $roles = Role::where('recruitment_id', $ad_id)->get();
        $user_roles = AccountRole::where('account_id', $user->account_id)->whereIn('role_id', $roles->pluck('id')->toArray())->get();

        die(json_encode(['success' => true, 'message' => $user_roles]));
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
        if (!AccountRole::userCanEditAd('group', $id))
            return redirect('/')->with('error', 'Unauthorized');

        $ad = RecruitmentAd::find($id);

        if ($ad == null && $id > 0)
            return redirect('/group/ads')->with('error', 'Invalid ad ID');

        $ad = ($ad == null) ? new RecruitmentAd() : $ad;

        $questions = FormQuestion::where('recruitment_id', $ad->id)->get();
        $requirements = (new RecruitmentRequirementController())->getApplicationRequirements($ad->id);

        return view('edit_ad', ['title' => 'Group', 'ad' => $ad, 'questions' => $questions, 'requirements' => $requirements]);
    }

    /**
     * Save a group recruitment ad
     *
     * @param Request $r
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveAd(Request $r)
    {
        $ad_id = $r->input('ad_id');
        $new = false;

        if (!$ad_id && !Auth::user()->hasRole('group admin'))
            return redirect('/')->with('error', 'Unauthorized');
        else if(!AccountRole::userCanEditAd('group', $ad_id))
            return redirect('/')->with('error', 'Unauthorized');

        $slug = $r->input('slug');
        $text = $r->input('text');
        $questions = $r->input('questions');
        $requirements = $r->input('requirements');
        $allow_listing = ($r->input('allow_listing') === null) ? 0 : 1;

        $name = $r->input('name');

        if (!$slug || !$text || !$name)
            die(json_encode(['success' => false, 'message' => 'Slug, text, and name are all required']));

        if (strpos($name, ':') !== false || strpos($name, '_') !== false)
            die(json_encode(['success' => false, 'message' => 'Group name cannot contain colons or underscores']));

        if (strpos($slug, ' ') !== false)
            die(json_encode(['success' => false, 'message' => 'Slug cannot contain spaces']));

        if (!$ad_id) {
            $ad = new RecruitmentAd();
            $ad->created_by = Auth::user()->id;
            $new = true;
        } else
            $ad = RecruitmentAd::find($ad_id);

        if (RecruitmentAd::where('group_name', $name)->exists() && $name != $ad->group_name)
            die(json_encode(['success' => false, 'message' => 'Group name already exists']));

        if (RecruitmentAd::where('slug', $slug)->exists() && $slug != $ad->slug)
            die(json_encode(['success' => false, 'message' => 'Slug already exists']));

        Role::updateGroupRoleName($ad->group_name, $name);

        $ad->slug = $slug;
        $ad->text = $text;
        $ad->group_name = $name;
        $ad->allow_listing = $allow_listing;
        $ad->save();

        Role::createRoleForAd($ad, 'group');

        if ($new)
            Auth::user()->giveRoles(1, $name . ' manager', $name . ' recruiter');

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
                    if (!$question)
                        continue;

                    $q = FormQuestion::find($id);
                    $q = (!$q) ? new FormQuestion() : $q;

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

                    if (sizeof($data) != 2)
                        continue;

                    $rec_id = $data[0];
                    $type = $data[1];

                    $dbRequirement = ($id == -1) ? new RecruitmentRequirement() : RecruitmentRequirement::find($id);
                    $dbRequirement->type = (int) $type;
                    $dbRequirement->requirement_id = $rec_id;
                    $dbRequirement->recruitment_id = $ad->id;
                    $dbRequirement->save();
                }
            }
        }

        die(json_encode(['success' => 'true', 'message' => 'Ad updated', 'data' => $ad->id]));
    }
}