<?php
namespace app\apicloud\controller;
use think\Controller;
use think\Db;
use app\apicloud\model\AliPayHelper;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong;
use app\common\model\Member as MemberModel;
use app\common\service\ComWxPay;
use app\admin\services\Upush;
use app\common\service\MiniWxPay;
use app\common\service\PortalMiniWxPay;

class Recharge extends Common{
    /**增加订单
     *  params:  充值金额   支付方式payway 0微信  1支付宝
     *  do:    生成订单信息  拉起支付
     **/
    public function createOrder(){
        //获取用户信息
        if(request()->isPost()){
            $gongyong = new Gongyong();
            $result = $gongyong->apivalidate();
            $price = (int)input('param.price');
            if($price < 1){
                datamsg(LOSE,'最少充值1元');
            }
            $payway = (int)input('param.payway');
            if($payway == 0){   //前端传递值为： 0支付宝  1微信
                $payway = 1; 
            }else{
                $payway = $payway; 
            }
            if($result['status'] == 200) {
                if(!isset($result['user_id'])){
                    datamsg(LOSE,'请登录');
                }
                $user_id = $result['user_id'];
                $userInfo = MemberModel::findById($user_id);
                $data['order_number'] = 'C'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);;
                $data['order_price'] = $price;
                $data['pay_way'] = $payway;
                $data['pay_status'] = 0;
                $data['uid'] = $result['user_id'];
                $data['created'] = date('Y-m-d H:i:s');
                $savefollow = db('recharge_order')->insert($data);
                if($savefollow){  //订单创建成功，拉取支付
                    $webconfig = $this->webconfig;
                    if($payway == 0){  //拉取微信支付信息
                        $wx = new Wxpay();     
                        // $wx = new MiniWxPay();                                                
                        $body = $webconfig['web_site_name'].'-商品支付';//支付说明
                        $out_trade_no = $data['order_number'];//订单号
                        $total_fee = $price * 100;//支付金额(乘以100)
                        $time_start = time();
                        $time_expire = $time_start+1800;
                        $notify_url = $webconfig['weburl'].'/apicloud/Recharge/wxNotify';//回调地址
                        $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url,$userInfo);//调用微信支付的方法
                        if($order['prepay_id']){
                            //判断返回参数中是否有prepay_id
                            $order1 = $wx->getOrder($order['prepay_id']);//执行二次签名返回参数
                            //组合二次签名参数给客户端拉起支付

                            $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$data['order_number'],'infos'=>$order1));
                        }else{
                            $value = array('status'=>400,'mess'=>$data['order_number'],'data'=>array('status'=>400));
                        }
                    }elseif ($payway == 2) { // 小程序支付  
                        $wx = new MiniWxPay();                                                
                        $body = $webconfig['web_site_name'].'-商品支付';//支付说明
                        $out_trade_no = $data['order_number'];//订单号
                        $total_fee = $price * 100;//支付金额(乘以100)
                        $time_start = time();
                        $time_expire = $time_start+1800;
                        $notify_url = $webconfig['weburl'].'/apicloud/Recharge/wxNotify';//回调地址
                        $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url,$userInfo);//调用微信支付的方法
                        if($order['prepay_id']){
                            //判断返回参数中是否有prepay_id
                            $order1 = $wx->getOrder($order);//执行二次签名返回参数
                            //组合二次签名参数给客户端拉起支付

                            $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$data['order_number'],'infos'=>$order1));
                        }else{
                            $value = array('status'=>400,'mess'=>$data['order_number'],'data'=>array('status'=>400));
                        }
                    } elseif ($payway == 3) { // 公众号支付
                        $wx = new ComWxPay();                                                
                        $body = '商品支付';//支付说明
                        $out_trade_no = $data['order_number'];//订单号
                        $total_fee = $price * 100;//支付金额(乘以100)
                        $time_start = time();
                        $time_expire = $time_start+1800;
                        $notify_url = $webconfig['weburl'].'/apicloud/Recharge/wxNotify';//回调地址
                        $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url,$userInfo);//调用微信支付的方法
                        if($order['prepay_id']){
                            //判断返回参数中是否有prepay_id
                            $order1 = $wx->getOrder($order);//执行二次签名返回参数
                            // Db::name('test')->insert(['value'=>json_encode($order1)]);
                            //组合二次签名参数给客户端拉起支付

                            $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$data['order_number'],'infos'=>$order1));
                        }else{
                            $value = array('status'=>400,'mess'=>$data['order_number'],'data'=>array('status'=>400));
                        }
                    } elseif ($payway == 4) { // 门户小程序支付
                        $wx = new PortalMiniWxPay();                                                
                        $body = $webconfig['web_site_name'].'-商品支付';//支付说明
                        $out_trade_no = $data['order_number'];//订单号
                        $total_fee = $price * 100;//支付金额(乘以100)
                        $time_start = time();
                        $time_expire = $time_start+1800;
                        $notify_url = $webconfig['weburl'].'/apicloud/Recharge/wxNotify';//回调地址
                        $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url,$userInfo);//调用微信支付的方法
                        if($order['prepay_id']){
                            //判断返回参数中是否有prepay_id
                            $order1 = $wx->getOrder($order['prepay_id']);//执行二次签名返回参数
                            //组合二次签名参数给客户端拉起支付

                            $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$data['order_number'],'infos'=>$order1));
                        }else{
                            $value = array('status'=>400,'mess'=>$data['order_number'],'data'=>array('status'=>400));
                        }
                    }else{   //拉取支付宝支付信息
                        $notify_url = $webconfig['weburl']."/apicloud/Recharge/aliNotify";
                        $AliPayHelper = new AliPayHelper();
                        $data = $AliPayHelper->getPrePayOrder($webconfig['web_site_name'],$price,$data['order_number'],$notify_url);
                        $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$data['order_number'],'infos'=>$data));
                    }
                    return json($value);
                }else{
                    datamsg(LOSE,"创建订单失败,请重试");
                }
            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
        
    }

    /***
     * 充值支付宝回调接口
     */
    public function aliNotify(){
        $data = $_POST;
        $AliPayHelper = new AliPayHelper();
        $return = $AliPayHelper->payReturn($data);
        $order_sn = $data['out_trade_no'];  //订单单号
        $price = $data['total_amount'];
        if($return == 1){   //验证成功
            $this->createOrderDetail($order_sn,$price);
            echo 'success';
        }else{
            echo 'fail';
        }
    }

    /***
     * 充值微信回调接口
     */
    public function wxNotify(){
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
            $this->createOrderDetail($order_sn,$total_fee);
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
    /***
     * 生成唯一订单号
     */
    private function makeOrderNum(){
        $uonid = uniqid();
        $order_number = 'Exq'.time().$uonid;
        return $order_number;
    }

    public function bctest(){
        $ono = "Exq15689783905d84b5d64407c";
        $price = 1;
        $res = $this->createOrderDetail($ono,$price);
        print_r($res);exit();
    }

    /***
     * 处理订单数据
     */
    private function createOrderDetail($order_number,$price){
        $client_id =  "";
        $charge_order = Db::name('recharge_order')->where('order_number',$order_number)->where('pay_status',0)->find();
        if($charge_order){
            $member_data = DB::name('member')->where(['id'=>$charge_order['uid']])->find();
            $client_id = $member_data['appinfo_code']; 
            $price = $charge_order['order_price'];
            // 启动事务
            Db::startTrans();
            try{
                Db::name('recharge_order')->update(array('id'=>$charge_order['id'],'pay_status'=>1));
                $wallet = Db::name('wallet')->where('user_id',$charge_order['uid'])->find();
                //return $wallet;
                if($wallet){   //增加金额
                    $before_price = $wallet['price'];
                    $now_price = $price + $before_price;
                    Db::name('wallet')->update(array('id'=>$wallet['id'],'price'=>$now_price));
                    //增加明细
                    $ddata = [
                        'de_type'  => 1 ,  'sr_type'  => 1 , 'zc_type' => 0 , 'price' => $price,'time' =>time(),
                        'order_type' => 5 , 'order_id' => 0, 'tx_id' => 0 , 'shop_id' => 0, 'wat_id' => $wallet['id'], 
                        'before_price' => $before_price, 'now_price' => $now_price
                    ];
                    db('shop_detail')->insert($ddata);



                    // 充值成功  增加记录
                    $d['de_type'] = 1;
                    $d['sr_type'] = 5;
                    $d['zc_type'] = 0;
                    $d['before_price'] = $wallet['price'];
                    $d['price'] = $price;
                    $d['after_price'] = $wallet['price']+$price;
                    $d['order_type'] = 0;
                    $d['order_type'] = 0;
                    $d['order_id'] = 0;
                    $d['tx_id'] = 0;
                    $d['user_id'] = $charge_order['uid'];
                    $d['wat_id'] = $wallet['id'];
                    $d['time'] = time();
                    $this->addDetail($d);
//                    Db::name('detail')->insert($d);
                    // 提交事务
                    Db::commit();
                }else{   //增加用户钱包值
                    $wdata = [
                        'price'  =>  $price,  'user_id'  => $charge_order['uid']
                    ];
                    $wid = db('wallet')->insertGetId($wdata);
                    //增加明细
                    $ddata = [
                        'de_type'  => 1 ,  'sr_type'  => 1 , 'zc_type' => 0 , 'price' => $price,'time' =>time(),
                        'order_type' => 5 , 'order_id' => 0, 'tx_id' => 0 , 'shop_id' => 0, 'wat_id' => $wid, 
                        'before_price' => '0.00', 'now_price' => $price
                    ];
                    db('shop_detail')->insert($ddata);
                    Db::commit();
                    //向此用户发送订单完成推送消息
                    if($client_id){
                        $data = [
                            'cid' => '3cd8c5f87234f02a1095c585f9a48a3a',
                            'title' => '充值成功提醒',
                            'content' => '您的账号余额到账',
                            'payload' => '{"title":"充值成功提醒","content":"您的账号余额到账","sound":"default","payload":"test","notice_type":"recharge","local":"1"}'
                        ];
                        $model = new Upush();
                        $model->pushOne($data);
                    }
                }
            }catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $value = array('status'=>400,'mess'=>'创建充值明细信息失败','data'=>array('status'=>400));
            }
        }
    }

    public function getRechargeList(){
        $res = $this->checkToken();

        if($res['status'] == 400){
            return json($res);
        }else{
            $userId = $res['user_id'];
        }

        if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
            $webconfig = $this->webconfig;
            $perpage = 20;
            $offset = (input('post.page')-1)*$perpage;
            $list = Db::name('recharge_order')->where('uid',$userId)->order('id desc')->limit($offset,$perpage)->select();

            $value = array('status'=>200,'mess'=>'获取成功','data'=>$list);
        }else{
            $value = array('status'=>400,'mess'=>'缺少页数','data'=>array('status'=>400));
        }
        return json($value);
    }

}