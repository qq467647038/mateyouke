<?php
namespace app\apicloud\controller;
use think\Controller;
use think\Db;

class Wxpayrzorder extends Controller{
    public function rznotify(){
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

            $rzorders = Db::name('rz_order')->where('ordernumber',$order_sn)->where('state',0)->find();
            if($rzorders){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('rz_order')->update(array('id'=>$rzorders['id'],'state'=>1,'zf_type'=>2,'pay_time'=>time()));
                    Db::name('apply_info')->update(array('state'=>1,'pay_time'=>time(),'id'=>$rzorders['apply_id']));
                    
                    $pt_wallets = Db::name('pt_wallet')->where('id',1)->find();
                    if($pt_wallets){
                        Db::name('pt_wallet')->where('id',1)->setInc('price', $rzorders['total_price']);
                        Db::name('pt_detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$rzorders['total_price'],'order_type'=>3,'order_id'=>$rzorders['id'],'wat_id'=>$pt_wallets['id'],'time'=>time()));
                    }
                
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
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