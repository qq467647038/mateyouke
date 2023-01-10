<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;
use think\Image;

class Share extends Common{

    //获取服务项信息列表信息接口
    public function goods_share(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                if(input('post.goods_id')){
                    $user_id = $result['user_id'];
                    $goods_id = input('goods_id');

                    $name = 'goods_' . $goods_id . '_' . $user_id . '.png';
                    $json = ['path' => "/platforms/homeSon/shop_details?id=" . $goods_id . '&shareid=' . $user_id, 'width' => 200, 'is_hyaline' => true];
                    
                    $res['code'] = $this->getQrcode($name,$json);
                    $res['user'] = Db::name('member')->where('id',$user_id)->field('headimgurl,user_name')->find();

                    if(!$res['user']['headimgurl']){
                        $res['user']['headimgurl'] = $res['code'];
                    }

                    $value = array('status'=>200,'mess'=>'获取分享链接成功','data'=>$res);
                }else{
                    $value = array('status'=>400,'mess'=>'缺少商品信息参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    //获取服务项信息列表信息接口
    public function goods_h5_share(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                if(input('post.goods_id')){
                    $user_id = $result['user_id'];
                    $goods_id = input('goods_id');

                    $name = 'goods_' . $goods_id . '_' . $user_id . '.png';
                    //$path = ['path' => "/platforms/homeSon/shop_details?id=" . $goods_id . '&shareid=' . $share_user_id, 'width' => 200, 'is_hyaline' => true];
                    $path ='/portal/platforms/homeSon/shop_details?id=' . $goods_id . '&shareid=' . $user_id;
                    
                    $res['code'] = $this->h5_code($name,$path);
                    $res['user'] = Db::name('member')->where('id',$user_id)->field('headimgurl,user_name')->find();

                    if(!$res['user']['headimgurl']){
                        $res['user']['headimgurl'] = $res['code'];
                    }

                    $value = array('status'=>200,'mess'=>'获取分享链接成功','data'=>$res);
                }else{
                    $value = array('status'=>400,'mess'=>'缺少商品信息参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }


    //获取用户小程序分享
    public function user_share(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                $goods_id = input('goods_id');

                $name = 'user_' . $user_id . '.png';
                $json = ['path' => "/pages/tabBar/my?shareid=" . $user_id, 'width' => 200, 'is_hyaline' => true];
                
                $res['code'] = $this->getQrcode($name,$json);
                $res['user'] = Db::name('member')->where('id',$user_id)->field('headimgurl,user_name')->find();

                if(!$res['user']['headimgurl']){
                    $res['user']['headimgurl'] = $res['code'];
                }

                $value = array('status'=>200,'mess'=>'获取分享链接成功','data'=>$res);
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    //权益卡分享
    public function rights_h5_share(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                $card_no = input('param.card_no');
                $info = Db::name('vip_rights_card')->where('user_id|use_user_id', $user_id)->where('card_no', $card_no)->find();
                if(is_null($info))
                {
                    $value = array('status'=>400,'mess'=>'网络异常','data'=>array('status'=>400));
                    return json($value);
                }

                if(empty($info['token']))
                {
                    $value = array('status'=>400,'mess'=>'权益卡未激活，请先激活','data'=>array('status'=>400));
                    return json($value);
                }

                if($info['use'] == 1)
                {
                    $value = array('status'=>400,'mess'=>'该权益卡已被使用','data'=>array('status'=>400));
                    return json($value);
                }

                $name = 'rights_' . $card_no . '.png';

                $path ='/portal/pagesB/personalSon/RightsCard?rights_token='.$info['token'].'&buy_id=' . $user_id . '&shareid='.$user_id;

                $res['code'] = $this->h5_code($name,$path);
                $res['data'] = $info;

                $value = array('status'=>200,'mess'=>'获取分享链接成功','data'=>$res);
            }else{
                $value = array('status'=>400,'mess'=>'请先登陆','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    //获取服务项信息列表信息接口
    public function user_h5_share(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                $user_id = $result['user_id'];

                $name = 'user_' . $user_id . '.png';

                $res['user'] = Db::name('member')->where('id',$user_id)->field('headimgurl,user_name,recode,member_recode')->find();
                // var_dump($res['user']);exit;
                $path = $this->webconfig['weburl'].'portal/pages/register/register?member_recode='.$res['user']['member_recode'];
                // var_dump($this->webconfig['weburl']);exit;

                $logo = './static/images/logo.png';
                $res['code'] = $this->h5_code_water_logo($name,$path,$logo);

                if(!$res['user']['headimgurl']){
                    $res['user']['headimgurl'] = $this->webconfig['weburl'].'static/images/empty_headurl.png';
                }

                $value = array('status'=>200,'mess'=>'获取分享链接成功','data'=>$res);
            }else{
//                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function h5_code_water_logo($name,$path,$logo)
    {
    //带LOGO
        Vendor('phpqrcode.phpqrcode');
        //生成二维码图片
        $object = new \QRcode();//实例化二维码类
        $url=$path;//网址或者是文本内容
        $level=3;
        $size=6;
        $pathname = "./uploads/Qrcode";
        if(!is_dir($pathname)) { //若目录不存在则创建之
            mkdir($pathname);
        }

        $ad = $pathname .'/'. $name;
        $errorCorrectionLevel =intval($level) ;//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        $object->png($url, $ad, $errorCorrectionLevel, $matrixPointSize, 2);
        // echo $ad;exit;
        $image = \think\Image::open($ad);
        // $image->water($logo, \think\Image::WATER_CENTER)->save($ad);

        $filePath = $this->getImageUrl('/uploads/Qrcode/'.$name);
        return $filePath;
    }
    
    private function getNetworkImgType($url){


        $ch = curl_init(); //初始化curl

        curl_setopt($ch, CURLOPT_URL, $url); //设置需要获取的URL
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1); //支持https
        curl_exec($ch);//执行curl会话
        $http_code = curl_getinfo($ch);//获取curl连接资源句柄信息
        curl_close($ch);//关闭资源连接
        if ($http_code['http_code'] == 200) {
            $theImgType = explode('/',$http_code['content_type']);
            if($theImgType[0] == 'image'){
                return $theImgType[1];
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    
    private function createImageFromFile($file){
        if(preg_match('/http(s)?:\/\//',$file)){
            $fileSuffix = $this->getNetworkImgType($file);
        }else{
            $fileSuffix = pathinfo($file, PATHINFO_EXTENSION);
        }

        if(!$fileSuffix) return false;
        switch ($fileSuffix){
            case 'jpeg':
                $theImage = @imagecreatefromjpeg($file);
                // dump($theImage);die;
                break;
            case 'jpg':
                $theImage = @imagecreatefromjpeg($file);
                break;
            case 'png':
                $theImage = @imagecreatefrompng($file);
                break;
            case 'gif':
                $theImage = @imagecreatefromgif($file);
                break;
            default:
                $theImage = @imagecreatefromstring(file_get_contents($file));
                break;
        }
        return $theImage;
    }
    
     //获取服务项信息列表信息接口
    public function alive_share(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                if(input('post.alive_info/a')){
                    $user_id = $result['user_id'];
                    $alive_info = input('alive_info/a');

                    $name = 'alive_'. $user_id . '.png';
                    $json = ['path' => "/platforms/mp-weixin/live-player?alive_info=" . $alive_info . '&shareid=' . $user_id, 'width' => 200, 'is_hyaline' => true];
                    
                    $res['code'] = $this->getQrcode($name,$json);
                    $res['user'] = Db::name('member')->where('id',$user_id)->field('headimgurl,user_name')->find();

                    if(!$res['user']['headimgurl']){
                        $res['user']['headimgurl'] = $res['code'];
                    }

                    $value = array('status'=>200,'mess'=>'获取分享链接成功','data'=>$res);
                }else{
                    $value = array('status'=>400,'mess'=>'缺少商品信息参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    public function alive_h5_share(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                 if(input('post.url')){
                 //if(input('alive_info/a')){
                    $url = input('post.url');
                    $shop_id = input('post.shop_id');
                    $user_id = $result['user_id'];
                    //$alive_info = input('alive_info/a');

                    $name = 'alive_'. $shop_id. '_'.$user_id . '.png';
                    
                    //$path = ['path' => "/platforms/homeSon/shop_details?id=" . $goods_id . '&shareid=' . $share_user_id, 'width' => 200, 'is_hyaline' => true];
                    $path ='/portal/platforms/mp-weixin/live-player?pullurl=' . $url . '&shop_id=' . $shop_id  . '&shareid=' . $user_id;

                    $res['code'] = $this->h5_code($name,$path);
                    $res['user'] = Db::name('member')->where('id',$user_id)->field('headimgurl,user_name')->find();

                    if(!$res['user']['headimgurl']){
                        $res['user']['headimgurl'] = $res['code'];
                    }

                    $value = array('status'=>200,'mess'=>'获取分享链接成功','data'=>$res);
                }else{
                    $value = array('status'=>400,'mess'=>'缺少商品信息参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }



     private function getQrcode($name,$json) {
        $access_token = $this->getAccessToken();
        $upload_path = $_SERVER["DOCUMENT_ROOT"] . '/uploads/qr_code';
        $filename = $name;
        //$filename = 'goods_' . $goods_id . '_' . $share_user_id . '.png';

        /*if(file_exists($upload_path . DS . $filename)){
            $filePath = $this->getImageUrl('/public/uploads/qr_code/' . $filename);
        }else{*/
            //$json = ['path' => "/platforms/homeSon/shop_details?id=" . $goods_id . '&shareid=' . $share_user_id, 'width' => 200, 'is_hyaline' => true];
            $json = json_encode($json);
            $url = "https://api.weixin.qq.com/wxa/getwxacode?access_token=" . $access_token;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
            $ret = curl_exec($ch);
            $file = fopen($upload_path . DS . $filename,"w");//打开文件准备写入  
            fwrite($file, $ret);//写入  
            fclose($file);//关闭 
            $filePath = $this->getImageUrl('/uploads/qr_code/' . $filename);
            $err = curl_error($ch);
            curl_close($ch);
        //}
        
        return $filePath;
    }




    /**
     * 获取accesstoken
     * @param unknown $appid
     * @return mixed|unknown
     */
    public function getAccessToken() {

        $cachekey = "accesstoken_{$this->wechatConfig['appid']}";
        $cache = cache($cachekey);
        if (!empty($cache) && !empty($cache['token']) && $cache['expire'] > time()) {
            return $cache['token'];
        }

        $secret = $this->wechatConfig['secret'];
        $appid = $this->wechatConfig['app_id'];
        $access_token = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";

         $ACCESS_TOKEN = "";
         if(!isset($_SESSION['access_token']) || (isset($_SESSION['expires_in']) && time() > $_SESSION['expires_in']))
         {
         
             $json = $this->httpRequest( $access_token );
             $json = json_decode($json,true);
             // var_dump($json);
             $_SESSION['access_token'] = $json['access_token'];
             $_SESSION['expires_in'] = time()+7200;
             $ACCESS_TOKEN = $json["access_token"];
         }
         else{
         
             $ACCESS_TOKEN =  $_SESSION["access_token"];
         }
         return $ACCESS_TOKEN;
       // dump($access_token);die;

        // if(self::isError($content)) {
        //     return self::getAccessToken($appid);
        //     //return error('-1', '获取微信公众号授权失败, 请稍后重试！错误详情: ' . $content['message']);
        // }
        //$access_token = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$APPID&secret=$APPSECRET";
    }



    public function httpRequest($url, $data='', $method='GET'){
        $curl = curl_init(); 
        curl_setopt($curl, CURLOPT_URL, $url); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); 
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); 
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); 
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); 
        if($method=='POST')
        {
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data != '')
            {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data); 
            }
        }
     
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); 
        curl_setopt($curl, CURLOPT_HEADER, 0); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
        $result = curl_exec($curl); 
        curl_close($curl); 
        return $result;
      }

    public function h5_code($name,$path)
    {
    //不带LOGO
        Vendor('phpqrcode.phpqrcode');
        //生成二维码图片
        $object = new \QRcode();//实例化二维码类
        $url=$path;//网址或者是文本内容
        $level=3;
        $size=6;
        $pathname = "./uploads/Qrcode";
        if(!is_dir($pathname)) { //若目录不存在则创建之
        mkdir($pathname);
        }
        $ad = $pathname .'/'. $name;
        $errorCorrectionLevel =intval($level) ;//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        $object->png($url, $ad, $errorCorrectionLevel, $matrixPointSize, 2);

        $filePath = $this->getImageUrl('/uploads/Qrcode/'.$name);


        return $filePath;
    }


    //获取二维码
    private function getImageUrl($imgurl){
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        if(empty($imgurl)){
            return $http_type . $_SERVER['HTTP_HOST'] . '/template/mobile/new2/static/course/images/default.png';
        }else{
            return $http_type . $_SERVER['HTTP_HOST'] . $imgurl;
        }

    }
    
}