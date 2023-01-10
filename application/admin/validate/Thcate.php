<?php
namespace app\admin\validate;
use think\Validate;

class Thcate extends Validate

{
    protected $rule = [
        'cate_name' => 'require|unique:thcate',
        'desc' => 'require|max:150',
        'fh_type' => 'require|in:0,1',
        'tui_type' => 'require|in:0,1',
        'zcfh_type' => 'require|in:0,1',
        'sort'=>['require','regex'=>'/^[0-9]+$/'],
        'is_show' => 'require|in:0,1',
    ];

    protected $message = [
        'cate_name.require' => '种类名称不能为空',
        'cate_name.unique' => '种类名称已存在',
        'desc.require' => '描述不能为空',
        'desc.max' => '描述最多50个字符',
        'fh_type.require' => '请选择是否需要平台发货',
        'fh_type.in' => '是否需要平台发货参数错误',
        'tui_type.require' => '请选择是否需要用户退货',
        'tui_type.in' => '是否需要用户退货参数错误',
        'zcfh_type.require' => '请选择是否需要平台再次发货',
        'zcfh_type.in' => '是否需要平台再次发货参数错误',
        'sort.require' => '排序不能为空',
        'sort.regex' => '排序一定要为数字！',
        'is_show.require' => '请选择是否显示',
        'is_show.in' => '是否显示参数错误',
    ];
    

}