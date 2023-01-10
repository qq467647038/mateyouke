<?php
namespace app\admin\validate;
use think\Validate;

class Shops extends Validate
{
    protected $rule = [
        'shop_name' => 'require|unique:shops|length:2,20',
        'shop_desc'=>'require|max:50',
//        'search_keywords' => 'require|max:100',
        'contacts' => 'require|length:2,5',
        'telephone' => 'require|regex:/^1[3456789]\d{9}$/',
//        'freight'=>['require','regex'=>'/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/'],
//        'reduce'=>['require','regex'=>'/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/'],
        'pro_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'city_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'area_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'address'=>'require|max:50',
        'latlon'=>'require|max:50',
//        'fenxiao'=>'require|in:0,1'
    ];

    protected $message = [
        'shop_name.require' => '店铺名称不能为空',
        'shop_name.unique' => '店铺名称已存在',
        'shop_name.length' => '店铺名称只能为2到20个字符',
        'shop_desc.require' => '商铺描述不能为空',
        'shop_desc.max' => '店铺描述最多50个字符',
        'search_keywords.require' => '搜索关键词不能为空',
        'search_keywords.max' => '搜索关键词最多100个字符',
        'contacts.require' => '联系人姓名不能为空',
        'contacts.length' => '联系人姓名只能为2到5个字符',
        'telephone.require' => '手机号不能为空',
        'telephone.regex' => '手机号格式不正确',
        'freight.require' => '运费不能为空',
        'freight.regex' => '运费价格格式错误',
        'reduce.require' => '满金额免运费不能为空',
        'reduce.regex' => '满金额免运费格式错误',
        'pro_id.require' => '请选择省份',
        'pro_id.regex' => '请选择省份',
        'city_id.require' => '请选择城市',
        'city_id.regex' => '请选择城市',
        'area_id.require' => '请选择区县',
        'area_id.regex' => '请选择区县',
        'address.require' => '商铺详细地址不能为空',
        'address.max' => '商铺详细地址最多50个字符',
        'latlon.require' => '商铺地址坐标不能为空',
        'latlon.max' => '商铺地址坐标最多50个字符',
        'fenxiao.require' => '请选择是否开启商品分销',
        'fenxiao.in' => '请选择是否开启商品分销',
    ];
    

}