<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
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

        if (!$user->hasRole('admin') && !$user->hasRoleLike('%manager'))
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $res = User::where('name', 'like', '%' . Input::get('search') . '%');

        die(json_encode(['success' => true, 'message' => $res->get()]));
    }
}