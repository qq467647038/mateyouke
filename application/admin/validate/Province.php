<?php
namespace app\admin\validate;
use think\Validate;

class Province extends Validate
{
    protected $rule = [
        'pro_name' => 'require|unique:province',
        'zm' => 'require',
        'sort' => 'require|number',
    ];

    protected $message = [
        'pro_name.require' => '省份名称不能为空',
        'pro_name.unique' => '省份名称已存在',
        'zm.require' => '首字母不能为空',
        'sort.require' => '排序不能为空',
        'sort.number' => '排序一定要为数字',
    ];
    

}