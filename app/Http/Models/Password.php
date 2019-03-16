<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Password extends Model
{
    use SoftDeletes;

    protected  $table = 'password';
    protected $fillable = ['uid', 'member_id', 'password', 'mobile', 'email', 'secret', 'combo_id', 'status', 'isopen']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除

    public function findByPwd($password)
    {
        return $this->whereNull('deleted_at')->where('password', $password)->first();
    }
}
