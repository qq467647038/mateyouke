<?php
namespace app\admin\validate;
use think\Validate;

class ShopCate extends Validate
{
    protected $rule = [
        'cate_name' => 'require|max:15',
        'sort'=>['require','regex'=>'/^[0-9]+$/'],
        'is_show'=>'require|in:0,1',
        'pid'=>['require','regex'=>'/^[0-9]+$/'],
        'shop_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];

    protected $message = [
        'cate_name.require' => '分类名称不能为空',
        'cate_name.max' => '最多15个字符',
        'sort.require' => '排序不能为空！',
        'sort.regex' => '排序一定要为数字！',
        'is_show.require' => '请选择显示或隐藏',
        'is_show.in' => '显示或隐藏参数错误',
        'pid.require' => '请所属分类',
        'pid.regex' => '所属分类参数错误',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
    ];

}