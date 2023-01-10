<?php
namespace app\admin\validate;
use think\Validate;

class Pos extends Validate
{
    protected $rule = [
        'pos_name' => ['require','unique'=>'pos'],
        'width' => 'require|number',
        'height' => 'require|number',
    ];

    protected $message = [
        'pos_name.require' => '广告位名称不能为空',
        'pos_name.unique' => '广告位名称已存在',
        'width.require' => '宽度不能为空',
        'width.number' => '宽度一定要为数字',
        'height.require' => '高度不能为空',
        'height.number' => '高度一定要为数字',
    ];
    
}