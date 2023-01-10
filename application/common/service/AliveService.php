<?php

namespace app\common\service;

use app\common\model\Alive as AliveModel;
use app\common\model\AliveOrder as AliveOrderModel;
use app\common\model\AliveChargeLog;

use think\Db;

class AliveService
{
    /**
     * 创建订单
     *
     * @param [type] $data
     * @return void
     */
    public function addOrder($data)
    {
        $data['order_sn'] = $this->orderSn();
        $data['state'] = 0;
        $data['addtime'] = date("Y-m-d H:i:s",time());
        AliveOrderModel::create($data);
        return $data['order_sn'];
    }

    /**
     * 通过订单号查询订单信息
     *
     * @param [type] $order_sn
     * @return void
     */
    public function findByOrderSn($order_sn)
    {
        $orderInfo = AliveOrderModel::where('order_sn',$order_sn)->find();
        return $orderInfo;
    }

    /**
     * 判断订单是否支付过
     *
     * @param [type] $orderInfo
     * @return void
     */
    public function checkOrderPay($alive_sn)
    {
        $result = AliveOrderModel::where(['alive_sn'=>$alive_sn,'state'=>1])->find();
        if($result){
            return true;
        } 
        return false;
    }

    /**
     * 生成订单号
     *
     * @return void
     */
    private function orderSn()
    {
        return 'A'.date("YmdHis").rand(100,999);
    }
}