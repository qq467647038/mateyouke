<?php
namespace app\admin\validate;
use think\Validate;

class Attr extends Validate
{
    protected $rule = [
        'attr_name' => 'require',
        'attr_type'=>'require|in:0,1',
        'type_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'is_sear'=>'require|in:0,1',
        'sort'=>['require','regex'=>'/^[0-9]+$/'],
    ];

    protected $message = [
        'attr_name.require' => '属性名称不能为空',
        'attr_type.require' => '请选择属性类型',
        'attr_type.in' => '属性类型参数错误',
        'type_id.require' => '请选择类型',
        'type_id.regex' => '类型参数错误',
        'is_sear.require' => '请选择是否设为筛选条件',
        'is_sear.in' => '请选择是否设为筛选条件',
        'sort.require' => '排序不能为空！',
        'sort.regex' => '排序一定要为数字！',
    ];

}