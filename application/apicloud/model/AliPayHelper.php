<?php
namespace app\apicloud\model;
use think\Model;
use think\Db;
class AliPayHelper extends Model{
    /*
    配置参数
    */
    protected $appId = '2021003130629645';//支付宝AppId
    protected $rsaPrivateKey = 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCkDfwDq7nClsnjz3MieMmC1JwL+WF8wmxhmMzXdF0kMqXChDw6zF6fofLeTSq63QxZu20t3fgKYsy58qnVwby3OZRcO9jd4tVDOJivq2SOAzlp5NhAZ8i4WXm7yx34YMRIgRCInzLzcMGfONb+464uL3uqh5w+AgV7k8AOtmpbQYccPJI7YSTijJGtFWaAPlMkjQzeJoPsHs0aXDRjwt1A45aewgP0Exk2Vu/sNjihlaC38AW2hPV5ckVQbqDBjfcRdLcKtFRVyENlwERWYutm6EfJ1EuIZUIB/NAHmB5mnjm78zy1jJvPH6aZkJ8Q70qGpCmaj0nghdQeEkIcaUNlAgMBAAECggEAFX7LlYOLrGZrf5Dv6gVfiefnpl3/mwQyhTsrI9PYXGTSeUEwTxf2Ef57PwtnXOKXuq4nKQpbdKjrYDXecOaYnn1J5iflS3VsMgmZX/MaEs1zWV+lwhKXJyh6HdQIUkIDlehrTStm1qTgicc9zFnyuZR5JKfuHeXP6Bg84vCd0OT4ZdOK+TvGgsI4j4IS+Bv04EHF6mMOsUk19loZ8p/DZBGStIrRuguKsndUos33ZCCrjwe6T+U8cCKB9kob031wA7veXEDkWiQXHmQNGkLV/pMTH+EMqdGyMJ4MtCmSbzcam8nfqX2AVVGnGcCHJpQywo60qEicPgR8KRL0RqDtxQKBgQDNJyQvcPew2fzkWYJXyb2Nv9AbhhNcN97ZaMIXMpW5IAiDv6QbJ276J4s6Za/VZPuA46ZL5xioPduju5W4DsOuKohZZLfePNn8JRZZqQVuKJmUR50Zpe3zkZwXIWfGmuxXsghU5i/2Q2KN92AqiVRDNRDj0ZJ2s2xHC+QwO6ES2wKBgQDMty0r0rUQvesxIrj+m9KyP10XJDtPZ3SnLZcftFwnrljUYY+L7/2gS3afA1T7hCZ2DlMMA7pIAShSb2404Z4lsDiIsapHrab1hay0Ude2gEApY4Dnr2qTgVWA0MZ2AIlQOp3qirqIEQ+D6qyv0teJsi8JWwBTvSHVaMgpBmY2vwKBgDBhHaBudIrpLUEwdpN7SM9Hv6zt9lzV9CCzGqpbzIEms7tWEz4wE3S8pJG17zxUnxbrGIlnyyHJzKUVFJ6eJLlK4HKsVMv8768Nk/K68EPlISqdpMeqoK3C1duCjjWAzWF045AZ5I+fnns6Lhx53DwpJH2FK5QAhfVPMZXKShbnAoGAcUOX9nsqOw0ZJ6JygExukriEJN2jAxfWbvjGeIAtzLal5zvjVCWASkP2aZxKVK6VKRRb1nXphxU83f9RFmkOOwP5A4hpEid+DLHdEBeIJi1nUn7/PzDK4rnYOOFKLNe3IXCNFsuS0N2/m9knmlApeMHhTGfREoO+SHkk5a3ot7kCgYBNtRNQpPAmKYPhtkN7GgnTHmlh+lHy13E5DfoIP1b+AnzDyGgM4HynjdT2+sLcW8tqhxK0OH2N0s9bzMEi+RaUx3l66fl+ypXZ/WSE6xT5J1Ktxodq0gMVilQKFlOy66Ne7qAAyqIJhzf/tbqJE4EIJ2x/tsecejuDahfRkmj6Qg==';//支付宝私钥
    protected $aliPayRsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAo76VptWomhZUzWJyc5OT5KnWRycuhDzQ1IKvG2Q4CFw1BVHTna2kCvS27DLCMbt4Ke5bkUqYrW3EupAQZ5cKbShixuHBvHfyjekUOqRlV7QMEg7TcE/z+mQ6/1GdedJqfgHB2nEfi0pAMPodRa1HMWNLY66CTrTiUVOOks0Q2sfZRhpEMwAeBDReBzUjKQzgjyOaOxC68sD7LsDiEl4a7hwjN/YJaX8MdPR5T6RQGl9TUsm59nnFFUViY5wwCXWl+YVaZLArM9F2eZyVpczKDMMswyjntMQzQTFOagFMf4XjJETOdeZ/dg24is9fgFbWyFSPTsPxVTEUr2er/OIrFwIDAQAB';//支付宝公钥
    
