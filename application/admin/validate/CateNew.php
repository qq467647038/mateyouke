<?php
namespace app\admin\validate;
use think\Validate;

class CateNew extends Validate
{
    protected $rule = [
        'cate_name' => ['require','unique'=>'category'],
        'sort' => 'require|number',
    ];

    protected $message = [
        'cate_name.require' => '栏目名称不能为空！',
        'cate_name.unique' => '栏目名称已存在',
        'sort.require' => '排序不能为空！',
        'sort.number' => '排序一定要为数字！',
    ];

}