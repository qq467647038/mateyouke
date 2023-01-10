<?php
namespace app\admin\validate;
use think\Validate;

class Area extends Validate
{
    protected $rule = [
        'area_name' => 'require',
        'city_id'=>'require|number',
        'zm' => 'require',
        'sort' => 'require|number'
    ];

    protected $message = [
        'area_name.require' => '区域名称不能为空',
        'city_id.require' => '请选择所属城市',
        'city_id.number' => '所属城市参数错误',
        'zm.require' => '首字母不能为空',
        'sort.require' => '排序不能为空',
        'sort.number' => '排序一定要为数字',
    ];
    

}