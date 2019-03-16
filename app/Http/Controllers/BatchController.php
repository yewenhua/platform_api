<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Models\Combo;
use App\Http\Models\Member;
use App\Http\Models\Memcombo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use QrCode;
use App\Http\Models\Fonts;

class BatchController extends Controller
{
    public function combo_day_clear(){
        return false;
        $hour = date('H');
        //if($hour == '00'){
            $now = date('Y-m-d H:i:s');

            //获取需要更新的用户列表及用户套餐
            $lists = DB::table('member')
                ->join('combo', 'combo.id', '=', 'member.combo_id')
                ->select('member.id', 'member.combo_id')
                ->whereNull('member.deleted_at')
                ->whereNull('combo.deleted_at')
                ->where('member.isopen', 1)
                ->where('combo.isopen', 1)
                ->where('member.begin', '<=', $now)
                ->where('member.end', '>=', $now)
                ->where('combo.type', 'day')
                ->get();

            if($lists){
                DB::beginTransaction();
                try {
                    foreach ($lists as $item){
                        //获取套餐内容
                        $comboSites = DB::table('combo_site')->whereNull('deleted_at')->where('combo_id', $item->combo_id)->get();
                        if($comboSites) {
                            $combo = Combo::find($item->combo_id);
                            if($combo){
                                DB::table('member')
                                    ->where('id', $item->id)
                                    ->update([
                                        "count" => $combo->count,
                                        "updated_at" => date('Y-m-d H:i;s')
                                    ]);
                            }

                            foreach ($comboSites as $detail) {
                                DB::table('memcombo')
                                    ->where('member_id', $item->id)
                                    ->where('site_id', $detail->website_id)
                                    ->update([
                                        "count" => $detail->count,
                                        "updated_at" => date('Y-m-d H:i;s')
                                    ]);
                            }
                        }
                    }

                    DB::commit();
                } catch (QueryException $ex) {
                    DB::rollback();
                }
            }
        //}
    }

    public function fontInsertDb(){
        $path = public_path('fonts');
        $this->read_all($path);
    }

    private function read_all ($dir){
        if(!is_dir($dir)) {
            return false;
        }

        $handle = opendir($dir);
        if($handle){
            while(($fl = readdir($handle)) !== false){
                $temp = $dir.DIRECTORY_SEPARATOR.$fl;
                //如果不加  $fl!='.' && $fl != '..'  则会造成把$dir的父级目录也读取出来
                if(is_dir($temp) && $fl!='.' && $fl != '..' && $fl !== 'imgs'){
                    //目录
                    $this->read_all($temp);
                }
                else{
                    if($fl!='.' && $fl != '..'){
                        //文件
                        $utf8_dir = iconv('GBK','utf-8', $dir);
                        $arr = explode(DIRECTORY_SEPARATOR, $utf8_dir);
                        $dir_name = $arr[count($arr)-1];
                        if($dir_name !== 'fonts'){
                            $this->createimg($temp, $dir_name, $fl);
                        }
                    }
                }
            }
        }
    }

    private function createimg($font_file, $utf8_dir, $font){
        $gbk_dir = iconv('utf-8','GBK', $utf8_dir);
        $path = public_path('fonts/imgs/'.$gbk_dir.'/font_' . $font . '.png');
        $parent_dir = public_path('fonts/imgs/'.$gbk_dir);
        if(!file_exists($parent_dir))
        {
            $this->mkdir($parent_dir);
        }
        if(strpos($gbk_dir, '英文') || strpos($gbk_dir, '英语')){
            $language = 'english';
        }
        else{
            $language = 'chinese';
        }

        if(!file_exists($path)) {
            $bgimg = public_path('fonts/font_bg.png');
            $font_img = imagecreatefrompng($bgimg);
            $width = imagesx ( $font_img );
            $height = imagesy ( $font_img );

            //这里很重要 意思是不要丢了图像的透明色;
            imagesavealpha($font_img,true);

            if (imageistruecolor($font_img)) {
                //将真彩色图像转换为调色板图像
                imagetruecolortopalette($font_img, false, 65535);
            }

            //设定图像的混色模式并启用
            imagealphablending($font_img, true);

            //为一幅图像分配颜色
            $black = imagecolorallocate($font_img, 0, 0, 0);
            $fontSize = 45;
            $font_str = $this->random_str($language);
            $font_str = '猫小鱼设计';

                //文字水平居中实质
            $fontBox = imagettfbbox($fontSize, 0, $font_file, $font_str);

            //使用 FreeType 2 字体将文本写入图像
            imagefttext($font_img, $fontSize, 0, ceil(($width - $fontBox[2]) / 2), ceil(($height - $fontBox[5]) / 2), $black, $font_file, $font_str);

            //建立 PNG 图型
            imagepng($font_img, $path);

            //销毁图像
            imagedestroy($font_img);

            $utf8_font = iconv('GBK','utf-8', $font);
            $font_arr = explode(".", $utf8_font);
            $fontObj = new Fonts();
            $row = $fontObj->findByName($font_arr[0]);
            if(!$row) {
                Fonts::create([
                    "name" => $font_arr[0],
                    "language" => $language,
                    "style" => $utf8_dir,
                    "example" => '/fonts/imgs/' . $utf8_dir . '/font_' . $utf8_font . '.png',
                    "isopen" => 1
                ]);
            }
        }
    }

    private function random_str($type)
    {
        if($type == 'chinese'){
            $cy_str = "和蔼可亲,无缘无故,兴高采烈,美丽动人,欢天喜地,无事生非,对牛弹琴,守株待兔,揠苗助长,掩耳盗铃,亡羊补牢,一叶障目,兴师问罪,七上八下,南辕北辙,上窜下跳,心不在焉,一心一意,一心二意,随机应变,专心致志,心无旁贷";
            $cy_arr = explode(",", $cy_str);
        }
        else{
            $cy_arr = array(
                "Hello world",
                "Why not?",
                "I see",
                "Come on",
                "Me too",
                "See you",
                "Shut up!",
                "Good job!",
                "Cheer up!",
                "How much?",
                "Good luck!",
                "I promise",
                "Of course!",
                "Have fun!",
                "Be careful!",
                "I'm single",
                "No problem!",
                "I love you!",
                "Are you sure",
                "Take it easy",
                "Any thing else",
                "Help yourself",
                "Congratulations"
            );
        }

        $str = '';
        $arr_len = count($cy_arr);
        $rand = mt_rand(0, $arr_len-1);
        $str = $cy_arr[$rand];

        return $str;
    }

    private function mkdir($path, $chmod=0777)
    {
        return is_dir($path) or ($this->mkdir(dirname($path),$chmod) and mkdir($path,$chmod));
    }

    public function word(){
        $host = "https://ocrapi-ecommerce.taobao.com";
        $path = "/ocrservice/ecommerce";
        $method = "POST";
        $appcode = "0172b53613af48ebbf0fd99fcda79342";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
        $querys = "";
        $bodys = "{\"url\":\"http://pic44.photophoto.cn/20170727/0847085325669151_b.jpg\",\"prob\":false}";
        $url = $host . $path;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
        $tmpInfo = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($tmpInfo, true);

        if($result['prism_wnum'] && is_array($result['prism_wordsInfo']) && count($result['prism_wordsInfo']) > 0) {
            $arr = array();
            foreach ($result['prism_wordsInfo'] as $item){
                $arr[] = $item['word'];
            }
            $format = array(
                "prism_wnum" => $result['prism_wnum'],
                "prism_wordsInfo" => $arr
            );
        }
        else{
            $format = array();
        }

        dd($format);
    }
}
