<?php
namespace app\admin\validate;
use think\Validate;

class Link extends Validate
{
    protected $rule = [
        'li_title' => 'require|unique:link',
        'li_url' => 'require|url',
        'sort' => 'require|number',
    ];

    protected $message = [
        'li_title.require' => '友链名称不能为空',
        'li_title.unique' => '友链名称已存在',
        'li_url.require' => '链接url不能为空',
        'li_url.url' => '链接url格式不正确',
        'sort.require' => '排序不能为空！',
        'sort.number' => '排序一定要为数字！',
    ];

}