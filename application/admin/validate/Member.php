<?php
namespace app\admin\validate;
use think\Validate;

class Member extends Validate

{
    protected $rule = [
        'user_name' => 'require|unique:member|chsAlphaNum|length:0,15',
        'phone' => 'require',
        'wz_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'password'=> 'require|chsAlphaNum|length:6,20',
        'paypwd'=> 'require|number|length:6',
        'email' => 'email',
        'wxnum' => 'max:60',
        'qqnum' => 'max:60',
        'checked'=> 'require|in:0,1',
        'emergency_name'=> 'require',
        'emergency_phone'=> 'require',
        'wx_name'=> 'require',
        'wx_telephone'=> 'require',
        'wx_qrcode'=> 'require',
        'zfb_name'=> 'require',
        'zfb_telephone'=> 'require',
        'zfb_qrcode'=> 'require',
        'bank_name'=> 'require',
        'bank_telephone'=> 'require',
        'bank_card_number'=> 'require',
        'bank_card_name'=> 'require',
    ];

    protected $message = [
        'user_name.require' => '姓名不能为空',
        'user_name.chsAlphaNum' => '姓名只能为汉字、字母和数字',
        'user_name.length' => '姓名只能为2到5位汉字',
        'phone.require' => '手机号不能为空',
        'phone.regex' => '手机号格式不正确',
        'wz_id.require' => '请选择销售职位',
        'wz_id.regex' => '销售职位参数错误',
        'password.require' => '密码不能为空',
        'password.chsAlphaNum' => '密码只能是汉字、字母和数字',
        'password.length' => '密码只能是6-20位汉字、字母和数字',
        'paypwd.require' => '支付密码不能为空',
        'paypwd.number' => '支付密码只能是6位数字',
        'paypwd.length' => '支付密码只能是6位数字',
        'email.email' => '邮箱格式错误',
        'wxnum.max' => '微信号最多20位',
        'qqnum.max' => 'qq号最多20位',
        'checked.require' => '请选择开启或关闭',
        'checked.in' => '选择开启或关闭参数错误',
        'emergency_name.require' => '紧急联系人不能为空',
        'emergency_phone.require' => '紧急联系手机号不能为空',
        'wx_name.require' => '微信名字不能为空',
        'wx_telephone.require' => '微信手机号不能为空',
        'wx_qrcode.require' => '微信收款码不能为空',
        'zfb_name.require' => '支付宝名字不能为空',
        'zfb_telephone.require' => '支付宝手机不能为空',
        'zfb_qrcode.require' => '支付宝收款码不能为空',
        'bank_name.require' => '银行卡姓名不能为空',
        'bank_telephone.require' => '银行卡手机号不能为空',
        'bank_card_number.require' => '银行卡号不能为空',
        'bank_card_name.require' => '银行名称不能为空',
    ];
    
    protected $scene = [
        'useradd' => ['user_name','phone','password','paypwd'],
        'useredit' => ['phone','password'=> 'chsAlphaNum|length:6,20','paypwd'=> 'number|length:6'],
        'saleadd'   =>  ['user_name','phone','wz_id','password','email','wxnum','qqnum','checked'],
        'saleedit'  =>  ['user_name','phone'=>'require|regex:/^1[3456789]\d{9}$/','wz_id','password'=>'number|length:6','email','wxnum','qqnum','checked'],
        'strationadd' => ['user_name','phone','wz_id','password','email','wxnum','qqnum','checked'],
        'strationedit'  =>  ['user_name','phone'=>'require|regex:/^1[3456789]\d{9}$/','wz_id','password'=>'number|length:6','email','wxnum','qqnum','checked'],
        'masteradd' => ['user_name','phone','password','email','wxnum','qqnum','checked'],
        'masteredit'  =>  ['user_name','phone'=>'require|regex:/^1[3456789]\d{9}$/','password'=>'number|length:6','email','wxnum','qqnum','checked'],
    ];
    

}