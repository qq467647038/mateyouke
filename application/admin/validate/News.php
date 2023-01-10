<?php
namespace app\admin\validate;
use think\Validate;

class News extends Validate
{
    protected $rule = [
        'ar_title' => 'require',
        'author' => 'require',
        'source' => 'require',
        'cate_id' => 'require',
        'sort' => 'require|number',
    ];

    protected $message = [
        'ar_title.require' => '文章名称不能为空',
        'author.require' => '作者不能为空',
        'source.require' => '出处不能为空',
        'cate_id.require' => '请选择分类栏目',
        'sort.require' => '排序不能为空！',
        'sort.number' => '排序一定要为数字！',
    ];

}