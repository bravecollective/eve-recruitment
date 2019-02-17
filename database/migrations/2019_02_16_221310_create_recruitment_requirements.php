<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecruitmentRequirements extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recruitment_requirement', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('recruitment_id');
            $table->unsignedInteger('requirement_id');
            $table->tinyInteger('type');
            $table->timestamps();

            $table->unique(['id', 'recruitment_id'], 'recruitment_requirement_pk');

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
        Schema::dropIfExists('recruitment_requirement');
    }
}
