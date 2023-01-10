<?php
namespace app\shop\validate;
use think\Validate;

class ManageCate extends Validate
{
    protected $rule = [
        'cate_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'shop_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];

    protected $message = [
        'cate_id.require' => '请选择经营类目',
        'cate_id.regex' => '经营类目参数错误',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
    ];

}