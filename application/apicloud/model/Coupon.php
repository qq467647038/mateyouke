<?php
/**
 * Created by PhpStorm.
 * @anthor: Pupil_Chen
 * Date: 2020/9/22 0022
 * Time: 10:27
 */

namespace app\apicloud\model;


use think\Model;

class Coupon extends Model
{
    /**
     * @function优惠券结束日期获取器
     * @param $value
     * @author Feifan.Chen <1057286925@qq.com>
     * @return false|string
     */
    public function getEndTimeAttr($value){

        return date('Y-m-d',$value);
    }

    /**
     * @function优惠券开始时间获取器
     * @param $value
     * @author Feifan.Chen <1057286925@qq.com>
     * @return false|string
     */
    public function getStartTimeAttr($value){
        return date('Y-m-d',$value);
    }

    public function coupon(){
        return $this->hasOne('MemberCoupon','coupon_id','id');
    }

}