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
        if (Auth::user()->hasRole('admin'))
            $scope = 'global';
        else if (Auth::user()->hasRole('director'))
            $scope = 'corp';
        else
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));

        $res = User::where('name', 'like', '%' . Input::get('search') . '%');

        switch ($scope)
        {
            case 'corp':
                $res->where('corporation_id', Auth::user()->getMainUser()->corporation_id);
                break;

            case 'global':
            default:
                break;
        }

        die(json_encode(['success' => true, 'message' => $res->get()]));
    }
}