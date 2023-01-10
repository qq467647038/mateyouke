<?php
namespace app\admin\validate;
use think\Validate;

class Custom extends Validate
{
    protected $rule = [
        'custom_name' => 'require',
        'type'=>'require|in:1,2',
        'sort'=>['require','regex'=>'/^[0-9]+$/'],
        'goods_id'=> 'require',
    ];

    protected $message = [
        'custom_name.require' => '推荐位名称不能为空！',
        'type.require' => '请选择信息类型',
        'type.in' => '信息类型参数错误',
        'sort.require' => '排序不能为空！',
        'sort.regex' => '排序为整数',
        'goods_id.require' => '请选择推荐信息',
    ];

}