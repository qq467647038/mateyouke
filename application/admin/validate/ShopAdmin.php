<?php
namespace app\admin\validate;
use think\Validate;

class ShopAdmin extends Validate
{
    protected $rule = [
        'phone' => 'require|unique:shop_admin|regex:/^1[3456789]\d{9}$/',
        'xieyi' => 'require|in:0,1',
    ];

    protected $message = [
        'phone.require' => '手机号不能为空',
        'phone.unique' => '手机号已存在',
        'phone.regex' => '手机号格式不正确',
        'xieyi.require' => '请同意注册协议',
        'xieyi.in' => '同意注册协议参数错误', 
    ];

}