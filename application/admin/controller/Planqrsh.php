<?php
namespace app\admin\controller;
use app\admin\controller\Basic;
use think\Db;

class Planqrsh extends Basic{
    
    public function index(){
        // 启动事务
        Db::startTrans();
        try{
            //过期自动收货
            $orderids = Db::name('order')->lock(true)->where('addtime','egt',time()-7200)->where('zdsh_time','elt',time())->where('state',1)->where('fh_status',1)->where('order_status',0)->where('shouhou',0)->where('is_show',1)->field('id,total_price,shop_id')->select();
            if($orderids){
                foreach ($orderids as $v){
                    Db::name('order')->where('id',$v['id'])->update(array('order_status'=>1,'coll_time'=>time()));
                    
                    if($v['shop_id'] != 1){
                        $shops = Db::name('shops')->where('id',$v['shop_id'])->field('id,indus_id')->find();
                        if($shops){
                            $tui_price = 0;
                    
                            $applys = Db::name('th_apply')->where('order_id',$v['id'])->where('thfw_id','in','1,2')->where('apply_status',3)->field('id,tui_price')->find();
                            if($applys){
                                $tui_price = Db::name('th_apply')->where('order_id',$v['id'])->where('thfw_id','in','1,2')->where('apply_status',3)->sum('tui_price');
                            }
                    
                            $total_price = $v['total_price']-$tui_price;
                            $remind = Db::name('industry')->where('id',$shops['indus_id'])->value('remind');
                            if($remind){
                                $remind_lv = $remind/1000;
                                $remind_price = sprintf("%.2f",$total_price*$remind_lv);
                                $total_price = sprintf("%.2f",$total_price-$remind_price);
                            }
                            $shop_wallets = Db::name('shop_wallet')->where('shop_id',$shops['id'])->find();
                            if($shop_wallets){
                                Db::name('shop_detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$total_price,'order_type'=>1,'order_id'=>$v['id'],'shop_id'=>$shops['id'],'wat_id'=>$shop_wallets['id'],'time'=>time()));
                                Db::name('shop_wallet')->where('id',$shop_wallets['id'])->setInc('price',$total_price);
                            }
                        }
                    }
                    
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