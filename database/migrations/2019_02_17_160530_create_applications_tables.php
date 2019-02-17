<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApplicationsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('application', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('recruitment_id');
            $table->tinyInteger('status');
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('account')->onDelete('cascade');
            $table->foreign('recruitment_id')->references('id')->on('recruitment_ad')->onDelete('cascade');
        });

        Schema::create('form_response', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('question_id');
            $table->unsignedInteger('application_id');
            $table->text('response');
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('account')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('form')->onDelete('cascade');
            $table->foreign('application_id')->references('id')->on('application')->onDelete('cascade');
        });

        Schema::create('application_changelog', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('application_id');
            $table->unsignedInteger('account_id');
            $table->tinyInteger('old_state');
            $table->tinyInteger('new_state');
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('application')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('account')->onDelete('cascade');
        });

        Schema::create('comment', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('application_id');
            $table->unsignedInteger('account_id');
            $table->text('comment');
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('application')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('account')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('application');
        Schema::dropIfExists('form_response');
        Schema::dropIfExists('application_changelog');
        Schema::dropIfExists('comment');
    }
}
