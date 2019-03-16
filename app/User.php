<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;  //添加软删除

class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'qq', 'email', 'password', 'discount', 'phone', 'api_address', 'website', 'isopen', 'status', 'combo_list'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected  $dates = ['deleted_at'];  //添加软删除

    /**
     * 一对一关系
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function userinfo(){
        return $this->hasOne('App\Userinfo', 'user_id');
    }

    /**
     * 一对多关系
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts(){
        return $this->hasMany('App\Post', 'user_id');
    }

    /**
     * 属于关系
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country(){
        return $this->belongsTo('App\Country', 'country_id');
    }

    /**
     * 多对多关系
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function group(){
        return $this->belongsToMany('App\Group', 'group_user', 'user_id', 'group_id');
    }

    public function roles(){
        return $this->belongsToMany('App\Role', 'role_user', 'user_id', 'role_id')->withTimestamps();
    }

    //判断用户是否有某个角色，某些角色
    public function isInRoles($roles){
        return !!$roles->intersect($this->roles)->count();   //两个感叹号返回布尔类型   intersect 方法返回两个集合的交集
    }

    //给用户分配角色
    public function assignRole($role){
        return $this->roles()->save($role);
    }

    //取消分配角色
    public function deleteRole($role){
        return $this->roles()->detach($role);
    }

    /*
     * 用户是否有某项权限
     * 权限的角色和用户的角色是否有交集
     */
    public function hasPermission($permission){
        return $this->isInRoles($permission->roles);
    }
}
