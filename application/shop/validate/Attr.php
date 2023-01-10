<?php
namespace app\shop\validate;
use think\Validate;

class Attr extends Validate
{
    protected $rule = [
        'attr_name' => 'require',
        'attr_type'=>'require|in:0,1',
        'type_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'sort'=>['require','regex'=>'/^[0-9]+$/'],
        'shop_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];

    protected $message = [
        'attr_name.require' => '属性名称不能为空',
        'attr_type.require' => '请选择属性类型',
        'attr_type.in' => '属性类型参数错误',
        'type_id.require' => '请选择类型',
        'type_id.regex' => '类型参数错误',
        'sort.require' => '排序不能为空！',
        'sort.regex' => '排序一定要为数字！',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
    ];

}