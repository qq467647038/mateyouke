<?php
namespace app\apicloud\controller;
use think\Controller;
use think\Db;
use app\admin\services\Upush;
use app\apicloud\model\AliPayHelper;

class CourseAliPay extends Controller{
    //商品购买支付宝支付回调地址
    public function aliNotify(){
        $data = $_POST;
        $d['data'] = $data;
        $d['type'] = 1;
        $d['addtime'] = time();

        Db::name('course_pay')->insert($d);
        $AliPayHelper = new AliPayHelper();
        $return = $AliPayHelper->payReturn($data);
        $order_sn = $data['out_trade_no'];  //订单单号
        $price = $data['total_amount'];
        if($return == 1){   //验证成功
            $this->ChangeOrder($order_sn,$price);
            echo 'success';
        }else{
            echo 'fail';
        }
    }

    /****
     * 更改订单状态处理
     */
    private function ChangeOrder($order_sn,$total_fee){
        $order               =   Db::name('course_order')->where('order_sn', $order_sn)->find();

        //订单是否存在
        if(!empty($order)){
            if($order['state'] == 1){
                datamsg(LOSE,'此订单，已完成支付!');
            }

            if($total_fee != $order['amount']){
                datamsg(LOSE,'支付金额异常');
            }
        }else{
            datamsg(LOSE,'此订单，不存在');
        }
        $member_data   =  DB::name('member')->where(['id'=>$order['user_id']])->find();
        $client_id = $member_data['appinfo_code'];

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
                'pay_code' => 'alipay',
                'pay_name' => '支付宝支付'
            ]);
            if(!$res){
                throw new \Exception('订单状态修改失败');
            }

            // 提交事务
            Db::commit();
            //向此用户发送订单完成推送消息
            if($client_id){
                $data = [
                    'cid' => '3cd8c5f87234f02f9a48a3a',
                    'title' => '您的订单支付成功了',
                    'content' => '订单编号：'.$order_sn,
                    'payload' => '{"title":"您的订单支付成功了","content":"订单编号："'.$order_sn.',"sound":"default","payload":"test","notice_type":"order","local":"1"}'
                ];
                $model = new Upush();
                $model->pushOne($data);
            }

            $value = array('status'=>200,'mess'=>'支付成功');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $value = array('status'=>400,'mess'=>'支付失败','data'=>array('status'=>400));
        }
    }
}