<?php
namespace app\admin\validate;
use think\Validate;

class Coupon extends Validate

{
    protected $rule = [
        'man_price' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>5],
        'dec_price' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>1],
        'start_time' => 'require|regex:/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/|lt:end_time',
        'end_time' => 'require|regex:/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/',
        'shop_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'onsale'=>'require|in:0,1',
        'is_recycle'=>'require|in:0,1',
        'sort'=>['require','regex'=>'/^[0-9]+$/'],
    ];

    protected $message = [
        'man_price.require' => '满金额不能为空',
        'man_price.regex' => '满金额只能为整数',
        'man_price.gt' => '满金额不能低于5元',
        'dec_price.require' => '减金额不能为空',
        'dec_price.regex' => '减金额只能为整数',
        'dec_price.gt' => '减金额不能低于1元',
        'start_time.require' => '开始时间不能为空',
        'start_time.regex' => '开始时间格式错误',
        'start_time.lt' => '开始时间需小于结束时间',
        'end_time.require' => '结束时间不能为空',
        'end_time.regex' => '结束时间格式错误',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
        'onsale.require' => '请选择上架或下架',
        'onsale.in' => '上下架参数错误',
        'is_recycle.require' => '请选择是否放入回收站',
        'is_recycle.in' => '是否放入回收站参数错误',
        'sort.require' => '排序不能为空',
        'sort.regex' => '排序为整数',
    ];
    

}