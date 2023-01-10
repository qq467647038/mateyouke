<?php
namespace app\admin\validate;
use think\Validate;

class AlipayConfig extends Validate

{
    protected $rule = [
        'appid' => 'require',
        'private_key' => 'require',
        'public_key' => 'require',
        'notify_url' => 'require|url',
    ];

    protected $message = [
        'appid.require' => '缺少应用id',
        'private_key.require' => '缺少私钥',
        'public_key.require' => '缺少公钥',
        'notify_url.require' => '缺少异步通知url地址',
        'notify_url.url' => '异步通知url地址格式不正确',
    ];
    

}