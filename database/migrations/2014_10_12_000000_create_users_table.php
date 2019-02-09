<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account', function (Blueprint $table) {
            $table->integer('id', true);
            $table->bigInteger('main_user_id');
            $table->timestamps();
        });

        Schema::create('user', function (Blueprint $table) {
            $table->integer('account_id');
            $table->bigInteger('character_id')->primary();
            $table->string('name');
            $table->bigInteger('corporation_id');
            $table->string('corporation_name');
            $table->bigInteger('alliance_id')->nullable()->default(null);
            $table->string('alliance_name')->nullable()->default(null);
            $table->timestamps();

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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('account');
        Schema::dropIfExists('user');
        Schema::enableForeignKeyConstraints();
    }
}
