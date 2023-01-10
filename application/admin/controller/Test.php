
<?php

namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Admin as AdminMx;

class Test extends Controller{
    
    // 发送验证码
    public function aa(){
       session_unset();
    }
 
}