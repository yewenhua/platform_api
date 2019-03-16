<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use JWTAuth;
use UtilService;
use WxpayService;
use WechatService;
use Illuminate\Support\Facades\Log;
use App\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class WxpayController extends Controller
{
    const AJAX_SUCCESS = 0;
    const AJAX_FAIL = -1;
    const AJAX_NO_DATA = 10001;
    const AJAX_NO_AUTH = 99999;
    const NO_PAY = 0;
    const PAYED = 1;
    const REFUND = 2;
    const CLOSED = 3;


}
