<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSdeInvTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invTypes', function (Blueprint $table) {
            $table->integer('factionID')->nullable()->default(null);
            $table->integer('metaLevel')->nullable()->default(null);
            $table->integer('techLevel')->nullable()->default(null);
            $table->integer('shipTreeGroupID')->nullable()->default(null);
            $table->double('packagedVolume')->nullable()->default(null);
            $table->tinyInteger('isDynamicType')->nullable()->default(null);
            $table->tinyInteger('isRepackable')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invTypes', function (Blueprint $table) {
            $table->dropColumn('factionID');
            $table->dropColumn('metaLevel');
            $table->dropColumn('techLevel');
            $table->dropColumn('shipTreeGroupID');
            $table->dropColumn('packagedVolume');
            $table->dropColumn('isDynamicType');
            $table->dropColumn('isRepackable');
        });
    }
}
