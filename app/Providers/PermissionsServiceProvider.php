<?php

namespace App\Providers;

use App\Models\Permissions\Permission;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PermissionsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        /*Permission::get()->map(function ($permission) {
            Gate::define($permission->slug, function ($user) use ($permission) {
                return $user->hasPermissionTo($permission);
            });
        });*/

        Blade::directive('role', function ($role){
            return "<?php if(auth()->user()->hasRole({$role})) : ?>";
        });

        Blade::directive('endrole', function (){
            return "<?php endif; ?>";
        });
    }
}
