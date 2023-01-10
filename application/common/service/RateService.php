<?php

namespace app\common\service;

use app\common\model\Member as MemberModel;
use app\common\model\Order as OrderModel;
use app\common\model\Xxk as XxkModel;
use app\common\model\Goods as GoodsModel;
use app\common\model\MemberLevel as MemberLevelModel;

use think\Db;
/**
 * 会员折扣类
 * 
 * 自营店铺打折
 * 掌柜会员 0.75
 * 东家 0.5
 * 普通用户没打折
 */
class RateService
{
    private $userId;
    private $rateShop;
    public function __construct($userId)
    {
        $this->userId = $userId;
        $this->rateShop = [1];
    }

    /**
     * 判断是否有会员折扣
     *
     * @param [type] $shopId
     * @return boolean
     */
    public function isRate($shopId)
    {
        return false;
        if(!$this->isSelfShop($shopId)) return false;
        $userInfo = MemberModel::findById($this->userId);
        if(!$this->isVip($userInfo['level'])) return false;
        return true;
    }

    public function findUserRate()
    {
        $userInfo = MemberModel::findById($this->userId);
        $rateInfo = MemberLevelModel::findById($userInfo['level']);
        if($rateInfo){
            return $rateInfo['rate'];
        }else{
            return 1;
        }
        
    }

    /**
     * 判断是否有会员折扣
     *
     * @param [type] $level
     * @return boolean
     */
    private function isVip($level)
    {
        if($level > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 判断是否自营小铺
     *
     * @param [type] $shopId
     * @return boolean
     */
    private function isSelfShop($shopId)
    {
        foreach ($this->rateShop as $value) {
            if($value == $shopId) return true;
        }
        return false;
    }
}