<?php
namespace app\admin\validate;
use think\Validate;

class GroupTravel extends Validate
{
    protected $rule = [
        'goods_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'active_name'=>['require'],
        'price'=>['require','regex'=>'/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/','egt'=>0],
//        'people_num'=>['require','regex'=>'/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/','gt'=>0],
    ];

    protected $message = [
        'active_name.require' => '活动名称不能为空',
        'goods_id.require' => '请选择商品',
        'goods_id.regex' => '商品参数错误',
        'price.require' => '金额不能为空',
        'price.egt' => '金额不能小于0',
        'price.regex' => '金额参数错误',
//        'people_num.require' => '组团人数必选',
//        'people_num.gt' => '组团人数必须大于0',
//        'people_num.regex' => '组团人数参数错误',
    ];
    

}