<?php

namespace app\common\service;

use app\common\util\MiniUtil as miniLogic;
use app\common\service\WxLoginService;
use app\common\model\Order as OrderModel;
use app\common\model\Member as MemberModel;
use app\common\model\WxTemplate as WxTemplateModel;
use app\common\model\Shops as ShopsModel;
use app\common\model\DeliveryDoc as DeliveryDocModel;
use app\common\model\WxConfig;
use app\common\util\WechatUtil;
use think\Cache;
use think\Db;

/**
 * 发送公众号模板消息
 */
class SendWxMsgService
{
    const PAY_ORDER_USER = 1; // 下单通知用户
    const PAY_ORDER_STORE = 2; // 下单通知商家
    const SEND_GOODS_MSG = 3; // 发货通知
    const SEND_CUSTOMER_SERVICE_ACCOUNT = 4;//发送客服账号消息通知
    const SEND_ORDER_COLLECT = 4; // 下单通知商家店小二
    const WARN_ORDER_STORE = 6; // 提醒商家发货

    public $appid;
    public $user_id;
    public $secret;
    public $token;
    public $access_token;
    public function __construct()
    {
        $config = WxConfig::get(1);
        $this->appid = $config['appid'];
        $this->secret = $config['appsecret'];
        $this->access_token = (new WxLoginService())->get_access_token();
    }

