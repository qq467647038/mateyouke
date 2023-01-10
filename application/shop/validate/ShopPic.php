<?php
namespace app\shop\validate;
use think\Validate;

class ShopPic extends Validate
{
    protected $rule = [
        'pic_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'url' => 'require|url',
        'sort'=>['require','regex'=>'/^[0-9]+$/'],
        'shop_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];

    protected $message = [
        'pic_id.require' => '请上传banner图片',
        'pic_id.regex' => '请上传banner图片',
        'url.require' => '链接url不能为空',
        'url.url' => '链接url格式不正确',
        'sort.require' => '排序不能为空！',
        'sort.number' => '排序一定要为数字！',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
    ];

}