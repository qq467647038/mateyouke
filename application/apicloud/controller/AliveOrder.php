<?php
namespace app\apicloud\controller;
use app\apicloud\model\Gongyong as GongyongMx;
use app\common\service\AliveService;
use app\common\service\PortalMiniWxPay;
use app\common\service\ComWxPay;
use app\common\model\Member as MemberModel;
use app\common\model\Alive as AliveModel;
use app\common\model\AliveOrder as AliveOrderModel;
use think\Db;

/**
 * 直播订单控制器
 */
class AliveOrder extends Common {

    private $user_id;

    public function __construct()
    {
        parent::__construct();
        if(!input('post.token')) return returnJson(400,'非法请求');
        $gongyong = new GongyongMx();
        $result = $gongyong->apivalidate();
        if($result['status'] != 200) return json($result);
        $this->user_id = $result['user_id'];
    }

    // /**
    //  * 创建订单
    //  */
    // public function createOrder()
    // {
    //     if(!request()->isPost()) return returnJson(400,'请求方式错误');
    //     $param = input('param.');
    //     if(!$param['pay_type']) return returnJson(400,'请选择支付方式');
    //     $param['user_id'] = $this->user_id;
    //     try {
    //         $order = (new AliveService())->addOrder($param);
    //         return returnJson(200,'创建订单成功',$order);
    //     } catch (\Throwable $th) {
    //         return returnJson(400,'创建订单失败');
    //     }
    // }

    /**
     * 支付
     *
     * @return void
     */
    public function pay()
    {
        if(!request()->isPost()) return returnJson(400,'请求方式错误');
        // $order_sn = input('order_sn');
        // if(!$order_sn) return returnJson(400,'订单号不能为空');
        $param = input('param.');
        $pay_type = input('zf_type');
        if(!$pay_type) return returnJson(400,'请选择支付类型');
        $orderData = [];
        $orderData['alive_sn'] = $param['alive_sn'];
        $orderData['user_id'] = $this->user_id;
        $orderData['room'] = $param['room'];
        $orderData['pay_code'] = $param['zf_type'];
        $roomInfo =  AliveModel::findByRoom($param['room']);
        if($roomInfo['is_pay'] == 0) return returnJson(400,'付费直播已结束');
        $orderData['price'] = $roomInfo['price'];

        Db::startTrans();
        try {
            $order_sn = (new AliveService())->addOrder($orderData);
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return returnJson(400,'系统错误');
        }

        $orderInfo = (new AliveService())->findByOrderSn($order_sn);
        if((new AliveService())->checkOrderPay($orderData['alive_sn'])) return json(['status'=>400,'mess'=>'该直播已支付过']);
        $userInfo = MemberModel::findById($this->user_id);

        $webconfig = $this->webconfig;
        // 订单号
        $out_trade_no = $orderInfo['order_sn'];
        // 支付金额
        $money = $orderInfo['price'];
        // 支付说明
        $body = '直播付费';
        // 支付金额
        $total_fee = $money * 100;
        $time_start = time();
        $time_expire = time();
        $notify_url = $webconfig['weburl'].'/apicloud/AliveNotify/notify';//回调地址
        $wx = null;
        switch ($pay_type) 
        {
            case '1':
                // 微信小程序
                $wx = new PortalMiniWxPay();
                break;
            case '2':
                // 微信公众号
                $wx = new ComWxPay();
                break;
            default:
                break;
        }
        if($wx == null) return returnJson(400,'请选择支付类型');
        $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url, $userInfo);//调用微信支付的方法
        if($order['prepay_id']){
            //判断返回参数中是否有prepay_id
            $order['out_trade_no'] = $out_trade_no;
            $order1 = $wx->getOrder($order);//执行二次签名返回参数
            $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$orderInfo['order_sn'],'infos'=>$order1));
        }else{
            $value = array('status'=>400,'mess'=>$order['err_code_des'],'data'=>array('status'=>400));
        }
        return json($value);
    }

    /**
     * 余额支付
     *
     * @return void
     */
    public function codePay()
    {

        $param = input('param.');
        $pay_type = input('zf_type');
        if(!$pay_type) return returnJson(400,'请选择支付类型');
        $orderData = [];
        $orderData['alive_sn'] = $param['alive_sn'];
        $orderData['user_id'] = $this->user_id;
        $orderData['room'] = $param['room'];
        $orderData['pay_code'] = $param['zf_type'];
        $roomInfo =  AliveModel::findByRoom($param['room']);
        if($roomInfo['is_pay'] == 0) return returnJson(400,'付费直播已结束');
        $orderData['price'] = $roomInfo['price'];
        $userInfo = MemberModel::findById($this->user_id);
        $wallets = Db::name('wallet')->where('user_id',$this->user_id)->find();
        $wallet_info = $wallets;
        if($wallets['price'] < $roomInfo['price']) return returnJson(400,'余额不足');
        $sheng_price = $wallets['price']-$roomInfo['price'];
        Db::startTrans();
        try {

            $order_sn = (new AliveService())->addOrder($orderData);
            Db::name('wallet')->update(array('price'=>$sheng_price,'id'=>$wallets['id']));

            $detail = [
                'de_type'=>2,
                'zc_type'=>2,
                'before_price'=> $wallet_info['price'],
                'price'=>$roomInfo['price'],
                'after_price'=> $wallet_info['price']-$roomInfo['price'],
                'order_type'=>2,
                'order_id'=>0,
                'user_id'=>$this->user_id,
                'wat_id'=>$wallets['id'],
                'time'=>time()
            ];
            $this->addDetail($detail);
//            Db::name('detail')->insert($detail);
            AliveOrderModel::where('order_sn',$order_sn)->update(['state'=>1,'paytime'=>date("Y-m-d H:i:s",time())]);
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            return returnJson(400,'系统错误');
        }

        return returnJson(200,'支付成功');

    }

}