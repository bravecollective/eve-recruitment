<?php

use Illuminate\Database\Migrations\Migration;

class AddRoleManager extends Migration
{
    public function up()
    {
        DB::table('role')->insert([
            'name' => 'supervisor',
            'slug' => 'supervisor',
            'created_at' => gmdate('Y-m-d H:i:s'),
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function down()
    {
        DB::table('role')->where('name', 'supervisor')->delete();
    }
}
