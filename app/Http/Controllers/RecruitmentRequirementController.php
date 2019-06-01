<?php

namespace App\Http\Controllers;

use App\Models\Permission\AccountRole;
use App\Models\RecruitmentRequirement;
use Illuminate\Support\Facades\Auth;

class RecruitmentRequirementController extends Controller
{

    /**
     * Ajax function to get the template for application requirements
     *
     * @throws \Throwable
     */
    public function getTemplate($type, $ad_id)
    {
        if (!AccountRole::userCanEditAd($type, $ad_id))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $requirements = RecruitmentRequirement::getPossibleRequirements(-1);

        die(view('parts/recruitment_requirement', ['requirements' => $requirements])->render());
    }

    // This is a controller function for access to the view() function
    public function getApplicationRequirements($ad_id)
    {
        // No need to check permissions - called from Ad controllers after permission checks
        $output = [];
        $requirements = RecruitmentRequirement::where('recruitment_id', $ad_id)->get();

        foreach ($requirements as $requirement)
            $output[] = view('parts/recruitment_requirement', ['requirements' => RecruitmentRequirement::getPossibleRequirements($requirement->id), 'selected' => $requirement->requirement_id . '-' . $requirement->type])->render();

        return $output;
    }
}