<?php
namespace app\admin\validate;
use think\Validate;

class Creditshop extends Validate
{
    protected $rule = [
        'goods_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'credit' => ['require','regex'=>'/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/','gt'=>0],
        'shop_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];

    protected $message = [
        'goods_id.require' => '请选择商品',
        'goods_id.regex' => '商品参数错误',
        'credit.require' => '积分不能为空',
        'credit.regex' => '积分格式错误',
        'credit.gt' => '积分需大于0',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
    ];

}