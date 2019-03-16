<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDownlistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('downlist', function (Blueprint $table) {
            $table->integer('id')->default(0);;
            $table->string('site', 50)->default(null)->nullable();
            $table->string('title', 30)->default(null)->nullable();
            $table->string('source', 100)->default(null)->nullable();
            $table->integer('item_id')->default(0);
            $table->string('status', 30)->default(null)->nullable();
            $table->string('error_info', 50)->default(null)->nullable();
            $table->string('fail_reason', 50)->default(null)->nullable();
            $table->string('attachments', 50)->default(null)->nullable();
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
        Schema::dropIfExists('downlist');
    }
}
