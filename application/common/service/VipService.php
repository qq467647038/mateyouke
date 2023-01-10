<?php

namespace app\common\service;

use app\common\model\Member as MemberModel;
use app\common\model\Order as OrderModel;
use app\common\model\Xxk as XxkModel;

use think\Db;

class VipService
{
    public function __construct()
    {
        
    }

    public function findMemberLevel()
    {

    }

    /**
     * 提升会员等级
     *
     * @param [type] $order_sn
     * @return void
     */
    public function upLevel($order_sn)
    {
        $orderInfo = OrderModel::findOrderByOrderNumber($order_sn);
        $userInfo = MemberModel::findById($orderInfo['user_id']);
        if($userInfo['level'] != 0) return;
        if(!$this->isVipGoods($orderInfo['goods'])) return ;
        $this->after($orderInfo);
    }

    private function after($orderInfo)
    {
        // 提升用户等级
        MemberModel::updateById($orderInfo['user_id'],['level'=>1]);
        // 添加孝笑卡
        $card = $this->randCardNum($orderInfo['user_id']);
        $pwd = $this->randPwd();
        $data = [
            'card_id' => $card,
            'card_psw' => $pwd,
            'user_id' => $orderInfo['user_id'],
            'order_id' => $orderInfo['id']
        ];
        XxkModel::add($data);
    }

    private function randCardNum($user_id)
    {
        $time = date("Ymd");
        $num = rand(1000,9999);
        $card = $time.$num.$user_id;
        return $card;
    }

    private function randPwd()
    {
        return rand(1000,9999);
    }

    private function isVipGoods($goodsList)
    {
        $sign = false;
        foreach ($goodsList as $value) {
            if($value['goods_id'] == 603) $sign = true;
        }
        return $sign;
    }
}