<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Combo extends Model
{
    use SoftDeletes;

    protected  $table = 'combo';
    protected $fillable = ['name', 'price', 'type', 'timelong', 'isopen', 'count', 'point', 'is_private']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除

    public function websites(){
        return $this->belongsToMany('App\Http\Models\Website', 'combo_site', 'combo_id', 'website_id')->withPivot('count')->withTimestamps();
    }

    public function grantSite($site){
        return $this->websites()->save($site);
    }

    public function deleteSite($site){
        return $this->websites()->detach($site);
    }

    public function grantComboDetail($value, $site){
        return DB::table('combo_site')
            ->where('combo_id', '=', $this->id)
            ->where('website_id', '=', $site->id)
            ->update($value);
    }
}
