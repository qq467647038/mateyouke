<?php
namespace app\apicloud\validate;
use think\Validate;

class ZFBCard extends Validate
{
    protected $rule = [
        'name|账户名称' => 'require|regex:/^[\x{4e00}-\x{9fa5}]{2,5}+$/u',
        'telephone|账号' => 'require',
        'qrcode|二维码' => ['require'],
    ];

    protected $message = [
    ];

}