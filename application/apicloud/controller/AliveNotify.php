<?php
namespace app\apicloud\controller;
use app\apicloud\model\Gongyong as GongyongMx;
use app\util\timeFormat;
use app\common\model\Alive as AliveModel;
use app\common\model\AliveOrder as AliveOrderModel;
use app\common\model\AliveChargeLog;
use think\Db;

/**
 * 直播订单回调
 */
class AliveNotify extends Common {

    public function notify()
    {
        $xml = file_get_contents('php://input');
        $data = xmlToArray($xml);
        $data_sign = $data['sign'];
        unset($data['sign']);
        $wx = new Wxpay;
        $sign = $wx->getSign($data);
        // 判断签名是否正确,判断支付状态
        if ( ($sign===$data_sign) && ($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS') ) {
            $result = $data;
            //获取服务器返回的数据
            $order_sn = $data['out_trade_no'];  //订单单号
            $time = date("Y-m-d H:i:s",time());
            AliveOrderModel::where('order_sn',$order_sn)->update(['state'=>1,'paytime'=>$time]);
        }else{
            $result = false;
        }
        // 返回状态给微信服务器
        if ($result) {
            $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }else{
            $str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
        }
        echo $str;
    }

}