<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use JWTAuth;
use UtilService;
use App\Http\Models\Website;
use App\Http\Models\Combo;
use App\Http\Models\Password;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Member;
use Illuminate\Support\Facades\Log;
use App\Http\Models\Cashlog;
use App\Http\Models\System;

class SettingController extends Controller
{
    const AJAX_SUCCESS = 0;
    const AJAX_FAIL = -1;
    const AJAX_NO_DATA = 10001;
    const AJAX_NO_AUTH = 99999;
    private $key = '67280552dd9f0a53389ce2fca801cf42';

    public function website(Request $request){
        $id = $request->input('id');
        $name = $request->input('name');
        $alias = $request->input('alias');
        $url = $request->input('url');
        $type = $request->input('type');
        $isopen = $request->input('isopen');

        $this->validate(request(), [
            'name'=>'required|min:1'
        ]);

        if($id){
            $obj = Website::find($id);
            $obj->id = $id;
            $obj->name = $name;
            $obj->alias = $alias;
            $obj->url = $url;
            $obj->type = $type ? $type : 'point';
            $obj->isopen = $isopen;
            $res = $obj->save();
        }
        else{
            $param = request(['name', 'url', 'isopen', 'alias']);
            $param['type'] = $type ? $type : 'point';
            $res = Website::create($param);
        }

        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function sitelist(Request $request){
        $lists = Website::whereNull('deleted_at')->get();

        if($lists){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function sitepage(Request $request){
        $search = $request->input('search');
        $page = $request->input('page');
        $limit = $request->input('num');
        $start = $request->input('start');
        $end = $request->input('end');
        $start_time = $start.' 00:00:00';
        $end_time = $end.' 23:59:59';
        $like = '%'.$search.'%';
        $offset = ($page - 1) * $limit;

        $total = Website::whereNull('deleted_at');
        $lists = Website::whereNull('deleted_at');
        if($start && $end){
            $total = $total->where('created_at', '>=', $start_time)->where('created_at', '<=', $end_time);
            $lists = $lists->where('created_at', '>=', $start_time)->where('created_at', '<=', $end_time);
        }

        if($search){
            $total = $total->where('name', 'like', $like);
            $lists = $lists->where('name', 'like', $like);
        }

        $total = $total->orderBy('id', 'desc')->get();
        $lists = $lists->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if($lists){
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

    public function sitedelete(Request $request){
        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required|min:1'
        ]);

        $idarray = explode(',', $idstring);
        $res = Website::whereIn('id', $idarray)->delete();;
        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function combo(Request $request){
        $this->validate(request(), [
            'name'=>'required|min:1',
            'price'=>'required',
            'type'=>'required',
            'timelong'=>'required',
            'count'=>'required',
            'sites'=>'required'
        ]);
        $id = $request->input('id');
        $name = $request->input('name');
        $price = $request->input('price');
        $type = $request->input('type');
        $timelong = $request->input('timelong');
        $count = $request->input('count');
        $point = $request->input('point');
        $isopen = $request->input('isopen');
        $sites = $request->input('sites');
        $ids = array();
        foreach ($sites as $item){
            $ids[] = $item['id'];
        }

        //按天是分别对应每个网站的下载次数，每天下载的最高上限次数
        if($type == 'day'){
            $count = 0;
            foreach ($sites as $item){
                $value = isset($item['value']) && $item['value'] ? $item['value'] : 0;
                $count = $count + $value;
            }
        }

        $site_arr = Website::findMany($ids);
        if($type == 'day' || $type == 'count') {
            DB::beginTransaction();
            try {
                if ($id) {
                    $obj = Combo::find($id);
                    $obj->name = $name;
                    $obj->price = $price;
                    $obj->type = $type;
                    $obj->timelong = $timelong;
                    $obj->count = $count;
                    $obj->point = $point;
                    $obj->isopen = $isopen;
                    $obj->save();

                    $currSites = $obj->websites;
                    $addSites = $site_arr->diff($currSites);
                    if(count($addSites) > 0) {
                        foreach ($addSites as $site) {
                            $obj->grantSite($site);

                            $value = array(
                                "count" => 0
                            );
                            foreach ($sites as $item) {
                                if ($item['id'] == $site->id) {
                                    $value['count'] = $item['value'] ? $item['value'] : 0;
                                }
                            }
                            $obj->grantComboDetail($value, $site);
                        }
                    }
                    else{
                        $value = array(
                            "count" => 0,
                            "updated_at" => date('Y-m-d H:i:s')
                        );
                        foreach ($site_arr as $site) {
                            foreach ($sites as $item) {
                                if ($item['id'] == $site->id) {
                                    $value['count'] = $item['value'] ? $item['value'] : 0;
                                }
                            }
                            $obj->grantComboDetail($value, $site);
                        }
                    }

                    $deleteSites = $currSites->diff($site_arr);
                    foreach ($deleteSites as $site) {
                        $obj->deleteSite($site);
                    }
                }
                else {
                    $param = request(['name', 'url', 'price', 'type', 'timelong', 'isopen', 'point']);
                    $param['count'] = $count;
                    $obj = Combo::create($param);
                    if ($obj) {
                        $value = array(
                            "count"=>0,
                            "updated_at" => date('Y-m-d H:i:s')
                        );
                        foreach ($site_arr as $site) {
                            foreach ($sites as $item){
                                if($item['id'] == $site->id){
                                    $value['count'] = isset($item['value']) && $item['value'] ? $item['value'] : 0;
                                }
                            }

                            $obj->grantSite($site);
                            $obj->grantComboDetail($value, $site);
                        }
                    }
                }
                DB::commit();
                return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', '');
            } catch (QueryException $ex) {
                DB::rollback();
                return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '参数错误', '');
        }
    }

    public function combolist(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        if($user->combo_list){
            $arr = explode(',', $user->combo_list);
            $lists = Combo::whereNull('deleted_at')->whereIn('id', $arr)->where('is_private', 0)->get();
        }
        else{
            $lists = Combo::whereNull('deleted_at')->where('is_private', 0)->get();
        }

        if($lists){
            foreach ($lists as $key=>$list) {
                $lists[$key]['sites'] = $list->websites;
            }
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function combopage(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $search = $request->input('search');
        $page = $request->input('page');
        $limit = $request->input('num');
        $start = $request->input('start');
        $end = $request->input('end');
        $start_time = $start.' 00:00:00';
        $end_time = $end.' 23:59:59';
        $like = '%'.$search.'%';
        $offset = ($page - 1) * $limit;

        if($user->combo_list){
            $arr = explode(',', $user->combo_list);
            $total = Combo::whereNull('deleted_at')->whereIn('id', $arr)->where('is_private', 0);
            $lists = Combo::whereNull('deleted_at')->whereIn('id', $arr)->where('is_private', 0);
        }
        else{
            $total = Combo::whereNull('deleted_at')->where('is_private', 0);
            $lists = Combo::whereNull('deleted_at')->where('is_private', 0);
        }

        if($start && $end){
            $total = $total->where('created_at', '>=', $start_time)->where('created_at', '<=', $end_time);
            $lists = $lists->where('created_at', '>=', $start_time)->where('created_at', '<=', $end_time);
        }

        if($search){
            $total = $total->where('name', 'like', $like);
            $lists = $lists->where('name', 'like', $like);
        }

        $total = $total->orderBy('id', 'desc')->get();
        $lists = $lists->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if($lists){
            foreach ($lists as $key=>$list) {
                $lists[$key]['sites'] = $list->websites;
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

    public function combodelete(Request $request){
        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required|min:1'
        ]);

        $idarray = explode(',', $idstring);
        $res = Combo::whereIn('id', $idarray)->delete();;
        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function pwddiy(Request $request){
        $this->validate(request(), [
            'price'=>'required',
            'type'=>'required',
            'timelong'=>'required',
            'count'=>'required',
            'num'=>'required',
            'sites'=>'required'
        ]);
        $type = $request->input('type');
        $count = $request->input('count');
        $sites = $request->input('sites');
        $num = $request->input('num');
        $uid = $request->input('uid');
        $ids = array();
        foreach ($sites as $item){
            $ids[] = $item['id'];
        }

        //按天是分别对应每个网站的下载次数，每天下载的最高上限次数
        if($type == 'day'){
            $count = 0;
            foreach ($sites as $item){
                $value = isset($item['value']) && $item['value'] ? $item['value'] : 0;
                $count = $count + $value;
            }
        }

        $site_arr = Website::findMany($ids);
        $user = DB::table('users')->where('id', $uid)->first();
        if($user && ($type == 'day' || $type == 'count')) {
            DB::beginTransaction();
            try {
                $param = request(['url', 'price', 'type', 'timelong', 'isopen', 'point']);
                $param['count'] = $count;
                $param['name'] = '私人定制';
                $param['is_private'] = 1;
                $obj = Combo::create($param);
                if ($obj) {
                    $value = array(
                        "count"=>0,
                        "updated_at" => date('Y-m-d H:i:s')
                    );
                    foreach ($site_arr as $site) {
                        foreach ($sites as $item){
                            if($item['id'] == $site->id){
                                $value['count'] = isset($item['value']) && $item['value'] ? $item['value'] : 0;
                            }
                        }

                        $obj->grantSite($site);
                        $obj->grantComboDetail($value, $site);
                    }
                }

                $arr = array();
                for($i=0 ;$i<$num; $i++){
                    $str = 'goodluck'.$i.'cat'.time();
                    $password = substr(md5($str),8,16);

                    $params = array(
                        "combo_id" => $obj->id,
                        "uid" => $user->id,
                        "isopen" => 1,
                        "password" => $password,
                        "secret" => md5('123456'),
                        "created_at" => date('Y-m-d H:i:s'),
                        "updated_at" => date('Y-m-d H:i:s')
                    );
                    $arr[] = $params;
                }

                $total_money = $obj->price * $num * $user->discount * 0.01;
                if($user->money >= $total_money) {
                    DB::table('users')->where('id', $user->id)->where('money', '>=', $total_money)->decrement('money', $total_money);;
                    DB::table('password')->insert($arr);
                    Cashlog::create(array(
                        "uid" => $user->id,
                        "type" => 'money',
                        "mark" => 'consume',
                        "money" => $total_money,
                    ));
                }

                DB::commit();
                return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', '');
            } catch (QueryException $ex) {
                DB::rollback();
                return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '参数错误', '');
        }
    }

    public function generatepwd(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $combo_id = $request->input('combo_id');
        $num = $request->input('num');

        $this->validate(request(), [
            'combo_id'=>'required',
            'num'=>'required'
        ]);

        $arr = array();
        for($i=0 ;$i<$num; $i++){
            $str = 'goodluck'.$i.'cat'.time();
            $password = substr(md5($str),8,16);

            $params = array(
                "combo_id" => $combo_id,
                "uid" => $user->id,
                "isopen" => 1,
                "password" => $password,
                "secret" => md5('123456'),
                "created_at" => date('Y-m-d H:i:s'),
                "updated_at" => date('Y-m-d H:i:s')
            );
            $arr[] = $params;
        }

        $combo = DB::table('combo')->where('id', $combo_id)->first();
        $total_money = $combo->price * $num * $user->discount * 0.01;
        $row = DB::table('users')->where('id', $user->id)->first();
        if($row && $row->money >= $total_money) {
            DB::beginTransaction();
            try {
                DB::table('users')->where('id', $user->id)->where('money', '>=', $total_money)->decrement('money', $total_money);;
                DB::table('password')->insert($arr);
                Cashlog::create(array(
                    "uid" => $user->id,
                    "type" => 'money',
                    "mark" => 'consume',
                    "money" => $total_money,
                ));
                DB::commit();
                return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', '');
            } catch (QueryException $ex) {
                DB::rollback();
                return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '余额不足', '');
        }
    }

    public function pwdlist(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员'){
                $flag = true;
                break;
            }
        }

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
        if(!$flag){
            $uid = $user->id;
        }

        $total = Password::select(['id'])->whereNull('deleted_at');
        $lists = Password::whereNull('deleted_at');
        if($start && $end){
            $total = $total->where('created_at', '>=', $start_time)->where('created_at', '<=', $end_time);
            $lists = $lists->where('created_at', '>=', $start_time)->where('created_at', '<=', $end_time);
        }

        if($uid != 'all' && $uid){
            $total = $total->where('uid', $uid);
            $lists = $lists->where('uid', $uid);
        }

        if($search){
            $total = $total->where('password', 'like', $like);
            $lists = $lists->where('password', 'like', $like);
        }

        $total = $total->orderBy('id', 'desc')->count();
        $lists = $lists->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if($lists){
            foreach ($lists as $key=>$list) {
                $combo = Combo::find($list->combo_id);
                $lists[$key]['combo'] = $combo;
                $row = \App\User::find($list->uid);
                $lists[$key]['agentname'] = $row ? $row->name: '';

                $row = Combo::find($list->combo_id);
                $lists[$key]['comboname'] = $row ? $row->name: '';
            }

            $res = array(
                'data' => $lists,
                'total' => $total
            );

            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function pwdfreeze(Request $request){
        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required|min:1'
        ]);

        $idarray = explode(',', $idstring);
        $res = Password::whereIn('id', $idarray)->update([
            "isopen" => 0
        ]);

        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    private function check_sign($param, $urlencode=false)
    {
        $rtn = array(
            "code" => 0,
            "message" => ''
        );
        //添加时间戳，隔太久失效，防止被截取重复调用
        if((time() - $param['timestamp'] ) <= 30){
            $buff = "";
            ksort($param);
            foreach ($param as $k => $v)
            {
                if($k != 'sign') {
                    if ($urlencode) {
                        $v = urlencode($v);
                    }
                    $buff .= $k . "=" . $v . "&";
                }
            }

            $string = '';
            if (strlen($buff) > 0)
            {
                //签名步骤一：按字典序排序参数
                $string = substr($buff, 0, strlen($buff)-1);
            }

            //签名步骤二：在string后加入KEY
            $string = $string."&key=".$this->key;
            //签名步骤三：MD5加密
            $string = md5($string);
            //签名步骤四：所有字符转为大写
            $res_sign = strtoupper($string);
            if($param['sign'] == $res_sign){
                //right
                $rtn['code'] = 0;
                $rtn['message'] = 'sign success';
            }
            else{
                //wrong
                $rtn['code'] = -1;
                $rtn['message'] = 'sign fail';
            }
        }
        else{
            //timeout
            $rtn['code'] = -1;
            $rtn['message'] = 'api timeout';
        }

        return $rtn;
    }

    private function sign($param, $key, $urlencode=false)
    {
        $buff = "";
        ksort($param);
        foreach ($param as $k => $v)
        {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }

        $string = '';
        if (strlen($buff) > 0)
        {
            //签名步骤一：按字典序排序参数
            $string = substr($buff, 0, strlen($buff)-1);
        }

        //签名步骤二：在string后加入KEY
        $string = $string."&key=".$key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $res_sign = strtoupper($string);

        return $res_sign;
    }

    public function system(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $id = $request->input('id');
        $webname = $request->input('webname');
        $service = $request->input('service');
        $qq = $request->input('qq');
        $buy_link = $request->input('buy_link');
        $announce = $request->input('announce');
        $icon = $request->input('icon');
        $bg = $request->input('bg');
        $guanggao = $request->input('guanggao');

        if($service) {
            $this->validate(request(), [
                'service' => 'required'
            ]);

            if ($id) {
                $obj = System::find($id);
                $obj->service = $service;
                $obj->webname = $webname;
                $obj->qq = $qq;
                $obj->buy_link = $buy_link;
                $obj->announce = $announce;
                $obj->icon = $icon;
                $obj->bg = $bg;
                $obj->guanggao = $guanggao;
                $res = $obj->save();
            } else {
                $param = request(['service', 'webname', 'qq', 'buy_link', 'announce', 'icon', 'bg', 'guanggao']);
                $param['uid'] = $user->id;
                $res = System::create($param);
            }

            if ($res) {
                return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
            } else {
                return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '参数错误', '');
        }
    }

    public function sysinfo(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $obj = System::whereNull('deleted_at')->where('uid', $user->id)->first();
        if ($obj) {
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $obj);
        } else {
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }
}
