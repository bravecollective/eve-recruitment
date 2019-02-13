<?php

namespace App\Http\Injectors;

use App\Models\Permission\AccountRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class ViewInjectorProvider extends ServiceProvider
{

    /**
     * Inject navbar contents into every view
     *
     * This must be done using view()->composer to ensure it's done post-service provider when the views are
     * being composed, or else the Auth class isn't populated yet
     */
    public function boot()
    {
        view()->composer('*', function ($view)
        {
            if (Auth::user() == null)
                return;

            $ads = AccountRole::getAdsUserCanView();
            $corps = AccountRole::getCorpsUserCanView();

            $view->with('recruitment_ads', $ads);
            $view->with('corporations', $corps);
        });
    }
}