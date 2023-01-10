<?php
namespace app\admin\validate;
use think\Validate;

class PromActivity extends Validate
{
    protected $rule = [
        'activity_name' => 'require|max:20',
        'type'=>'require|in:1,2,3',
        'reduction' => ['regex'=>'/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/'],
        'start_time' => 'require',
        'end_time' => 'require',
        'content' => 'require',
        'shop_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];

    protected $message = [
        'activity_name.require' => '活动名称不能为空',
        'activity_name.max' => '活动名称最多20个字符',
        'type.require' => '请选择促销活动类型',
        'type.in' => '请选择促销活动类型',
        'reduction.regex' => '立减价格格式错误',
        'reduction.gt' => '立减价格不得小于1元',
        'num.require' => '限购数量不能为空',
        'num.regex' => '限购数量一定要为正整数',
        'start_time.require' => '开始时间不能为空',
        'end_time.require' => '结束时间不能为空',
        'goods_id.require' => '请选择商品',
        'content.require' => '请填写活动介绍',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
    ];


}