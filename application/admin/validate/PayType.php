<?php
namespace app\admin\validate;
use think\Validate;

class PayType extends Validate
{
    protected $rule = [
        'pay_name' => ['require','unique'=>'pay_type'],
        'only_name' => ['require','unique'=>'pay_type'],
        'is_open'=>'require|in:0,1',
        'sort'=>['require','regex'=>'/^[0-9]+$/'],
    ];

    protected $message = [
        'pay_name.require' => '支付方式名称不能为空',
        'pay_name.unique' => '支付方式名称已存在',
        'only_name.require' => '支付别名不能为空',
        'only_name.unique' => '支付别名已存在',
        'is_open.require' => '请选择开启或关闭',
        'is_open.in' => '显示或隐藏参数错误',
        'sort.require' => '排序不能为空！',
        'sort.regex' => '排序一定要为数字！',
    ];

}