    // 应用私钥
// MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCkDfwDq7nClsnjz3MieMmC1JwL+WF8wmxhmMzXdF0kMqXChDw6zF6fofLeTSq63QxZu20t3fgKYsy58qnVwby3OZRcO9jd4tVDOJivq2SOAzlp5NhAZ8i4WXm7yx34YMRIgRCInzLzcMGfONb+464uL3uqh5w+AgV7k8AOtmpbQYccPJI7YSTijJGtFWaAPlMkjQzeJoPsHs0aXDRjwt1A45aewgP0Exk2Vu/sNjihlaC38AW2hPV5ckVQbqDBjfcRdLcKtFRVyENlwERWYutm6EfJ1EuIZUIB/NAHmB5mnjm78zy1jJvPH6aZkJ8Q70qGpCmaj0nghdQeEkIcaUNlAgMBAAECggEAFX7LlYOLrGZrf5Dv6gVfiefnpl3/mwQyhTsrI9PYXGTSeUEwTxf2Ef57PwtnXOKXuq4nKQpbdKjrYDXecOaYnn1J5iflS3VsMgmZX/MaEs1zWV+lwhKXJyh6HdQIUkIDlehrTStm1qTgicc9zFnyuZR5JKfuHeXP6Bg84vCd0OT4ZdOK+TvGgsI4j4IS+Bv04EHF6mMOsUk19loZ8p/DZBGStIrRuguKsndUos33ZCCrjwe6T+U8cCKB9kob031wA7veXEDkWiQXHmQNGkLV/pMTH+EMqdGyMJ4MtCmSbzcam8nfqX2AVVGnGcCHJpQywo60qEicPgR8KRL0RqDtxQKBgQDNJyQvcPew2fzkWYJXyb2Nv9AbhhNcN97ZaMIXMpW5IAiDv6QbJ276J4s6Za/VZPuA46ZL5xioPduju5W4DsOuKohZZLfePNn8JRZZqQVuKJmUR50Zpe3zkZwXIWfGmuxXsghU5i/2Q2KN92AqiVRDNRDj0ZJ2s2xHC+QwO6ES2wKBgQDMty0r0rUQvesxIrj+m9KyP10XJDtPZ3SnLZcftFwnrljUYY+L7/2gS3afA1T7hCZ2DlMMA7pIAShSb2404Z4lsDiIsapHrab1hay0Ude2gEApY4Dnr2qTgVWA0MZ2AIlQOp3qirqIEQ+D6qyv0teJsi8JWwBTvSHVaMgpBmY2vwKBgDBhHaBudIrpLUEwdpN7SM9Hv6zt9lzV9CCzGqpbzIEms7tWEz4wE3S8pJG17zxUnxbrGIlnyyHJzKUVFJ6eJLlK4HKsVMv8768Nk/K68EPlISqdpMeqoK3C1duCjjWAzWF045AZ5I+fnns6Lhx53DwpJH2FK5QAhfVPMZXKShbnAoGAcUOX9nsqOw0ZJ6JygExukriEJN2jAxfWbvjGeIAtzLal5zvjVCWASkP2aZxKVK6VKRRb1nXphxU83f9RFmkOOwP5A4hpEid+DLHdEBeIJi1nUn7/PzDK4rnYOOFKLNe3IXCNFsuS0N2/m9knmlApeMHhTGfREoO+SHkk5a3ot7kCgYBNtRNQpPAmKYPhtkN7GgnTHmlh+lHy13E5DfoIP1b+AnzDyGgM4HynjdT2+sLcW8tqhxK0OH2N0s9bzMEi+RaUx3l66fl+ypXZ/WSE6xT5J1Ktxodq0gMVilQKFlOy66Ne7qAAyqIJhzf/tbqJE4EIJ2x/tsecejuDahfRkmj6Qg==
    // 应用公钥
// MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApA38A6u5wpbJ489zInjJgtScC/lhfMJsYZjM13RdJDKlwoQ8Osxen6Hy3k0qut0MWbttLd34CmLMufKp1cG8tzmUXDvY3eLVQziYr6tkjgM5aeTYQGfIuFl5u8sd+GDESIEQiJ8y83DBnzjW/uOuLi97qoecPgIFe5PADrZqW0GHHDySO2Ek4oyRrRVmgD5TJI0M3iaD7B7NGlw0Y8LdQOOWnsID9BMZNlbv7DY4oZWgt/AFtoT1eXJFUG6gwY33EXS3CrRUVchDZcBEVmLrZuhHydRLiGVCAfzQB5geZp45u/M8tYybzx+mmZCfEO9KhqQpmo9J4IXUHhJCHGlDZQIDAQAB
    private $seller = '123456';

