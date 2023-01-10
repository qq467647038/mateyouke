<?php
namespace app\admin\validate;
use think\Validate;

class Nav extends Validate

{
    protected $rule = [
        'nav_name' => 'require|unique:nav|max:20'
    ];

    protected $message = [
        'nav_name.require' => '导航位名称不能为空',
        'nav_name.unique' => '导航位名称已存在',
        'nav_name.max' => '导航位名称最多20个字符'
    ];
    

}