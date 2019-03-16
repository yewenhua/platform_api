<?php

namespace App\Services;
use UtilService;
use Illuminate\Support\Facades\Log;

/**
 * 微信公众号
 */

class WechatService
{
    const SNSAPI_BASE = 'snsapi_base';
    const SNSAPI_INFO = 'snsapi_userinfo';

    public function getPageUrl(){
        $url = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') ? 'https://' : 'http://';
        $url .= $_SERVER['HTTP_HOST'];
        $url .= isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : urlencode($_SERVER['PHP_SELF']) . '?' . urlencode($_SERVER['QUERY_STRING']);
        return $url;
    }

    /**
     * 官方access_token有效时间为7200S， 本例设置为6000S，过期后再去重新获取
     * 获取accesstoken...
     * 若正确返回access_token，否则返回null
     */
    private function getAccessToken() {
        $appId = config('wechat.appid');
        $appSecret = config('wechat.appsecret');
        $data = json_decode(file_get_contents(dirname(__FILE__)."/access_token.json"));
        if ($data == null || empty($data) || $data->expire_time < time()) {
            if ($data == null || empty($data)){
                //accesstoken为空
                $data = (object)array();
            }

            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appId."&secret=".$appSecret;
            $result = UtilService::curl_get($url);
            if($result && isset($result['access_token'])){
                //请求接口成功
                $accessToken = $result['access_token'];
                $data->expire_time = time() + 6000;
                $data->access_token = $accessToken;
                $fp = fopen(dirname(__FILE__)."/access_token.json", "w");
                fwrite($fp, json_encode($data));
                fclose($fp);
            }
            else{
                //请求接口失败
                $accessToken = null;
                $fileName = dirname(__FILE__)."/access_token.json";
                if(file_exists($fileName)){
                    //accesstoken清空
                    file_put_contents($fileName, '');
                }
            }
        }
        else {
            //accesstoken有效
            $accessToken = $data->access_token;
        }

        return $accessToken;
    }

    /**
     * 官方jsapi_ticket有效时间为7200S， 本例设置为6000S，过期后再去重新获取
     * 获取jsapi_ticket...
     * 若正确返回jsapi_ticket，否则返回null
     */
    private function getJsapiTicket() {
        $data = json_decode(file_get_contents(dirname(__FILE__)."/jsapi_ticket.json"));
        if ($data == null || empty($data) || $data->expire_time < time()) {
            if ($data == null || empty($data)){
                //jsapi_ticket为空
                $data = (object)array();
            }

            $accesstoken = $this->getAccessToken();
            if($accesstoken !== null){
                $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$accesstoken.'&type=jsapi';
                $result = UtilService::curl_get($url);
                if($result && $result['errcode'] == 0 &&  isset($result['ticket'])){
                    //getJsapiTicket请求接口成功
                    $jsapi_ticket = $result['ticket'];
                    $data->expire_time = time() + 6000;
                    $data->jsapi_ticket = $jsapi_ticket;
                    $fp = fopen(dirname(__FILE__)."/jsapi_ticket.json", "w");
                    fwrite($fp, json_encode($data));
                    fclose($fp);
                }
                else{
                    //getJsapiTicket请求接口失败
                    $jsapi_ticket = null;
                    $fileName = dirname(__FILE__)."/jsapi_ticket.json";
                    if(file_exists($fileName)){
                        //jsapi_ticket清空
                        file_put_contents($fileName, '');
                    }
                }
            }
            else{
                //accesstoken请求接口失败
                $jsapi_ticket = null;
            }
        }
        else {
            //jsapi_ticket有效
            $jsapi_ticket = $data->jsapi_ticket;
        }

        return $jsapi_ticket;
    }

    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign"){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    public function jsapi(){
        $param_url = $this->getPageUrl();
        $jsapi_ticket = $this->getJsapiTicket();
        $appid = config('wechat.appid');
        $params = array();
        $params["url"] = $param_url;
        $params["timestamp"] = time();
        $noncestr = rand(1000000, 9999999);
        $params["noncestr"] = "$noncestr";
        $params["jsapi_ticket"] = $jsapi_ticket;
        ksort($params);
        $paramString = $this->ToUrlParams($params);
        $addrSign = sha1($paramString);

