<?php

namespace app\common\logic;

use app\common\service\SendWxMsgService;
use app\common\service\SmsService;
use app\common\service\VipService;

use think\Cache;

/**
 * 订单某些动作之后的操作
 */
class OrderAfterLogic {

    /**
     * 发货之后的操作
     *
     * @return void
     */
    public function sendGoodsOp($order_sn)
    {
        // 发货公众号模板通知
        // (new SendWxMsgService())->sendGoodsToUser($order_sn);
    }

    /**
     * 提醒商家发货之后的操作
     *
     * @return void
     */
    public function warnSendOrder($order_sn)
    {
        (new SendWxMsgService())->warnOrderMsg($order_sn);
        (new SmsService())->sendOrderWarnToShop($order_sn);
    }

    /**
     * 支付之后的操作
     *
     * @return void
     */
    public function payOrderOp($order_sn)
    {
        // // 购买会员分佣
        // (new CommissionLogic())->setUserVipProfit($order_sn);
        // 发送公众号模板通知
        (new SendWxMsgService())->sendWapOrderMsgToUser($order_sn);
        // 短信通知
        (new SmsService())->sendBuyOrderToShop($order_sn);
        // 如果是购买掌柜卡,提升权益
        (new VipService())->upLevel($order_sn);

    }

    /**
     * 确认收货之后的操作
     *
     * @return void
     */
    public function confirmOrder($order_sn)
    {

    }

    /**
     * 直播推送
     *
     * @return void
     */
    public function sendmsg($user,$data)
    {

        foreach ($user as $value) {
            if(!$value['wx_openid']) continue ;
            $data['openid'] = $value['wx_openid'];
            (new SendWxMsgService())->popOpenIdToSend($data);
        }

        //$data['openid'] = "oixGL6XbsHfz3uBv7n9oMlrLpIj8";
        // $data['title'] = '直播开始了';
        // $data['content'] = '旅游直播即将开始将点击前往';
        // $data['url'] = 'http://www.xinglidatravel.com.cn/portal/';
        // $data['time'] = 'http://www.xinglidatravel.com.cn/portal/';
        //(new SendWxMsgService())->popOpenIdToSend($data);



        // for ($i = 0; $i < 1000; $i++) { 
        //     $data = $this->handler->rpop('sendWx');
        //     if(!$data) return ;
        //     // // $array = [
        //     // //     'userid','openid','url'
        //     // // ];
        //     $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $this->access_token;
        //     // $array = explode(",",$data);
        //     // $userId = $array[0];
        //     // $openId = $array[1];
        //     // $text = $array[2];
        //     // $map = [
        //     //     'touser' => $openId,
        //     //     'msgtype' => 'text',
        //     //     'text' => [
        //     //         'content' => $text
        //     //     ]
        //     // ];
        //     $array = json_decode($data,true);
        //     $map = [
        //         'touser' => $array['openid'],
        //         'msgtype' => 'news',
        //         'news' => [
        //             'articles' => [
        //                 [
        //                     'title' => $array['title'],
        //                     'description' => $array['content'],
        //                     'url' => $array['url'],
        //                 ]
        //             ]
        //         ]
        //     ];
        //     $mapData = json_encode($map, JSON_UNESCAPED_UNICODE);
        //     $resault =HttpUtil::httpPost($url, $mapData);
        //     $resault = json_decode($resault,true);
        //     if($resault['errmsg'] == 'ok'){
        //         $status = 1;
        //     }else{
        //         $status = 0;
        //     }
        //     // $log = [
        //     //     'user_id' => $array['user_id'],
        //     //     'status' => $status,
        //     //     'text' => $array['title'].",".$array['content'].",".$array['url'],
        //     //     'c_time' => time(),
        //     //     'desc' => json_encode($resault)
        //     // ];
        //     // M('mass_wx_log')->save($log);
        // }
    }



}