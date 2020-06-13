<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateSde extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::unprepared(file_get_contents(dirname(__FILE__) . '/../dumps/invTypes.sql'));
        \Illuminate\Support\Facades\DB::unprepared(file_get_contents(dirname(__FILE__) . '/../dumps/invGroups.sql'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invTypes');
        Schema::dropIfExists('invGroups');
    }
}
