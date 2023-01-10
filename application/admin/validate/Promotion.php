<?php
namespace app\admin\validate;
use think\Validate;

class Promotion extends Validate
{
    protected $rule = [
        'activity_name' => 'require|max:30',
        'type'=>'require|eq:1',
        'man_num'=>'require',
        'discount'=>'require',
        'start_time'=>'require',
        'end_time'=>'require',
        'goods_id'=>'require',
        'shop_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];
    
    protected $message = [
        'activity_name.require' => '活动名称不能为空',
        'activity_name.max' => '活动名称最多30个字符',
        'type.require' => '请选择促销活动类型',
        'type.eq' => '请选择促销活动类型',
        'man_num.require' => '满件数不能为空',
        'discount.require' => '折扣不能为空',
        'start_time.require' => '开始时间不能为空',
        'end_time.require' => '结束时间不能为空',
        'goods_id.require' => '请选择商品',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
    ];

}