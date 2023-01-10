<?php
namespace app\admin\validate;
use think\Validate;

class Admin extends Validate
{
    protected $rule = [
        'username' => 'require|unique:admin|regex:/^[a-zA-Z][a-zA-Z0-9]{4,16}$/',
        'en_name' => 'require|unique:admin',
        'password' => 'regex:/^[A-Z][a-zA-Z0-9]{5,17}$/',
        'repwd' => 'requireWith:password|confirm:password|regex:/^[A-Z][a-zA-Z0-9]{5,17}$/',
    ];

    protected $message = [
        'username.require' => '用户名必须',
        'username.unique' => '用户名已存在',
        'username.regex' => '用户名以字母开头，长度在5-17位，字符、数字或下划线',
        'en_name.require' => '昵称不能为空',
        'en_name.unique' => '昵称已存在',
        'password.regex' => '密码以大写字母开头6-18位，字符、数字或下划线',
        'repwd.requireWith' => '确认密码不能为空',
        'repwd.confirm' => '确认密码不正确',
        'repwd.regex' => '确认密码以大写字母开头6-18位，字符、数字或下划线',
    ];

}