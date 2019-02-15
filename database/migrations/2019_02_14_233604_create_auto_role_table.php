<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAutoRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auto_role', function (Blueprint $table) {
            $table->integer('core_group_id');
            $table->unsignedInteger('role_id');

            $table->foreign('core_group_id')->references('id')->on('group');
            $table->foreign('role_id')->references('id')->on('role')->onDelete('cascade');

            $table->primary(['core_group_id', 'role_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auto_role');
    }
}
