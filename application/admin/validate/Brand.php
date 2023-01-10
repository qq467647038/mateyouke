<?php
namespace app\admin\validate;
use think\Validate;

class Brand extends Validate
{
    protected $rule = [
        'brand_name' => ['require','unique'=>'brand'],
        'is_show'=>'require|in:0,1',
    ];

    protected $message = [
        'brand_name.require' => '品牌名称不能为空',
        'brand_name.unique' => '品牌名称已存在',
        'is_show.require' => '请选择显示或隐藏',
        'is_show.in' => '显示或隐藏参数错误',
    ];

}