    /**
     * 订单下单成功公众号模板消息通知用户和商家
     *
     * @param [type] $access_token
     * @param [type] $templateid
     * @param [type] $data
     * @return void
     */
    public function sendWapOrderMsgToUser($order_sn)
    {
        try {
            $this->orderMsgToUser($order_sn);
            $this->orderMsgToMerchant($order_sn);
            // $this->orderMsgToMerchantCollect($order_sn);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }









    /**
     * 发货通知用户
     *
     * @return void
     */
    public function sendGoodsToUser($order_sn){
        try {

            $this->goodsSendMsgToUser($order_sn);

        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * 提醒发货
     * 
     * @return void
     */
    public function warnOrderMsg($order_sn)
    {
        try {
            
            $this->warnOrderToMerchant($order_sn);
            // $this->warnOrderToCollect($order_sn);

        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * 提醒店小二发货
     *
     * @param [type] $order_sn
     * @return void
     */
    private function warnOrderToCollect($order_sn)
    {

        $text = WxTemplateModel::findByType(self::WARN_ORDER_STORE);
        $MuId = $text['wx_template_id'];
        $order = OrderModel::getOrderInfoByOrderSn($order_sn);
        $users = Model('user_shop')->where('merchant_id',$order['merchant_id'])->select();
        if(!$users) return ;
        $url = $text['url'].$order['merchant_id'];
        foreach ($users as $value) {
            $user = MemberModel::getUserInfoByID($value['user_id']);
            if(!$user) continue ;
            $data = [
                'openid' => $user['openid'],
                'linkurl' => $url,
                'data' => [
                    'first' => [
                        'value' => $text['first']
                    ],
                    'keyword1' => [
                        'value' => $order_sn // 订单编号
                    ],
                    'keyword2' => [
                        'value' => '已支付' // 支付状态
                    ],
                    'keyword3' => [
                        'value' => $order['order_amount'] // 支付金额
                    ],
                    'remark' => [
                        'value' => $text['remark']
                    ]
                ]
            ];
            $res = miniLogic::sendWapTemplateMessage($this->access_token, $MuId, $data);
            $res = json_decode($res,true);
            if($res['errmsg'] == 'ok'){
                $this->addLog($user['user_id'],$order_sn,1,'提醒店小二发货');
            }else{
                $this->addLog($user['user_id'],$order_sn,0,'提醒店小二发货');
            }
        }

    }


    /**
     * 开播提醒
     *
     * @param
     * @return void
     */
    public function popOpenIdToSend($data)
    {
        $MuId = '8PcMrfRUzgTjZfq795mWVD_P4QqigRGvI1MYvzjOJL8';
        $data = [
            'openid' => $data['openid'],
            //'linkurl' => 'http://www.xinglidatravel.com.cn/portal/pages/tabBar/Live',
            'linkurl' => $data['url'],
            'data' => [
                'first' => [
                    'value' => $data['title']
                ],
                'keyword1' => [
                    'value' => $data['time']  // 时间
                ],
                'keyword2' => [
                    'value' =>  $data['title'] // 连接地址
                ],
                'remark' => [
                    'value' => $data['content']
                ]
            ]
        ];
        $res = miniLogic::sendWapTemplateMessage($this->access_token, $MuId, $data);
        $res = json_decode($res,true);
        dump($res);die;
        if($res['errmsg'] == 'ok'){
            $this->addLog(0,0,1,'直播提醒');
        }else{
            $this->addLog(0,0,0,'直播提醒');
        }

        // $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $this->access_token;
        // //dump($url);die;
        // $map = [
        //     'touser' => $data['openid'],
        //     'msgtype' => 'news',
        //     'news' => [
        //         'articles' => [
        //             [
        //                 'title' => $data['title'],
        //                 'description' => $data['content'],
        //                 'url' => $data['url'],
        //             ]
        //         ]
        //     ]
        // ];
        // $mapData = json_encode($map, JSON_UNESCAPED_UNICODE);
        // $resault =$this->httpRequest($url, 'POST', $mapData);
        // $resault = json_decode($resault,true);
        // dump($resault);die;
        // if($resault['errmsg'] == 'ok'){
        //     $status = 1;
        // }else{
        //     $status = 0;
        // }


    }


     public function httpRequest($url, $method = 'GET', $fields = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $method = strtoupper($method);
        if ($method == 'GET' && !empty($fields)) {
            is_array($fields) && $fields = http_build_query($fields);
            $url = $url . (strpos($url,"?")===false ? "?" : "&") . $fields;
        }
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($method != 'GET') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($fields)) {
                if (is_array($fields)) {
                    $hadFile = false;
                    /* 支持文件上传 */
                    if (class_exists('\CURLFile')) {
                        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
                        foreach ($fields as $key => $value) {
                            if ($this->isPostHasFile($value)) {
                                $fields[$key] = new \CURLFile(realpath(ltrim($value, '@')));
                                $hadFile = true;
                            }
                        }
                    } elseif (defined('CURLOPT_SAFE_UPLOAD')) {
                        if ($this->isPostHasFile($value)) {
                            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
                            $hadFile = true;
                        }
                    }
                }
                $fields = (!$hadFile && is_array($fields)) ? http_build_query($fields) : $fields;
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            }
        }

        /* 关闭https验证 */
        if ("https" == substr($url, 0, 5)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $content = curl_exec($ch);
        curl_close($ch);

        return $content;
    }


    /**
     * 提醒商家发货
     *
     * @return void
     */
    public function warnOrderToMerchant($order_sn)
    {
            
        $text = WxTemplateModel::findByType(self::WARN_ORDER_STORE);
        $MuId = $text['wx_template_id'];
        $order = OrderModel::findOrderByOrderNumber($order_sn);
        $user = MemberModel::findByShopId($order['shop_id']);
        $url = $text['url'];
        $data = [
            'openid' => $user['wx_openid'],
            'linkurl' => $url,
            'data' => [
                'first' => [
                    'value' => $text['first']
                ],
                'keyword1' => [
                    'value' => $order_sn // 订单编号
                ],
                'keyword2' => [
                    'value' => '已支付'
                ],
                'keyword3' => [
                    'value' => $order['total_price'] // 支付金额
                ],
                'remark' => [
                    'value' => $text['remark']
                ]
            ]
        ];
        $res = miniLogic::sendWapTemplateMessage($this->access_token, $MuId, $data);
        $res = json_decode($res,true);
        if($res['errmsg'] == 'ok'){
            $this->addLog($user['id'],$order_sn,1,'提醒商家发货');
        }
    }

    /**
     * 发货通知用户
     *
     * @param [type] $order_sn
     * @return void
     */
    private function goodsSendMsgToUser($order_sn){
        if(Cache::get($order_sn.self::SEND_GOODS_MSG)) return ;
        $text = WxTemplateModel::findByType(self::SEND_GOODS_MSG);
        $MuId = $text['wx_template_id'];
        $order = OrderModel::getOrderInfoByOrderSn($order_sn);
        $user = MemberModel::getUserInfoByID($order['user_id']);
        $doc = DeliveryDocModel::findByOrderId($order['order_id']);
        $data = [
            'openid' => $user['openid'],
            'linkurl' => $text['url'],
            'data' => [
                'first' => [
                    'value' => $text['first']
                ],
                'keyword1' => [
                    'value' => $order_sn // 订单编号
                ],
                'keyword2' => [
                    'value' => $doc['create_time'] // 发货时间
                ],
                'keyword3' => [
                    'value' => $doc['shipping_name'] // 物流公司
                ],
                'keyword4' => [
                    'value' => $doc['invoice_no'] // 快递单号
                ],
                'remark' => [
                    'value' => $text['remark']
                ]
            ]
        ];
        $res = miniLogic::sendWapTemplateMessage($this->access_token, $MuId, $data);
        $res = json_decode($res,true);
        if($res['errmsg'] == 'ok'){
            $this->addLog($user['user_id'],$order_sn,1,'发货通知用户');
            Cache::set($order_sn.self::SEND_GOODS_MSG,'1');
        }else{
            $this->addLog($user['user_id'],$order_sn,0,'发货通知用户');
        }
    }

    /**
     * 下单通知商家
     *
     * @param [type] $order_sn
     * @return void
     */
    public function orderMsgToMerchant($order_sn){
        if(Cache::get($order_sn.self::PAY_ORDER_STORE)) return ;
        $text = WxTemplateModel::findByType(self::PAY_ORDER_STORE);
        $MuId = $text['wx_template_id'];
        $order = OrderModel::findOrderByOrderNumber($order_sn);
        $user = MemberModel::findByShopId($order['shop_id']);
        $url = $text['url'];
        $data = [
            'openid' => $user['wx_openid'],
            'linkurl' => $text['url'],
            'data' => [
                'first' => [
                    'value' => $text['first']
                ],
                'keyword1' => [
                    'value' => $order_sn
                ], // 订单编号
                'keyword2' => [
                    'value' => $order['goods'][0]['goods_name']
                ], // 商品名称
                'keyword3' => [
                    'value' => $order['total_price']
                ], // 订单总价
                'keyword4' => [
                    'value' => '已支付'
                ], // 订单状态
                'keyword5' => [
                    'value' => date("Y-m-d",$order['addtime'])
                ], // 下单时间
                'remark' => [
                    'value' => $text['remark']
                ]
            ]
        ];
        $res = miniLogic::sendWapTemplateMessage($this->access_token, $MuId, $data);
        $res = json_decode($res,true);
        if($res['errmsg'] == 'ok'){
            $this->addLog($user['id'],$order_sn,1,'下单通知商家');
            Cache::set($order_sn.self::PAY_ORDER_STORE,'1');
        }else{
            $this->addLog($user['id'],$order_sn,0,'下单通知商家');
        }
    }

    /**
     * 下单通知店小二
     *
     * @param [type] $order_sn
     * @return void
     */
    private function orderMsgToMerchantCollect($order_sn)
    {
        if(Cache::get($order_sn.self::SEND_ORDER_COLLECT)) return ;
        $text = WxTemplateModel::findByType(self::PAY_ORDER_STORE);
        $MuId = $text['wx_template_id'];
        $order = OrderModel::getOrderInfoByOrderSn($order_sn);
        $users = Model('user_shop')->where('merchant_id',$order['merchant_id'])->select();
        $url = $text['url'].$order['merchant_id'];
        if(!$users) return ;
        foreach ($users as $value) {
            $user = MemberModel::getUserInfoByID($value['user_id']);
            if(!$user) continue ;
            $data = [
                'openid' => $user['openid'],
                'linkurl' => $url,
                'data' => [
                    'first' => [
                        'value' => $text['first']
                    ],
                    'keyword1' => [
                        'value' => $order_sn // 订单编号
                    ],
                    'keyword2' => [
                        'value' => $order['goods_list'][0]['goods_name'] // 商品名称
                    ],
                    'keyword3' => [
                        'value' => $order['order_amount'] // 支付金额
                    ],
                    'keyword4' => [
                        'value' => '已支付'
                    ],
                    'keyword5' => [
                        'value' => date("Y-m-d",$order['add_time'])
                    ],
                    'remark' => [
                        'value' => $text['remark']
                    ]
                ]
            ];
            $res = miniLogic::sendWapTemplateMessage($this->access_token, $MuId, $data);
            if($res['errmsg'] == 'ok'){
                $this->addLog($user['user_id'],$order_sn,1,'下单通知店小二');
            }else{
                $this->addLog($user['user_id'],$order_sn,0,'下单通知店小二');
            }
        }
        Cache::set($order_sn.self::SEND_ORDER_COLLECT,'1');
    }

    /**
     * 下单通知用户
     *
     * @param [type] $order_sn
     * @return void
     */
    public function orderMsgToUser($order_sn){
        if(Cache::get($order_sn.self::PAY_ORDER_USER)) return ;
        $text = WxTemplateModel::findByType(self::PAY_ORDER_USER);
        $MuId = $text['wx_template_id'];
        $order = OrderModel::findOrderByOrderNumber($order_sn);
        $user = MemberModel::findById($order['user_id']);
        $data = [
            'openid' => $user['wx_openid'],
            'linkurl' => $text['url'],
            'data' => [
                'first' => [
                    'value' => $text['first']
                ],
                'keyword1' => [
                    'value' => $order_sn
                ], // 订单编号
                'keyword2' => [
                    'value' => $order['goods'][0]['goods_name']
                ], // 商品名称
                'keyword3' => [
                    'value' => $order['total_price']
                ], // 订单总价
                'keyword4' => [
                    'value' => '已支付'
                ], // 订单状态
                'keyword5' => [
                    'value' => date("Y-m-d",$order['addtime'])
                ], // 下单时间
                'remark' => [
                    'value' => $text['remark']
                ]
            ]
        ];
        $res = miniLogic::sendWapTemplateMessage($this->access_token, $MuId, $data);
        $res = json_decode($res,true);
        if($res['errmsg'] == 'ok'){
            $this->addLog($user['id'],$order_sn,1,'下单公众号通知用户');
            Cache::set($order_sn.self::PAY_ORDER_USER,'1');
        }else{
            $this->addLog($user['id'],$order_sn,0,'下单公众号通知用户');
        }
    }

    private function addLog($user_id,$order_sn,$status,$desc = '')
    {
        $map = [
            'user_id' => $user_id,
            'order' => $order_sn,
            'status' => $status,
            'type' => 'wx',
            'desc' => '公众号模板消息',
            'c_time' => date('Y-m-d H:i:s',time()),
            'desc' => $desc
        ];
        return Db::name('wx_send_log')->insert($map);
    }


    /**
     * @function发送客服账号
     * @param $user_id
     * @author Feifan.Chen <1057286925@qq.com>
     * @return \app\mini\logic\unknown|mixed
     */
    public function sendCustomerServiceAccount($user_id){
        $text = WxTemplateModel::findByType(self::SEND_CUSTOMER_SERVICE_ACCOUNT);
        $MuId = $text['wx_template_id'];
        $merchant_info = ShopsModel::with('user')
            ->where('user_id',$user_id)
            ->find()
            ->toArray();
        $user = MemberModel::getUserInfoByID($user_id);
        $user_name = "客服".$merchant_info['merchant_id'];
        $password = 123456;
        $link = 'http://kf.cxy365.com/admin/login/index/business_id/'.$merchant_info['merchant_id'].'.html';
        $remark = "登录账号：$user_name"."\r\n"."密码：$password"."\r\n"."客服后台地址：$link"."\r\n".'请及时登录后台修改默认密码！';
        $data = [
            'openid' => $user['openid'],
            'linkurl' => 'http://kf.cxy365.com/admin/login/index/business_id/'.$merchant_info['merchant_id'].'.html',
            'data' => [
                'first' => [
                    'value' => $text['first']
                ],
                'keyword1' => [
                    'value' => $user['nickname'] // 姓名
                ],
                'keyword2' => [
                    'value' => $user['mobile'] // 手机
                ],
                'keyword3' => [
                    'value' => date('Y-m-d H:i:s',time()) // 开通时间
                ],
                'keyword4' => [
                    'value' => '无限期'//到期时间
                ],
                'remark' => [
                    'value' => $remark
                ]
            ]
        ];
        //TODO 检测是否发过了模板消息
        Db::name('kf_log')->where('merchant_id',$merchant_info['merchant_id'])->find();
        $msg = miniLogic::sendWapTemplateMessage($this->access_token, $MuId, $data);
        Db::name('kf_log')->insert(['type'=>1,'info'=>$msg,'create_time'=>time(),'merchant_id'=>$merchant_info['merchant_id']]);
//        if (!empty($merchant_info['mobile'])){
//            $this->customerServiceSendMsg($merchant_info['mobile'],$merchant_info['user']['nickname'],$user_name,$password,$link,$merchant_info['merchant_id']);
//        }
    }

    /**
     * @function开通客服账号发送短信
     * @param $mobile手机号
     * @param $name用户昵称
     * @param $user_name账号
     * @param $password密码
     * @param $link后台的地址
     * @param $merchant_id商户id
     * @desc 阿里云短信参数长度不能超过20 新浪短连接暂停提供接口 o(╥﹏╥)o 此方法不可用
     * @author Feifan.Chen <1057286925@qq.com>
     */
    public function customerServiceSendMsg($mobile,$name,$user_name,$password,$link,$merchant_id){

        $scene = 23;
        $params = [
            'name' => $name,
            'user_name' => $user_name,
            'password' => $password,
            'link' => $this->shortUrl($link)
        ];
        $msg = sendSms($scene, $mobile,$params,0);
        Db::name('kf_log')->insert(['type'=>2,'info'=>json_encode($msg),'create_time'=>time(),'merchant_id'=>$merchant_id]);
    }

    /**
     * @function生成短链接 用于打开APP下载页
     * @param $url需要生成短链接的地址
     * @author Feifan.Chen <1057286925@qq.com>
     * @return bool|string
     */
    public function shortUrl($url){
        $appkey   = '5a293c636f8e16a8553f35f8394883f2';
        $long_url = urlencode($url);
        $sign     = md5($appkey.md5($long_url));
        $url      = "http://www.mynb8.com/api/sina?appkey=".$appkey."&sign=".$sign."&long_url=".$long_url;
        $json     = file_get_contents( $url );
        $json = json_decode($json,1);

        if ($json['rs_code'] == 0 && $json['rs_msg'] == 'ok'){
            return $json['short_url'];
        }else{
            return $json['long_url'];
        }

    }
}