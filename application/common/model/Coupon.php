<?php
namespace app\common\model;
use think\Model;
use think\Db;

class Coupon extends Model
{
    public static function findById($id)
    {
        $where = [
            'id' => $id,
            'onsale' => 1
        ];
        return self::where($where)->find();
    }

    public static function findByShopId($shop_id)
    {
        $where = [
            'shop_id' => $shop_id,
            'start_time' => ['elt',time()],
            'end_time' => ['gt',time()-3600*24],
            'onsale' => 1
        ];
        return self::where($where)->select();
    }
}