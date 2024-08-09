<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Permission\Role;

class InstallApplication extends Command
{
    protected $signature = 'app:install';

    protected $description = 'This command runs all the necessary procedures to install the eve-recruitment application.';

    public function handle()
    {
        $this->info("=== Roles ===");

        $this->setupRoles();

        $this->line(" ");
        $this->info("DONE!");

        return 1;
    }

    private function setupRoles()
    {
        $roles = config('constants.roles');

        $this->info("Adding roles: ".count($roles));
        $this->info(json_encode($roles));

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
