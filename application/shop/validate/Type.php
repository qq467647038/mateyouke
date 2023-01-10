<?php
namespace app\shop\validate;
use think\Validate;

class Type extends Validate
{
    protected $rule = [
        'type_name' => 'require',
        'shop_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];

    protected $message = [
        'type_name.require' => '类型名称不能为空',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
    ];

}