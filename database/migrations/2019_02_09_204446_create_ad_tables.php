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
            $table->bigInteger('corp_id')->unique()->nullable()->default(null);
            $table->string('slug');
            $table->longText('text');
            $table->timestamps();
        });

        Schema::create('form', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('recruitment_id');
            $table->mediumText('question');
            $table->timestamps();

            $table->foreign('recruitment_id')->references('id')->on('recruitment_ad')->onDelete('cascade');
        });

        Schema::create('form_response', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('question_id');
            $table->integer('application_id');
            $table->timestamps();

            // TODO: Application FK
            $table->foreign('question_id')->references('id')->on('form')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_response');
        Schema::dropIfExists('form');
        Schema::dropIfExists('recruitment_ad');
    }
}
