<?php
namespace app\apicloud\controller;

use app\common\logic\OrderAfterLogic;

use think\Controller;
use think\Db;

class CourseWxpaynotify extends Controller{
    public function notify(){
        $xml = file_get_contents('php://input');
        // 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了
        //file_put_contents('./Api/wxpay/logs/log.txt',$xml,FILE_APPEND);
        //将服务器返回的XML数据转化为数组
        // $data = xmlToArray($xml);
        libxml_disable_entity_loader(true); //禁止引用外部xml实体

        $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);//XML转数组

        $data = (array)$data;


        // var_dump($data);exit;

        $d['data'] = $xml;
        $d['type'] = 2;
        $d['order_sn'] = $data['out_trade_no'] ? $data['out_trade_no'] : '';
        $d['addtime'] = time();

        Db::name('course_pay')->insert($d);

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

            $order               =   Db::name('course_order')->where('order_sn', $order_sn)->find();

            //订单是否存在
            if(!empty($order)){
                if($order['state'] == 1){
                    datamsg(LOSE,'此订单，已完成支付!');
                }

                if($total_fee/100 != $order['amount']){
                    datamsg(LOSE,'支付金额异常');
                }
            }else{
                datamsg(LOSE,'此订单，不存在');
            }

            // 启动事务
            Db::startTrans();
            try{
//            $pt_wallets = Db::name('pt_wallet')->where('id',1)->find();
//            if($pt_wallets){
//                Db::name('pt_wallet')->where('id',1)->setInc('price', $order['amount']);
//                Db::name('pt_detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$order['amount'],'order_type'=>1,'order_id'=>$order['order_id'],'wat_id'=>$pt_wallets['id'],'time'=>time()));
//            }

                // 更改订单相关信息
                $res = Db::name('course_order')->where('order_sn', $order_sn)->update([
                    'state' => 1,
                    'paytime' => time(),
                    'pay_code' => 'weixin',
                    'pay_name' => '微信支付'
                ]);
                if(!$res){
                    throw new \Exception('订单状态修改失败');
                }

                // 提交事务
                Db::commit();

                $value = array('status'=>200,'mess'=>'支付成功');
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $value = array('status'=>400,'mess'=>'支付失败','data'=>array('status'=>400));
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