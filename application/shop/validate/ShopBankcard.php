<?php
namespace app\shop\validate;
use think\Validate;

class ShopBankcard extends Validate

{
    protected $rule = [
        'name' => 'require|regex:/^[\x{4e00}-\x{9fa5}]{2,5}+$/u',
        'telephone' => 'require|regex:/^1[3456789]\d{9}$/',
        'card_number' => ['require','regex'=>'/^\d{16}|\d{19}$/'],
        'bank_name' => 'require|chs|max:60',
        'province' => 'require|chs|max:60',
        'city' => 'require|chs|max:60',
        'area' => 'require|chs|max:60',
        'branch_name' => 'require|chs|max:90',
        'shop_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];
    
    protected $message = [
        'name.require' => '真实姓名不能为空',
        'name.regex' => '真实姓名在2到5个汉字之间',
        'telephone.require' => '手机号不能为空',
        'telephone.regex' => '请输入正确的手机号码',
        'card_number.require' => '请填写银行卡号',
        'card_number.regex' => '请输入正确银行卡号',
        'bank_name.require' => '请填写所属银行名称',
        'bank_name.chs' => '请填写正确的银行名称',
        'bank_name.max' => '银行名称最多20个字符',
        'province.require' => '请填写省份名称',
        'province.chs' => '请填写正确的省份名称',
        'province.max' => '省份名称最多20个字符',
        'city.require' => '请填写城市名称',
        'city.chs' => '请填写正确的城市名称',
        'city.max' => '城市名称最多20个字符',
        'area.require' => '请填写区县名称',
        'area.chs' => '请填写正确的区县名称',
        'area.max' => '区县名称最多20个字符',
        'branch_name.require' => '请填写所属支行',
        'branch_name.chs' => '请填写正确的支行名称',
        'branch_name.max' => '支行名称最多30个字符',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
    ];
    
    

}