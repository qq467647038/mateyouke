<?php
namespace app\admin\validate;
use think\Validate;

class WineHomeFive extends Validate
{
    protected $rule = [
        'name' => 'require',
        'path' => 'require'
    ];

    protected $message = [
        'name.require' => '名称不能为空',
        'path.require' => '路径不能为空',
    ];
    

}