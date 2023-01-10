<?php
namespace app\admin\validate;
use think\Validate;

class Gifts extends Validate
{
    protected $rule = [
        'cid' => 'require',
        'name'=>'require',
        'point'=>'require',
        'pic' => 'require',
        'picgif' => 'require',
    ];

    protected $message = [
        'cid.require' => '客服名称不能为空',
        'name.require' => '客服名称不能为空',
        'point.require' => '客服名称不能为空',
        'pic.require' => '客服名称不能为空',
        'picgif.require' => '客服名称不能为空',
    ];


    //场景验证不同的字段
    protected $scene = [
        'add' => ['cid','name','point','pic','picgif'],
        'edit' =>['username','phone','headimgurl']
    ];

}