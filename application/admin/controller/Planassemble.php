<?php
namespace app\admin\controller;
use app\admin\controller\Basic;
use think\Db;

class Planassemble extends Basic{
    
    //过期自动拼团失败
    public function pintuanout(){
        // 启动事务
        Db::startTrans();
        try{
            $pintuanres = Db::name('pintuan')->lock(true)->where('timeout','elt',time())->where('state',1)->where('pin_status',0)->field('id,pin_num,tuan_num,pin_status,timeout')->select();
            if($pintuanres){
                foreach ($pintuanres as $v){
                    Db::name('pintuan')->where('id',$v['id'])->update(array('pin_status'=>2));
                    
                    $order_assembleres = Db::name('order_assemble')->where('pin_id',$v['id'])->where('state',1)->where('tui_status',0)->select();
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
                }
            }
        
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }
    
    //开启退款状态的拼团单给用户钱包打款
    public function dakuanuser(){
        $pintuanres = Db::name('pintuan')->where('state',1)->where('pin_status',2)->where('tk_status',0)->field('id,pin_num,tuan_num,pin_status,timeout')->select();
        if($pintuanres){
            foreach ($pintuanres as $v){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('pintuan')->where('id',$v['id'])->update(array('tk_status'=>1,'tk_time'=>time()));
                    
                    $order_assembleres = Db::name('order_assemble')->where('pin_id',$v['id'])->where('state',1)->where('tui_status',1)->where('dakuan_status',0)->select();
                    if($order_assembleres){
                        foreach ($order_assembleres as $vrc){
                            $pinorders = Db::name('order')->where('id',$vrc['order_id'])->where('state',1)->where('fh_status',0)->where('order_status',2)->where('order_type',2)->where('is_show',1)->field('id,total_price,user_id')->find();
                            if($pinorders){
                                Db::name('order_assemble')->where('id',$vrc['id'])->update(array('dakuan_status'=>1,'dakuan_time'=>time()));
                    
                                $wallets = Db::name('wallet')->where('user_id',$pinorders['user_id'])->find();
                                if($wallets){
                                    Db::name('wallet')->where('id',$wallets['id'])->setInc('price', $pinorders['total_price']);
                                    Db::name('detail')->insert(array('de_type'=>1,'sr_type'=>2,'price'=>$pinorders['total_price'],'order_type'=>4,'order_id'=>$pinorders['id'],'user_id'=>$pinorders['user_id'],'wat_id'=>$wallets['id'],'time'=>time()));
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
    }
    
    
}