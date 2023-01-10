<?php
namespace app\apicloud\validate;
use think\Validate;

class PersonapplyInfo extends Validate
{
    protected $rule = [
        'indus_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'shop_name' => 'require|unique:shops|length:2,30',
        'shop_desc'=>'require|max:50',
        //'cate_ids'=> 'require',
        'contacts' => 'require|length:2,5',
        'telephone' => 'require|regex:/^1[3456789]\d{9}$/',
        'sfz_num'=>'require|length:18',
        'pro_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'city_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'area_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'address'=>'require|max:50',
    ];

    protected $message = [
        'indus_id.require' => '请选择行业',
        'indus_id.regex' => '请选择行业',
        'shop_name.require' => '店铺名称不能为空',
        'shop_name.unique' => '店铺名称已存在',
        'shop_name.length' => '店铺名称只能为2到30个字符',
        'shop_desc.require' => '商铺描述不能为空',
        'shop_desc.max' => '店铺描述最多50个字符',
        //'cate_ids.require' => '请选择经营类目信息',
        'contacts.require' => '联系人姓名不能为空',
        'contacts.length' => '联系人姓名只能为2到5个字符',
        'telephone.require' => '手机号不能为空',
        'telephone.regex' => '手机号格式不正确',
        'sfz_num.require' => '身份证号不能为空',
        'sfz_num.length' => '身份证号为18位字符',
        'pro_id.require' => '请选择省份',
        'pro_id.regex' => '请选择省份',
        'city_id.require' => '请选择城市',
        'city_id.regex' => '请选择城市',
        'area_id.require' => '请选择区县',
        'area_id.regex' => '请选择区县',
        'address.require' => '商铺详细地址不能为空',
        'address.max' => '商铺详细地址最多50个字符',
    ];
    

}
