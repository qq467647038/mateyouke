<?php
namespace app\admin\validate;
use think\Validate;

class City extends Validate
{
    protected $rule = [
        'city_name' => 'require|unique:city',
        'pro_id'=>'require|number',
        'zm' => 'require',
        'sort' => 'require|number',
    ];

    protected $message = [
        'city_name.require' => '城市名称不能为空',
        'city_name.unique' => '城市名称已存在',
        'pro_id.require' => '请选择所属省份',
        'pro_id.number' => '选择省份参数错误',
        'zm.require' => '首字母不能为空',
        'sort.require' => '排序不能为空',
        'sort.number' => '排序一定要为数字',
    ];
    

}