<?php
namespace app\admin\validate;
use think\Validate;

class Position extends Validate

{
    protected $rule = [
        'position_name' => 'require|unique:position',
        'quyu_level'=>'require|in:0,1,2,3',
        'sort'=>['require','unique'=>'position','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];

    protected $message = [
        'position_name.require' => '职位名称不能为空',
        'position_name.unique' => '职位名称已存在',
        'quyu_level.require' => '请选择区域等级',
        'quyu_level.in' => '区域等级参数错误',
        'sort.require' => '排序不能为空',
        'sort.unique' => '排序已存在',
        'sort.regex' => '排序为非零的正整数',
    ];
    

}