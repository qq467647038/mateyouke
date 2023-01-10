<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use app\common\logic\OrderAfterLogic;
use think\Db;

class MemberOrder extends Common{
    
    //订单列表信息接口
    public function index(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                        $ordouts = Db::name('order_timeout')->where('id',1)->find();
                        $webconfig = $this->webconfig;
                        $perpage = 20;
                        $offset = (input('post.page')-1)*$perpage;
                        
                        $filter = input('post.filter');
                        if(!$filter || !in_array($filter, array(1,2,3,4,5,6,7))){
                            $filter = 6;
                        }

                        switch($filter){
                            //待付款
                            case 1:
                                $where = array('a.user_id'=>$user_id,'a.state'=>0,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
                                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                                break;
                            //待发货
                            case 2:
                                $where = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
                                $sort = array('a.pay_time'=>'desc','a.id'=>'desc');
                                break;
                            //待收货
                            case 3:
                                $where = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>0,'a.is_show'=>1);
                                $sort = array('a.fh_time'=>'desc','a.id'=>'desc');
                                break;
                            //待评价
                            case 4:
                                $where = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1,'a.ping'=>0,'a.is_show'=>1);
                                $sort = array('a.coll_time'=>'desc','a.id'=>'desc');
                                break;
                            //全部
                            case 6:
                                $where = array('a.user_id'=>$user_id,'a.is_show'=>1);
                                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                                break;
                            //可以申请售后的订单
                            case 7:
                                $where = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>'1 or a.fh_status =0','a.order_status'=>0,'a.is_show'=>1);
                                $sort = array('a.fh_time'=>'desc','a.id'=>'desc');
                                break;
                        }

                        if(in_array($filter,array(1,2,3,4,6,7))){
                            $orderes = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name,a.zong_id,og.goods_id,g.type')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where)->order($sort)->join('order_goods og', 'og.order_id = a.id', 'left')->join('goods g', 'g.id = og.goods_id', 'left')->limit($offset,$perpage)->select();
                            if($orderes){
                                foreach ($orderes as $k => $v){
                                    if($v['state'] == 0 && $v['fh_status'] == 0 && $v['order_status'] == 0 && $v['is_show'] == 1){
                                        $orderes[$k]['order_number'] = Db::name('order_zong')->where('id', $v['zong_id'])->value('order_number');

                                        $orderes[$k]['order_zt'] = "待付款";
                                        $orderes[$k]['filter'] = 1;
                                        
                                        if($v['time_out'] <= time()){
                                            // 启动事务
                                            Db::startTrans();
                                            try{
                                                //过期自动关闭
                                                Db::name('order')->where('id',$v['id'])->update(array('order_status'=>2,'can_time'=>time()));
                                                
                                                if($v['coupon_id']){
                                                    Db::name('member_coupon')->where('user_id',$user_id)->where('coupon_id',$v['coupon_id'])->where('is_sy',1)->where('shop_id',$v['shop_id'])->update(array('is_sy'=>0));
                                                }
                                            
                                                $goodinfos = Db::name('order_goods')->where('order_id',$v['id'])->field('id,goods_id,goods_attr_id,goods_num,hd_type,hd_id')->select();
                                                if($goodinfos){
                                                    foreach ($goodinfos as $val3){
                                                        if(in_array($val3['hd_type'],array(0,2,3))){
                                                            $prokc = Db::name('product')->where('goods_id',$val3['goods_id'])->where('goods_attr',$val3['goods_attr_id'])->find();
                                                            if($prokc){
                                                                Db::name('product')->where('goods_id',$val3['goods_id'])->where('goods_attr',$val3['goods_attr_id'])->setInc('goods_number', $val3['goods_num']);
                                                            }
                                                        }elseif($val3['hd_type'] == 1){
                                                            $hdactivitys = Db::name('rush_activity')->where('id',$val3['hd_id'])->find();
                                                            if($hdactivitys){
                                                                Db::name('rush_activity')->where('id',$val3['hd_id'])->setInc('kucun',$val3['goods_num']);
                                                                Db::name('rush_activity')->where('id',$val3['hd_id'])->setDec('sold',$val3['goods_num']);
                                                            }
                                                        }
                                                    }
                                                }
                                                // 提交事务
                                                Db::commit();
                                            } catch (\Exception $e) {
                                                // 回滚事务
                                                Db::rollback();
                                            }
                                        }
                                    }elseif($v['state'] == 1 && $v['fh_status'] == 0 && $v['order_status'] == 0 && $v['is_show'] == 1){
                                        $orderes[$k]['order_zt'] = "待发货";
                                        $orderes[$k]['filter'] = 2;
                                        
                                        if($v['order_type'] == 2){
                                            $pintuans = Db::name('pintuan')->where('id',$v['pin_id'])->where('state',1)->where('pin_status',0)->where('timeout','elt',time())->field('id,pin_num,tuan_num,pin_status,timeout')->find();
                                            if($pintuans){
                                                // 启动事务
                                                Db::startTrans();
                                                try{
                                                    Db::name('pintuan')->where('id',$pintuans['id'])->update(array('pin_status'=>2));
                                                
                                                    $order_assembleres = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('state',1)->where('tui_status',0)->select();
                                                    if($order_assembleres){
                                                        foreach ($order_assembleres as $vrc){
                                                            $pinorders = Db::name('order')->where('id',$vrc['order_id'])->where('state',1)->where('fh_status',0)->where('order_status',0)->where('order_type',2)->where('is_show',1)->field('id,total_price,user_id')->find();
                                                            if($pinorders){
                                                                Db::name('order_assemble')->where('id',$vrc['id'])->update(array('tui_status'=>1));
                                                                Db::name('order')->where('id',$pinorders['id'])->update(array('order_status'=>2,'can_time'=>time()));
                                                                
                                                                $orgoods = Db::name('order_goods')->where('order_id',$pinorders['id'])->field('goods_id,goods_attr_id,goods_num,hd_type,hd_id')->find();
                                                                if($orgoods){
                                                                    Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->setInc('goods_number', $orgoods['goods_num']);
                                                                }
                                                            }
                                                        }
                                                    }
                                                    // 提交事务
                                                    Db::commit();
                                                } catch (\Exception $e) {
                                                    // 回滚事务
                                                    Db::rollback();
                                                }
                                            }
                                        }
                                    }elseif($v['state'] == 1 && $v['fh_status'] == 1 && $v['order_status'] == 0 && $v['is_show'] == 1){
                                        $orderes[$k]['order_zt'] = "待收货";
                                        $orderes[$k]['filter'] = 3;
                                        if($v['shouhou'] == 0 && $v['zdsh_time'] <= time()){
                                            // 启动事务
                                            Db::startTrans();
                                            try{
                                                //过期自动收货
                                                Db::name('order')->where('id',$v['id'])->update(array('order_status'=>1,'coll_time'=>time()));
                                            
                                                $goodinfos = Db::name('order_goods')->where('order_id',$v['id'])->field('id,goods_id,goods_attr_id,goods_num,th_status,shop_id')->select();
                                                if($goodinfos){
                                                    foreach ($goodinfos as $val2){
                                                        if(in_array($val2['th_status'], array(0,8))){
                                                            $gdinfos = Db::name('goods')->where('id',$val2['goods_id'])->field('id,sale_num,deal_num')->find();
                                                            if($gdinfos){
                                                                $deal_num = $gdinfos['deal_num']+$val2['goods_num'];
                                                                $deal_lv = sprintf("%.2f",$deal_num/$gdinfos['sale_num'])*100;
                                                                Db::name('goods')->update(array('id'=>$val2['goods_id'],'deal_num'=>$deal_num,'deal_lv'=>$deal_lv));
                                                            }
                                            
                                                            $spinfos = Db::name('shops')->where('id',$val2['shop_id'])->field('id,sale_num,deal_num')->find();
                                                            if($spinfos){
                                                                $shop_deal_num = $spinfos['deal_num']+$val2['goods_num'];
                                                                $shop_deal_lv = sprintf("%.2f",$shop_deal_num/$spinfos['sale_num'])*100;
                                                                Db::name('shops')->update(array('id'=>$val2['shop_id'],'deal_num'=>$shop_deal_num,'deal_lv'=>$shop_deal_lv));
                                                            }
                                                        }
                                                    }
                                                }
                                            
                                                // 提交事务
                                                Db::commit();
                                            } catch (\Exception $e) {
                                                // 回滚事务
                                                Db::rollback();
                                            }
                                        }
                                    }elseif($v['state'] == 1 && $v['fh_status'] == 1 && $v['order_status'] == 1 && $v['is_show'] == 1){
                                        $orderes[$k]['order_zt'] = "已完成";
                                        $orderes[$k]['filter'] = 4;
                                    }elseif($v['order_status'] == 2 && $v['is_show'] == 1){
                                        $orderes[$k]['order_zt'] = "已关闭";
                                        $orderes[$k]['filter'] = 5;
                                    }
                                    
                                    $orderes[$k]['goodsinfo'] = Db::name('order_goods')->where('order_id',$v['id'])->field('id,goods_id,goods_name,thumb_url,goods_attr_str,price,goods_num,th_status,order_id')->select();

                                    $domain = $webconfig['weburl'];

                                    foreach ($orderes[$k]['goodsinfo'] as $key => $val){
                                        $orderes[$k]['goodsinfo'][$key]['thumb_url'] = $val['thumb_url'];
                                        $hasComment = Db::name('comment')->where('goods_id',$val['goods_id'])->where('order_id',$val['order_id'])->find();
                                        if($hasComment){
                                            $orderes[$k]['goodsinfo'][$key]['hasComment'] = 1;
                                        }else{
                                            $orderes[$k]['goodsinfo'][$key]['hasComment'] = 0;
                                        }
                                        unset($hasComment);
                                    }
                                    $orderes[$k]['spnum'] = Db::name('order_goods')->where('order_id',$v['id'])->sum('goods_num');
                                }
                            }
                        }else{
                            $orderes = Db::name('th_apply')->alias('a')->field('a.id,a.th_number,a.thfw_id,a.apply_status,a.tui_price,a.tui_num,a.orgoods_id,a.order_id,a.dcfh_status,a.sh_status,a.fh_status,a.shou_status,a.check_timeout,a.shoptui_timeout,a.yhfh_timeout,a.yhshou_timeout,a.shop_id,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.user_id',$user_id)->order('a.apply_time desc')->limit($offset,$perpage)->select();
                            if($orderes){
                                foreach ($orderes as $k => $v){
                                    switch($v['thfw_id']){
                                        case 1:
                                            if($v['apply_status'] == 0){
                                                $orderes[$k]['order_zt'] = '待平台处理';
                                            }elseif($v['apply_status'] == 1){
                                                $orderes[$k]['order_zt'] = '待平台退款';
                                            }elseif($v['apply_status'] == 2){
                                                $orderes[$k]['order_zt'] = '平台拒绝申请';
                                            }elseif($v['apply_status'] == 3){
                                                $orderes[$k]['order_zt'] = '退款已完成';
                                            }elseif($v['apply_status'] == 4){
                                                $orderes[$k]['order_zt'] = '已撤销';
                                            }
                                            break;
                                        case 2:
                                            if($v['apply_status'] == 0){
                                                $orderes[$k]['order_zt'] = '待平台处理';
                                            }elseif($v['apply_status'] == 1){
                                                if($v['dcfh_status'] == 0){
                                                    $orderes[$k]['order_zt'] = '待用户发货';
                                                }elseif($v['dcfh_status'] == 1 && $v['sh_status'] == 0){
                                                    $orderes[$k]['order_zt'] = '待平台收货';
                                                }elseif($v['dcfh_status'] == 1 && $v['sh_status'] == 1){
                                                    $orderes[$k]['order_zt'] = '待平台退款';
                                                }
                                            }elseif($v['apply_status'] == 2){
                                                $orderes[$k]['order_zt'] = '平台拒绝申请';
                                            }elseif($v['apply_status'] == 3){
                                                $orderes[$k]['order_zt'] = '退款已完成';
                                            }elseif($v['apply_status'] == 4){
                                                $orderes[$k]['order_zt'] = '已撤销';
                                            }
                                            break;
                                        case 3:
                                            if($v['apply_status'] == 0){
                                                $orderes[$k]['order_zt'] = '待平台处理';
                                            }elseif($v['apply_status'] == 1){
                                                if($v['dcfh_status'] == 0){
                                                    $orderes[$k]['order_zt'] = '待用户发货';
                                                }elseif($v['dcfh_status'] == 1 && $v['sh_status'] == 0){
                                                    $orderes[$k]['order_zt'] = '待平台收货';
                                                }elseif($v['sh_status'] == 1 && $v['fh_status'] == 0){
                                                    $orderes[$k]['order_zt'] = '待平台发货';
                                                }elseif($v['fh_status'] == 1 && $v['shou_status'] == 0){
                                                    $orderes[$k]['order_zt'] = '待用户收货';
                                                }
                                            }elseif($v['apply_status'] == 2){
                                                $orderes[$k]['order_zt'] = '平台拒绝申请';
                                            }elseif($v['apply_status'] == 3){
                                                $orderes[$k]['order_zt'] = '换货已完成';
                                            }elseif($v['apply_status'] == 4){
                                                $orderes[$k]['order_zt'] = '已撤销';
                                            }
                                            break;
                                    }
                                    $orderes[$k]['orgoods'] = Db::name('order_goods')->where('id',$v['orgoods_id'])->where('order_id',$v['order_id'])->field('id,goods_id,goods_name,thumb_url,goods_attr_str,goods_num,th_status,order_id')->find();
                                    
                                    if($webconfig['cos_file'] == '开启'){
                                        $a = config('tengxunyun')['cos_domain'].'/'.$item['cover'];
                                    }else{
                                        $a = $webconfig['weburl'].'/'.$item['cover'];
                                    }
                                    $orderes[$k]['orgoods']['thumb_url'] = $a.$orderes[$k]['orgoods']['thumb_url'];
                                
                                    if($v['apply_status'] == 0 && $v['check_timeout'] <= time()){
                                        // 启动事务
                                        Db::startTrans();
                                        try{
                                            if($v['thfw_id'] == 1){
                                                $shoptui_timeout = time()+$ordouts['shoptui_timeout']*24*3600;
                                                Db::name('th_apply')->update(array('apply_status'=>1,'agree_time'=>time(),'shoptui_timeout'=>$shoptui_timeout,'id'=>$v['id']));
                                            }elseif(in_array($v['thfw_id'], array(2,3))){
                                                $yhfh_timeout = time()+$ordouts['yhfh_timeout']*24*3600;
                                                Db::name('th_apply')->update(array('apply_status'=>1,'agree_time'=>time(),'yhfh_timeout'=>$yhfh_timeout,'id'=>$v['id']));
                                            }
                                            
                                            if(in_array($v['thfw_id'], array(1,2))){
                                                $th_status = 2;
                                            }elseif($v['thfw_id'] == 3){
                                                $th_status = 6;
                                            }
                                            
                                            if(!empty($th_status)){
                                                Db::name('order_goods')->update(array('th_status'=>$th_status,'id'=>$v['orgoods_id']));
                                            }
                                        
                                            // 提交事务
                                            Db::commit();
                                        } catch (\Exception $e) {
                                            // 回滚事务
                                            Db::rollback();
                                        }
                                    }elseif($v['thfw_id'] == 1 && $v['apply_status'] == 1 && $v['shoptui_timeout'] <= time()){
                                        $orgoods = Db::name('order_goods')->where('id',$v['orgoods_id'])->field('goods_id,goods_attr_id,hd_type,hd_id')->find();
                                        if($orgoods){
                                            // 启动事务
                                            Db::startTrans();
                                            try{
                                                Db::name('th_apply')->update(array('apply_status'=>3,'com_time'=>time(),'id'=>$v['id']));
                                                Db::name('order_goods')->update(array('th_status'=>4,'id'=>$v['orgoods_id']));
                                                $ordergoods = Db::name('order_goods')->where('id','neq',$v['orgoods_id'])->where('order_id',$v['order_id'])->where('th_status','in','0,1,2,3,5,6,7,8')->field('id')->find();
                                                if(!$ordergoods){
                                                    $orders = Db::name('order')->where('id',$v['order_id'])->find();
                                                    if($orders){
                                                        Db::name('order')->where('id',$v['order_id'])->update(array('order_status'=>2,'shouhou'=>0,'can_time'=>time()));
                                                        if($orders['coupon_id']){
                                                            Db::name('member_coupon')->where('user_id',$orders['user_id'])->where('coupon_id',$orders['coupon_id'])->where('is_sy',1)->where('shop_id',$orders['shop_id'])->update(array('is_sy'=>0));
                                                        }
                                                    }
                                                }else{
                                                    $ordergoodres = Db::name('order_goods')->where('id','neq',$v['orgoods_id'])->where('order_id',$v['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id')->find();
                                                
                                                    if($ordergoodres){
                                                        $shouhou = 1;
                                                    }else{
                                                        $shouhou = 0;
                                                    }
                                                
                                                    if($shouhou == 0){
                                                        $orders = Db::name('order')->where('id',$v['order_id'])->find();
                                                        if($orders){
                                                            $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                                            Db::name('order')->where('id',$v['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                                                        }
                                                    }
                                                }
                                                
                                                if(in_array($orgoods['hd_type'],array(0,2,3))){
                                                    $prokc = Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->find();
                                                    if($prokc){
                                                        Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->setInc('goods_number', $v['tui_num']);
                                                    }
                                                }elseif($orgoods['hd_type'] == 1){
                                                    $hdactivitys = Db::name('rush_activity')->where('id',$orgoods['hd_id'])->find();
                                                    if($hdactivitys){
                                                        Db::name('rush_activity')->where('id',$orgoods['hd_id'])->setInc('kucun',$v['tui_num']);
                                                        Db::name('rush_activity')->where('id',$orgoods['hd_id'])->setDec('sold',$v['tui_num']);
                                                    }
                                                }
                                            
                                                // 提交事务
                                                Db::commit();
                                            } catch (\Exception $e) {
                                                // 回滚事务
                                                Db::rollback();
                                            }
                                        }
                                    }elseif($v['thfw_id'] == 2 && $v['apply_status'] == 1 && $v['dcfh_status'] == 1 && $v['sh_status'] == 1 && $v['shoptui_timeout'] <= time()){
                                        $orgoods = Db::name('order_goods')->where('id',$v['orgoods_id'])->field('goods_id,goods_attr_id,hd_type,hd_id')->find();
                                        if($orgoods){
                                            // 启动事务
                                            Db::startTrans();
                                            try{
                                                Db::name('th_apply')->update(array('apply_status'=>3,'com_time'=>time(),'id'=>$v['id']));
                                                Db::name('order_goods')->update(array('th_status'=>4,'id'=>$v['orgoods_id']));
                                                $ordergoods = Db::name('order_goods')->where('id','neq',$v['orgoods_id'])->where('order_id',$v['order_id'])->where('th_status','in','0,1,2,3,5,6,7,8')->field('id')->find();
                                                if(!$ordergoods){
                                                    $orders = Db::name('order')->where('id',$v['order_id'])->find();
                                                    if($orders){
                                                        Db::name('order')->where('id',$v['order_id'])->update(array('order_status'=>2,'shouhou'=>0,'can_time'=>time()));
                                                        if($orders['coupon_id']){
                                                            Db::name('member_coupon')->where('user_id',$orders['user_id'])->where('coupon_id',$orders['coupon_id'])->where('is_sy',1)->where('shop_id',$orders['shop_id'])->update(array('is_sy'=>0));
                                                        }
                                                    }
                                                }else{
                                                    $ordergoodres = Db::name('order_goods')->where('id','neq',$v['orgoods_id'])->where('order_id',$v['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id')->find();
                                                
                                                    if($ordergoodres){
                                                        $shouhou = 1;
                                                    }else{
                                                        $shouhou = 0;
                                                    }
                                                
                                                    if($shouhou == 0){
                                                        $orders = Db::name('order')->where('id',$v['order_id'])->find();
                                                        if($orders){
                                                            $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                                            Db::name('order')->where('id',$v['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                                                        }
                                                    }
                                                }
                                                
                                                if(in_array($orgoods['hd_type'],array(0,2,3))){
                                                    $prokc = Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->find();
                                                    if($prokc){
                                                        Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->setInc('goods_number', $v['tui_num']);
                                                    }
                                                }elseif($orgoods['hd_type'] == 1){
                                                    $hdactivitys = Db::name('rush_activity')->where('id',$orgoods['hd_id'])->find();
                                                    if($hdactivitys){
                                                        Db::name('rush_activity')->where('id',$orgoods['hd_id'])->setInc('kucun',$v['tui_num']);
                                                        Db::name('rush_activity')->where('id',$orgoods['hd_id'])->setDec('sold',$v['tui_num']);
                                                    }
                                                }
                                            
                                                // 提交事务
                                                Db::commit();
                                            } catch (\Exception $e) {
                                                // 回滚事务
                                                Db::rollback();
                                            }
                                        }
                                    }elseif(in_array($v['thfw_id'], array(2,3)) && $v['apply_status'] == 1 && $v['dcfh_status'] == 0 && $v['yhfh_timeout'] <= time()){
                                        $orders = Db::name('order')->where('id',$v['order_id'])->where('state',1)->where('fh_status',1)->field('id')->find();
                                        if($orders){
                                            // 启动事务
                                            Db::startTrans();
                                            try{
                                                Db::name('th_apply')->update(array('apply_status'=>4,'che_time'=>time(),'id'=>$v['id']));
                                                Db::name('order_goods')->update(array('th_status'=>0,'id'=>$v['orgoods_id']));
                                            
                                                $ordergoods = Db::name('order_goods')->where('id','neq',$v['orgoods_id'])->where('order_id',$v['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id')->find();
                                            
                                                if($ordergoods){
                                                    $shouhou = 1;
                                                }else{
                                                    $shouhou = 0;
                                                }
                                            
                                                if($shouhou == 0){
                                                    $orders = Db::name('order')->where('id',$v['order_id'])->find();
                                                    if($orders){
                                                        $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                                        Db::name('order')->where('id',$v['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                                                    }
                                                }
                                                
                                                // 提交事务
                                                Db::commit();
                                            } catch (\Exception $e) {
                                                // 回滚事务
                                                Db::rollback();
                                            }
                                        }
                                    }elseif($v['thfw_id'] == 3 && $v['apply_status'] == 1 && $v['dcfh_status'] == 1 && $v['sh_status'] == 1 && $v['fh_status'] == 1 && $v['shou_status'] == 0 && $v['yhshou_timeout'] <= time()){
                                        // 启动事务
                                        Db::startTrans();
                                        try{
                                            Db::name('th_apply')->update(array('shou_status'=>1,'apply_status'=>3,'shou_time'=>time(),'com_time'=>time(),'id'=>$v['id']));
                                            Db::name('order_goods')->update(array('th_status'=>8,'id'=>$v['orgoods_id']));
                                        
                                            $ordergoods = Db::name('order_goods')->where('id','neq',$v['orgoods_id'])->where('order_id',$v['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id,th_status')->find();
                                        
                                            if($ordergoods){
                                                $shouhou = 1;
                                            }else{
                                                $shouhou = 0;
                                            }
                                        
                                            if($shouhou == 0){
                                                $orders = Db::name('order')->where('id',$v['order_id'])->find();
                                                if($orders){
                                                    $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                                    Db::name('order')->where('id',$v['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                                                }
                                            }
                                        
                                            // 提交事务
                                            Db::commit();
                                        } catch (\Exception $e) {
                                            // 回滚事务
                                            Db::rollback();
                                        }
                                    }
                                }
                            }
                        }
                        $value = array('status'=>200,'mess'=>'获取订单信息成功','data'=>$orderes);
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少页数参数','data'=>array('status'=>400));
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
    
    //取消订单
    public function quxiao(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.order_num')){
                        $order_num = input('post.order_num');
                        $orders = Db::name('order')->where('ordernumber',$order_num)->where('user_id',$user_id)->where('state',0)->where('fh_status',0)->where('order_status',0)->where('is_show',1)->find();
                        if($orders){
                            $orgoodres = Db::name('order_goods')->where('order_id',$orders['id'])->field('goods_id,goods_attr_id,goods_num,hd_type,hd_id')->select();
                            if($orgoodres){
                                // 启动事务
                                Db::startTrans();
                                try{
                                    Db::name('order')->update(array('order_status'=>2,'can_time'=>time(),'id'=>$orders['id']));
                                    
                                    if($orders['coupon_id']){
                                        Db::name('member_coupon')->where('user_id',$user_id)->where('coupon_id',$orders['coupon_id'])->where('is_sy',1)->where('shop_id',$orders['shop_id'])->update(array('is_sy'=>0));
                                    }
                                    
                                    foreach($orgoodres as $v){
                                        if(in_array($v['hd_type'],array(0,2,3))){
                                            $prokc = Db::name('product')->where('goods_id',$v['goods_id'])->where('goods_attr',$v['goods_attr_id'])->find();
                                            if($prokc){
                                                Db::name('product')->where('goods_id',$v['goods_id'])->where('goods_attr',$v['goods_attr_id'])->setInc('goods_number', $v['goods_num']);
                                            }
                                        }elseif($v['hd_type'] == 1){
                                            $hdactivitys = Db::name('rush_activity')->where('id',$v['hd_id'])->find();
                                            if($hdactivitys){
                                                Db::name('rush_activity')->where('id',$v['hd_id'])->setInc('kucun',$v['goods_num']);
                                                Db::name('rush_activity')->where('id',$v['hd_id'])->setDec('sold',$v['goods_num']);
                                            }
                                        }
                                    }
                                
                                    // 提交事务
                                    Db::commit();
                                    $value = array('status'=>200,'mess'=>'取消订单成功','data'=>array('status'=>200));
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'取消订单失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关类型订单商品','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关类型订单','data'=>array('status'=>400));
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
    
    //删除订单
    public function delorder(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.order_num')){
                        $order_num = input('post.order_num');
                        $orders = Db::name('order')->where('ordernumber',$order_num)->where('user_id',$user_id)->where('state',0)->where('fh_status',0)->where('order_status',2)->where('is_show',1)->find();
                        if($orders){
                            $count = Db::name('order')->update(array('is_show'=>0,'del_time'=>time(),'id'=>$orders['id']));
                            
                            if($count > 0){
                                $value = array('status'=>200,'mess'=>'删除订单成功','data'=>array('status'=>200));
                            }else{
                                $value = array('status'=>400,'mess'=>'删除订单失败','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关类型订单','data'=>array('status'=>400));
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
    
    //订单详情
    public function orderinfo(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.order_num')){
                        $order_num = input('post.order_num');
                        $orders = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.contacts,a.telephone,a.province,a.city,a.zf_type,a.area,a.address,a.goods_price,a.freight,a.youhui_price,a.coupon_id,a.coupon_price,a.coupon_str,a.total_price,a.beizhu,a.state,a.pay_time,a.fh_status,a.fh_time,a.order_status,a.shouhou,a.is_show,a.coll_time,a.can_time,a.ping,a.order_type,a.pin_type,a.pin_id,a.zong_id,a.shop_id,a.addtime,a.zdsh_time,a.time_out,b.order_number,c.shop_name')->join('sp_order_zong b','a.zong_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->where('a.ordernumber',$order_num)->where('a.user_id',$user_id)->where('a.is_show',1)->find();
                        if($orders){
                            if($orders['state'] == 0 && $orders['fh_status'] == 0 && $orders['order_status'] == 0 && $orders['is_show'] == 1 && $orders['time_out'] <= time()){
                                // 启动事务
                                Db::startTrans();
                                try{
                                    //过期自动关闭
                                    Db::name('order')->where('id',$orders['id'])->update(array('order_status'=>2,'can_time'=>time()));
                                    
                                    if($orders['coupon_id']){
                                        Db::name('member_coupon')->where('user_id',$user_id)->where('coupon_id',$orders['coupon_id'])->where('is_sy',1)->where('shop_id',$orders['shop_id'])->update(array('is_sy'=>0));
                                    }
                                    
                                    $goodinfos = Db::name('order_goods')->where('order_id',$orders['id'])->field('id,goods_id,goods_attr_id,goods_num,hd_type,hd_id')->select();
                                    if($goodinfos){
                                        foreach ($goodinfos as $val3){
                                            if(in_array($val3['hd_type'],array(0,2,3))){
                                                $prokc = Db::name('product')->where('goods_id',$val3['goods_id'])->where('goods_attr',$val3['goods_attr_id'])->find();
                                                if($prokc){
                                                    Db::name('product')->where('goods_id',$val3['goods_id'])->where('goods_attr',$val3['goods_attr_id'])->setInc('goods_number', $val3['goods_num']);
                                                }
                                            }elseif($val3['hd_type'] == 1){
                                                $hdactivitys = Db::name('rush_activity')->where('id',$val3['hd_id'])->find();
                                                if($hdactivitys){
                                                    Db::name('rush_activity')->where('id',$val3['hd_id'])->setInc('kucun',$val3['goods_num']);
                                                    Db::name('rush_activity')->where('id',$val3['hd_id'])->setDec('sold',$val3['goods_num']);
                                                }
                                            }
                                        }
                                    }                                
                                    // 提交事务
                                    Db::commit();
                                    $orders = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.contacts,a.telephone,a.province,a.city,a.area,a.address,a.goods_price,a.freight,a.youhui_price,a.coupon_id,a.total_price,a.beizhu,a.state,a.pay_time,a.fh_status,a.fh_time,a.order_status,a.shouhou,a.is_show,a.coll_time,a.can_time,a.ping,a.order_type,a.pin_type,a.pin_id,a.zong_id,a.shop_id,a.addtime,a.zdsh_time,a.time_out,b.order_number,c.shop_name')->join('sp_order_zong b','a.zong_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->where('a.ordernumber',$order_num)->where('a.user_id',$user_id)->where('a.is_show',1)->find();
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                                    return json($value);
                                } 
                            }elseif($orders['state'] == 1 && $orders['fh_status'] == 0 && $orders['order_status'] == 0 && $orders['order_type'] == 2 && $orders['is_show'] == 1){
                                $pintuans = Db::name('pintuan')->where('id',$orders['pin_id'])->where('state',1)->where('pin_status',0)->where('timeout','elt',time())->field('id,pin_num,tuan_num,pin_status,timeout')->find();
                                if($pintuans){
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        Db::name('pintuan')->where('id',$pintuans['id'])->update(array('pin_status'=>2));
                                
                                        $order_assembleres = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('state',1)->where('tui_status',0)->select();
                                        if($order_assembleres){
                                            foreach ($order_assembleres as $vrc){
                                                $pinorders = Db::name('order')->where('id',$vrc['order_id'])->where('state',1)->where('fh_status',0)->where('order_status',0)->where('order_type',2)->where('is_show',1)->field('id,total_price,user_id')->find();
                                                if($pinorders){
                                                    Db::name('order_assemble')->where('id',$vrc['id'])->update(array('tui_status'=>1));
                                                    Db::name('order')->where('id',$pinorders['id'])->update(array('order_status'=>2,'can_time'=>time()));

                                                    $orgoods = Db::name('order_goods')->where('order_id',$pinorders['id'])->field('goods_id,goods_attr_id,goods_num,hd_type,hd_id')->find();
                                                    if($orgoods){
                                                        Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->setInc('goods_number', $orgoods['goods_num']);
                                                    }
                                                }
                                            }
                                        }
                                        // 提交事务
                                        Db::commit();
                                        $orders = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.contacts,a.telephone,a.province,a.city,a.area,a.address,a.goods_price,a.freight,a.youhui_price,a.coupon_id,a.total_price,a.beizhu,a.state,a.pay_time,a.fh_status,a.fh_time,a.order_status,a.shouhou,a.is_show,a.coll_time,a.can_time,a.ping,a.order_type,a.pin_type,a.pin_id,a.zong_id,a.shop_id,a.addtime,a.zdsh_time,a.time_out,b.order_number,c.shop_name')->join('sp_order_zong b','a.zong_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->where('a.ordernumber',$order_num)->where('a.user_id',$user_id)->where('a.is_show',1)->find();
                                    } catch (\Exception $e) {
                                        // 回滚事务
                                        Db::rollback();
                                        $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                            }elseif($orders['state'] == 1 && $orders['fh_status'] == 1 && $orders['order_status'] == 0 && $orders['shouhou'] == 0 && $orders['is_show'] == 1 && $orders['zdsh_time'] <= time()){
                                // 启动事务
                                Db::startTrans();
                                try{
                                    //过期自动收货
                                    Db::name('order')->where('id',$orders['id'])->update(array('order_status'=>1,'coll_time'=>time()));
                                    
                                    $goodinfos = Db::name('order_goods')->where('order_id',$orders['id'])->field('id,goods_id,goods_attr_id,goods_num,th_status,shop_id')->select();
                                    if($goodinfos){
                                        foreach ($goodinfos as $val2){
                                            if(in_array($val2['th_status'], array(0,8))){
                                                $gdinfos = Db::name('goods')->where('id',$val2['goods_id'])->field('id,sale_num,deal_num')->find();
                                                if($gdinfos){
                                                    $deal_num = $gdinfos['deal_num']+$val2['goods_num'];
                                                    $deal_lv = sprintf("%.2f",$deal_num/$gdinfos['sale_num'])*100;
                                                    Db::name('goods')->update(array('id'=>$val2['goods_id'],'deal_num'=>$deal_num,'deal_lv'=>$deal_lv));
                                                }
                                    
                                                $spinfos = Db::name('shops')->where('id',$val2['shop_id'])->field('id,sale_num,deal_num')->find();
                                                if($spinfos){
                                                    $shop_deal_num = $spinfos['deal_num']+$val2['goods_num'];
                                                    $shop_deal_lv = sprintf("%.2f",$shop_deal_num/$spinfos['sale_num'])*100;
                                                    Db::name('shops')->update(array('id'=>$val2['shop_id'],'deal_num'=>$shop_deal_num,'deal_lv'=>$shop_deal_lv));
                                                }
                                            }
                                        }
                                    }
                                
                                    // 提交事务
                                    Db::commit();
                                    $orders = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.contacts,a.telephone,a.province,a.city,a.area,a.address,a.goods_price,a.freight,a.youhui_price,a.coupon_id,a.total_price,a.beizhu,a.state,a.pay_time,a.fh_status,a.fh_time,a.order_status,a.shouhou,a.is_show,a.coll_time,a.can_time,a.ping,a.order_type,a.pin_type,a.pin_id,a.zong_id,a.shop_id,a.addtime,a.zdsh_time,a.time_out,b.order_number,c.shop_name')->join('sp_order_zong b','a.zong_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->where('a.ordernumber',$order_num)->where('a.user_id',$user_id)->where('a.is_show',1)->find();
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }
                            
                            if($orders['pay_time']){
                                $orders['pay_time'] = date('Y-m-d H:i:s',$orders['pay_time']);
                            }
                            
                            if($orders['fh_time']){
                                $orders['fh_time'] = date('Y-m-d H:i:s',$orders['fh_time']);
                            }
                            
                            if($orders['coll_time']){
                                $orders['coll_time'] = date('Y-m-d H:i:s',$orders['coll_time']);
                            }
                            
                            if($orders['can_time']){
                                $orders['can_time'] = date('Y-m-d H:i:s',$orders['can_time']);
                            }
                            
                            if($orders['addtime']){
                                $orders['addtime'] = date('Y-m-d H:i:s',$orders['addtime']);
                            }
                            
                            if($orders['state'] == 0 && $orders['fh_status'] == 0 && $orders['order_status'] == 0 && $orders['is_show'] == 1){
                                $orders['order_zt'] = "待付款";
                                $orders['filter'] = 1;
                                if($orders['time_out'] > time()){
                                    $orders['sytime'] = time2string($orders['time_out']-time());
                                }else{
                                    $orders['sytime'] = '';
                                }
                            }elseif($orders['state'] == 1 && $orders['fh_status'] == 0 && $orders['order_status'] == 0 && $orders['is_show'] == 1){
                                $orders['order_zt'] = "待发货";
                                $orders['filter'] = 2;
                            }elseif($orders['state'] == 1 && $orders['fh_status'] == 1 && $orders['order_status'] == 0 && $orders['is_show'] == 1){
                                $orders['order_zt'] = "待收货";
                                $orders['filter'] = 3;
                                if($orders['sysh_time'] > time()){
                                    $orders['sysh_time'] = time2string($orders['zdsh_time']-time());
                                }else{
                                    $orders['sysh_time'] = '';
                                }
                            }elseif($orders['state'] == 1 && $orders['fh_status'] == 1 && $orders['order_status'] == 1 && $orders['is_show'] == 1){
                                $orders['order_zt'] = "已完成";
                                $orders['filter'] = 4;
                            }elseif($orders['order_status'] == 2 && $orders['is_show'] == 1){
                                $orders['order_zt'] = "已关闭";
                                $orders['filter'] = 5;
                            }
                            
                            $orders['pinzhuangtai'] = 0;
                            
                            if($orders['state'] == 1 && $orders['fh_status'] == 0 && $orders['order_status'] == 0 && $orders['order_type'] == 2 && $orders['is_show'] == 1){
                                $pinzts = Db::name('pintuan')->where('id',$orders['pin_id'])->where('state',1)->field('id,pin_num,tuan_num,pin_status,timeout')->find();
                                if($pinzts){
                                    if($pinzts['pin_status'] == 0){
                                        $order_assembleres = Db::name('order_assemble')->where('pin_id',$pinzts['id'])->where('order_id',$orders['id'])->where('user_id',$user_id)->where('state',1)->where('tui_status',0)->find();
                                        if($order_assembleres){
                                            $orders['pinzhuangtai'] = 1;
                                        }else{
                                            $orders['pinzhuangtai'] = 2;
                                        }
                                    }elseif($pinzts['pin_status'] == 2){
                                        $orders['pinzhuangtai'] = 2;
                                    }
                                }else{
                                    $orders['pinzhuangtai'] = 2;
                                }
                            }
                            
                            $orders['goodsinfo'] = Db::name('order_goods')->where('order_id',$orders['id'])->field('id,goods_id,goods_name,thumb_url,goods_attr_str,real_price,goods_num,th_status,order_id')->select();
                            if($this->webconfig['cos_file'] == '开启'){
                                $domain = config('tengxunyun')['cos_domain'];
                            }else{
                                $domain = $this->webconfig['weburl'];
                            }
                            //$webconfig = $this->webconfig;
                            
                            // foreach ($orders['goodsinfo'] as $key => $val){
                            //     $orders['goodsinfo'][$key]['thumb_url'] = $domain.'/'.$val['thumb_url'];
                            // }
							
							$orders['logistics'] = "";
                            
                            if($orders['fh_status'] == 1){
                                $order_wulius = Db::name('order_wuliu')->alias('a')->field('a.id,a.psnum,b.log_name,b.telephone,b.kdniao_code')->join('sp_logistics b','a.ps_id = b.id','LEFT')->where('a.order_id',$orders['id'])->find();
                                $orders['wulius'] = $order_wulius;
								$orders['logistics'] = $order_wulius['log_name'];
								$orders['logistics_no'] = $order_wulius['psnum'];
                            }
							
							//支付方式
							$paytype = Db::name('pay_type')->field("pay_name")->where('id',$orders['zf_type'])->find();
							$orders['paytype'] = empty($paytype['pay_name'])?"未知":$paytype['pay_name'];
							
							
                            $value = array('status'=>200,'mess'=>'获取订单详情成功','data'=>$orders);
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关订单','data'=>array('status'=>400));
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
    
    
    //获取退换货订单详情接口
    public function thorderinfo(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.th_number')){
                        $th_number = input('post.th_number');
                        //如果用户是商家
                        $member_data = Db::name('member')->find($user_id);
                        if($member_data['shop_id'] > 0){
                            //这个退换货对应的用户信息
                            $th_user = Db::name('th_apply')->where('th_number',$th_number)->field('user_id')->find();
                            if(empty($th_user)){
                                $value = array('status'=>400,'mess'=>'找不到相关退换货信息','data'=>array('status'=>400));
                            }else{
                                $user_id = $th_user['user_id'];
                            }
                        }
                        
                        $applys = Db::name('th_apply')->where('th_number',$th_number)->where('user_id',$user_id)->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')->find();
                        if($applys){
                            $ordouts = Db::name('order_timeout')->where('id',1)->find();
                            
                            $webconfig = $this->webconfig;
                            if($applys['apply_status'] == 0 && $applys['check_timeout'] <= time()){
                                // 启动事务
                                Db::startTrans();
                                try{
                                    if($applys['thfw_id'] == 1){
                                        $shoptui_timeout = time()+$ordouts['shoptui_timeout']*24*3600;
                                        Db::name('th_apply')->update(array('apply_status'=>1,'agree_time'=>time(),'shoptui_timeout'=>$shoptui_timeout,'id'=>$applys['id']));
                                    }elseif(in_array($applys['thfw_id'], array(2,3))){
                                        $yhfh_timeout = time()+$ordouts['yhfh_timeout']*24*3600;
                                        Db::name('th_apply')->update(array('apply_status'=>1,'agree_time'=>time(),'yhfh_timeout'=>$yhfh_timeout,'id'=>$applys['id']));
                                    }
                            
                                    if(in_array($applys['thfw_id'], array(1,2))){
                                        $th_status = 2;
                                    }elseif($applys['thfw_id'] == 3){
                                        $th_status = 6;
                                    }
                            
                                    if(!empty($th_status)){
                                        Db::name('order_goods')->update(array('th_status'=>$th_status,'id'=>$applys['orgoods_id']));
                                    }
                            
                                    // 提交事务
                                    Db::commit();
                                    $applys = Db::name('th_apply')->where('th_number',$th_number)->where('user_id',$user_id)->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')->find();
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }elseif($applys['thfw_id'] == 1 && $applys['apply_status'] == 1 && $applys['shoptui_timeout'] <= time()){
                                $orgoods = Db::name('order_goods')->where('id',$applys['orgoods_id'])->field('goods_id,goods_attr_id,hd_type,hd_id')->find();
                                if($orgoods){
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        Db::name('th_apply')->update(array('apply_status'=>3,'com_time'=>time(),'id'=>$applys['id']));
                                        Db::name('order_goods')->update(array('th_status'=>4,'id'=>$applys['orgoods_id']));
                                        $ordergoods = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','0,1,2,3,5,6,7,8')->field('id')->find();
                                        if(!$ordergoods){
                                            $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                                            if($orders){
                                                Db::name('order')->where('id',$applys['order_id'])->update(array('order_status'=>2,'shouhou'=>0,'can_time'=>time()));
                                                if($orders['coupon_id']){
                                                    Db::name('member_coupon')->where('user_id',$orders['user_id'])->where('coupon_id',$orders['coupon_id'])->where('is_sy',1)->where('shop_id',$orders['shop_id'])->update(array('is_sy'=>0));
                                                }
                                            }
                                        }else{
                                            $ordergoodres = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id')->find();
                            
                                            if($ordergoodres){
                                                $shouhou = 1;
                                            }else{
                                                $shouhou = 0;
                                            }
                            
                                            if($shouhou == 0){
                                                $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                                                if($orders){
                                                    $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                                    Db::name('order')->where('id',$applys['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                                                }
                                            }
                                        }
                            
                                        if(in_array($orgoods['hd_type'],array(0,2,3))){
                                            $prokc = Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->find();
                                            if($prokc){
                                                Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->setInc('goods_number', $applys['tui_num']);
                                            }
                                        }elseif($orgoods['hd_type'] == 1){
                                            $hdactivitys = Db::name('rush_activity')->where('id',$orgoods['hd_id'])->find();
                                            if($hdactivitys){
                                                Db::name('rush_activity')->where('id',$orgoods['hd_id'])->setInc('kucun',$applys['tui_num']);
                                                Db::name('rush_activity')->where('id',$orgoods['hd_id'])->setDec('sold',$applys['tui_num']);
                                            }
                                        }
                            
                                        // 提交事务
                                        Db::commit();
                                        $applys = Db::name('th_apply')->where('th_number',$th_number)->where('user_id',$user_id)->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')->find();
                                    } catch (\Exception $e) {
                                        // 回滚事务
                                        Db::rollback();
                                        $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                            }elseif($applys['thfw_id'] == 2 && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['shoptui_timeout'] <= time()){
                                $orgoods = Db::name('order_goods')->where('id',$applys['orgoods_id'])->field('goods_id,goods_attr_id,hd_type,hd_id')->find();
                                if($orgoods){
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        Db::name('th_apply')->update(array('apply_status'=>3,'com_time'=>time(),'id'=>$applys['id']));
                                        Db::name('order_goods')->update(array('th_status'=>4,'id'=>$applys['orgoods_id']));
                                        $ordergoods = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','0,1,2,3,5,6,7,8')->field('id')->find();
                                        if(!$ordergoods){
                                            $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                                            if($orders){
                                                Db::name('order')->where('id',$applys['order_id'])->update(array('order_status'=>2,'shouhou'=>0,'can_time'=>time()));
                                                if($orders['coupon_id']){
                                                    Db::name('member_coupon')->where('user_id',$orders['user_id'])->where('coupon_id',$orders['coupon_id'])->where('is_sy',1)->where('shop_id',$orders['shop_id'])->update(array('is_sy'=>0));
                                                }
                                            }
                                        }else{
                                            $ordergoodres = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id')->find();
                            
                                            if($ordergoodres){
                                                $shouhou = 1;
                                            }else{
                                                $shouhou = 0;
                                            }
                            
                                            if($shouhou == 0){
                                                $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                                                if($orders){
                                                    $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                                    Db::name('order')->where('id',$applys['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                                                }
                                            }
                                        }
                            
                                        if(in_array($orgoods['hd_type'],array(0,2,3))){
                                            $prokc = Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->find();
                                            if($prokc){
                                                Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->setInc('goods_number', $applys['tui_num']);
                                            }
                                        }elseif($orgoods['hd_type'] == 1){
                                            $hdactivitys = Db::name('rush_activity')->where('id',$orgoods['hd_id'])->find();
                                            if($hdactivitys){
                                                Db::name('rush_activity')->where('id',$orgoods['hd_id'])->setInc('kucun',$applys['tui_num']);
                                                Db::name('rush_activity')->where('id',$orgoods['hd_id'])->setDec('sold',$applys['tui_num']);
                                            }
                                        }
                            
                                        // 提交事务
                                        Db::commit();
                                        $applys = Db::name('th_apply')->where('th_number',$th_number)->where('user_id',$user_id)->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')->find();
                                    } catch (\Exception $e) {
                                        // 回滚事务
                                        Db::rollback();
                                        $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                            }elseif(in_array($applys['thfw_id'], array(2,3)) && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 0 && $applys['yhfh_timeout'] <= time()){
                                $orders = Db::name('order')->where('id',$applys['order_id'])->where('state',1)->where('fh_status',1)->field('id')->find();
                                if($orders){
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        Db::name('th_apply')->update(array('apply_status'=>4,'che_time'=>time(),'id'=>$applys['id']));
                                        Db::name('order_goods')->update(array('th_status'=>0,'id'=>$applys['orgoods_id']));
                            
                                        $ordergoods = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id')->find();
                            
                                        if($ordergoods){
                                            $shouhou = 1;
                                        }else{
                                            $shouhou = 0;
                                        }
                            
                                        if($shouhou == 0){
                                            $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                                            if($orders){
                                                $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                                Db::name('order')->where('id',$applys['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                                            }
                                        }
                            
                                        // 提交事务
                                        Db::commit();
                                        $applys = Db::name('th_apply')->where('th_number',$th_number)->where('user_id',$user_id)->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')->find();
                                    } catch (\Exception $e) {
                                        // 回滚事务
                                        Db::rollback();
                                        $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                            }elseif($applys['thfw_id'] == 3 && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['fh_status'] == 1 && $applys['shou_status'] == 0 && $applys['yhshou_timeout'] <= time()){
                                // 启动事务
                                Db::startTrans();
                                try{
                                    Db::name('th_apply')->update(array('shou_status'=>1,'apply_status'=>3,'shou_time'=>time(),'com_time'=>time(),'id'=>$applys['id']));
                                    Db::name('order_goods')->update(array('th_status'=>8,'id'=>$applys['orgoods_id']));
                            
                                    $ordergoods = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id,th_status')->find();
                            
                                    if($ordergoods){
                                        $shouhou = 1;
                                    }else{
                                        $shouhou = 0;
                                    }
                            
                                    if($shouhou == 0){
                                        $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                                        if($orders){
                                            $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                            Db::name('order')->where('id',$applys['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                                        }
                                    }
                            
                                    // 提交事务
                                    Db::commit();
                                    $applys = Db::name('th_apply')->where('th_number',$th_number)->where('user_id',$user_id)->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')->find();
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }

                            $orders = Db::name('order')
                                ->where('id',$applys['order_id'])
                                ->where('state',1)
                                ->where('user_id',$user_id)
                                ->field('id,ordernumber,fh_status')
                                ->find();

                            if($orders){
                                //申请已经撤销的情况 退款详情应当也能看到
                                if ($applys['apply_status'] == 4){
                                    $where = '1=1';
                                }else{
                                    $where = 'th)status <> 0';
                                }

                                $orgoods = Db::name('order_goods')
                                    ->where('id',$applys['orgoods_id'])
                                    ->where('order_id',$orders['id'])
                                    ->where($where)
                                    ->field('id,goods_id,goods_name,thumb_url,goods_attr_str,price,goods_num,order_id')
                                    ->find();

                                if($orgoods){
                                    $orgoods['ordernumber'] = $orders['ordernumber'];
                                    $orgoods['fh_status'] = $orders['fh_status'];
                                    if($this->webconfig['cos_file'] == '开启'){
                                        $a = config('tengxunyun')['cos_domain'];
                                    }else{
                                        $a = $this->webconfig['weburl'];
                                    }
                                    $orgoods['thumb_url'] = $a.'/'.$orgoods['thumb_url'];
                            
                                    $applys['apply_time'] = date('Y-m-d H:i:s',$applys['apply_time']);
                                    if(!empty($applys['agree_time'])){
                                        $applys['agree_time'] = date('Y-m-d H:i:s',$applys['agree_time']);
                                    }
                                    
                                    if(!empty($applys['refuse_time'])){
                                        $applys['refuse_time'] = date('Y-m-d H:i:s',$applys['refuse_time']);
                                    }
                                    
                                    if(!empty($applys['dcfh_time'])){
                                        $applys['dcfh_time'] = date('Y-m-d H:i:s',$applys['dcfh_time']);
                                    }
                                    
                                    if(!empty($applys['sh_time'])){
                                        $applys['sh_time'] = date('Y-m-d H:i:s',$applys['sh_time']);
                                    }
                                    
                                    if(!empty($applys['fh_time'])){
                                        $applys['fh_time'] = date('Y-m-d H:i:s',$applys['fh_time']);
                                    }
                                    
                                    if(!empty($applys['shou_time'])){
                                        $applys['shou_time'] = date('Y-m-d H:i:s',$applys['shou_time']);
                                    }
                                    
                                    if(!empty($applys['che_time'])){
                                        $applys['che_time'] = date('Y-m-d H:i:s',$applys['che_time']);
                                    }
                                    
                                    if(!empty($applys['com_time'])){
                                        $applys['com_time'] = date('Y-m-d H:i:s',$applys['com_time']);
                                    }
                                    
                                    $applys['thfw'] = Db::name('thcate')->where('id',$applys['thfw_id'])->value('cate_name');
                                    
                                    if($applys['apply_status'] == 0){
                                        $applys['zhuangtai'] = '待商家同意';
                                        $applys['filter'] = 1;
                                        $applys['sycheck_timeout'] = time2string($applys['check_timeout']-time());
                                    }elseif(in_array($applys['apply_status'], array(1,3))){
                                        switch ($applys['thfw_id']){
                                            case 1:
                                                if($applys['apply_status'] == 1){
                                                    $applys['zhuangtai'] = '商家已同意（退款中）';
                                                    $applys['filter'] = 2;
                                                    $applys['syshoptui_timeout'] = time2string($applys['shoptui_timeout']-time());
                                                }elseif($applys['apply_status'] == 3){
                                                    $applys['zhuangtai'] = '退款已完成';
                                                    $applys['filter'] = 3;
                                                }
                                                break;
                                            case 2:
                                                if($applys['apply_status'] == 1){
                                                    if($applys['dcfh_status'] == 0){
                                                        $applys['zhuangtai'] = '商家已同意（填写退货物流信息）';
                                                        $applys['filter'] = 4;
                                                        $applys['syyhfh_timeout'] = time2string($applys['yhfh_timeout']-time());
                                                    }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 0){
                                                        $applys['zhuangtai'] = '等待商家确认收货（退货退款中）';
                                                        $applys['filter'] = 5;
                                                    }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 1){
                                                        $applys['zhuangtai'] = '商家已收货（退货退款中）';
                                                        $applys['filter'] = 6;
                                                        $applys['syshoptui_timeout'] = time2string($applys['shoptui_timeout']-time());
                                                    }
                                                }elseif($applys['apply_status'] == 3){
                                                    $applys['zhuangtai'] = '退货退款已完成';
                                                    $applys['filter'] = 7;
                                                }
                                                break;
                                            case 3:
                                                if($applys['apply_status'] == 1){
                                                    if($applys['dcfh_status'] == 0){
                                                        $applys['zhuangtai'] = '商家已同意（填写退货物流信息）';
                                                        $applys['filter'] = 8;
                                                        $applys['syyhfh_timeout'] = time2string($applys['yhfh_timeout']-time());
                                                    }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 0){
                                                        $applys['zhuangtai'] = '等待商家确认收货（换货中）';
                                                        $applys['filter'] = 9;
                                                    }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['fh_status'] == 0){
                                                        $applys['zhuangtai'] = '商家已收货（换货中）';
                                                        $applys['filter'] = 10;
                                                    }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['fh_status'] == 1){
                                                        $applys['zhuangtai'] = '商家已发货（换货中）';
                                                        $applys['filter'] = 11;
                                                        $applys['syyhshou_timeout'] = time2string($applys['yhshou_timeout']-time());
                                                    }
                                                }elseif($applys['apply_status'] == 3){
                                                    $applys['zhuangtai'] = '换货已完成';
                                                    $applys['filter'] = 12;
                                                }
                                                break;
                                        }
                                    }elseif($applys['apply_status'] == 2){
                                        $applys['zhuangtai'] = '商家已拒绝';
                                        $applys['filter'] = 13;
                                    }elseif($applys['apply_status'] == 4){
                                        $applys['zhuangtai'] = '已撤销';
                                        $applys['filter'] = 14;
                                    }
                                    
                                    $thpicres = Db::name('thapply_pic')->where('th_id',$applys['id'])->select();
                                    
                                    if(in_array($applys['thfw_id'],array(2,3)) && $applys['apply_status'] == 1){
                                        $shopdzs = Db::name('shop_shdz')->where('shop_id',$applys['shop_id'])->find();
                                    }else{
                                        $shopdzs = array();
                                    }
                                    
                                    $tuiwulius = array();
                                    if(in_array($applys['thfw_id'], array(2,3)) && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 1){
                                        $tuiwulius = Db::name('tui_wuliu')->where('th_id',$applys['id'])->find();
                                    }
                                    
                                    $wulius = array();
                                    if($applys['thfw_id'] == 3 && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['fh_status'] == 1){
                                        $wulius = Db::name('huan_wuliu')->alias('a')->field('a.*,b.log_name,b.telephone')->join('sp_logistics b','a.ps_id = b.id','LEFT')->where('a.th_id',$applys['id'])->find();
                                    }
                                    
                                    $thapplyinfo = array('orgoods'=>$orgoods,'applys'=>$applys,'thpicres'=>$thpicres,'shopdzs'=>$shopdzs,'tuiwulius'=>$tuiwulius,'wulius'=>$wulius);
                                    $value = array('status'=>200,'mess'=>'获取退换货申请信息成功','data'=>$thapplyinfo);                            
                                }else{
                                    $value = array('status'=>400,'mess'=>'订单商品信息错误','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'订单信息错误','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关退换货信息'.$user_id,'data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少退换订单编号','data'=>array('status'=>400));
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

    
    //支付获取订单信息
    public function zhifuorder(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.order_nums/a') && is_array(input('post.order_nums/a'))){
                        if(input('post.zf_type') && in_array(input('post.zf_type'), array(1,2,3,4,5,6,7))){
                            $zf_type = input('post.zf_type');
                            $order_nums = input('post.order_nums/a');
                            $order_nums = array_unique($order_nums);
                            
                            $total_price = 0;
                            $orderids = array();
                            $outarr = array();
                            
                            foreach ($order_nums as $v){
                                $orders = Db::name('order')->where('ordernumber',$v)->where('user_id',$user_id)->where('state',0)->where('fh_status',0)->where('order_status',0)->where('is_show',1)->field('id,total_price,shop_id,time_out')->find();
                                if($orders){
                                    if($orders['time_out'] > time()){
                                        $total_price+=$orders['total_price'];
                                        $orderids[] = $orders['id'];
                                        $outarr[] = $orders['time_out'];
                                    }else{
                                        $value = array('status'=>400,'mess'=>'订单已过期，操作失败','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'订单信息错误，操作失败','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }
                            
                            $order_number = 'Z'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                            $dingdan = Db::name('order_zong')->where('order_number',$order_number)->find();
                            if(!$dingdan){
                                $datainfo = array();
                                $datainfo['order_number'] = $order_number;
                                $datainfo['total_price'] = $total_price;
                                $datainfo['state'] = 0;
                                $datainfo['zf_type'] = 0;
                                $datainfo['user_id'] = $user_id;
                                $datainfo['addtime'] = time();
                                $datainfo['time_out'] = min($outarr);

                                // 启动事务
                                Db::startTrans();
                                try{
                                    $zong_id = Db::name('order_zong')->insertGetId($datainfo);
                                    if($zong_id){
                                        foreach ($orderids as $v2){
                                            Db::name('order')->update(array('zong_id'=>$zong_id,'id'=>$v2));
                                        }
                                    }
                                    try {
                                        (new OrderAfterLogic())->payOrderOp($order_nums[0]);
                                    } catch (\Throwable $th) {
                                        //throw $th;
                                    }
                                    // 提交事务
                                    Db::commit();
 
                                    $orderinfos = array('order_number'=>$order_number,'zf_type'=>$zf_type);
                                    $value = array('status'=>200,'mess'=>'获取订单信息成功','data'=>$orderinfos);
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'获取订单信息失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'支付方式参数错误','data'=>array('status'=>400));
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
    
    //支付接口
    public function zhifu(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.order_num')){
                        if(input('post.zf_type') && input('post.zf_type') == 1){
                            $zf_type = input('post.zf_type');
                            $order_num = input('post.order_num');
                            $orderinfos = Db::name('order')->where('ordernumber',$order_num)->where('user_id',$user_id)->where('state',0)->where('fh_status',0)->where('order_status',0)->find();
                            if($orderinfos){
                                $order_number = 'D'.date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                $dingdan = Db::name('order_zong')->where('order_number',$order_number)->find();
                                if(!$dingdan){
                                    $datainfo = array();
                                    $datainfo['order_number'] = $order_number;
                                    $datainfo['contacts'] = $orderinfos['contacts'];
                                    $datainfo['telephone'] = $orderinfos['telephone'];
                                    $datainfo['pro_id'] = $orderinfos['pro_id'];
                                    $datainfo['city_id'] = $orderinfos['city_id'];
                                    $datainfo['area_id'] = $orderinfos['area_id'];
                                    $datainfo['province'] = $orderinfos['province'];
                                    $datainfo['city'] = $orderinfos['city'];
                                    $datainfo['area'] = $orderinfos['area'];
                                    $datainfo['address'] = $orderinfos['address'];
                                    $datainfo['dz_id'] = $orderinfos['dz_id'];
                                    $datainfo['freight'] = $orderinfos['freight'];
                                    $datainfo['total_price'] = $orderinfos['total_price'];
                                    $datainfo['state'] = 0;
                                    $datainfo['zf_type'] = 0;
                                    $datainfo['user_id'] = $user_id;
                                    $datainfo['addtime'] = time();
                                    
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        $zong_id = Db::name('order_zong')->insertGetId($datainfo);
                                        if($zong_id){
                                            Db::name('order')->update(array('zong_id'=>$zong_id,'id'=>$orderinfos['id']));
                                        }
                                        // 提交事务
                                        Db::commit();
                                        $webconfig = $this->webconfig;
                                        
                                        $orderzong_infos = Db::name('order_zong')->where('id',$zong_id)->where('state',0)->where('user_id',$user_id)->field('order_number,total_price')->find();
                                        //获取订单号
                                        $reoderSn = $orderzong_infos['order_number'];
                                        //获取支付金额
                                        $money = $orderzong_infos['total_price'];
                                        
                                        $wx = new Wxpay();
                                         
                                        $body = '商品支付';//支付说明
                                        
                                        $out_trade_no = $reoderSn;//订单号
                                        
                                        $total_fee = $money * 100;//支付金额(乘以100)
                                        
                                        $notify_url = $webconfig['weburl'].'/apicloud/Wxpaynotify/notify';//回调地址
                                        
                                        $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $notify_url);//调用微信支付的方法
                                        if($order['prepay_id']){
                                            //判断返回参数中是否有prepay_id
                                            $order1 = $wx->getOrder($order['prepay_id']);//执行二次签名返回参数
                                            $value = array('status'=>200,'mess'=>'成功','data'=>array('ordernumber'=>$orderzong_infos['order_number'],'wxpayinfos'=>$order1));
                                        }else{
                                            $value = array('status'=>400,'mess'=>$order['err_code_des'],'data'=>array('status'=>400));
                                        }
                                    } catch (\Exception $e) {
                                        // 回滚事务
                                        Db::rollback();
                                        $value = array('status'=>400,'mess'=>'支付失败','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关订单','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'支付方式参数错误','data'=>array('status'=>400));
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
    
    public function warnToShop(){
        if(!request()->isPost()) return json(array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400)));
        if(!input('post.token')) return json(array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400)));
        $gongyong = new GongyongMx();
        $result = $gongyong->apivalidate();
        if($result['status'] != 200) return json($result);
        $order_id = input('post.order_id');
        $order_num = input('post.order_num');
        if(!$order_num) return json(array('status'=>400,'mess'=>'缺少订单号','data'=>array('status'=>400)));

        $ext_time = 60 * 60 * 24;
        $time = time();
        $result = Model('warn_log')->where('order_id',$order_id)->order('id desc')->find();
        if($result && $time < $result['c_time'] + $ext_time) return json(array('status'=>400,'mess'=>'每次提醒需要间隔24小时','data'=>array('status'=>400)));
        Model('warn_log')->save(['order_id'=>$order_id,'order_sn'=>$order_num,'c_time' => time()]);
        $logic = new OrderAfterLogic();
        $logic->warnSendOrder($order_num);
        return json(array('status'=>200,'mess'=>'提醒成功','data'=>array('status'=>200)));
    }
    
    //确认收货
    public function qrshouhuo(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.order_num')){
                        $order_num = input('post.order_num');
                        
                        $orders = Db::name('order')->where('ordernumber',$order_num)->where('user_id',$user_id)->where('state',1)->where('fh_status',1)->where('order_status',0)->where('is_show',1)->field('id,total_price,shop_id,shouhou,user_id,type')->find();
                        if($orders){
                            //if($orders['shouhou'] == 0){
                                // 启动事务
                                Db::startTrans();
                                try{
                                    Db::name('order')->where('id',$orders['id'])->update(array('order_status'=>1,'coll_time'=>time()));
                                
                                    $goodinfos = Db::name('order_goods')->where('order_id',$orders['id'])->field('id,goods_id,goods_attr_id,goods_num,th_status,shop_id')->select();
                                    if($goodinfos){
                                        foreach ($goodinfos as $val2){
                                            if(in_array($val2['th_status'], array(0,8))){
                                                $gdinfos = Db::name('goods')->where('id',$val2['goods_id'])->field('id,sale_num,deal_num,cate_id')->find();
                                                if($gdinfos){
                                                    $deal_num = $gdinfos['deal_num']+$val2['goods_num'];
                                                    $deal_lv = sprintf("%.2f",$deal_num/$gdinfos['sale_num'])*100;
                                                    Db::name('goods')->update(array('id'=>$val2['goods_id'],'deal_num'=>$deal_num,'deal_lv'=>$deal_lv));
                                                }
                                
                                                $spinfos = Db::name('shops')->where('id',$val2['shop_id'])->field('id,sale_num,deal_num')->find();
                                                if($spinfos){
                                                    $shop_deal_num = $spinfos['deal_num']+$val2['goods_num'];
                                                    $shop_deal_lv = sprintf("%.2f",$shop_deal_num/$spinfos['sale_num'])*100;
                                                    Db::name('shops')->update(array('id'=>$val2['shop_id'],'deal_num'=>$shop_deal_num,'deal_lv'=>$shop_deal_lv));
                                                }
                                            }
                                        }
                                    }
                                    $total_price = $orders['total_price'];
                                    $shop_wallet_id = Db::name('shop_wallet')
                                        ->where('shop_id',$orders['shop_id'])
                                        ->value('id');

                        //                                    //商户收入明细表.确认收货后进入钱包
                        //                                    Db::name('shop_detail')->insert([
                        //                                        'de_type' => 1,
                        //                                        'sr_type' => 1,
                        //                                        'price' => $total_price,
                        //                                        'order_type' => 1,
                        //                                        'order_id' => $orders['id'],
                        //                                        'shop_id' => $orders['shop_id'],
                        //                                        'wat_id' => $shop_wallet_id,
                        //                                        'time'  => time()
                        //                                    ]);
									//8购物消费（%）送积分(会员积分)
								// 	$num0 = $this->getIntegralRules(8);//获取积分
								// 	$num1 = sprintf("%.2f",$total_price*($num0/100));
								// 	$this->addIntegral($user_id,$num1,8,$orders['id']);
									
									//9购物一次（签收无退货） 10购物金额分（签收无退货）每100元(直播间粉丝积分)
								// 	$num3 = $this->getAliveIntegralRules(9);
								// 	$this->addAliveIntegral($user_id,$shopid,$room,$num3,9,$orders['id']);
									
								// 	$num4 = $this->getAliveIntegralRules(10);
								// 	$num5 = floor($total_price/100) * $num4;
								// 	$this->addAliveIntegral($user_id,$shopid,$room,$num5,10,$orders['id']);


//                                    $distributions = Db::name('travel_distribute_profit')
//                                        ->where('id',1)
//                                        ->find();

                                    // 土特产商品
                                    // $category_id_arr = Db::name('category')->where('pid', 348)->whereOr('id', 348)->column('id');

                                    // if(in_array($gdinfos['cate_id'], $category_id_arr)){
                                    //     // 土特产商品
                                    //      distribute_profit($orders['user_id'], $orders['id']);
                                    //      Db::name('order')
                                    //         ->where('id',$orders['id'])
                                    //         ->update(['settle_status'=>1]);
                                    // }

//									//分销
//									$distributions = Db::name('distribution')
//                                        ->where('id',1)
//                                        ->find();
//
//                            //									Db::name('order')->where('id',$orders['id'])->update(array('dakuan_status'=>1,'dakuan_time'=>time()));
//                                    $shops = Db::name('shops')
//                                        ->where('id',$orders['shop_id'])
//                                        ->field('id,indus_id,fenxiao')
//                                        ->find();
//                                    $member_is_vip = Db::name('member')->where('id',$user_id)->value('is_vip');
//									//查找分销配置是否打开
//									//if($distributions['is_open'] == 1){
//									if($distributions['is_open'] == 1 && $shops['fenxiao'] == 1){
//									    //if($total_price >= 10){
//									        $levelinfos = Db::name('member')
//                                                ->where('id',$orders['user_id'])
//                                                ->field('id,one_level,two_level')
//                                                ->find();
//									        //一级
//									        if($levelinfos['one_level']){
//                                                $one_wallets = Db::name('wallet')
//                                                    ->where('user_id',$levelinfos['one_level'])
//                                                    ->find();
//
//									            if($one_wallets){
//                                                    $onefen_price = Db::name('order')
//                                                        ->where('id',$orders['id'])
//                                                        ->value('onefen_price');
//                                                    if (empty($onefen_price)){
//                                                        $onefen_price = sprintf("%.2f",$total_price*($distributions['one_profit']/100));
//                                                    }
//
//									                Db::name('wallet')
//                                                        ->where('id',$one_wallets['id'])
//                                                        ->setInc('price', $onefen_price);
//									                Db::name('detail')
//                                                        ->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$onefen_price,'order_type'=>1,'order_id'=>$orders['id'],'user_id'=>$levelinfos['one_level'],'wat_id'=>$one_wallets['id'],'time'=>time()));
//									                Db::name('order')
//                                                        ->where('id',$orders['id'])
//                                                        ->update(['settle_status'=>1]);
//
//                            //									                Db::name('order')
//                            //                                                        ->where('id',$orders['id'])
//                            //                                                        ->update(array('onefen_id'=>$levelinfos['one_level'],'onefen_price'=>$onefen_price));
//                            //									                $total_price = $total_price-$onefen_price;
//									            }
//									        }
//
//									        //二级
//									        if($levelinfos['two_level']){
//									            $two_wallets = Db::name('wallet')
//                                                    ->where('user_id',$levelinfos['two_level'])
//                                                    ->find();
//									            if($two_wallets){
//                                                    $twofen_price = Db::name('order')
//                                                        ->where('id',$orders['id'])
//                                                        ->value('twofen_price');
//                                                    if (empty($total_price)){
//                                                        $twofen_price = sprintf("%.2f",$total_price*($distributions['two_profit']/100));
//                                                    }
//									                Db::name('wallet')
//                                                        ->where('id',$two_wallets['id'])
//                                                        ->setInc('price', $twofen_price);
//									                Db::name('detail')
//                                                        ->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$twofen_price,'order_type'=>1,'order_id'=>$orders['id'],'user_id'=>$levelinfos['two_level'],'wat_id'=>$two_wallets['id'],'time'=>time()));
//                                                    Db::name('order')
//                                                        ->where('id',$orders['id'])
//                                                        ->update(['settle_status'=>1]);
//
//                            //									                Db::name('order')
//                            //                                                        ->where('id',$orders['id'])
//                            //                                                        ->update(array('twofen_id'=>$levelinfos['two_level'],'twofen_price'=>$twofen_price));
//                            //									                $total_price = $total_price-$twofen_price;
//									            }
//									        }
//									    //}
//									}
									
                                    // 提交事务
                                    Db::commit();
                                    $value = array('status'=>200,'mess'=>'确认收货成功','data'=>array('status'=>200));
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'确认收货失败','data'=>array('status'=>400));
                                }
                            //}else{
                            //    $value = array('status'=>400,'mess'=>'订单存在未完成的售后商品，确认收货失败','data'=>array('status'=>400));
                            //    return json($value);
                            //}
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关类型订单信息','data'=>array('status'=>400));
                            return json($value);
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

    public static function checkVip($user_id,$order_id){

    }
    
}