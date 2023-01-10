<?php
namespace app\admin\validate;
use think\Validate;

class Logistics extends Validate
{
    protected $rule = [
        'log_name' => ['require','unique'=>'logistics'],
        'telephone' => 'require',
        'sort'=>['require','regex'=>'/^[0-9]+$/'],
        'is_show'=>'require|in:0,1',
    ];

    protected $message = [
        'log_name.require' => '物流名称不能为空',
        'log_name.unique' => '物流信息已存在',
        'telephone.require' => '手机号不能为空',
        'sort.require' => '排序不能为空！',
        'sort.regex' => '排序一定要为数字！',
        'is_show.require' => '请选择显示或隐藏',
        'is_show.in' => '显示或隐藏参数错误',
    ];

}