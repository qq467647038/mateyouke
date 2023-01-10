<?php
namespace app\index\controller;
use think\Controller;
use think\Db;

class Common extends Controller{
    public $webconfig;
    public $user_id = null;
    public $gourl = "index/index";
    
    public function _initialize(){
//        $this->user_id = session('user_id');
        $this->user_id = 22;
        $this->checkUser();
        $this->_getconfig();//获取配置项
        
        header('Location: /portal');
    }
    
    public function _getconfig(){
        $_configres = Db::name('config')->where('ca_id','in','1,2,5,8,11,15')->field('ename,value')->select(); //1,2,5,8,11,15 为配置参数的分类
        $configres = array();
        foreach ($_configres as $v){
            $configres[$v['ename']] = $v['value'];
        }
        
        $this->webconfig=$configres;
        // dump($configres);die;
        $this->assign('configres',$configres);
    }
    
    public function checkUser($flag = false) {
        if (!$this->user_id) {
            if (!$flag) {
                return false;
            }
            $this->error('用户信息错误', $this->gourl);
        }
        return true;
    }
    
    public function checkLogin() {
        if (!$this->user_id) {
            return $this->fetch('login/index');
        }
    }

    
    
}