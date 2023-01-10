<?php
namespace app\admin\validate;
use think\Validate;

class Industry extends Validate
{
    protected $rule = [
        'industry_name' => 'require|max:20',
        'ser_price'=>['require','regex'=>'/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/'],
        'remind'=>['require','regex'=>'/^\+?[1-9][0-9]*$/','lt'=>10],
        'is_show'=>'require|in:0,1',
        'sort'=>['require','regex'=>'/^[0-9]+$/'],
        'goods_id'=> 'require',
    ];

    protected $message = [
        'industry_name.require' => '项目名称不能为空',
        'industry_name.max' => '行业最多20个字符',
        'ser_price.require' => '目标价格不能为空',
        'ser_price.regex' => '目标价格格式错误',
        'remind.require' => '费率不能为空',
        'remind.regex' => '费率格式错误',
        'remind.lt' => '费率小于10',
        'is_show.require' => '请选择显示或隐藏',
        'is_show.in' => '显示或隐藏参数错误',
        'sort.require' => '排序不能为空！',
        'sort.regex' => '排序一定要为数字！',
        'goods_id.require' => '请选择关联分类信息',
    ];

}