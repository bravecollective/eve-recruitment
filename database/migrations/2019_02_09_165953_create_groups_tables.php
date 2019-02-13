<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name');
        });

        Schema::create('account_group', function (Blueprint $table) {
            $table->unsignedInteger('account_id');
            $table->integer('group_id');

            $table->primary(['account_id', 'group_id']);

            $table->foreign('account_id')->references('id')->on('account')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('group')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('account_group');
        Schema::dropIfExists('group');
        Schema::enableForeignKeyConstraints();
    }
}
