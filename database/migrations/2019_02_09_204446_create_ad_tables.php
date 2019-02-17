<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recruitment_ad', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('corp_id')->nullable()->default(null);
            $table->string('slug');
            $table->longText('text');
            $table->bigInteger('created_by');
            $table->string('group_name')->nullable()->default(null);
            $table->timestamps();
        });

        Schema::create('form', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('recruitment_id');
            $table->mediumText('question');
            $table->timestamps();

            $table->foreign('recruitment_id')->references('id')->on('recruitment_ad')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form');
        Schema::dropIfExists('recruitment_ad');
    }
}
