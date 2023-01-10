<?php

namespace app\common\service;

use think\Db;

class PortalMiniWxPay
{

    private $mch_id;
    private $key;
    private $appid;
    private $openId;
    
    public function __construct()
    {
        $paymentPlugin = Db::name('plugin')->where("code='weixin' and  type = 'payment' ")->find(); // 找到微信支付插件的配置
        $config_value = unserialize($paymentPlugin['config_value']); // 配置反序列化

        $this->mch_id = $config_value['mchid']; // * MCHID：商户号（必须配置，开户邮件中可查看）
        $this->key = $config_value['key']; // KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
        $this->appid = 'wx03eb78d0613ef338';
        // $app = M('cxyapp')->find();
        // $this->appid = $app['appid'];
        // $user = M('users')->where('user_id', $this->user_id)->find();
        // $this->openId = $user['miniopenid'];
    }

    public function getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url, $userInfo)
    {
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $onoce_str = $this->getRandChar(32);
        $data["appid"] = $this->appid;
        $data["body"] = $body;
        $data["mch_id"] = $this->mch_id;
        $data["nonce_str"] = $onoce_str;
        $data["notify_url"] = $notify_url;
        $data['openid'] = $userInfo['portal_openid'];
        $data["out_trade_no"] = $out_trade_no;
        $data["spbill_create_ip"] = $_SERVER['REMOTE_ADDR'];
        $data["total_fee"] = $total_fee;
        $data["trade_type"] = "JSAPI";
        $s = $this->getSign($data, false);
        $data["sign"] = $s;
        $xml = $this->arrayToXml($data);
        $response = $this->postXmlCurl($xml, $url);
        //将微信返回的结果xml转成数组
        return xmlToArray($response);
    }

    public function getOrder($order)
    {
        $time = time();
        $data['appId'] = $this->appid;
        $data['nonceStr'] = $order['nonce_str'];
        $data['package'] = 'prepay_id=' . $order['prepay_id'];
        $data['signType'] = 'MD5';
        $data['timeStamp'] = "$time";
        $data['paySign'] = $this->getSign($data,false); // 签名
        // $data['out_trade_no'] = $order['out_trade_no'];
        return $data;
    }

    /**
     * 微信小程序支付
     * @param $price 支付金额
     */
    public function wxPay($price, $order)
    {
        $fee = $price; // 支付金额
        $appid = $this->appid;
        $body = '购物';
        $mch_id = $this->mch_id; // 商户号
        $nonce_str = $this->nonce_str(); // 随机字符串
        // $notify_url = SITE_URL .'/mini/Notify/Notify'; // 回调的url
        $openid = $this->openId;
        $total_fee = $fee * 100; // 微信支付单位是分
        $out_trade_no = $order['order_sn'];
        $spbill_create_ip = $_SERVER['REMOTE_ADDR']; // 服务器的ip

        $trade_type = 'JSAPI'; // 交易类型

        // 拼接XML
        $post['appid'] = $appid;
        $post['body'] = $body;
        $post['mch_id'] = $mch_id;
        $post['nonce_str'] = $nonce_str;// 随机字符串
        $post['notify_url'] = $notify_url;
        $post['openid'] = $openid;
        $post['out_trade_no'] = $out_trade_no;
        $post['spbill_create_ip'] = $spbill_create_ip;
        $post['total_fee'] = $total_fee;
        $post['trade_type'] = $trade_type;

        $text="appid=".$appid."&body=".$body."&mch_id=".$mch_id."&nonce_str=".$nonce_str."&notify_url=".$notify_url."&openid=".$openid."&out_trade_no=".$out_trade_no."&spbill_create_ip=".$spbill_create_ip."&total_fee=".$total_fee."&trade_type=JSAPI";
        $text .= "&key=".$this->key;
        $sign=strtoupper(md5($text));
        $post['sign']=$sign;
        $xml = $this->arrayToXml($post);
        // 统一接口prepay_id
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $xml = $this->http_request($url,$xml);
        $array = $this->xml($xml); // 转换大写
        if ($array['return_code'] == 'SUCCESS' && $array['result_code'] == 'SUCCESS') 
        {
            $time = time();
            $tmp = []; // 临时数组用于签名
            $tmp['appId'] = $appid; 
            $tmp['nonceStr'] = $nonce_str;
            $tmp['package'] = 'prepay_id=' . $array['prepay_id'];
            $tmp['signType'] = 'MD5';
            $tmp['timeStamp'] = "$time";
            $msg = [
                'order_sn' => $out_trade_no,
                'appid' => $appid,
                'nonce_str' => $nonce_str,
                'package' => 'prepay_id=' . $array['prepay_id'],
                'sign_type' => 'MD5',
                'time_stamp' => "$time"
            ];
            // db('water_wxpay_order')->insert($msg);

            $data['state'] = 200;
            $data['timeStamp'] = "$time"; // 时间戳
            $data['nonceStr'] = $nonce_str; // 随机字符串
            $data['signType'] = 'MD5';
            $data['package'] = 'prepay_id=' . $array['prepay_id'];
            $data['paySign'] = $this->sign($tmp); // 签名
            $data['out_trade_no'] = $out_trade_no;
            $data['appId'] = $appid;

        }
        else
        {
            $data['state'] = 0;
            $data['text'] = "错误";
            $data['RETURN_CODE'] = $array['RETURN_CODE'];
            $data['RETURN_MSG'] = $array['RETURN_MSG'];
        }

        echo json_encode($data);
    }

    //post https请求，CURLOPT_POSTFIELDS xml格式
    function postXmlCurl($xml,$url,$second=30)
    {
        //初始化curl
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data)
        {
            curl_close($ch);
            return $data;
        }
        else
        {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error"."<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }

    /*
        生成签名
    */
    function getSign($Obj)
    {
        // foreach ($Obj as $k => $v)
        // {
        //     $Parameters[strtolower($k)] = $v;
        // }
        // //签名步骤一：按字典序排序参数
        // ksort($Parameters);
        // $String = $this->formatBizQueryParaMap($Parameters, false);
        // //echo "【string】 =".$String."</br>";
        // //签名步骤二：在string后加入KEY
        // $String = $String."&key=".$this->key;
        // //签名步骤三：MD5加密
        // $result_ = strtoupper(md5($String));
        // return $result_;
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->key;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }

    //获取指定长度的随机字符串
    function getRandChar($length){
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;

        for($i=0;$i<$length;$i++){
            $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        return $str;
    }

    // 随机32位字符串
    private function nonce_str()
    {
        $result = '';
        $str = 'QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm';
        for ($i=0; $i < 32; $i++) 
        { 
            $result .= $str[rand(0,48)];
        }
        return $result;
    }

    //数组转换成xml
    private function arrayToXml($arr) 
    {
        $xml = "<root>";
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $xml .= "<" . $key . ">" . $this->arrayToXml($val) . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        $xml .= "</root>";
        return $xml;
    }
        //作用：生成签名
        private function sign($Obj) 
        {
            foreach ($Obj as $k => $v) {
                $Parameters[$k] = $v;
            }
            //签名步骤一：按字典序排序参数
            ksort($Parameters);
            $String = $this->formatBizQueryParaMap($Parameters, false);
            //签名步骤二：在string后加入KEY
            $String = $String . "&key=" . $this->key;
            //签名步骤三：MD5加密
            $String = md5($String);
            //签名步骤四：所有字符转为大写
            $result_ = strtoupper($String);
            return $result_;
        }
    
    
        ///作用：格式化参数，签名过程需要使用
        private function formatBizQueryParaMap($paraMap, $urlencode) 
        {
            $buff = "";
            ksort($paraMap);
            foreach ($paraMap as $k => $v) {
                if ($urlencode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
            $reqPar = '';
            if (strlen($buff) > 0) {
                $reqPar = substr($buff, 0, strlen($buff) - 1);
            }
            return $reqPar;
        }

    // curl请求
    public function http_request($url, $data = null, $headers = array())
    {
        $curl = curl_init();
        if (count($headers) >= 1) 
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        if(!empty($data))
        {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    //获取xml
    private function xml($xml)
    {
        if($xml == '') return '';
        libxml_disable_entity_loader(true);
        $arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
        return $arr;
        // $p = xml_parser_create();
        // xml_parse_into_struct($p, $xml, $vals, $index);
        // xml_parser_free($p);
        // $data = '';
        // foreach ($index as $key => $value) 
        // {
        //     if ($key == 'xml' || $key == 'XML') continue;
        //     $tag = $vals[$value[0]]['tag'];
        //     $value = $vals[$value[0]]['value'];
        //     $data[$tag] = $value;
        // }
        // return $data;
    }
    //Xml转数组
    function XmlToArr($xml)
    {	
        if($xml == '') return '';
        libxml_disable_entity_loader(true);
        $arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
        return $arr;
    }
}