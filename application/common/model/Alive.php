<?php
namespace app\common\model;
use think\Model;
use think\Db;

class Alive extends Model
{
    /**
     * 通过房间号获取直播信息
     *
     * @param [type] $room
     * @return void
     */
    public static function findByRoom($room)
    {
        return self::where('room',$room)->find();
    }
}