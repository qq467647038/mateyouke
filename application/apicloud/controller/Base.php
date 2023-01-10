<?php

namespace app\apicloud\controller;
use think\Cache;
use think\Controller;
use app\apicloud\model\Gongyong as GongyongMx;

class Base extends Controller
{
    public $data;
    public function _initialize(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET,POST");
        header('Access-Control-Allow-Headers: X-Requested-With,content-type,if-modified-since,Auth');
        !request()->isPost() && datamsg(LOSE,'非法操作');
        $this->data = input();
    }

    /**
     * @func 验证token是否有效
     * @param $id当前登录的用户id
     */
    protected function checktoken(){
        $GongyongMx = new GongyongMx;
        $result     = $GongyongMx->apivalidate();
        $result['status'] != WIN && datamsg(LOSE,$result['mess']);
        Cache::set('user_id',$result['user_id']);
    }

    /**
     * 验证字段
     */
    public function validate_code($data,$val,$sence="goods"){
        $validate=validate($sence);
        if(!$validate->scene($val)->check($data)){
            return datamsg(LOSE,$validate->getError());
        }
    }


    /**
     * 空方法提示
     */
    public function _empty(){
        datamsg(LOSE, '抱歉，您要查看的数据不存在或已被删除!!!');
    }
}
