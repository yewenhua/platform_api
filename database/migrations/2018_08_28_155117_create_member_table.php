<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member', function (Blueprint $table) {
            $table->increments('id');
            $table->string('mobile')->default(null)->nullable();
            $table->string('password')->default(null)->nullable();
            $table->integer('uid')->default(0);
            $table->integer('combo_id')->default(0);
            $table->dateTime('begin')->default(null)->nullable();
            $table->dateTime('end')->default(null)->nullable();
            $table->integer('count')->default(0);
            $table->integer('isopen')->default(0);
            $table->dateTime('last_login_time')->default(null)->nullable();
            $table->timestamps();
            $table->softDeletes(); //添加软删除
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member');
    }
}
