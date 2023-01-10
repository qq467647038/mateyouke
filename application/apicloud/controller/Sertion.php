<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class Sertion extends Common{
    
    //获取服务项信息列表信息接口
    public function serlst(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                if(input('post.goods_id')){
                    $goods_id = input('post.goods_id');
                    $goods = Db::name('goods')->where('id',$goods_id)->where('onsale',1)->field('id,fuwu')->find();
                    if($goods){
                        if($goods['fuwu']){
                            $sertionres = Db::name('sertion')->where('id','in',$goods['fuwu'])->where('is_show',1)->field('id,ser_name,ser_remark')->order('sort asc')->select();
                        }else{
                            $sertionres = array();
                        }
                        $value = array('status'=>200,'mess'=>'获取优惠券信息成功','data'=>$sertionres);
                    }else{
                        $value = array('status'=>400,'mess'=>'找不到相关商品信息','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'缺少商品信息参数','data'=>array('status'=>400));
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