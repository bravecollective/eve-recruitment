<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController
{
    public function home(Request $r)
    {
        $characters = null;

        if (Auth::check())
            $characters = User::getUsers(Auth::user()->account_id);

        return view('home', ['characters' => $characters]);
    }
}