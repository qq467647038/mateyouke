<?php
namespace app\admin\validate;
use think\Validate;

class SaleTime extends Validate

{
    protected $rule = [
        'time'=>['require','unique'=>'sale_time','regex'=>'/^[0-9]+$/','between'=>'0,22'],
    ];

    protected $message = [
        'position_name.require' => '职位名称不能为空',
        'position_name.unique' => '职位名称已存在',
        'time.require' => '时间段不能为空',
        'time.unique' => '时间段已存在',
        'time.regex' => '时间段为整数',
        'time.between' => '时间段在0-22区间内',
    ];
    

}