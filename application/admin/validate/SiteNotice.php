<?php
namespace app\admin\validate;
use think\Validate;

class SiteNotice extends Validate
{
    protected $rule = [
        'title' => 'require',
        'content' => 'require'
    ];

    protected $message = [
        'title.require' => '公告名称不能为空',
        'content.require' => '公告内容不能为空'
    ];

}