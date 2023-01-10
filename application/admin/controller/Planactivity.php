<?php
namespace app\admin\controller;
use app\admin\controller\Basic;
use think\Db;

class Planactivity extends Basic{
    
    //秒杀、团购、拼团活动结束和开始后自动更新参与商品展示价格
    public function planhd(){
        $nowtime = time();

        //过期秒杀信息
        $end_rushres = Db::name('rush_activity')->where('checked',1)->where('hd_bs',1)->where('is_show',1)->where('end_time','elt',$nowtime)->field('id,goods_id')->select();
        if($end_rushres){
            foreach ($end_rushres as $vr){
                $rumin_price = Db::name('goods')->where('id',$vr['goods_id'])->value('min_price');
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('rush_activity')->update(array('hd_bs'=>2,'id'=>$vr['id']));
                    Db::name('goods')->update(array('id'=>$vr['goods_id'],'zs_price'=>$rumin_price,'is_activity'=>0));
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
        }
        
        //过期团购信息
        $end_groupres = Db::name('group_buy')->where('checked',1)->where('hd_bs',1)->where('is_show',1)->where('end_time','elt',$nowtime)->field('id,goods_id')->select();
        if($end_groupres){
            foreach ($end_groupres as $vp){
                $acmin_price = Db::name('goods')->where('id',$vp['goods_id'])->value('min_price');
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('group_buy')->update(array('hd_bs'=>2,'id'=>$vp['id']));
                    Db::name('goods')->update(array('id'=>$vp['goods_id'],'zs_price'=>$acmin_price,'is_activity'=>0));
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
        }
        
        //过期拼团信息
        $end_pinres = Db::name('assemble')->where('checked',1)->where('hd_bs',1)->where('is_show',1)->where('end_time','elt',$nowtime)->field('id,goods_id')->select();
        if($end_pinres){
            foreach ($end_pinres as $va){
                $asmin_price = Db::name('goods')->where('id',$va['goods_id'])->value('min_price');
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('assemble')->update(array('hd_bs'=>2,'id'=>$va['id']));
                    Db::name('goods')->update(array('id'=>$va['goods_id'],'zs_price'=>$asmin_price,'is_activity'=>0));
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
        }
        

        //秒杀中信息
        $rushres = Db::name('rush_activity')->where('checked',1)->where('hd_bs',0)->where('is_show',1)->where('start_time','elt',$nowtime)->where('end_time','gt',$nowtime)->field('id,goods_id,goods_attr,num,price')->select();
        if($rushres){
            foreach($rushres as $v){
                if($v['goods_attr']){
                    $number = Db::name('product')->where('goods_id',$v['goods_id'])->where('goods_attr',$v['goods_attr'])->field('id,goods_number')->find();
                    if(!empty($number) && $number['goods_number'] >= $v['num']){
                        // 启动事务
                        Db::startTrans();
                        try{
                            Db::name('rush_activity')->update(array('hd_bs'=>1,'id'=>$v['id']));
                            Db::name('goods')->update(array('id'=>$v['goods_id'],'zs_price'=>$v['price'],'is_activity'=>1));
                            // 提交事务
                            Db::commit();
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                        }                      
                    }else{
                        //给商家后台推送商品实际库存小于秒杀报名库存信息
                        Db::name('rush_activity')->update(array('checked'=>2,'id'=>$v['id']));
                        
                    }
                }else{
                    $goods_number = Db::name('product')->where('goods_id',$v['goods_id'])->sum('goods_number');
                    if(!empty($goods_number) && $goods_number >= $v['num']){
                        // 启动事务
                        Db::startTrans();
                        try{
                            Db::name('rush_activity')->update(array('hd_bs'=>1,'id'=>$v['id']));
                            Db::name('goods')->update(array('id'=>$v['goods_id'],'zs_price'=>$v['price'],'is_activity'=>1));
                            // 提交事务
                            Db::commit();
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                        }
                    }else{
                        //给商家后台推送商品实际库存小于秒杀报名库存信息
                        Db::name('rush_activity')->update(array('checked'=>2,'id'=>$v['id']));
                        
                    }
                }
            }
        }
        
        //团购中信息
        $groupres = Db::name('group_buy')->where('checked',1)->where('hd_bs',0)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,price')->select();
        if($groupres){
            foreach ($groupres as $val){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('group_buy')->update(array('hd_bs'=>1,'id'=>$val['id']));
                    Db::name('goods')->update(array('id'=>$val['goods_id'],'zs_price'=>$val['price'],'is_activity'=>2));
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
        }
        
        //拼团中信息
        $pinres = Db::name('assemble')->where('checked',1)->where('hd_bs',0)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,price')->select();
        if($pinres){
            foreach ($pinres as $val2){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('assemble')->update(array('hd_bs'=>1,'id'=>$val2['id']));
                    Db::name('goods')->update(array('id'=>$val2['goods_id'],'zs_price'=>$val2['price'],'is_activity'=>3));
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