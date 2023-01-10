<?php
namespace app\admin\validate;
use think\Validate;

class RushActivity extends Validate
{
    protected $rule = [
        'activity_name' => 'require|max:20',
        'goods_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'price'=>['require','regex'=>'/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/','gt'=>0],
        'num'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'xznum'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'start_time' => 'require',
        'end_time' => 'require',
        'remark' => 'max:100',
        'shop_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];
    
    protected $message = [
        'activity_name.require' => '活动名称不能为空',
        'activity_name.max' => '活动名称最多20个字符',
        'goods_id.require' => '请选择商品',
        'goods_id.regex' => '商品参数错误',
        'price.require' => '抢购价格不能为空',
        'price.regex' => '抢购价格格式错误',
        'price.gt' => '抢购价格需大于0',
        'num.require' => '抢购量不能为空',
        'num.regex' => '抢购量一定要为正整数',
        'xznum.require' => '限购数量不能为空',
        'xznum.regex' => '限购数量一定要为正整数',
        'start_time.require' => '开始时间不能为空',
        'end_time.require' => '结束时间不能为空',
        'remark.max' => '活动描述最多100个字符',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
    ];

}