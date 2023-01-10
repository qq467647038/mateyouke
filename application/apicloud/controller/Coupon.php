<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;
use app\apicloud\model\Coupon as CouponModel;

class Coupon extends Common{
    
    //获取优惠券列表信息接口
    public function couponlst(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                if(!empty($result['user_id'])){
                    $user_id = $result['user_id'];
                }
                
                if(input('post.shop_id')){
                    $shop_id = input('post.shop_id');
                    $shops = Db::name('shops')->where('id',$shop_id)->where('open_status',1)->field('id')->find();
                    if($shops){
                        $couponres = Db::name('coupon')->where('shop_id',$shop_id)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id,name,man_price,dec_price,start_time,end_time,shop_id')->order('man_price asc')->select();
                        foreach ($couponres as $k => $v){
                            $couponres[$k]['start_time'] = date('Y-m-d',$v['start_time']);
                            $couponres[$k]['end_time'] = date('Y-m-d',$v['end_time']);
            
                            if(!empty($user_id)){
                                $member_coupons = Db::name('member_coupon')->where('user_id',$user_id)->where('coupon_id',$v['id'])->where('is_sy',0)->where('shop_id',$v['shop_id'])->find();
                                if($member_coupons){
                                    $couponres[$k]['have'] = 1;
                                }else{
                                    $couponres[$k]['have'] = 0;
                                }
                            }else{
                                $couponres[$k]['have'] = 0;
                            }
                        }
                        $value = array('status'=>200,'mess'=>'获取优惠券信息成功','data'=>$couponres);
                    }else{
                        $value = array('status'=>400,'mess'=>'找不到相关商家信息','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'缺少商家参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
  
      /**
     * 查询用户优惠券信息
     */
    public function memberCouponList() {

        if(request()->isPost()){

            $used     = input('post.used');
            $userid   = input('post.user_id');
            $condition = ' and m.user_id=' . $userid;
            $gongyong = new GongyongMx();
            $result   = $gongyong->apivalidate();

            if ($result['status'] !== 200) {
                echoFail(LOSE,'请求方式不正确');
            }

            if (!input('post.user_id')) {
                echoFail(LOSE,'用户ID不存在');
            }
          
          	if (!input('post.used')) {
                echoFail(LOSE,'参数缺失');
            }

            $used = $used == 3 ? $condition .= ' and end_time < UNIX_TIMESTAMP(now())' : $condition .= ' and m.is_sy =' . $used;

            $conpous = Db::query('select c.id,c.man_price,c.dec_price,c.start_time,c.end_time,m.user_id from sp_coupon as c LEFT JOIN  sp_member_coupon m on c.id = m.coupon_id where 1 ' . $condition . ' ORDER BY m.id desc');
                                         
            $sql = Db::table('contract')->getLastSql();

            echoSuccess(WIN, $conpous);exit();

        }
        
    }
  
    
    //领取优惠券接口
    public function getcoupons(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.coupon_id') && input('post.shop_id')){
                        $coupon_id = input('post.coupon_id');
                        $shop_id = input('post.shop_id');
                        $coupons = Db::name('coupon')->where('id',$coupon_id)->where('shop_id',$shop_id)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id')->find();
                        if($coupons){
                            $member_coupons = Db::name('member_coupon')->where('user_id',$user_id)->where('coupon_id',$coupons['id'])->where('is_sy',0)->where('shop_id',$shop_id)->find();
                            if(!$member_coupons){
                                $lastId = Db::name('member_coupon')->insert(array('coupon_id'=>$coupons['id'],'is_sy'=>0,'shop_id'=>$shop_id,'user_id'=>$user_id));
                                if($lastId){
                                    $value = array('status'=>200,'mess'=>'领券成功','data'=>array('status'=>200));
                                }else{
                                    $value = array('status'=>400,'mess'=>'领券失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'该优惠券已领取，请勿重复领取','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关优惠券信息或已过期','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少参数','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    /**
     * @function获取所有优惠券列表
     * @author Feifan.Chen <1057286925@qq.com>
     * @return \think\response\Json
     */
    public function couponList(){
        if (request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200){
                    define('USERID',$result['user_id']);
                    $list = CouponModel::with(['coupon'=>
                        function($query){
                        $query->where(['user_id'=>USERID,'is_sy'=>0]);
                    }])
                        ->where([
                        'checked' =>1,
                        'onsale' => 1,
                        'is_recycle' => 0,
                    ])
                        ->where("start_time<=".TIME." and end_time>=".TIME)
                        ->order('sort','desc')
                        ->select();
                    $value = [
                        'status'=>200,
                        'mess'=>'获取优惠券信息成功',
                        'data'=>$list
                    ];
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }

        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }


}