        $data = array(
            "signature" => $addrSign,
            "appId" => $appid,
            "timeStamp" => $params["timestamp"],
            "nonceStr" => $params["noncestr"],
        );
        return $data;
    }

    /**
     * 生成网页授权跳转地址 ，用户同意授权，获取code...
     * @param $redirect_uri
     * @param $stateArr
     * @param $scope
     */
    private function oauth_url($redirect_uri, $state, $scope)
    {
        $urlparam = array(
            'appid=' . config('wechat.appid'),
            'redirect_uri=' . urlencode($redirect_uri),
            'response_type=code',
            'scope=' . $scope,
            'state=' . $state,
        );
        return "https://open.weixin.qq.com/connect/oauth2/authorize?" . join("&", $urlparam) . "#wechat_redirect";
    }

    /**
     * snsapi_base为scope发起的网页授权，是用来获取进入页面的用户的openid的 ...
     * @param $redirect_uri
     * @param $state
     * @return code ...
     */
    public function set_oauth_snsapi_base($redirect_uri, $state)
    {
        return $this->oauth_url($redirect_uri, $state, self::SNSAPI_BASE);
    }

    /**
     * snsapi_userinfo为scope发起的网页授权，是用来获取用户的基本信息的 ...
     * @param $redirect_uri
     * @param $state
     * @return code ...
     */
    public function set_oauth_snsapi_userinfo($redirect_uri, $state)
    {
        return $this->oauth_url($redirect_uri, $state, self::SNSAPI_INFO);
    }

    /**
     * 从微信API获取,通过code换取网页授权access_token等信息
     * @param $code string api返回的code
     * @return array access_token、expires_in、refresh_token、savetime等信息
     */
    public function getOauthInfoByCode($code)
    {
        $urlparam = array(
            'appid=' . config('wechat.appid'),
            'secret=' . config('wechat.appsecret'),
            'code=' . $code,
            'grant_type=authorization_code',
        );
        $apiUrl = "https://api.weixin.qq.com/sns/oauth2/access_token?" . join("&", $urlparam);
        $json = file_get_contents($apiUrl);
        $result = json_decode($json);

        if(isset($result->errcode) && $result->errcode != 0){
            return null;
        }
        else{
            return $this->objectToArray($result);
        }
    }

    /**
     * 网页授权情况下  根据openid获取用户信息
     * @param string $openid 用户openid
     * @param string $accessToken 通过code获取的accessToken
     * @return array用户基本数据
     */
    public function getUserInfoByOauth($openid, $accessToken)
    {
        $urlparam = array(
            'access_token=' . $accessToken,
            'openid=' . $openid,
            'lang=zh_CN',
        );
        $apiUrl = "https://api.weixin.qq.com/sns/userinfo?" . join("&", $urlparam);
        $json = file_get_contents($apiUrl);
        $result = json_decode($json);

        if(isset($result->errcode) && $result->errcode != 0){
            return null;
        }
        else{
            return $this->objectToArray($result);
        }
    }

    /**
     * 非网页授权情况下  根据openid获取用户信息
     * @param string $openid 用户openid
     * @param string $accessToken 通过code获取的accessToken
     * @return array用户基本数据
     */
    public function getUserInfo($openid, $accessToken)
    {
        $urlparam = array(
            'access_token=' . $accessToken,
            'openid=' . $openid,
            'lang=zh_CN',
        );
        $apiUrl = "https://api.weixin.qq.com/cgi-bin/user/info?" . join("&", $urlparam);
        $json = file_get_contents($apiUrl);
        $result = json_decode($json);

        if(isset($result->errcode) && $result->errcode != 0){
            return null;
        }
        else{
            return $this->objectToArray($result);
        }
    }

    private function objectToArray($e){
        $e=(array)$e;
        foreach($e as $k=>$v){
            if( gettype($v)=='resource' ) return;
            if( gettype($v)=='object' || gettype($v)=='array' )
                $e[$k]=(array)($this->objectToArray($v));
        }
        return $e;
    }

    public function arrayToObject($e){
        if( gettype($e)!='array' ) return;
        foreach($e as $k=>$v){
            if( gettype($v)=='array' || getType($v)=='object' )
                $e[$k]=(object)arrayToObject($v);
        }
        return (object)$e;
    }

    /**
     * 对变量进行 JSON 编码
     * @param mixed value 待编码的 value ，除了resource 类型之外，可以为任何数据类型，该函数只能接受 UTF-8 编码的数据
     * @return string 返回 value 值的 JSON 形式
     */
    private function json_encode_ex($value)
    {
        if (version_compare(PHP_VERSION,'5.4.0','<'))
        {
            $str = $this->encode_json($value);
            return $str;
        }
        else
        {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
    }

    //5.3之前中文转码
    private function encode_json($str) {
        return urldecode(json_encode($this->url_encode($str)));
    }

    /**
     *
     */
    private function url_encode($str) {
        if(is_array($str)) {
            foreach($str as $key=>$value) {
                $str[urlencode($key)] = $this->url_encode($value);
            }
        } else {
            $str = urlencode($str);
        }

        return $str;
    }

    public function isInWechat()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        }
        return false;
    }

    /**
     * 创建二维码...
     */
    public function qrcode($type, $scene_str) {
        Log::info('创建二维码，场景值ID:'.$scene_str);
        $qrcode_img_url = null;
        $access_token = $this->getAccessToken();
        if($access_token !== null){
            $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$access_token;
            if($type == 'limit'){
                //临时二维码
                $param = <<<PARAM
    			    {
	    			    "expire_seconds": 2592000,
	    			    "action_name": "QR_STR_SCENE", 
	    			    "action_info": 
	    			        {"scene": 
	    			           {"scene_str": "{$scene_str}"}
    		                }
    		        }
PARAM;
            }
            else{
                //永久二维码
                $param = <<<PARAM
    			    {
	    			    "action_name": "QR_LIMIT_STR_SCENE", 
	    			    "action_info": 
	    			        {"scene": 
	    			           {"scene_str": "{$scene_str}"}
    		                }
    		        }
PARAM;
            }

            $result = UtilService::curl_post($url, $param);
            Log::info('创建二维码，结果：'.var_export($result, true));
            if(isset($result->errcode) && $result->errcode != 0){
                $qrcode_img_url = null;
            }
            else{
                $qrcode_img_url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".urlencode($result->ticket);
            }

        }
        else{
            Log::info('qrcode获取token出错');
        }
        return $qrcode_img_url;
    }

    public function createMenu() {
        header("content-type:text/html; charset=UTF-8");
        $access_token = $this->getAccessToken();
        if($access_token != null){
            $menu_body = config('wechat.menu_array');
            //https请求
            $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
            $return = UtilService::curl_post($url, json_encode($menu_body, JSON_UNESCAPED_UNICODE));
            if(isset($return->errcode) && $return->errcode != 0){
                return '抱歉，菜单生成出错，错误代码为：'.$return->errcode;
            }
            else{
                return '恭喜你，菜单已生成';
            }
        }
        else{
            return 'access_token 错误！';
        }
    }

    public function addressParameters($url, $accesstoken, &$data)
    {
        //签名过程中所有参数名均为小写字符，例如appId 在排序后字符串则为appid，签名五个参数
        $data = array();
        $data["appid"] = config('wechat.appid');
        $data["url"] = $url;  //调用JavaScript API的网页url
        $time = time();
        $data["timestamp"] = "$time";
        $noncestr = rand(1000000, 9999999);
        $data["noncestr"] = "$noncestr";
        $data["accesstoken"] = $accesstoken;  //获取accessToken使用网页授权接口，获取accessToken的scope是snsapi_base
        ksort($data);
        $params = $this->ToUrlParams($data);
        $addrSign = sha1($params);

        $parameters = array(
            "addrSign" => $addrSign,
            "signType" => "sha1",
            "scope" => "jsapi_address",
            "appId" => $data["appid"],
            "timeStamp" => $data["timestamp"],
            "nonceStr" => $data["noncestr"]
        );
        return $parameters;
    }

    public function sendCustomMessage($params)
    {
        header("content-type:text/html; charset=UTF-8");
        $access_token = $this->getAccessToken();
        if($access_token != null){
            if($params['msgtype'] == 'text') {
                $postData = array(
                    'touser' => $params['openid'],
                    'msgtype' => $params['msgtype'],
                    'text' => array(
                        'content' => $params['content']
                    ),
                );
            }
            elseif($params['msgtype'] == 'image'){
                $postData = array(
                    'touser' => $params['openid'],
                    'msgtype' => $params['msgtype'],
                    'image' => array(
                        'media_id'=>$params['media_id']
                    ),
                );
            }
            else{
                $postData = array();
            }

            $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
            $postData = $this->json_encode_ex($postData);
            $return = UtilService::curl_post($url, $postData);
            if(isset($return->errcode) && $return->errcode != 0){
                return false;
            }
            else{
                return true;
            }
        }
        else{
            return false;
        }
    }

    public function sendTemplateMessage($postData)
    {
        $access_token = $this->getAccessToken();
        if($access_token != null){
            $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
            $postData = $this->json_encode_ex($postData);
            $return = UtilService::curl_post($url, $postData);
            if(isset($return->errcode) && $return->errcode != 0){
                return false;
            }
            else{
                return true;
            }
        }
        else{
            return false;
        }
    }

    /**
     * 新增临时素材
     * @param $type image voice video thumb
     */
    public function uploadMediaFile($media_path, $type)
    {
        $access_token = $this->getAccessToken();
        if($access_token !== null){
            $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token={$access_token}&type=image";
            $curl = curl_init ();
            curl_setopt ( $curl, CURLOPT_URL, $url );
            curl_setopt ( $curl, CURLOPT_POST, 1 );
            curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt ( $curl, CURLOPT_CONNECTTIMEOUT, 5 );
            curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, FALSE );
            curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, false );

            if (class_exists('\CURLFile')) {
                $curlfile = curl_file_create(realpath($media_path));
            }
            else{
                $curlfile = '@' . realpath($media_path);
            }
            $postdata = array('media' => $curlfile);

            curl_setopt($curl, CURLOPT_POST,1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($curl, CURLOPT_INFILESIZE,filesize($media_path));
            $return = curl_exec ( $curl );

            if(curl_errno($curl)==0){
                curl_close($curl);
                $result = json_decode($return, true);
                if(isset($result['errcode']) && $result['errcode'] != 0){
                    return null;
                }
                else{
                    return $result['media_id'];
                }
            }else {
                return null;
            }
        }
        else{
            Log::info('uploadMediaFile获取token出错');
            return null;
        }
    }
}