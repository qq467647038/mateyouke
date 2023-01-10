<?php
namespace app\admin\validate;
use think\Validate;

class Role extends Validate
{
    protected $rule = [
        'rolename' => ['require','unique'=>'role'],
    ];

    protected $message = [
        'rolename.require' => '角色名称不能为空',
        'rolename.unique' => '角色名称已存在',
    ];
    
}