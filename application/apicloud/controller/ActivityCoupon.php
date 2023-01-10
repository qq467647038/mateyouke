<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use app\common\service\MemberCouponService;
use app\common\service\CouponService;
use app\common\service\WxLoginService;
use app\common\model\Member as MemberModel;
use think\Db;

class ActivityCoupon extends Common{

    private $user_id;
    public function __construct()
    {
        parent::__construct();
        $gongyong = new GongyongMx();
        $result = $gongyong->apivalidate();
        if($result['status'] == 200){
            $this->user_id = $result['user_id'];
        }else{
            return returnJson(400,$result['mess'],['status'=>400]);
        }
    }

    /**
     * 获取可用优惠券
     *
     * @return void
     */
    public function getCoupon()
    {
        $couponService = new CouponService();
        $couponList = $couponService->findByShopsId(1);
        $count = count($couponList);
        if($count > 0){
            $num = rand(0,$count-1);
            return $couponList[$num];
        }else{
            return null;
        }
        
    }

    /**
     * 验证信息,发送优惠券
     *
     * @return void
     */
    public function sendCoupon(MemberCouponService $service)
    {
        if(!request()->isPost()) return returnJson(400,'请求方式不正确',['status'=>400]);
        $param = input('param.');
        $coupon = $this->getCoupon();
        if($coupon == null) return returnJson(400,'暂无可领取优惠券');
        $code = Db::name('sms')->where(['phone'=>$param['phone']])->find();
        if(!$code || $code['smscode'] != $param['code']) return returnJson(400,'验证码错误',['status'=>400]);
        $res = Db::name('member_coupon_log')->where(['user_id'=>$this->user_id])->find();
        if($res) return returnJson(400,'您已领取过优惠券');

        try {
            $service->sendCoupon($this->user_id,$coupon['id']);
            
            return returnJson(200,'领取成功');
        } catch (\Throwable $th) {
            return returnJson(400,'领取失败');
        }
        
        
    }

    /**
     * 发送验证码
     *
     * @return void
     */
    public function sendCode()
    {
        if(!request()->isPost()) return returnJson(400,'请求方式错误');
        // 判断用户是否关注公众号
        $wxService = new WxLoginService();
        $accessToken = $wxService->get_access_token();
        $userInfo = MemberModel::findById($this->user_id);
        $openInfo = $wxService->GetUserInfo($accessToken,$userInfo['wx_openid']);
        if($openInfo['subscribe'] == 0) return returnJson(401,'请先关注公众号再领取');
        
        $phone = input('post.phone');
        if(!$phone) return returnJson(400,'请输入手机号');

        $smscode = createSMSCode(4);
        $data['phone'] = $phone;
        $data['qtime'] = time();
        $data['smscode'] = $smscode;
        $outputArr = sendSms($phone,$smscode);
        $outputArr = object_to_array($outputArr);
        if($outputArr['msg'] == 'OK'){
            // 启动事务
            Db::startTrans();
            try{
                $res = Db::name('sms')->where(['phone'=>$phone])->find();
                if($res){
                    Db::name('sms')->where('id',$res['id'])->update($data);
                }else{
                    Db::name('sms')->insert($data);
                }
                $codenum = Db::name('code_num')->where('phone',$phone)->find();
                $jtime = time();
                if($codenum){
                    Db::name('code_num')->update(array('num'=>1,'phone'=>$phone,'id'=>$codenum['id']));
                }else{
                    $time_out = strtotime(date('Y-m-d',time()))+3600*24;
                    Db::name('code_num')->insert(array('time_out'=>$time_out,'num'=>1,'phone'=>$phone));
                }
                // 提交事务
                Db::commit();
                $value = array('status'=>200,'mess'=>'发送验证码成功！','data'=>array('status'=>200));
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $value = array('status'=>400,'mess'=>'系统错误，发送验证码失败！','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400, 'mess'=>'发送验证码失败！','data'=>array('status'=>400));
        }
        return json($value);
    }

}