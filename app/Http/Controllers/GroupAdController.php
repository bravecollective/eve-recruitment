<?php

namespace App\Http\Controllers;

use App\Models\FormQuestion;
use App\Models\Permissions\Role;
use App\Models\RecruitmentAd;
use App\Models\RecruitmentRequirement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        if ($dbAd->corporation_id == null && $dbAd->created_by != Auth::user()->id)
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $corp_name = ($dbAd->corporation_id != null) ? User::where('corporation_id', $dbAd->corporation_id)->first()->coropration_name : null;

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

        if ($dbAd->corporation_id == null && $dbAd->created_by != Auth::user()->id)
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $corp_name = ($dbAd->corporation_id != null) ? User::where('corporation_id', $dbAd->corporation_id)->first()->coropration_name : null;

        if ($corp_name != null && !Auth::user()->hasRole($corp_name . ' director'))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $requirement = RecruitmentRequirement::find($requirement_id);

        if (!$requirement)
            die(json_encode(['success' => false, 'message' => 'Invalid requirement ID']));

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
        if (!Auth::user()->hasRole('director'))
            return redirect('/')->with('error', 'Unauthorized');

        $ads = RecruitmentAd::where('created_by', Auth::user()->id)->where('corp_id', null)->get();

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
        if (!Auth::user()->hasRole('director'))
            return redirect('/')->with('error', 'Unauthorized');

        $ad = RecruitmentAd::find($id);

        if ($ad == null && $id > 0)
            return redirect('/group/ads')->with('error', 'Invalid ad ID');

        if ($ad != null && $ad->created_by !== Auth::user()->id)
            return redirect('/')->with('error', 'Unauthorized');

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
        if (!Auth::user()->hasRole('director'))
            return redirect('/')->with('error', 'Unauthorized');

        $slug = $r->input('slug');
        $text = $r->input('text');
        $ad_id = $r->input('ad_id');
        $questions = $r->input('questions');
        $requirements = $r->input('requirements');

        // TODO: No colons or underscores allowed in the name
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
        } else
            $ad = RecruitmentAd::find($ad_id);

        if (RecruitmentAd::where('group_name', $name)->exists() && $name != $ad->group_name)
            die(json_encode(['success' => false, 'message' => 'Group name already exists']));

        if (RecruitmentAd::where('slug', $slug)->exists() && $slug != $ad->slug)
            die(json_encode(['success' => false, 'message' => 'Slug already exists']));

        $ad->slug = $slug;
        $ad->text = $text;
        $ad->group_name = $name;
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
                    if (!$question)
                        continue;

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

                    $dbRequirement = ($id == 0) ? new RecruitmentRequirement() : RecruitmentRequirement::find($id);
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