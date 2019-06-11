<?php

namespace App\Http\Controllers;

use App\Models\Permission\AccountRole;
use App\Models\Permissions\Role;
use App\Models\RecruitmentAd;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

class SearchController extends Controller
{

    /**
     * Search for a character, limiting scope by a user's permission
     * @param Request $r
     */
    public function characterSearch(Request $r)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin') && !$user->hasRoleLike('%manager') && !$user->hasRoleLike('%director'))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $res = User::where('name', 'like', '%' . Input::get('search') . '%');

        die(json_encode(['success' => true, 'message' => $res->get()]));
    }

    /**
     * Search from the navbar
     *
     * @param Request $r
     */
    public function navbarCharacterSearch(Request $r)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin') && !$user->hasRoleLike('%recruiter') && !$user->hasRoleLike('%director'))
            return redirect('/')->with('error', 'Unauthorized');

        /*
         * 1. Get account roles
         * 2. Get corresponding corp IDs
         * 3. Limit search results by those IDs
         */
        $roles = AccountRole::where('account_id', Auth::user()->id)->get()->pluck('role_id')->toArray();
        $ads = array_unique(Role::whereIn('id', $roles)
            ->whereNotNull('recruitment_id')
            ->get()
            ->pluck('recruitment_id')
            ->toArray());
        $corps = RecruitmentAd::whereIn('id', $ads)
            ->whereNotNull('corp_id')
            ->get()
            ->pluck('corp_id')
            ->toArray();

        $res = User::where('name', 'like', '%' . Input::get('search') . '%')->whereIn('corporation_id', $corps)->get();
        return view('search_results', ['results' => $res]);
    }
}