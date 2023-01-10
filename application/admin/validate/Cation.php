<?php
namespace app\admin\validate;
use think\Validate;

class Cation extends Validate
{
    protected $rule = [
        'ca_name' => 'require',
        'sort' => 'require|number',
    ];

    protected $message = [
        'ca_name.require' => '配置分类名称不能为空！',
        'sort.require' => '排序不能为空！',
        'sort.number' => '排序一定要为数字！',
    ];

}