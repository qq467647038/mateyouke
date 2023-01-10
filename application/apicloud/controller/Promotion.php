<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class Promotion extends Common{
    
    public function huodonginfo(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                if(input('post.shop_id')){
                    if(input('post.goods_id')){
                        if(input('post.prom_id')){
                            $shop_id = input('post.shop_id');
                            $goods_id = input('post.goods_id');
                            $prom_id = input('post.prom_id');
                            
                            $promotions = Db::name('promotion')->where('id',$prom_id)->where('shop_id',$shop_id)->where("find_in_set('".$goods_id."',info_id)")->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,start_time,end_time,shop_id')->find();
                            
                            if($promotions){
                                $prom_typeres = Db::name('prom_type')->where('prom_id',$promotions['id'])->select();
                                if($prom_typeres){
                                    $start_time = date('Y年m月d日 H时',$promotions['start_time']);
                                    $end_time = date('Y年m月d日 H时',$promotions['end_time']);
                                    $promotion_infos = '';
                                    
                                    foreach ($prom_typeres as $kcp => $vcp){
                                        $zhekou = $vcp['discount']/10;
                                        if($kcp == 0){
                                            $promotion_infos = '商品满 '.$vcp['man_num'].'件 享'.$zhekou.'折';
                                        }else{
                                            $promotion_infos = $promotion_infos.'  满 '.$vcp['man_num'].'件 享'.$zhekou.'折';
                                        }
                                    }
                                    $goods_promotion = array('prom_id'=>$promotions['id'],'shop_id'=>$shop_id,'goods_id'=>$goods_id,'promotion_name'=>$promotion_infos,'time'=>'有效期：'.$start_time.'至'.$end_time.'截止');
                                    $value = array('status'=>200,'mess'=>'获取活动信息成功','data'=>$goods_promotion);
                                }else{
                                    $value = array('status'=>400,'mess'=>'找不到相关活动信息或活动已过期','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关活动信息或活动已过期','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少活动参数','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少商品参数','data'=>array('status'=>400));
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
}