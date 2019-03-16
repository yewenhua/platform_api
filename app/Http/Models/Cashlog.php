<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cashlog extends Model
{
    use SoftDeletes;

    protected  $table = 'cashlog';
    protected $fillable = ['uid', 'money', 'type', 'point', 'mark']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除
}
