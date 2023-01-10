<?php
namespace app\apicloud\controller;
use think\Controller;
use think\Db;

class Wxpaynotify extends Controller{
    public function notify(){
        $xml = file_get_contents('php://input');
        // 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了
        //file_put_contents('./Api/wxpay/logs/log.txt',$xml,FILE_APPEND);
        //将服务器返回的XML数据转化为数组
        $data = xmlToArray($xml);
        // 保存微信服务器返回的签名sign
        $data_sign = $data['sign'];
        // sign不参与签名算法
        unset($data['sign']);
        $wx = new Wxpay;
        $sign = $wx->getSign($data);
        
        // 判断签名是否正确  判断支付状态
        if ( ($sign===$data_sign) && ($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS') ) {
            $result = $data;
            // 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了
            //file_put_contents('./Api/wxpay/logs/log1.txt',$xml,FILE_APPEND);
        
            //获取服务器返回的数据
            $order_sn = $data['out_trade_no'];  //订单单号
            $total_fee = $data['total_fee'];    //付款金额

            $orderzongs = Db::name('order_zong')->where('order_number',$order_sn)->where('state',0)->find();
            if($orderzongs){
                $orderes = Db::name('order')->where('zong_id',$orderzongs['id'])->where('state',0)->where('fh_status',0)->where('order_status',0)->select();
                if($orderes){
                    // 启动事务
                    Db::startTrans();
                    try{
                        Db::name('order_zong')->update(array('id'=>$orderzongs['id'],'state'=>1,'zf_type'=>2,'pay_time'=>time()));
                        $pt_wallets = Db::name('pt_wallet')->where('id',1)->find();
                        if($pt_wallets){
                            Db::name('pt_wallet')->where('id',1)->setInc('price', $orderzongs['total_price']);
                            Db::name('pt_detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$orderzongs['total_price'],'order_type'=>1,'order_id'=>$orderzongs['id'],'wat_id'=>$pt_wallets['id'],'time'=>time()));
                        }
                    
                        foreach ($orderes as $vr){
                            Db::name('order')->update(array('id'=>$vr['id'],'state'=>1,'zf_type'=>2,'pay_time'=>time()));
                            $goodinfos = Db::name('order_goods')->where('order_id',$vr['id'])->field('id,goods_id,goods_num,hd_type,hd_id,shop_id')->select();
                            
                            foreach ($goodinfos as $kd => $vd){
                                $goods = Db::name('goods')->where('id',$vd['goods_id'])->field('id')->find();
                                if($goods){
                                    Db::name('goods')->where('id',$vd['goods_id'])->setInc('sale_num',$vd['goods_num']);
                                }
                                $shops = Db::name('shops')->where('id',$vd['shop_id'])->field('id')->find();
                                if($shops){
                                    Db::name('shops')->where('id',$vd['shop_id'])->setInc('sale_num',$vd['goods_num']);
                                }
                            }
                        }
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                    }
                }
            }       
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