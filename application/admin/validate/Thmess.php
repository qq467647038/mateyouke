<?php
namespace app\admin\validate;
use think\Validate;

class Thmess extends Validate

{
    protected $rule = [
        'cate_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'leixing'=>'require|in:0,1',
        'mess' => 'require|max:60',
        'sort'=>['require','regex'=>'/^[0-9]+$/'],
    ];

    protected $message = [
        'cate_id.require' => '请选择退换种类',
        'cate_id.regex' => '退换种类参数错误',
        'leixing.require' => '请选择类型',
        'leixing.in' => '类型参数错误',
        'mess.require' => '原因信息不能为空',
        'mess.max' => '原因信息最多20个字符',
        'sort.require' => '排序不能为空',
        'sort.regex' => '排序为整数',
    ];
    

}