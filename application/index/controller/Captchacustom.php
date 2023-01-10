<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
 

class Captchacustom extends Controller{
     public function index(){
        $config =    [
            // 验证码字体大小
            'fontSize'    =>    30,    
            // 验证码位数
            'length'      =>    4,   
            // 关闭验证码杂点
            'useNoise'    =>    false, 
            // 是否画混淆曲线
            'useCurve'    =>    false,
        ];
        $captcha = new \think\captcha\Captcha($config);
        return $captcha->entry();
     }

     public function checkVerify($code, $id = ''){
        $captcha = new \think\captcha\Captcha();
        $res = $captcha->check($code, $id);
        if(!$res){
            //验证失败
            return false;
           }else{
               return true;
           }
    }
}