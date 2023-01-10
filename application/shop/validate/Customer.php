<?php
namespace app\shop\validate;
use think\Validate;

class Customer extends Validate
{
    protected $rule = [
        'username' => 'require|unique:chat_customer',
        'phone'=>['require','regex'=>'/^1[23456789]\d{9}$/'],
        'password'=>['require','regex'=>'/^[_.0-9a-z]{6,16}$/'],
        'headimgurl' => 'require'
    ];

    protected $message = [
        'username.require' => '客服名称不能为空',
        'username.unique' => '客服名称已经存在',
        'phone.regex' => '请输入正确的手机号',
        'password.regex' => '密码格式不符合(密码由6-16位下划线(-)点(.)字母和数字组成)',
        'headimgurl.require' => '请上传头像',
    ];


    //场景验证不同的字段
    protected $scene = [
        'add' => ['username','phone','password','headimgurl'],
        'edit' =>['username','phone','headimgurl']
    ];

}