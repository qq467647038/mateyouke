<?php
namespace app\admin\validate;
use think\Validate;

class Ptdz extends Validate

{
    protected $rule = [
        'name' => 'require|max:30',
        'telephone'=>'require|regex:/^1[3456789]\d{9}$/',
        'shengshiqu' => 'require|max:60',
        'address'=>'require|max:150',
    ];

    protected $message = [
        'name.require' => '请填写收货人姓名',
        'name.max' => '收货人姓名最多10个字符',
        'telephone.require' => '请填写联系手机号',
        'telephone.regex' => '联系手机格式错误',
        'shengshiqu.require' => '省市区不能为空',
        'shengshiqu.max' => '省市区最多20个字符',
        'address.require' => '详细地址不能为空',
        'address.max' => '详细地址最多50个字符',
    ];
    

}