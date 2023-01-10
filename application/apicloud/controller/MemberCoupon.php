<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class MemberCoupon extends Common{
    
    //读取用户优惠券列表
    public function couponlst(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];

                    if(input('post.filter') && in_array(input('post.filter'), array(1,2,3))){
                        $filter = input('post.filter');
                    }else{
                        $filter = 1;
                    }
                    
                    if($filter == 1){
                        $couponres = Db::name('member_coupon')
                            ->alias('a')
                            ->field('a.id,b.name,b.man_price,b.dec_price,b.start_time,b.end_time,b.shop_id,c.shop_name,c.logo')
                            ->join('sp_coupon b','a.coupon_id = b.id','INNER')
                            ->join('sp_shops c','a.shop_id = c.id','INNER')
                            ->where('a.user_id',$user_id)
                            ->where('a.is_sy',0)
                            ->where('b.start_time','elt',time())
                            ->where('b.end_time','gt',time()-3600*24)
                            ->where('b.onsale',1)
                            ->where('c.open_status',1)
                            ->order('b.man_price asc')
                            ->select();
                    }elseif($filter == 3){
                        $couponres = Db::name('member_coupon')->alias('a')->field('a.id,b.man_price,b.dec_price,b.start_time,b.end_time,b.shop_id,c.shop_name,c.logo')->join('sp_coupon b','a.coupon_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.user_id',$user_id)->where('a.is_sy',0)->where('b.start_time','elt',time())->where('b.end_time','elt',time()-3600*24)->where('b.onsale',1)->where('c.open_status',1)->order('b.man_price asc')->select();
                    }elseif($filter == 2){
                        $couponres = Db::name('member_coupon')->alias('a')->field('a.id,b.man_price,b.dec_price,b.start_time,b.end_time,b.shop_id,c.shop_name,c.logo')->join('sp_coupon b','a.coupon_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.user_id',$user_id)->where('a.is_sy',1)->where('b.onsale',1)->where('c.open_status',1)->order('b.man_price asc')->select();
                    }
                    
                    $couponarr = array();
                    
                    $webconfig = $this->webconfig;
                    if($couponres){
                        foreach ($couponres as $key => $val){
                            $couponres[$key]['start_time'] = date('Y-m-d',$couponres[$key]['start_time']);
                            $couponres[$key]['end_time'] = date('Y-m-d',$couponres[$key]['end_time']);
                            
                            $couponres[$key]['logo'] = $webconfig['weburl'].'/'.$val['logo'];
                            if($val['start_time'] <= time() && $val['end_time'] > time()-3600*24){
                                $couponres[$key]['filter'] = 1;
                                $couponres[$key]['zt'] = '去使用';
                            }elseif($val['start_time'] <= time() && $val['end_time'] <= time()-3600*24){
                                $couponres[$key]['filter'] = 2;
                                $couponres[$key]['zt'] = '已过期';
                            }
                        }
                        
                        foreach ($couponres as $k => $v){
                            $couponarr[$v['shop_id']][] = $v;
                        }
                    }

                    if($couponarr){
                        $couponarr = array_values($couponarr);
                        $arr = [];
                        foreach ($couponarr as $k=>$v){
                            $arr = array_merge($arr,array_values($v));
                        }
                        $couponarr = $arr;
                    }
                    $value = array('status'=>200,'mess'=>'获取优惠券信息成功','data'=>$couponarr);
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
}