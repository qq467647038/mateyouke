<?php

namespace app\common\service;

use app\common\model\Member as MemberModel;
use app\common\model\Coupon as CouponModel;

use app\common\service\MemberCouponService;

use think\Db;

/**
 * 优惠券
 */
class CouponService
{
    /**
     * 获取店铺所有优惠券
     *
     * @return void
     */
    public function findByShopsId($shop_id)
    {
        return CouponModel::findByShopId($shop_id);
    }
}