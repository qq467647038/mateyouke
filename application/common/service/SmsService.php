<?php
namespace app\common\service;
use app\common\service\SignatureHelper;
use app\common\model\SendSmsLog;
use app\common\model\Order as OrderModel;
use app\common\model\Member as MemberModel;

/**
 * 短信类
 */
class SmsService
{
    private $ACCESS_KEY_ID;
    private $ACCESS_KEY_SECRET;
    private $SIGN_NAME; // 短信签名
    private $SMS_CODE_TEMPLATE; // 短信模板
    private $SMS_BUY_TO_SHOP;
    private $SMS_WARN_TO_SHOP;

    public function __construct()
    {
        $this->ACCESS_KEY_ID = '';
        $this->ACCESS_KEY_SECRET = '';
        $this->SIGN_NAME = '';
        $this->SMS_CODE_TEMPLATE = ''; // 短信
        $this->SMS_BUY_TO_SHOP = ''; // 下单提醒商家
        $this->SMS_WARN_TO_SHOP = ''; // 提醒商家发货
    }

    /**
     * 发送验证码短信
     *
     * @param [type] $phone
     * @param [type] $smsCode
     * @return void
     */
    public function sendCode($phone,$smsCode)
    {
        $param = [
            "code" => $smsCode
        ];

        try {
            return $this->sendSms($phone,$param,$this->SMS_CODE_TEMPLATE);
        } catch (\Throwable $th) {
            //throw $th;
            $res = [
                "Code" => "FAIL"
            ];
            return $res;
        }
    }

    /**
     * 下单提醒商家
     *
     * @return void
     */
    public function sendBuyOrderToShop($order_sn)
    {
        $order = OrderModel::findOrderByOrderNumber($order_sn);
        $user = MemberModel::findByShopId($order['shop_id']);
        $phone = $user['phone'];
        $array = [
            'consignee' => $order['contacts'],
            'phone' => $phone
        ];
        try {
            $result = $this->sendSms($phone,$array,$this->SMS_BUY_TO_SHOP);
            $result = object_to_array($result);
            $this->addLog($result,$phone,'下单提醒商家');
            return $result;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * 催单发送短信给商家
     *
     * @param [type] $phone
     * @param [type] $array
     * @return void
     */
    public function sendOrderWarnToShop($order_sn)
    {
        $order = OrderModel::findOrderByOrderNumber($order_sn);
        $user = MemberModel::findByShopId($order['shop_id']);
        $phone = $user['phone'];
        $array = [
            'consignee' => $order['contacts'],
            'phone' => $phone
        ];
        try {
            $result = $this->sendSms($phone,$array,$this->SMS_WARN_TO_SHOP);
            $result = object_to_array($result);
            $this->addLog($result,$phone,'催单发送短信给商家');
            return $result;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    private function addLog($result,$phone,$desc = '')
    {

        if($result['Code'] == "OK"){
            $status = 1;
        }else{
            $status = 0;
        }

        $data = [
            'phone' => $phone,
            'desc' => $desc,
            'status' => $status
        ];
        return SendSmsLog::add($data);
    }

    /**
     * 发送短信
     *
     * @param [type] $phone 手机号码
     * @param [type] $array 模板需要的参数
     * @param [type] $template 模板CODE
     * @return void
     */
    private function sendSms($phone,$array,$template)
    {
        $params = array ();

        // *** 需用户填写部分 ***
        // fixme 必填：是否启用https
        $security = false;
    
        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = $this->ACCESS_KEY_ID;
        $accessKeySecret = $this->ACCESS_KEY_SECRET;
    
        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $phone;
    
        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = $this->SIGN_NAME;
    
        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = $template;
    
        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $params['TemplateParam'] = $array;
    
        // fixme 可选: 设置发送短信流水号
        // $params['OutId'] = "12345";
    
        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        // $params['SmsUpExtendCode'] = "1234567";
    
    
        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }
    
        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();
    
        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            )),
            $security
        );
        return $content;
    }

}