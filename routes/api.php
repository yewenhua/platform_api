<?php

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::group(['namespace' => 'API', 'middleware'=>['cros']], function () {
    Route::post('login', 'AuthController@login');
    Route::get('logout', 'AuthController@logout');
    Route::post('register', 'AuthController@register');

    Route::group(['middleware' => 'jwt.auth'], function () {
        Route::get('data', function () {
            // Just acting as a ping service.
            return response()->json(['data' => '9999'], 200);
        });
    });
});


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['jwt.auth', 'cros']], function () {
    //用户
    Route::get('admins', 'AdminController@index'); //用户列表页
    Route::get('admins/create', 'AdminController@create'); //用户创建页
    Route::post('admins/store', 'AdminController@store'); //创建用户保存
    Route::get('admins/{user}/role', 'AdminController@role');  //用户角色页   路由模型绑定
    Route::post('admins/{user}/role', 'AdminController@storeRole'); //保存用户角色页   路由模型绑定
    Route::post('admins/delete', 'AdminController@delete');
    Route::post('admins/batchdelete', 'AdminController@batchdelete');
    Route::post('admins/batchfreeze', 'AdminController@batchfreeze');
    Route::post('admins/chgpwd', 'AdminController@chgpwd');
    Route::post('admins/check', 'AdminController@check');
    Route::post('admins/resetpwd', 'AdminController@resetpwd');
    Route::post('admins/charge', 'AdminController@charge');
    Route::get('admins/cashlist', 'AdminController@cashlist');


    //角色
    Route::get('roles', 'RoleController@index');   //列表展示页面
    Route::get('roles/create', 'RoleController@create'); //创建页面
    Route::post('roles/store', 'RoleController@store'); //创建提交页面
    Route::get('roles/{role}/permission', 'RoleController@permission'); //角色权限页面  路由模型绑定
    Route::post('roles/{role}/permission', 'RoleController@storePermission'); //角色权限提交页面  路由模型绑定
    Route::post('roles/delete', 'RoleController@delete');
    Route::get('roles/lists', 'RoleController@lists');


    //权限
    Route::get('permissions', 'PermissionController@index');
    Route::get('permissions/create', 'PermissionController@create');
    Route::post('permissions/store', 'PermissionController@store');
    Route::post('permissions/delete', 'PermissionController@delete');
    Route::post('permissions/batchdelete', 'PermissionController@batchdelete');
    Route::get('allpermissions', 'PermissionController@permissions');
});

Route::group(['namespace' => 'API', 'middleware'=>['cros']], function () {
    Route::get('auth/refresh', 'AuthController@refresh');
});

Route::group(['middleware' => ['jwt.auth', 'cros']], function () {
    Route::get('admins/info', 'AdminController@userInfo');
    Route::get('admins/agents', 'AdminController@agents');

    Route::post('setting/website', 'SettingController@website');
    Route::get('setting/sitelist', 'SettingController@sitelist');
    Route::get('setting/sitepage', 'SettingController@sitepage');
    Route::post('setting/sitedelete', 'SettingController@sitedelete');

    Route::post('setting/combo', 'SettingController@combo');
    Route::get('setting/combolist', 'SettingController@combolist');
    Route::get('setting/combopage', 'SettingController@combopage');
    Route::post('setting/combodelete', 'SettingController@combodelete');

    Route::get('setting/pwdlist', 'SettingController@pwdlist');
    Route::post('setting/generatepwd', 'SettingController@generatepwd');
    Route::post('setting/pwddiy', 'SettingController@pwddiy');
    Route::post('setting/pwdfreeze', 'SettingController@pwdfreeze');

    Route::post('setting/system', 'SettingController@system');
    Route::get('setting/sysinfo', 'SettingController@sysinfo');
    Route::get('setting/statistic', 'SettingController@statistic');

    Route::get('member/lists', 'MemberController@lists');
    Route::post('member/batchfreeze', 'MemberController@batchfreeze');
    Route::post('member/batchopen', 'MemberController@batchopen');
    Route::post('member/resetpwd', 'MemberController@resetpwd');

    Route::get('fonts/page', 'FontsController@findBypage');
    Route::post('fonts/chgstatus', 'FontsController@chgstatus');
});

Route::get('tree/lists', 'TreeController@lists');
Route::post('tree/insert', 'TreeController@insert');
Route::post('tree/update', 'TreeController@update');
Route::post('tree/delete', 'TreeController@delete');
Route::post('tree/singlefile', 'TreeController@singlefile');
Route::get('tree/column', 'TreeController@column');
Route::get('tree/children', 'TreeController@children');
Route::get('tree/home', 'TreeController@home');

Route::get('share/wxlogin', 'ShareController@wxlogin');
Route::get('share/signin', 'ShareController@signinByOpenid');
Route::post('share/order', 'ShareController@order');

Route::get('news', 'NewsController@index');
Route::post('news/store', 'NewsController@store');
Route::post('news/delete', 'NewsController@delete');
Route::post('news/batchdelete', 'NewsController@batchdelete');
Route::get('news/home', 'NewsController@home');
Route::get('news/detail', 'NewsController@detail');

Route::get('cases', 'CaseController@index');
Route::post('cases/store', 'CaseController@store');
Route::post('cases/delete', 'CaseController@delete');
Route::post('cases/batchdelete', 'CaseController@batchdelete');
Route::get('cases/home', 'CaseController@home');

Route::get('banners', 'BannerController@index');
Route::post('banners/store', 'BannerController@store');
Route::post('banners/delete', 'BannerController@delete');
Route::post('banners/batchdelete', 'BannerController@batchdelete');
Route::get('banners/home', 'BannerController@home');

Route::get('batch/combodayclear', 'BatchController@combo_day_clear');
Route::get('batch/fonts', 'BatchController@fontInsertDb');

Route::group(['middleware' => ['cros']], function () {
    Route::get('downaccounts', 'SettingController@downaccounts');
    Route::post('downreport', 'SettingController@downreport');
    Route::get('testget', 'SettingController@testget');
    Route::get('testpost', 'SettingController@testpost');
});





