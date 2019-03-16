<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComboTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('combo', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50)->default(null);
            $table->decimal('price')->default(0);
            $table->string('type', 50)->default(null);
            $table->string('timelong', 50)->default(null);
            $table->integer('isopen');
            $table->integer('is_private');
            $table->integer('count');
            $table->integer('point');
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
        Schema::dropIfExists('combo');
    }
}
