<?php
namespace app\admin\validate;
use think\Validate;

class Privilege extends Validate
{
    protected $rule = [
        'pri_name' => ['require','unique'=>'privilege'],
        'mname' => 'require',
        'cname' => 'require',
        'aname' => 'require',
        'fwname' => 'require',
        'sort' => 'require|number',
    ];

    protected $message = [
        'pri_name.require' => '权限名称不能为空',
        'pri_name.unique' => '权限名称已存在',
        'mname.require' => '模块名称不能为空',
        'cname.require' => '控制器名称不能为空',
        'aname.require' => '方法名称不能为空',
        'fwname.require' => '控制器别名不能为空',
        'sort.require' => '排序不能为空',
        'sort.number' => '排序只能是整数',
    ];
    
}