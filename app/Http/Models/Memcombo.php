<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Memcombo extends Model
{
    use SoftDeletes;

    protected  $table = 'memcombo';
    protected $fillable = ['member_id', 'site_id', 'count']; //批量赋值
    protected  $dates = ['deleted_at'];

}
