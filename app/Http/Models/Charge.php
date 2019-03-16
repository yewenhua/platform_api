<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Charge extends Model
{
    use SoftDeletes;

    protected  $table = 'chargelog';
    protected $fillable = ['member_id', 'password']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除
}
