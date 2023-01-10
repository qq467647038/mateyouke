<?php
namespace app\common\model;
use think\Model;
use think\Db;

/**
 * 用户优惠券
 */
class MemberCoupon extends Model
{
    public static function findByUserId($user_id)
    {
        $where = [
            'user_id' => $user_id
        ];
        return self::where($where)->select();
    }

    public static function findByUserIdAndCouponId($user_id,$coupon_id)
    {
        $where = [
            'user_id' => $user_id,
            'coupon_id' => $coupon_id
        ];
        return self::where($where)->find();
    }

    public static function sendCoupon($user_id,$coupon_id,$shop_id)
    {
        $data = [
            'user_id' => $user_id,
            'coupon_id' => $coupon_id,
            'shop_id' => $shop_id
        ];
        return self::create($data);
    }
}