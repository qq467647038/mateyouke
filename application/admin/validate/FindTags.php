<?php
namespace app\admin\validate;
use think\Validate;

class FindTags extends Validate
{
    protected $rule = [
        'name' => 'require',
        'cate_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/']
    ];

    protected $message = [
        'name.require' => '商品名称不能为空',
        'cate_id.require' => '请选择商品分类',
        'cate_id.regex' => '商品分类参数错误'
    ];

}