<?php
namespace app\shop\validate;
use think\Validate;

class ApplyInfo extends Validate
{
    protected $rule = [
        'shop_name' => 'require|unique:shops|length:2,20',
        'shop_desc'=>'require|max:50',
        'indus_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'contacts' => 'require|length:2,5',
        'telephone' => 'require|regex:/^1[3456789]\d{9}$/',
        'faren_name' => 'require|length:2,5',
        'sfz_num'=>'require',
        'sfzz_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'sfzb_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'frsfz_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'zhizhao_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'pro_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'city_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'area_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'address'=>'require|max:50',
        'latlon'=>'require|max:50',
    ];

    protected $message = [
        'shop_name.require' => '店铺名称不能为空',
        'shop_name.unique' => '店铺名称已存在',
        'shop_name.length' => '店铺名称只能为2到20个字符',
        'shop_desc.require' => '商铺描述不能为空',
        'shop_desc.max' => '店铺描述最多50个字符',
        'indus_id.require' => '请选择行业',
        'indus_id.regex' => '请选择行业',
        'contacts.require' => '联系人姓名不能为空',
        'contacts.length' => '联系人姓名只能为2到5个字符',
        'telephone.require' => '手机号不能为空',
        'telephone.regex' => '手机号格式不正确',
        'faren_name.require' => '法人姓名不能为空',
        'faren_name.length' => '法人姓名只能为2到5个字符',
        'sfz_num.require' => '身份证号不能为空',
        'sfzz_id.require' => '请上传法人身份证正面照片',
        'sfzz_id.regex' => '请上传法人身份证正面照片',
        'sfzb_id.require' => '请上传法人身份证背面照片',
        'sfzb_id.regex' => '请上传法人身份证背面照片',
        'frsfz_id.require' => '请上传法人手持身份证照片',
        'frsfz_id.regex' => '请上传法人手持身份证照片',
        'zhizhao_id.require' => '请上传营业执照照片',
        'zhizhao_id.regex' => '请上传营业执照照片',
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
    ];
    

}