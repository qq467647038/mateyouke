<?php
namespace app\admin\validate;
use think\Validate;

class Category extends Validate
{
    protected $rule = [
        'cate_name' => ['require','unique'=>'category'],
        'type_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'search_keywords'=>'require|max:100',
        'sort'=>['require','regex'=>'/^[0-9]+$/'],
        'is_show'=>'require|in:0,1',
        'recommend'=>'require|in:0,1',
        'show_in_recommend'=>'require|in:0,1',
        'pid'=>['require','regex'=>'/^[0-9]+$/'],
    ];

    protected $message = [
        'cate_name.require' => '分类名称不能为空',
        'cate_name.unique' => '分类名称已存在',
        'type_id.require' => '请选择商品类型',
        'type_id.regex' => '商品类型参数错误',
        'search_keywords.require' => '搜索关键字不能为空',
        'search_keywords.max' => '搜索关键字最多100个字符',
        'sort.require' => '排序不能为空！',
        'sort.regex' => '排序一定要为数字！',
        'is_show.require' => '请选择显示或隐藏',
        'is_show.in' => '显示或隐藏参数错误',
        'recommend.require' => '请选择是否设为主页推荐',
        'recommend.in' => '请选择是否设为主页推荐',
        'show_in_recommend.require' => '请选择是否设为推荐分类',
        'show_in_recommend.in' => '请选择是否设为推荐分类',
        'pid.require' => '请所属分类',
        'pid.regex' => '所属分类参数错误',
    ];

}