    /**
     *支付宝网关地址
     */
    var $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';

    public function __construct(){
        //$this->config = $config;
    }
    /*
     * 支付宝支付
     */
    public function getPrePayOrder($body, $total_amount, $product_code, $notify_url)
    {
        /**
         * 调用支付宝接口。
         */
        $base_route = dirname(dirname(__FILE__)).'/libs/alipay/aop/';
        include($base_route.'AopClient.php');
        include($base_route.'/request/AlipayTradeAppPayRequest.php');
        $aop = new \AopClient();
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->rsaPrivateKey;
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = $this->aliPayRsaPublicKey;
        $request = new \AlipayTradeAppPayRequest();
        $arr['body'] = $body;
        $arr['subject'] = $body;
        $arr['out_trade_no'] = $product_code;
        $arr['timeout_express'] = '30m';
        $arr['total_amount'] = floatval($total_amount);
        $arr['product_code'] = 'QUICK_MSECURITY_PAY';

        $json = json_encode($arr);
        $request->setNotifyUrl($notify_url);
        $request->setBizContent($json);

        $response = $aop->sdkExecute($request);
        return $response;

    }

    /***
     * 支付宝回调方法
     */
    public function payReturn($data){
        define('IN_ECS', true);
        $base_route = dirname(dirname(__FILE__)).'/libs/alipay/aop/';
        include($base_route.'AopClient.php');
        include($base_route.'/request/AlipayTradeAppPayRequest.php');
        $aop = new \AopClient();
        $aop->alipayrsaPublicKey = $this->aliPayRsaPublicKey;
        $flag = $aop->rsaCheckV1($data, NULL, "RSA2");
        //记录支付回调日志
        $myfile = fopen("alipay.log", "a");
        fwrite($myfile, "\r\n");
        fwrite($myfile, json_encode($data));
        fclose($myfile);
        // $data = json_decode('{"gmt_create":"2022-09-01 16:43:20","charset":"UTF-8","seller_email":"673337943@qq.com","subject":"\u5546\u54c1\u652f\u4ed8","sign":"HevJ0qOwin+67W5R5fn3sil+dsfgexNEwxm9ex0tfzhn+6cpKDTReQxwtMEvU0+hPRTMUE1bUzCNvbQVjd2r7GsmiC8qh+KjGAW5GORC4yHvKIk+CXYQw31P5vOm+Q1nId7OH0BvRtRMnTEtJicQgttRnhsedrU8JGlt8fYLD4lEocqbo6kCrwspjVrEmoYOCNqGO0jInrdg+IuH7Mj5y+CBShxx12uv6ykdDnlofdN9vGYGoa4W4zokCoaKSM3DCX6TtGMeo\/z0KpORFxw1m+vGVHQmorrKt3ktXyrEF6LG1+9rL+jtVPyOpjaC3OPcZL\/VIfUE5IEi9kGO16jDFA==","body":"\u5546\u54c1\u652f\u4ed8","buyer_id":"2088802415345831","invoice_amount":"1.00","notify_id":"2022090100222164321045831416818154","fund_bill_list":"[{\"amount\":\"1.00\",\"fundChannel\":\"ALIPAYACCOUNT\"}]","notify_type":"trade_status_sync","trade_status":"TRADE_SUCCESS","receipt_amount":"1.00","app_id":"2021003130629645","buyer_pay_amount":"1.00","sign_type":"RSA2","seller_id":"2088441435777491","gmt_payment":"2022-09-01 16:43:21","notify_time":"2022-09-01 16:43:21","version":"1.0","out_trade_no":"Z2022090116431551555149","total_amount":"1.00","trade_no":"2022090122001445831456706908","auth_app_id":"2021003130629645","buyer_logon_id":"467***@qq.com","point_amount":"0.00"}', true);
        // var_dump($data['trade_status']);exit;
        if($data['trade_status'] == 'TRADE_SUCCESS' ){
            //业务处理   验证签名成功
            return 1;
        }else{
            return 0;
        }
    }


    function createLinkstring($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }


    function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }
}

?>