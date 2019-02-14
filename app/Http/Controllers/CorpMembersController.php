<?php

namespace App\Http\Controllers;

use App\Models\Permission\AccountRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class CorpMembersController extends Model
{

    /**
     * Get a listing of corp members
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function viewCorpMembers($corp_id)
    {
        if (!AccountRole::canViewCorpMembers($corp_id))
            return redirect('/')->with('error', 'Unauthorized');

        $corpMembers = User::getCorpMembers($corp_id);

        return view('corp_members', ['corpMembers' => $corpMembers]);
    }
}