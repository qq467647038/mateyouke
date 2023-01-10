<?php
namespace app\common\model;
use think\Model;
use think\Db;

class AliveOrder extends Model
{
    /**
     * 查找房间当天订单
     *
     * @param [type] $room
     * @return void
     */
    public static function findByTodayOrder($roomInfo,$user_id)
    {
        $where = [
            'alive_sn' => $roomInfo['alive_sn'],
            'state' => 1,
            'room' => $roomInfo['room'],
            'user_id' => $user_id
        ];
        return self::where($where)->find();
    }

    /**
     * 通过直播编码和用户id获取订单详情
     *
     * @param [type] $user_id
     * @param [type] $alive_sn
     * @return void
     */
    public static function findByUseridAndSn($user_id,$alive_sn)
    {
        $where = [
            'user_id' => $user_id,
            'alive_sn' => $alive_sn,
            'state' => 1
        ];
        return self::where($where)->find();
    }
}