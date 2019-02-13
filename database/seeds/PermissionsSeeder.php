<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class PermissionsSeeder extends Seeder
{
    // Setup default permissions
    public function run()
    {
        $permission_list = Config::get('constants.permissions');
        $roles = Config::get('constants.roles');

        Schema::disableForeignKeyConstraints();
        DB::table('permission')->truncate();
        DB::table('role')->truncate();
        Schema::enableForeignKeyConstraints();

        foreach ($permission_list as $permission)
        {
            $perm = new \App\Models\Permissions\Permission();
            $perm->name = str_replace('-', ' ', $permission);
            $perm->slug = $permission;
            $perm->save();
        }

        foreach ($roles as $name => $permissions)
        {
            $role = new \App\Models\Permissions\Role();
            $role->name = str_replace('-', ' ', $name);
            $role->slug = $name;
            $role->save();

            foreach ($permissions as $permission)
            {
                $p = \App\Models\Permissions\Permission::where('slug', $permission_list[$permission])->first();
                $role->permissions()->attach($p);
            }
        }
    }
}