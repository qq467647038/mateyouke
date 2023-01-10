<?php
namespace app\admin\validate;
use think\Validate;

class ShopShdz extends Validate

{
    protected $rule = [
        'name' => 'require|max:10',
        'telephone'=>'require|regex:/^1[3456789]\d{9}$/',
        'province' => 'require|max:10',
        'city' => 'require|max:10',
        'area' => 'require|max:10',
        'address'=>'require|max:50',
        'shop_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];

    protected $message = [
        'name.require' => '请填写收货人姓名',
        'name.max' => '收货人姓名最多10个字符',
        'telephone.require' => '请填写联系手机号',
        'telephone.regex' => '联系手机格式错误',
        'province.require' => '省份不能为空',
        'province.max' => '省份最多10个字符',
        'city.require' => '城市不能为空',
        'city.max' => '城市最多10个字符',
        'area.require' => '区县不能为空',
        'area.max' => '区县最多10个字符',
        'address.require' => '详细地址不能为空',
        'address.max' => '详细地址最多50个字符',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
    ];
    

}