<?php
namespace app\apicloud\controller;
use think\Controller;
use think\Db;
use app\admin\services\Upush;
use app\apicloud\model\AliPayHelper;

class AliPay extends Controller{
    //商品购买支付宝支付回调地址
    public function aliNotify(){
        $data = $_POST;
        file_put_contents('./alipay_test.log', $data, FILE_APPEND);
        file_put_contents('./alipay_test11.log', file_get_contents("php://input"), FILE_APPEND);

        $AliPayHelper = new AliPayHelper();
        $return = $AliPayHelper->payReturn($data);
        $order_sn = $data['out_trade_no'];  //订单单号 Z2022090116431551555149
        $price = $data['total_amount'];
        if($return == 1){   //验证成功
            $this->ChangeOrder($order_sn,$price);
            echo 'success';
        }else{
            echo 'fail';
        }
    }

    /****
     * 更改订单状态处理
     */
    private function ChangeOrder($order_sn,$total_fee){// Z2022090116431551555149  1.00
        $client_id = "";
        $orderzongs = Db::name('order_zong')->where('order_number',$order_sn)->where('state',0)->find();

        if($orderzongs){
            $orderes = Db::name('order')->where('zong_id',$orderzongs['id'])->where('state',0)->where('fh_status',0)->where('order_status',0)->select();
            if($orderes){
                $member_data = DB::name('member')->where(['id'=>$orders[0]['user_id']])->find();
                $client_id = $member_data['appinfo_code']; 
                $leixing = 1;
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('order_zong')->update(array('id'=>$orderzongs['id'],'state'=>1,'zf_type'=>2,'pay_time'=>time()));
                    $pt_wallets = Db::name('pt_wallet')->where('id',1)->find();
                    if($pt_wallets){
                        Db::name('pt_wallet')->where('id',1)->setInc('price', $orderzongs['total_price']);
                        Db::name('pt_detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$orderzongs['total_price'],'order_type'=>1,'order_id'=>$orderzongs['id'],'wat_id'=>$pt_wallets['id'],'time'=>time()));
                    }
                
                    foreach ($orderes as $vr){
                        $uid = $vr['user_id'];
                        Db::name('order')->update(array('id'=>$vr['id'],'state'=>1,'zf_type'=>2,'pay_time'=>time()));

                        // $goodinfos = Db::name('order_goods')->where('order_id',$vr['id'])->field('id,goods_id,goods_num,hd_type,hd_id,shop_id,type')->select();
                        $goodinfos = Db::name('order_goods')->alias('og')->where('og.order_id', $vr['id'])
                            ->field('og.goods_id, og.id, og.hd_id, og.hd_type, og.goods_num, og.shop_id, g.type')
                            ->join('goods g', 'g.id = og.goods_id', 'INNER')->select();
                           
                        foreach ($goodinfos as $kd => $vd){ 
                            if($vd['type']==3){
                                $remark = "购买积分商品";
                            }
                            if($vd['type']==2){
                                $remark = "购买激活商品";
                            }
                            if($vd['type']==1){
                                $remark = "购买解锁商品";
							    Db::name('member')->where('id', $uid)->update([
							       'sale_earnings' => 0,
							       'wash_amount' => 0
							    ]);
                            }
                            $sdfdsf = new \app\apicloud\controller\Common;
         
                            if($vd['type']==2 || $vd['type']==3){
                                $total_price = $orderzongs['total_price'];
                                if($vd['type'] == 3){
                                    $wallet_info = Db::name('wallet')->where('user_id', $uid)->find();
                                    $ressss = Db::name('wallet')->where('user_id', $uid)->setInc('point', $total_price);
                                    if($ressss){
                                        $detailss = [
                                            'de_type'=>1,
                                            'sr_type'=>500,
                                            'before_price'=> $wallet_info['point'],
                                            'price'=>$total_price,
                                            'after_price'=> $wallet_info['point']+$total_price,
                                            'order_type'=>1,
                                            'order_id'=>$orderzongs['id'],
                                            'user_id'=>$uid,
                                            'wat_id'=>$wallet_info['id'],
                                            'time'=>time()
                                        ];
                                        
                                        $sdfdsf->addDetail($detailss);
                                    }
                                }
                                
							    $count1 = Db::name('wx_card')->where('user_id', $uid)->count();
							    $count2 = Db::name('zfb_card')->where('user_id', $uid)->count();
							    $count3 = Db::name('bank_card')->where('user_id', $uid)->count();
							    if($count1 || $count2 || $count3){
									$res = Db::name('member')->where('id', $uid)->where('true_name', '<>', '')->inc('reg_enable_deposit', $total_price)->update([
									    'reg_enable'=>1,
									    'reg_enable_deposit_count'=>0
									]);
									
									if(!$res){
									    Db::name('member')->where('id', $uid)->inc('reg_enable_deposit', $total_price)->inc('reg_enable_deposit_count', 1)->update();
									}
									else{
									    // 升级
								        $sdfdsf->wineGoodsUpgrade($uid);
									}
							    }
							    else{
							        Db::name('member')->where('id', $uid)->inc('reg_enable_deposit', $total_price)->inc('reg_enable_deposit_count', 1)->update();
							    }
                            }
                            
                            $goods = Db::name('goods')->where('id',$vd['goods_id'])->field('id')->find();
                            if($goods){
                                Db::name('goods')->where('id',$vd['goods_id'])->setInc('sale_num',$vd['goods_num']);
                            }
                            $shops = Db::name('shops')->where('id',$vd['shop_id'])->field('id')->find();
                            if($shops){
                                Db::name('shops')->where('id',$vd['shop_id'])->setInc('sale_num',$vd['goods_num']);
                            }
                        }
                    }
                    
                    
                    // if(count($orderes) == 1){
                    //     if($orderes[0]['order_type'] == 2){
                    //         $pinorder_id = $orderes[0]['id'];
                    //         $pin_type = $orderes[0]['pin_type'];
                    //         $pin_id = $orderes[0]['pin_id'];
                    //         $user_id = $orderes[0]['user_id'];
                            
                    //         if($pin_type == 1){
                    //             $pintuans = Db::name('pintuan')->where('id',$pin_id)->where('tz_id',$user_id)->find();
                    //             $order_assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('order_id',$pinorder_id)->where('pin_type',1)->where('user_id',$user_id)->where('state',0)->where('tui_status',0)->find();
                    //             Db::name('pintuan')->where('id',$pintuans['id'])->update(array('state'=>1,'tuan_num'=>1));
                    //             Db::name('order_assemble')->where('id',$order_assembles['id'])->update(array('state'=>1));
                    //         }elseif($pin_type == 2){
                    //             $pintuans = Db::name('pintuan')->where('id',$pin_id)->where('tz_id','neq',$user_id)->find();
                    //             $order_assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('order_id',$pinorder_id)->where('pin_type',2)->where('user_id',$user_id)->where('state',0)->where('tui_status',0)->find();
                    //             Db::name('order_assemble')->where('id',$order_assembles['id'])->update(array('state'=>1));
                    //             Db::name('pintuan')->where('id',$pintuans['id'])->seInc('tuan_num',1);
                                
                    //             $tuannums = Db::name('pintuan')->lock(true)->where('id',$pintuans['id'])->field('pin_num,tuan_num')->find();
                    //             if($tuannums['pin_num'] <= $tuannums['tuan_num']){
                    //                 Db::name('pintuan')->where('id',$pintuans['id'])->update(array('pin_status'=>1,'com_time'=>time()));
                    //             }
                    //         }
                    //     }
                    // }
                    // 提交事务
                    Db::commit();
                    //向此用户发送订单完成推送消息
                    // if($client_id){
                    //     $data = [
                    //         'cid' => '3cd8c5f87234f02f9a48a3a',
                    //         'title' => '您的订单支付成功了',
                    //         'content' => '订单编号：'.$order_sn,
                    //         'payload' => '{"title":"您的订单支付成功了","content":"订单编号："'.$order_sn.',"sound":"default","payload":"test","notice_type":"order","local":"1"}'
                    //     ];
                    //     $model = new Upush();
                    //     $model->pushOne($data);
                    // }
                    
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
        }
    }
}