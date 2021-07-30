<?php

use App\Models\Permission\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class PermissionsSeeder extends Seeder
{
    // Setup default permissions
    public function run()
    {
        $roles = Config::get('constants.roles');

        Schema::disableForeignKeyConstraints();
        DB::table('role')->truncate();
        Schema::enableForeignKeyConstraints();

        foreach ($roles as $name)
        {
            $role = new Role();
            $role->name = str_replace('-', ' ', $name);
            $role->slug = $name;
            $role->save();
        }
    }
}
