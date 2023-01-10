<?php
namespace app\admin\validate;
use think\Validate;

class Alive extends Validate
{
    protected $rule = [ 
        'title' => 'require|unique:alive',
        'shop_id' => 'require|unique:alive',
        'type_id' => 'require',
        'room' => 'require|unique:alive',
        'notice' => 'require',
        'user_profile' => 'require',
        'cover' => 'require',
    ];

    protected $message = [
        'title.require' => '标题不能为空',
        'shop_id.require' => '店铺ID不能为空',
        'shop_id.unique' => '店铺ID已存在',
        'type_id.require' => '商品类型不能为空',
        'room.require' => '房间号不能为空',
        'room.unique' => '房间号已存在',
        'notice.require' => '房间公告不能为空',
        'user_profile.require' => '主播介绍不能为空',
        'cover.require' => '封面图片不能为空',
        
    ];
    

}