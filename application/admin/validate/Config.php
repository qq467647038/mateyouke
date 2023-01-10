<?php
namespace app\admin\validate;
use think\Validate;

class Config extends Validate
{
    protected $rule = [
        'cname' => 'require|unique:config',
        'ename' => 'require|unique:config',
        'ca_id'=>'require|number',
        'type'=>'require|number',
    ];

    protected $message = [
        'cname.require' => '中文名称不能为空',
        'cname.unique' => '中文名称已存在',
        'ename.require' => '英文名称不能为空',
        'ename.unique' => '英文名称已存在',
        'ca_id.require' => '请选择配置分类',
        'ca_id.number' => '配置分类参数错误',
        'type.require' => '请选择配置类型',
        'type.number' => '配置类型参数错误',
    ];

}