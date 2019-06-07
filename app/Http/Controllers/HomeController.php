<?php

namespace App\Http\Controllers;

use App\Connectors\CoreConnection;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController
{
    public function home(Request $r)
    {
        $characters = null;
        $applications = null;

        if (Auth::check())
        {
            $characters = Auth::user()->characters()->get();
            $applications = Application::getUserApplications();
        }

        return view('home', ['characters' => $characters, 'applications' => $applications]);
    }
}