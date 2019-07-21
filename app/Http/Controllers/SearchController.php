<?php

namespace App\Http\Controllers;

use App\Models\Permission\AccountRole;
use App\Models\Permissions\Role;
use App\Models\RecruitmentAd;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function navbarCharacterSearch()
    {
        $user = Auth::user();

        if (!$user->hasRole('admin') && !$user->hasRoleLike('%recruiter') && !$user->hasRoleLike('%director'))
            return redirect('/')->with('error', 'Unauthorized');

        /*
         * 1. Get account roles
         * 2. Get corresponding corp IDs
         * 3. Limit search results by those IDs
         * or
         * 1. Get ad IDs that the user can view
         * 2. Limit search results to characters from applications of those IDs
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
        $adIds = array_map(function($ad) {
            return $ad->id;
        }, AccountRole::getAdsUserCanView());

        $res = DB::table('user')
            ->select('user.*')
            ->leftJoin('application', 'application.account_id', '=', 'user.account_id')
            ->where('name', 'like', '%' . Input::get('search') . '%')
            ->where(function (Builder $query) use ($corps, $adIds) {
                $query
                    ->whereIn('user.corporation_id', $corps)
                    ->orWhereIn('application.recruitment_id', $adIds)
                ;
            })->distinct()->get();
        return view('search_results', ['results' => $res]);
    }
}
