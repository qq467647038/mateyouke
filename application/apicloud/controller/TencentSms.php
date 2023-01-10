<?php


namespace app\apicloud\controller;


// 导入对应产品模块的client
use TencentCloud\Sms\V20210111\SmsClient;

// 导入要请求接口对应的Request类
use TencentCloud\Sms\V20210111\Models\SendSmsRequest;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Credential;

// 导入可选配置类
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\AbstractClient;


class TencentSms extends AbstractClient
{
    function __construct($credential, $region, $profile=null)
    {
        parent::__construct($this->endpoint, $this->version, $credential, $region, $profile);
    }

    /**
     * @var string
     */
    protected $version = "2021-01-11";

    function send($phone,$code)
    {


        try {

            $cred = new Credential("AKIDQpAQafdUErNm4EHC0e7SehdQ5c0GMK9q", "brzynU2QflhVHtNiuyrY4QdjgUAh6MTR");

            // 实例化一个http选项，可选的，没有特殊需求可以跳过
            $httpProfile = new HttpProfile();

            $httpProfile->setReqMethod("GET");  // post请求(默认为post请求)
            $httpProfile->setReqTimeout(30);    // 请求超时时间，单位为秒(默认60秒)
            $httpProfile->setEndpoint("sms.tencentcloudapi.com");  // 指定接入地域域名(默认就近接入)

            // 实例化一个client选项，可选的，没有特殊需求可以跳过
            $clientProfile = new ClientProfile();
            $clientProfile->setSignMethod("TC3-HMAC-SHA256");  // 指定签名算法(默认为HmacSHA256)
            $clientProfile->setHttpProfile($httpProfile);

            $client = new SmsClient($cred, "ap-guangzhou", $clientProfile);

            // 实例化一个 sms 发送短信请求对象,每个接口都会对应一个request对象。
            $req = new SendSmsRequest();


            $req->SmsSdkAppId = "1400671536";

            $req->SignName = "遵义金锤子";
            $req->TemplateId = "1396149";
            $req->TemplateParamSet = array("$code");
            $req->PhoneNumberSet = array("$phone");
            /* 用户的 session 内容（无需要可忽略）: 可以携带用户侧 ID 等上下文信息，server 会原样返回 */
            $req->SessionContext = "";
            /* 短信码号扩展号（无需要可忽略）: 默认未开通，如需开通请联系 [腾讯云短信小助手] */
            $req->ExtendCode = "";
            /* 国际/港澳台短信 SenderId（无需要可忽略）: 国内短信填空，默认未开通，如需开通请联系 [腾讯云短信小助手] */
            $req->SenderId = "";

            // 通过client对象调用SendSms方法发起请求。注意请求方法名与请求对象是对应的
            // 返回的resp是一个SendSmsResponse类的实例，与请求对象对应
            $resp = $client->SendSms($req);
            if($resp->SendStatusSet[0]->Code == "Ok"){
                return true;
            }else{
                return false;
            }
            // var_dump($resp->SendStatusSet[0]->Code);
            // var_dump($resp->SendStatusSet);
            // 输出json格式的字符串回包
            // var_dump(json_decode($resp->toJsonString(),true));


        } catch (TencentCloudSDKException $e) {
            echo $e;
        }
    }


}