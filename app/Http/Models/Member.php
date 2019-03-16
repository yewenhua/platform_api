<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use SoftDeletes;

    protected  $table = 'member';
    protected $fillable = ['uid', 'mobile', 'last_login_time', 'password', 'isopen', 'combo_id', 'begin', 'end', 'count', 'point']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除

    public function findByMobile($mobile)
    {
        return $this->whereNull('deleted_at')->where('mobile', $mobile)->first();
    }
}
