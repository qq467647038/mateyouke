<?php
namespace app\admin\validate;
use think\Validate;

class School extends Validate
{
    protected $rule = [
        // 'cate_name' => ['require','unique'=>'category'],
        // 'sort' => 'require|number',
		// 'pic_id' => 'requireIf:check_pic_id',
		'teacher_id' => 'require|number|gt:0|integer', 
		'course_id' => 'require|number|gt:0|integer',
		'pid' => 'require|number|gt:0|integer',
		'catid' => 'require|number|gt:0|integer',
		'out_videourl' => 'url',
		'user_id' => 'require|number|integer',
		'title' => 'require',
		'ranktitle' => 'require',
		'description' => 'require',
		'content' => 'require',
		'catname' => 'require',
		'displaytime' => 'require|number|egt:0|integer',
		
		'price' => 'require|number',
    ];

    protected $message = [
        'teacher_id.gt' => '所属老师不能为空！',
        'teacher_id.require' => '所属老师不能为空！',
		'teacher_id.number' => '所属老师一定要为数字！',
		
		'out_videourl.url' => '视频外部链接',
		
        'course_id.gt' => '所属课程不能为空！',
        'course_id.require' => '所属课程不能为空！',
		'course_id.number' => '所属课程一定要为数字！',
		
        'pid.gt' => '一级目录不能为空！',
        'pid.require' => '一级目录不能为空！',
		'pid.number' => '一级目录一定要为数字！',
		
        'catid.gt' => '二级目录|所属分类 不能为空！',
        'catid.require' => '二级目录|所属分类 不能为空！',
		'catid.number' => '二级目录|所属分类 一定要为数字！',
		
        // 'pic_id.require' => '封面图不能为空！',
		// 'pic_id.check_pic_id' => '123',
		
        'displaytime.egt' => '试看时间只能大于等于0！',
        'displaytime.require' => '试看时间不能为空！',
		'displaytime.number' => '试看时间一定要为数字！',
		
		
        'user_id.require' => '用户id不能为空！',
		'user_id.number' => '用户id一定要为数字！',
        'title.require' => '名称不能为空！',
        'ranktitle.require' => '老师称号不能为空！',
        'description.require' => '简介不能为空！',
        'content.require' => '介绍不能为空！',
        'catname.require' => '分类名称不能为空！',
        // 'cate_name.require' => '栏目名称不能为空！',
        // 'cate_name.unique' => '栏目名称已存在',
        // 'sort.require' => '排序不能为空！',
        // 'sort.number' => '排序一定要为数字！',
		
        'price.require' => '价格不能为空！',
		'price.number' => '价格一定要为数字！',
    ];
    
    protected $scene = [
        'teacher'  =>  ['user_id','title','ranktitle','description','content'],
		'category' => ['catname'],
		'course' => ['teacher_id','title','description','catid','price','content'],
		'video' => ['teacher_id', 'course_id', 'pid', 'catid', 'displaytime', 'out_videourl'],
    ];
	
	function check_pic_id($value, $tip, $data){
		return false;
	}

}