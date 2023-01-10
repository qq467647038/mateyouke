<?php
namespace app\shop\controller;
use think\Controller;
use think\Db;

class Common extends Controller{
    public $webconfig;
    
    Public function _initialize(){
        if(!session('shopadmin_id') || !session('shopsh_id')){
            $this->redirect('login/index');
        }
        
        $this->_getconfig();
    }
    
    public function _getconfig(){
        $_configres = Db::name('config')->where('ca_id','in','1,2,4,5,10,15')->field('ename,value')->select();
        $configres = array();
        foreach ($_configres as $v){
            $configres[$v['ename']] = $v['value'];
        }
        $this->webconfig=$configres;
        $this->assign('configres',$configres);
    }
    
}