<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;

class Basic extends Controller{
    public $webconfig;
    
    Public function _initialize(){
        $_configres = Db::name('config')->where('ca_id','in','1,2,4,5,10')->field('ename,value')->select();
        $configres = array();
        foreach ($_configres as $v){
            $configres[$v['ename']] = $v['value'];
        }
        $this->webconfig=$configres;
        $this->assign('configres',$configres);
    }
    
}