<?php

namespace App\Http\Controllers;

use App\Models\Permission\AccountRole;
use App\Models\User;
use Eloquent;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * App\Http\Controllers\CorpMembersController
 *
 * @method static Builder|CorpMembersController newModelQuery()
 * @method static Builder|CorpMembersController newQuery()
 * @method static Builder|CorpMembersController query()
 * @mixin Eloquent
 */
class CorpMembersController extends Model
{

    /**
     * Get a listing of corp members
     *
     * @return Factory|RedirectResponse|View
     */
    public function viewCorpMembers($corp_id)
    {
        if (!AccountRole::canViewCorpMembers($corp_id))
            return redirect('/')->with('error', 'Unauthorized');

        $corpMembers = User::getCorpMembers($corp_id);

        return view('corp_members', ['corpMembers' => $corpMembers]);
    }
}
