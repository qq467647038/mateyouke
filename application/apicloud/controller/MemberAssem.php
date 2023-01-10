<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class MemberAssem extends Common{
    //获取拼团详情状态接口
    public function info(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.order_num')){
                        $order_num = input('post.order_num');
                        $orders = Db::name('order')->where('ordernumber',$order_num)->where('user_id',$user_id)->where('order_type',2)->where('state',1)->where('is_show',1)->field('id,ordernumber,order_type,pin_type,pin_id')->find();
                        
                        if($orders){
                            if($orders['pin_type'] == 1){
                                $pintuans = Db::name('pintuan')->where('id',$orders['pin_id'])->where('tz_id',$user_id)->where('state',1)->field('id,pin_num,tuan_num,pin_status,timeout')->find();
                            }elseif($orders['pin_type'] == 2){
                                $pintuans = Db::name('pintuan')->where('id',$orders['pin_id'])->where('tz_id','neq',$user_id)->where('state',1)->field('id,pin_num,tuan_num,pin_status,timeout')->find();
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关拼团信息','data'=>array('status'=>400));
                                return json($value);
                            }
                            
                            if($pintuans){
                                $order_assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('order_id',$orders['id'])->where('pin_type',$orders['pin_type'])->where('user_id',$user_id)->where('state',1)->where('tui_status',0)->find();
                                if($order_assembles){
                                    $webconfig = $this->webconfig;
                                    $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                    
                                    foreach ($member_assem as $key => $val){
                                        $member_assem[$key]['headimgurl'] = $webconfig['weburl'].'/'.$val['headimgurl'];
                                    }
                                    
                                    if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                        if($order_assembles['pin_type'] == 1){
                                            $pininfo = '发起拼团成功';
                                            $zhuangtai = 1;
                                            $tuan_name = '快快邀请神秘的TA来参团,距结束仅剩';
                                        }elseif($order_assembles['pin_type'] == 2){
                                            $pininfo = '参与拼团成功';
                                            $zhuangtai = 1;
                                            $tuan_name = '快快邀请神秘的TA来参团,距结束仅剩';
                                        }
                                    }elseif($pintuans['pin_status'] == 1){
                                        $pininfo = '拼团成功';
                                        $zhuangtai = 2;
                                        $tuan_name = '';
                                        foreach ($member_assem as $k => $v){
                                            if($k == 0){
                                                $tuan_name = $v['user_name'];
                                            }else{
                                                $tuan_name = $tuan_name.'、'.$v['user_name'];
                                            }
                                        }
                                        $tuan_name = $tuan_name.'也算一起拼过得人了';
                                    }elseif(($pintuans['pin_status'] == 2) || ($pintuans['pin_status'] == 0 && $pintuans['timeout'] <= time())){
                                        $pininfo = '拼团失败';
                                        $zhuangtai = 3;
                                        $tuan_name = '';
                                    }else{
                                        $value = array('status'=>400,'mess'=>'找不到相关拼团信息','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                    
                                    $order_num = $orders['ordernumber'];
                                    $pin_id = $pintuans['id'];
                                    $tuan_id = $order_assembles['id'];
                                    $nowtime = time();
                                    $timeout = $pintuans['timeout'];
                                    
                                    $goodsinfo = Db::name('order_goods')->where('order_id',$orders['id'])->field('id,goods_id,goods_name,thumb_url,goods_attr_str,price,goods_num,th_status,order_id')->find();
                                    $goodsinfo['thumb_url'] = $webconfig['weburl'].'/'.$goodsinfo['thumb_url'];
                                    $goodsinfo['pin_num'] = $pintuans['pin_num'];
                                    $value = array('status'=>200,'mess'=>'获取拼团状态信息成功','data'=>array('goodsinfo'=>$goodsinfo,'pininfo'=>$pininfo,'zhuangtai'=>$zhuangtai,'order_num'=>$order_num,'pin_id'=>$pin_id,'tuan_id'=>$tuan_id,'nowtime'=>$nowtime,'timeout'=>$timeout,'member_assem'=>$member_assem,'tuan_name'=>$tuan_name));
                                }else{
                                    $value = array('status'=>400,'mess'=>'找不到相关拼团信息','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关拼团信息','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关订单信息','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少订单号','data'=>array('status'=>400));
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
    
    public function yaoqing(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.pin_id') && input('post.tuan_id')){
                        $pin_id = input('post.pin_id');
                        $tuan_id = input('post.tuan_id');
                        
                        $order_assembles = Db::name('order_assemble')->where('id',$tuan_id)->where('pin_id',$pin_id)->where('user_id',$user_id)->where('state',1)->where('tui_status',0)->find();
                        if($order_assembles){
                            if($order_assembles['pin_type'] == 1){
                                $pintuans = Db::name('pintuan')->where('id',$order_assembles['pin_id'])->where('tz_id',$user_id)->where('state',1)->where('pin_status',0)->where('timeout','gt',time())->field('id,assem_number,goods_id,pin_num,tuan_num,pin_status,timeout')->find();
                            }elseif($order_assembles['pin_type'] == 2){
                                $pintuans = Db::name('pintuan')->where('id',$order_assembles['pin_id'])->where('tz_id','neq',$user_id)->where('state',1)->where('pin_status',0)->where('timeout','gt',time())->field('id,assem_number,goods_id,pin_num,tuan_num,pin_status,timeout')->find();
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关拼团信息','data'=>array('status'=>400));
                                return json($value);
                            }
                            
                            if($pintuans){
                                $orders = Db::name('order')->where('id',$order_assembles['order_id'])->where('user_id',$user_id)->where('order_type',2)->where('pin_type',$order_assembles['pin_type'])->where('state',1)->where('is_show',1)->field('id,ordernumber,order_type,pin_type,pin_id')->find();
                                if($orders){
                                    $goodsinfo = Db::name('order_goods')->where('order_id',$orders['id'])->field('goods_id,goods_name,goods_attr_str')->find();
                                    if($goodsinfo){
                                        $webconfig = $this->webconfig;
                                        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                        
                                        foreach ($member_assem as $key => $val){
                                            $member_assem[$key]['headimgurl'] = $webconfig['weburl'].'/'.$val['headimgurl'];
                                        }

                                        $goods_name = $goodsinfo['goods_name'].$goodsinfo['goods_attr_str'];
                                        
                                        $num = $pintuans['pin_num']-$pintuans['tuan_num'];
                                        $goods_id = $order_assembles['goods_id'];
                                        $pin_number = $pintuans['assem_number'];
                                        $weburl = $webconfig['weburl'];
                                        $value = array('status'=>200,'mess'=>'获取拼团邀请信息成功','data'=>array('member_assem'=>$member_assem,'num'=>$num,'goods_name'=>$goods_name,'goods_id'=>$goods_id,'pin_number'=>$pin_number,'weburl'=>$weburl));
                                    }else{
                                        $value = array('status'=>400,'mess'=>'找不到相关拼团信息','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'找不到相关拼团信息','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关拼团信息','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关拼团信息','data'=>array('status'=>400));
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
}