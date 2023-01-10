<?php
namespace app\admin\validate;
use think\Validate;

class Ad extends Validate
{
    protected $rule = [
        'ad_name' => 'require|unique:ad|max:60',
        'ad_type' => 'require|in:1,2',
        'pos_id' => 'require',
    ];

    protected $message = [
        'ad_name.require' => '广告名称不能为空',
        'ad_name.unique' => '广告名称已存在',
        'ad_name.max' => '广告名称最多20个字符',
        'ad_type.require' => '广告类型不能为空',
        'ad_type.in' => '广告类型参数错误',
        'pos_id.require' => '请选择广告位置',
    ];
    

}