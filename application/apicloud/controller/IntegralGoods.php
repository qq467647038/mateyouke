<?php
/**
 * Created by PhpStorm.
 * User: 云
 * Date: 2022/1/4
 * Time: 23:26
 */

namespace app\apicloud\controller;

use think\Db;

class IntegralGoods extends Common
{
    public function lst()
    {
        $list = Db::name('integral_goods')->where('onsale', 1)->select();

        $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>$list);

        return json($value);
    }
}