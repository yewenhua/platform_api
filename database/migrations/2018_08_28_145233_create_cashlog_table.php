<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCashlogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cashlog', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('uid')->default(null);
            $table->decimal('money')->default(0);
            $table->string('type', 50)->default(null);
            $table->integer('point')->default(0);
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
        Schema::dropIfExists('cashlog');
    }
}
