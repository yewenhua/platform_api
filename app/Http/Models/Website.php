<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;  //添加软删除

class Website extends Model
{
    use SoftDeletes;

    protected  $table = 'website';
    protected $fillable = ['name', 'url', 'isopen', 'alias', 'type']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除

    public function combos(){
        return $this->belongsToMany('App\Http\Models\Combo', 'combo_site', 'website_id', 'combo_id')->withTimestamps();
    }
}
