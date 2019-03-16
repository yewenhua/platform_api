<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFontsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fonts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 30)->default(null)->nullable();
            $table->string('language', 30)->default(null)->nullable();
            $table->string('style', 30)->default(null)->nullable();
            $table->string('example', 100)->default(null)->nullable();
            $table->integer('isopen')->default(0);
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
        Schema::dropIfExists('fonts');
    }
}
