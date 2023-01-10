<?php
namespace app\admin\validate;
use think\Validate;

class Keyword extends Validate
{
    protected $rule = [
        'keyword_name' => 'require|unique:keyword',
        'key_type' => 'require|in:1,2,3',
    ];

    protected $message = [
        'keyword_name.require' => '关键字不能为空',
        'keyword_name.unique' => '关键字已存在',
        'key_type.require' => '请选择关键字类型',
        'key_type.in' => '关键字类型参数错误',
    ];

}