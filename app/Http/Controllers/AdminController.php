<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use UtilService;
use JWTAuth;
use Hash;
use Illuminate\Support\Facades\Gate;
use App\Order;
use App\Role;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Cashlog;
use App\Http\Models\Member;

class AdminController extends Controller
{
    const AJAX_SUCCESS = 0;
    const AJAX_FAIL = -1;
    const AJAX_NO_DATA = 10001;
    const AJAX_NO_AUTH = 99999;

    const NO_PAY = 0;
    const PAYED = 1;
    const REFUND = 2;
    const CLOSED = 3;

    public function index(Request $request){
        $userObj = JWTAuth::parseToken()->authenticate();
        $page = $request->input('page');
        $limit = $request->input('num');
        $search = $request->input('search');
        $offset = ($page - 1) * $limit;
        $like = '%' . $search . '%';

        $total = \App\User::where('name', 'like', $like);
        $users = \App\User::where('name', 'like', $like);

        $total = $total->orderBy('id', 'desc')
            ->get();

        $users = $users->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if ($users) {
            foreach ($users as $key=>$item) {
                $mem_num = Member::select(['id'])->where('uid', $item->id)->count();
                $roles = $item->roles;
                $users[$key]->mem_num = $mem_num;
                if($roles && count($roles) > 0) {
                    $users[$key]->role_name = $roles[0]->name;
                }
                else{
                    $users[$key]->role_name = '';
                }
            }

            $res = array(
                'user' => $userObj,
                'data' => $users,
                'total' => count($total)
            );
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $res);
        } else {
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function create(){

    }

    public function store(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员'){
                $flag = true;
                break;
            }
        }

