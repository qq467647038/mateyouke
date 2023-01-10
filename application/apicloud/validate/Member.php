<?php
namespace app\apicloud\validate;
use think\Validate;

class Member extends Validate
{
    protected $rule = [
        'phone' => 'require',
        'phonecode' => ['require','length'=>6,'regex'=>'/^\+?[1-9][0-9]*$/'],
        'password' => 'require|regex:/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,10}$/|alphaNum',
        'repwd' => 'requireWith:password|confirm:password|regex:/^[a-zA-Z0-9]{6,10}$/',
        'xieyi' => 'require|in:0,1',
        'sex' => 'in:1,2',
        'birth' => 'regex:/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/',
        'email' => 'email',
        'num|数量'=>'require|gt:0',
        'pay_pwd|支付密码'=>['require', 'number', 'length'=>6],
        // 'jiedianphone|接点人手机号'=>'require',
        'emergency_name|紧急联系人'=>'require|chs',
        'emergency_phone|紧急联系电话'=>'require',
        'user_name|用户名' => 'require|length:2,12|chsAlpha',
        'nick_name|账号'=>'require|unique:member'
    ];

    protected $message = [
        'phone.require' => '手机号不能为空',
        'nick_name.require' => '账号不能为空',
        'nick_name.unique' => '账号已存在',
        'phone.regex' => '手机号格式不正确',
        'phonecode.require' => '验证码不能为空',
        'phonecode.length' => '验证码为6位数字',
        'phonecode.regex' => '验证码为6位数字',
        'password.require' => '密码 不能为空',
        'password.regex' => '密码为6-10位，数字和字母',
        'repwd.requireWith' => '确认密码不能为空',
        'repwd.confirm' => '确认密码不正确',
        'repwd.regex' => '确认密码为6-10位，字符、数字或下划线',
        'xieyi.require' => '请同意注册协议',
        'xieyi.in' => '同意注册协议参数错误', 
        'user_name.length' => '昵称为2到10位字符',
        'sex.in' => '性别错误',
        'birth.regex' => '生日格式错误',
        'email.email' => '邮箱格式错误',
        'pay_pwd.length' => '支付密码6位数',
    ];
    
    protected $scene = [
        'register' => ['phone','password','pay_pwd','nick_name'],
        'brandTransferAccount'=>[ 'num', 'paypwd'],
        'pointTransferAccount'=>[ 'num', 'paypwd'],
        'brandExchangeBuyTicket'=>[ 'num', 'paypwd'],
        'managerRewardToBuyTicket'=>[ 'num', 'paypwd'],
//        'register' => ['phone','phonecode','password','repwd','xieyi'],
        'edit' => ['user_name','sex','birth','email'],
        'shezhi' => ['phone','phonecode','password','repwd'],
    ];

}