<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('role', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('recruitment_id')->nullable();

            $table->unique(['name', 'slug', 'recruitment_id']);

            $table->foreign('recruitment_id')->references('id')->on('recruitment_ad')->onDelete('cascade');

            $table->timestamps();
        });

        Schema::create('account_role', function (Blueprint $table) {
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('role_id');
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('account')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('role')->onDelete('cascade');

            $table->unique(['account_id', 'role_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('account_role');
        Schema::dropIfExists('role');
    }
}