        if($flag) {
            $id = $request->input('id');
            $name = $request->input('name');
            $qq = $request->input('qq');
            $email = $request->input('email');
            $api_address = $request->input('api_address');
            $discount = $request->input('discount');
            $website = $request->input('website');
            $password = $request->input('password');
            $isopen = $request->input('isopen');
            $idlist = $request->input('idlist');
            $this->validate(request(), [
                'name' => 'required|min:1',
                'email' => 'required',
                'api_address' => 'required',
                'qq' => 'required',
                'website' => 'required'
            ]);

            if ($id) {
                $user = \App\User::find($id);
                $user->name = $name;
                $user->qq = $qq;
                $user->email = $email;
                $user->api_address = $api_address;
                $user->discount = $discount;
                $user->website = $website;
                $user->isopen = $isopen;
                if($idlist){
                    $user->combo_list = $idlist;
                }
                $res = $user->save();
            } else {
                $params = request(['name', 'qq', 'email', 'api_address', 'discount', 'website', 'isopen']);
                $params['password'] = bcrypt($password);
                $params['status'] = 1;
                if($idlist){
                    $params['combo_list'] = $idlist;
                }
                $res = \App\User::create($params); //save 和 create 的不同之处在于 save 接收整个 Eloquent 模型实例而 create 接收原生 PHP 数组
            }

            if ($res) {
                return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
            } else {
                return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '没有权限', '');
        }
    }

    //路由模型绑定user实例
    public function role(\App\User $user){
        $roles = \App\Role::all(); // all roles
        $myRoles = $user->roles; //带括号的是返回关联对象实例，不带括号是返回动态属性

        //compact 创建一个包含变量名和它们的值的数组
        $data = compact('roles', 'myRoles', 'role');
        return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $data);
    }

    //储存用户角色
    public function storeRole(\App\User $user){
        //验证
        $roles = \App\Role::findMany(request('roles'));
        $myRoles = $user->roles;

        //要增加的角色
        $addRoles = $roles->diff($myRoles);
        foreach ($addRoles as $role){
            $user->assignRole($role);
        }

        //要删除的角色
        $deleteRoles = $myRoles->diff($roles);
        foreach ($deleteRoles as $role){
            $user->deleteRole($role);
        }

        return UtilService::format_data(self::AJAX_SUCCESS, '保存成功', []);
    }

    public function delete(Request $request){
        $id = $request->input('id');
        $this->validate(request(), [
            'id'=>'required|min:1'
        ]);

        $user = \App\User::find($id);
        $res = $user->delete();
        if($user && $res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function batchdelete(Request $request){
        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required|min:1'
        ]);

        $idarray = explode(',', $idstring);
        $res = \App\User::whereIn('id', $idarray)->delete();;
        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function batchfreeze(Request $request){
        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required|min:1'
        ]);

        $idarray = explode(',', $idstring);
        $res = \App\User::whereIn('id', $idarray)->update([
            "isopen" => 0
        ]);

        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function chgpwd(Request $request){
        $id = $request->input('id');
        $oldpwd = $request->input('oldpwd');
        $newpwd = $request->input('newpwd');
        $this->validate(request(), [
            'id'=>'required',
            'oldpwd'=>'required|min:1',
            'newpwd'=>'required'
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        if($user && $user->id == $id){
            $flag = Hash::check($oldpwd, $user->password);
            if($flag) {
                $user->password = bcrypt($newpwd);
                $res = $user->save();
                if ($res) {
                    return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
                } else {
                    return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
                }
            }
            else{
                return UtilService::format_data(self::AJAX_FAIL, '原密码错误', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '用户错误', '');
        }
    }

    public function userInfo(){
        $userObj = JWTAuth::parseToken()->authenticate();
        $roles = $userObj->roles;
        foreach ($roles as $role) {
            $permissions = $role->permissions;
        }

        return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $userObj);
    }

    public function agents(){
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员'){
                $flag = true;
                break;
            }
        }

        if($flag) {
            $lists = \App\User::whereNull('deleted_at')->where('isopen', 1)->get();

            if($lists){
                return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
            }
            else{
                return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '没有权限', '');
        }
    }

    public function resetpwd(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员'){
                $flag = true;
                break;
            }
        }

        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required'
        ]);

        if($flag){
            $idarray = explode(',', $idstring);
            $password = bcrypt('123456');
            $res = \App\User::whereIn('id', $idarray)->update([
                'password' => $password
            ]);

            if ($res) {
                return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
            } else {
                return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '用户错误', '');
        }
    }

    public function charge(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员'){
                $flag = true;
                break;
            }
        }

        $uid = $request->input('uid');
        $type = $request->input('type');
        $value = $request->input('value');
        $this->validate(request(), [
            'uid'=>'required'
        ]);

        if($flag){
            DB::beginTransaction();
            try {
                $params = array(
                    "uid" => $uid,
                    "mark" => 'charge'
                );

                if($type == 'money') {
                    $params['type'] = 'money';
                    $params['money'] = $value;
                    DB::table('users')->where('id', $uid)->increment('money', $value);
                }
                elseif($type == 'point') {
                    $params['type'] = 'point';
                    $params['point'] = $value;
                    DB::table('users')->where('id', $uid)->increment('point', $value);
                }

                Cashlog::create($params);

                DB::commit();
                return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', '');
            } catch (QueryException $ex) {
                DB::rollback();
                return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '没有权限', '');
        }
    }

    public function cashlist(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员'){
                $flag = true;
                break;
            }
        }

        $page = $request->input('page');
        $limit = $request->input('num');
        $start = $request->input('start');
        $end = $request->input('end');
        $start_time = $start.' 00:00:00';
        $end_time = $end.' 23:59:59';
        $uid = $request->input('uid');
        $offset = ($page - 1) * $limit;
        if(!$flag){
            $uid = $user->id;
        }

        $total = Cashlog::whereNull('deleted_at');
        $lists = Cashlog::whereNull('deleted_at');
        if($start && $end){
            $total = $total->where('created_at', '>=', $start_time)->where('created_at', '<=', $end_time);
            $lists = $lists->where('created_at', '>=', $start_time)->where('created_at', '<=', $end_time);
        }

        if($uid != 'all' && $uid){
            $total = $total->where('uid', $uid);
            $lists = $lists->where('uid', $uid);
        }

        $total = $total->orderBy('id', 'desc')->get();
        $lists = $lists->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if($lists){
            foreach ($lists as $key=>$item){
                $row = \App\User::find($item->uid);
                $lists[$key]['username'] = $row ? $row->name: '';
            }
            $res = array(
                'data' => $lists,
                'total' => count($total)
            );
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }
}
