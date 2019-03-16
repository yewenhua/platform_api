<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBackendTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('backend', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('site_id')->default(0);
            $table->integer('server_id')->default(0);
            $table->string('username', 30)->default(null)->nullable();
            $table->string('password', 30)->default(null)->nullable();
            $table->string('login_type', 30)->default(null)->nullable();
            $table->integer('isopen')->default(0);
            $table->integer('sync_id')->default(0);
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
        Schema::dropIfExists('backend');
    }
}
