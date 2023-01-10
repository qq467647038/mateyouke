<?php
namespace app\admin\validate;
use think\Validate;

class MemberLevel extends Validate

{
    protected $rule = [
        'level_name' => 'require|unique:member_level',
        'points_min'=>'require',
        'points_max'=>'require',
        'rate'=>'require',
        'sort'=>['require','unique'=>'member_level','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];

    protected $message = [
        'level_name.require' => '会员等级名称不能为空',
        'level_name.unique' => '会员等级名称已存在',
        'points_min.require' => '最小积分不能为空',
        'points_max.require' => '最大积分不能为空',
        'rate.require' => '折扣率不能为空',
        'sort.unique' => '排序已存在',
        'sort.regex' => '排序为非零的正整数',
    ];
    

}