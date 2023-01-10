<?php
namespace app\common\model;
use think\Model;
use think\Db;

class WxTemplate extends Model
{
    /**
     * 通过场景类型查找模板信息
     *
     * @param [type] $type
     * @return void
     */
    public static function findByType($type)
    {
        return self::where('type',$type)->find();
    }
}