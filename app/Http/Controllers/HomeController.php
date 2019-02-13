<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController
{
    public function home(Request $r)
    {
        $characters = null;

        if (Auth::check())
            $characters = Auth::user()->characters()->get();

        return view('home', ['characters' => $characters]);
    }
}