<?php
namespace app\apicloud\validate;
use think\Validate;

class PayProof extends Validate
{
    protected $rule = [
        'id|商品信息' => ['require'],
        'qrcode|付款凭证' => ['require'],
        'paypwd|支付密码' => ['require'],
        'paywayindex|支付方式' => ['require'],
    ];

    protected $message = [
    ];

}