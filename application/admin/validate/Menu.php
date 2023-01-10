<?php
namespace app\admin\validate;
use think\Validate;

class Menu extends Validate
{
    protected $rule = [
        'name' => 'require',
        'sort' => 'require|number',
    ];

    protected $message = [
        'name.require' => '菜单名称不能为空！',
        'sort.require' => '排序不能为空！',
        'sort.number' => '排序一定要为数字！',
    ];
    

}