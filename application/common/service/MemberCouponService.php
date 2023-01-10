<?php

namespace app\common\service;

use app\common\model\Member as MemberModel;
use app\common\model\Coupon as CouponModel;
use app\common\model\MemberCoupon as MemberCouponModel;
use app\common\model\MemberCouponLog as MemberCouponLogModel;

use think\Db;

/**
 * 用户优惠券
 */
class MemberCouponService
{

    public function sendCoupon($user_id,$coupon_id)
    {
        $couponInfo = CouponModel::findById($coupon_id);
        if(!$couponInfo) return ['status'=>false,'mess'=>'找不到优惠券相关信息'];
        Db::startTrans();
        try {
            MemberCouponModel::sendCoupon($user_id,$coupon_id,$couponInfo['shop_id']);
            $data = [
                'user_id' => $user_id,
                'coupon_id' => $coupon_id,
                'ctime' => date("Y-m-d H:i:s",time())
            ];
            MemberCouponLogModel::create($data);
            // 提交事务
            Db::commit();
        } catch (\Throwable $th) {
            // 回滚
            Db::rollback();
        }
        
        return ['status'=>true];
    }

    public function findByUserIdAndCouponId($user_id,$coupon_id)
    {
        return MemberCouponModel::findByUserIdAndCouponId($user_id,$coupon_id);
    }
}