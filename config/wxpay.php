<?php

/**
 * 微信支付
 */

return [
    'appid' => env('WXPAY_APPID',"wx9e236139460fcf49"),
    'appsecret' => env('WXPAY_APPSECRET',"67280552dd9f0a53389ce2fca801cf42"),
    'mchid' => env('WXPAY_MCHID',"1490706062"),
    'WxPayKey' => env('WXPAY_KEY',"4d5b358f56b8558afb15674b4136ca43"),
    'SSLCERT_PATH' => dirname(dirname(__FILE__)).'/wxpaycer/apiclient_cert.pem',
    'SSLKEY_PATH' => dirname(dirname(__FILE__)).'/wxpaycer/apiclient_key.pem',
    'token' => '08525b857f40f7b87ee4a0206e8e318f'
];