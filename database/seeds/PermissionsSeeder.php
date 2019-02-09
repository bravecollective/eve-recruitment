<?php

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Config;

class PermissionsSeeder extends Seeder
{
    // Setup default permissions
    public function run()
    {
        $permission_list = Config::get('constants.permissions');
        $permissions = Config::get('constants.roles');

        Schema::disableForeignKeyConstraints();
        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();
        Schema::enableForeignKeyConstraints();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Reset cached roles and permissions
        app()['cache']->forget('spatie.permission.cache');

        foreach ($permission_list as $permission)
            Permission::create(['name' => $permission]);

        foreach ($permissions as $group => $permissions_list)
        {
            $role = Role::create(['name' => $group]);
            foreach ($permissions_list as $perm)
                $role->givePermissionTo($permission_list[$perm]);
        }
    }
}