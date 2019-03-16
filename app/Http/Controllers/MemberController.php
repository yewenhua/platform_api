<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Models\Member;
use App\Http\Models\Password;
use App\Http\Models\Charge;
use App\Http\Models\Combo;
use Illuminate\Support\Facades\DB;
use UtilService;
use JWTAuth;

class MemberController extends Controller
{
    const AJAX_SUCCESS = 0;
    const AJAX_FAIL = -1;
    const AJAX_NO_DATA = 10001;
    const AJAX_NO_AUTH = 99999;

    public function lists(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $isadmin = $this->isadmin();
        $search = $request->input('search');
        $page = $request->input('page');
        $limit = $request->input('num');
        $uid = $request->input('uid');
        $start = $request->input('start');
        $end = $request->input('end');
        $start_time = $start.' 00:00:00';
        $end_time = $end.' 23:59:59';
        $like = '%'.$search.'%';
        $offset = ($page - 1) * $limit;
        if(!$isadmin){
            $uid = $user->id;
        }

        $total = Member::whereNull('deleted_at');
        $lists = Member::whereNull('deleted_at');
        if($start && $end){
            $total = $total->where('created_at', '>=', $start_time)->where('created_at', '<=', $end_time);
            $lists = $lists->where('created_at', '>=', $start_time)->where('created_at', '<=', $end_time);
        }

        if($uid != 'all' && $uid){
            $total = $total->where('uid', $uid);
            $lists = $lists->where('uid', $uid);
        }

        if($search){
            $total = $total->where('mobile', 'like', $like);
            $lists = $lists->where('mobile', 'like', $like);
        }

        $total = $total->orderBy('id', 'desc')->get();
        $lists = $lists->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if($lists){
            foreach ($lists as $key=>$list) {
                $combo = Combo::find($list->combo_id);
                $lists[$key]['combo'] = $combo;
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

    public function batchfreeze(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $isadmin = $this->isadmin();
        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required|min:1'
        ]);

        $idarray = explode(',', $idstring);
        if($isadmin){
            $res = Member::whereIn('id', $idarray)->update([
                "isopen" => 0
            ]);
        }
        else{
            $res = Member::whereIn('id', $idarray)->where("uid", $user->id)->update([
                "isopen" => 0
            ]);
        }

        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function batchopen(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $isadmin = $this->isadmin();
        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required|min:1'
        ]);

        $idarray = explode(',', $idstring);
        if($isadmin){
            $res = Member::whereIn('id', $idarray)->update([
                "isopen" => 1
            ]);
        }
        else{
            $res = Member::whereIn('id', $idarray)->where("uid", $user->id)->update([
                "isopen" => 1
            ]);
        }

        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function resetpwd(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $isadmin = $this->isadmin();
        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required'
        ]);

        $idarray = explode(',', $idstring);
        $password = md5('ilovethisgame123456maoxy');
        if($isadmin){
            $res = Member::whereIn('id', $idarray)->update([
                'password' => $password
            ]);
        }
        else{
            $res = Member::whereIn('id', $idarray)->where('uid', $user->id)->update([
                'password' => $password
            ]);
        }

        if ($res) {
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        } else {
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    private function isadmin(){
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员'){
                $flag = true;
                break;
            }
        }

        return $flag;
    }
}
