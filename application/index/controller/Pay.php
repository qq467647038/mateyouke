<?php

namespace app\index\controller;

use app\index\controller\Common;
use app\index\controller\QRcode;
use think\Db;

class Pay extends Common {

    //提交订单，支付页面
    public function orderpay() {
        $user_id = $this->user_id;
        $order_number = input("order_number");
        if (!$order_number) {
            $this->error('订单信息错误', $this->gourl);
        }
        
        $orderinfo = Db::name('order_zong')->where('order_number', $order_number)->where('user_id', $user_id)->where('state', 0)->find();
        if (!$orderinfo) {
            $this->error('订单信息错误', $this->gourl);
        }
        if (time() > $orderinfo['time_out']) {
            $this->error('订单已过期', $this->gourl);
        }
        $this->assign('orderinfo', $orderinfo);
        return $this->fetch();
    }
    
    //确认支付
    public function paysubmit() {
        if (!request()->isPost()) {
            $this->error('请求方式错误', $this->gourl);
        }
        $user_id = $this->user_id;
        $order_number = input('post.pay_sn');
        $payment = input('post.payment_code');
        if (!$user_id) {
            $this->error('用户信息错误', $this->gourl);
        }
        if (!$order_number) {
            $this->error('订单参数错误', $this->gourl);
        }
        if (!in_array($payment, array(1,2))) {
            $this->error('支付参数错误', $this->gourl);
        }
        $orderinfos = Db::name('order_zong')->where('order_number',$order_number)->where('state',0)->where('user_id',$user_id)->find();
        if (!$orderinfos) {
            $this->error('找不到相关类型订单', $this->gourl);
        }
        $nowtime = time();
        if ($nowtime > $orderinfos['time_out']) {
            $this->error('订单已过期，支付失败', $this->gourl);
        }
        $orderes = Db::name('order')->where('zong_id',$orderinfos['id'])->field('id,ordernumber,state,fh_status,order_status,time_out')->select();
        if (!$orderes) {
            $this->error('找不到相关类型订单', $this->gourl);
        }
        foreach ($orderes as $val2){
            if($val2['state'] != 0 || $val2['fh_status'] != 0 || $val2['order_status'] != 0){
                $this->error('订单类型信息错误，支付失败', $this->gourl);
            }
        }
        $leixing = 0;
        $zforder_num = '';
        if(count($orderes) == 1){
            $leixing = 1;
            $zforder_num = $orderes[0]['ordernumber'];
        }
        
        $webconfig = $this->webconfig;
        switch($payment){
            case 1:
                $this->error('支付宝支付暂未开通', $this->gourl);
                break;
            case 2:
                $quxiao_time = $orderinfos['time_out']-$nowtime;
                if($quxiao_time > 60){
                    //获取订单号
                    $reoderSn = $orderinfos['order_number'];
                    //获取支付金额
                    $money = $orderinfos['total_price'];

                    $wx = new Wxpay();

                    $body = '商品支付';//支付说明

                    $out_trade_no = $reoderSn;//订单号

                    $total_fee = $money * 100;//支付金额(乘以100)

                    $time_start = $nowtime;

                    $time_expire = $orderinfos['time_out'];

                    $notify_url = $webconfig['weburl'].'/apicloud/Wxpaynotify/notify';//回调地址
                    
                    $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url, $orderinfos['product_id']);//调用微信支付的方法
                    if(!empty($order['prepay_id']) && !empty($order['code_url'])){
                        $this->assign('prepay_url', urlencode($order['code_url']));
                        $this->assign('orderinfos', $orderinfos);
                        return $this->fetch();
                    }else{
                        $err_code_des = isset($order['err_code_des']) ? $order['err_code_des'] : '支付参数错误';
                        $this->error($err_code_des, $this->gourl);
                    }
                    
                }else{
                    $this->error('订单唤起支付超时，支付失败', $this->gourl);
                }
                break;
            case 3:
                $paypwd = Db::name('member')->where('id',$user_id)->value('paypwd');
                if($paypwd){
                    $pay_password = input('post.pay_password');
                    if($pay_password && preg_match("/^\\d{6}$/", $pay_password)){
                        if($paypwd == md5($pay_password)){
                            $wallets = Db::name('wallet')->where('user_id',$user_id)->find();
                            if($wallets['price'] >= $orderinfos['total_price']){
                                $sheng_price = $wallets['price']-$orderinfos['total_price'];

                                // 启动事务
                                Db::startTrans();
                                try{
                                    Db::name('wallet')->update(array('price'=>$sheng_price,'id'=>$wallets['id']));

                                    Db::name('detail')->insert(array('de_type'=>2,'zc_type'=>2,'price'=>$orderinfos['total_price'],'order_type'=>1,'order_id'=>$orderinfos['id'],'user_id'=>$user_id,'wat_id'=>$wallets['id'],'time'=>time()));

                                    Db::name('order_zong')->update(array('id'=>$orderinfos['id'],'state'=>1,'zf_type'=>3,'pay_time'=>time()));

                                    foreach ($orderes as $vr){
                                        Db::name('order')->update(array('id'=>$vr['id'],'state'=>1,'zf_type'=>3,'pay_time'=>time()));
                                        $goodinfos = Db::name('order_goods')->where('order_id',$vr['id'])->field('id,goods_id,goods_num,hd_type,hd_id,shop_id')->select();

                                        foreach ($goodinfos as $kd => $vd){
                                            $goodhds = Db::name('goods')->where('id',$vd['goods_id'])->field('id')->find();
                                            if($goodhds){
                                                Db::name('goods')->where('id',$vd['goods_id'])->setInc('sale_num',$vd['goods_num']);
                                            }
                                            $shophds = Db::name('shops')->where('id',$vd['shop_id'])->field('id')->find();
                                            if($shophds){
                                                Db::name('shops')->where('id',$vd['shop_id'])->setInc('sale_num',$vd['goods_num']);
                                            }
                                        }
                                    }

                                    // 提交事务
                                    Db::commit();
                                    if($leixing == 0){
                                        $zfinfos = array('leixing'=>$leixing,'order_num'=>$zforder_num);
                                    }elseif($leixing == 1){
                                        $zfinfos = array('leixing'=>$leixing,'order_num'=>$zforder_num);
                                    }
                                    $this->success('支付成功', $this->gourl);
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $this->error('钱包余额支付失败', $this->gourl);
                                }
                            }else{
                                $this->error('钱包余额不足，支付失败', $this->gourl);
                            }
                        }else{
                            $this->error('支付密码错误', $this->gourl);
                        }
                    }else{
                        $this->error('支付密码错误', $this->gourl);
                    }
                }else{
                    $this->error('请先设置支付密码', $this->gourl);
                }
                break;
        }
        
    }
    
    //支付页面定时请求查看订单是否回调支付成功
    public function check_order_state() {
        $user_id = $this->user_id;
        $order_id = input('post.order_id');
        if (!$order_id) {
            return json(array('status'=>400,'mess'=>'订单信息错误','data'=>array('status'=>400)));
        }
        $where = array('id' => $order_id, 'user_id' => $user_id, 'state' => 1);
        $order = Db::name('order_zong')->where($where)->where('time_out', 'gt', time())->find();
        if (!$order) {
            return json(array('status'=>400,'mess'=>'订单未支付','data'=>array('status'=>400)));
        }
        return json(array('status'=>200,'mess'=>'订单已支付','data'=>array('status'=>200)));
    }
    
    public function paysuccess() {
        return $this->fetch();
    }
    
    public function get_prepay_url() {
        $prepay_url = urldecode(input('prepay_url'));
        if(substr($prepay_url, 0, 6) == "weixin"){
            QRcode::png($prepay_url);
        }else{
            header('HTTP/1.1 404 Not Found');
        }
    }
    
}
