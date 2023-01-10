<?php
namespace app\apicloud\controller;

use app\common\logic\OrderAfterLogic;

use think\Controller;
use think\Db;
use think\Exception;

class Wxpaynotify extends Controller{
    public function notify(){
        $xml = file_get_contents('php://input');
        // 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了
        file_put_contents('./log.txt',$xml,FILE_APPEND);
        //将服务器返回的XML数据转化为数组
        $data = xmlToArray($xml);

        // 保存微信服务器返回的签名sign
        $data_sign = $data['sign'];
        // sign不参与签名算法
        unset($data['sign']);
        $wx = new Wxpay;
        $sign = $wx->getSign($data);

        // 判断签名是否正确  判断支付状态
        if ( ($sign===$data_sign) && ($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS') ) {
            $result = $data;
            // 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了
            //file_put_contents('./Api/wxpay/logs/log1.txt',$xml,FILE_APPEND);

            //获取服务器返回的数据
            $order_sn = $data['out_trade_no'];  //订单单号
            $total_fee = $data['total_fee'];    //付款金额

            $orderzongs = Db::name('order_zong')->where('order_number',$order_sn)->where('state',0)->find();

            if($orderzongs){
                $orderes = Db::name('order')->where('zong_id',$orderzongs['id'])->where('state',0)->where('fh_status',0)->where('order_status',0)->select();
                try {
                    foreach ($orderes as $value) {
                        (new OrderAfterLogic())->payOrderOp($value['ordernumber']);
                    }
                } catch (\Throwable $th) {
                    //throw $th;
                }

                if($orderes){
                    $leixing = 1;

                    // 启动事务
                    Db::startTrans();
                    try{
                        Db::name('order_zong')->where('id', $orderzongs['id'])->update(array('state'=>1,'zf_type'=>2,'pay_time'=>time()));
                        $pt_wallets = Db::name('pt_wallet')->where('id',1)->find();
                        if($pt_wallets){
                            Db::name('pt_wallet')->where('id',1)->setInc('price', $orderzongs['total_price']);
                            Db::name('pt_detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$orderzongs['total_price'],'order_type'=>1,'order_id'=>$orderzongs['id'],'wat_id'=>$pt_wallets['id'],'time'=>time()));
                        }
                        $flag = false;
                        foreach ($orderes as $vr){

                            Db::name('order')->where('id', $vr['id'])->update(array('state'=>1,'zf_type'=>2,'pay_time'=>time()));
                            $goodinfos = Db::name('order_goods')->where('order_id',$vr['id'])->field('id,goods_id,goods_num,hd_type,hd_id,shop_id')->select();

                            foreach ($goodinfos as $kd => $vd){
                                $goods = Db::name('goods')->where('id',$vd['goods_id'])->field('id,cate_id')->find();
                                if($goods){
                                    Db::name('goods')->where('id',$vd['goods_id'])->setInc('sale_num',$vd['goods_num']);
                                }
                                $shops = Db::name('shops')->where('id',$vd['shop_id'])->field('id')->find();
                                if($shops){
                                    Db::name('shops')->where('id',$vd['shop_id'])->setInc('sale_num',$vd['goods_num']);
                                }



                                $distributions = Db::name('travel_distribute_profit')
                                    ->where('id',1)
                                    ->find();

                                // 土特产商品
                                $category_id_arr = Db::name('category')->where('pid', 348)->whereOr('id', 348)->column('id');

                                if ($vd['goods_id'] == $distributions['goods_id']){
                                    // VIP商品
                                    // 非实物商品直接更新为已确认收货
                                    $ress = Db::name('order')->where('order_status', 0)->where('id',$vr['id'])->update(array('order_status'=>1,'fh_status'=>1,'coll_time'=>time()));

                                    if($ress){
                                        $userInfo = Db::name('member')->where('id',$orderzongs['user_id'])->find();

                                        if($userInfo['is_vip'] == 0){
                                            $update = Db::name('member')->where('id',$vr['user_id'])->update(['is_vip'=>1]);

                                            if($update){
                                                // 代理等级验证升级...
                                                $state = uplevel_agent($vr['user_id'], $vr['id']);

                                                if($state == true)
                                                {
                                                    distribute_profit($vr['user_id'], $vr['id']);

                                                    $vip_goods_data['use_user_id'] = $vr['user_id'];
                                                    $vip_goods_data['use'] = 1;
                                                    $vip_goods_data['token'] = 'TOKEN'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                                    $vip_goods_data['activetime'] = time();

                                                }
                                                else
                                                {
                                                    throw new Exception('代理等级升级失败');
                                                }
                                            }
                                            else{
                                                throw new Exception('升级经销商失败');
                                            }

                                        }
                                        else
                                        {
                                            distribute_profit($vr['user_id'], $vr['id']);
                                        }

                                        $vip_goods_data['phone'] = $vr['telephone'];
                                        $vip_goods_data['name'] = $vr['contacts'];
                                        $vip_goods_data['user_id'] = $vr['user_id'];
                                        $vip_goods_data['goods_price'] = $vr['goods_price'];
                                        $vip_goods_data['card_no'] = 'VIP'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                        $vip_goods_data['order_id'] = $vr['id'];
                                        $vip_goods_data['addtime'] = $vip_goods_data['uptime'] = time();

                                        $res = Db::name('vip_rights_card')->insert($vip_goods_data);

                                        if(!$res)
                                        {
                                            throw new Exception('VIP权益卡添加失败');
                                        }
                                    }
                                }
                                else{
                                    // 非VIP商品
                                    if(in_array($goods['cate_id'], $category_id_arr)){
                                        // 土特产商品
                                        // distribute_profit($user_id, $vr['id']);
                                    }
                                    else{
                                        // 旅游商品
                                        // 非实物商品直接更新为已确认收货
                                        $ress = Db::name('order')->where('order_status', 0)->where('id', $vr['id'])->update(array('order_status'=>1,'fh_status'=>1,'coll_time'=>time()));

                                        if($ress){
                                            distribute_profit($orderzongs['user_id'], $vr['id']);
                                        }
                                    }
                                }
//                                $distributions = Db::name('distribution')
//                                    ->where('id',1)
//                                    ->find();
//                                if ($vd['goods_id'] == $distributions['goods_id']){
//                                    $flag = true;
//                                }
//                                if ($flag){
//                                    //当为购买会员的订单时，更新订单状态为已确认收货
//                                    Db::name('order')->update(array('id'=>$vr['id'],'order_status'=>1,'fh_status'=>1,'coll_time'=>time()));
//                                    $update = Db::name('member')->where('id',$orderzongs['user_id'])->update(['is_vip'=>1]);
//                                    if ($update){
//                                        //----------推荐人购买vip商品上级获得奖励begin
//                                        $levelinfos = Db::name('member')
//                                            ->where('id',$orderzongs['user_id'])
//                                            ->field('id,one_level,two_level')
//                                            ->find();
//                                        $tuan_num = Db::name('member_friend')
//                                            ->field('GROUP_CONCAT(fid) as ids')
//                                            ->where(['uid'=>$orderzongs['user_id'],'level'=>1])
//                                            ->find();
//                                        //找出已经够买vip商品的订单
//                                        if ($tuan_num['ids']){
//                                            $num = Db::name('member')->whereIn('id',$tuan_num['ids'])
//                                                ->where('is_vip',1)
//                                                ->count();
//                                        }
//                                        if ($num == 10 && $levelinfos['is_vip_two'] == 0){
//                                            $update_two = Db::name('member')
//                                                ->where('id',$orderzongs['user_id'])
//                                                ->update(['is_vip_two' => 1]);
//                                            if ($update_two){
//                                                //记录升级记录升级到第二级 运营商
//                                                Db::name('member_up_log')->insert([
//                                                    'user_id'   => $orderzongs['user_id'],
//                                                    'goods_id'  => $distributions['goods_id'],
//                                                    'level' => 2,
//                                                    'create_time'   => time(),
//                                                ]);
//                                            }
//                                        }
//
//                                        //一级
//                                        if($levelinfos['one_level']){
//                                            $one_wallets = Db::name('wallet')
//                                                ->where('user_id',$levelinfos['one_level'])
//                                                ->find();
//
//                                            if($one_wallets){
//                                                if ($num<11){
//                                                    $distributions_one_price = $distributions['one_vip'];
//                                                }else{
//                                                    $distributions_one_price = $distributions['ten_one_vip'];
//                                                }
//                                                Db::name('wallet')
//                                                    ->where('id',$one_wallets['id'])
//                                                    ->setInc('price', $distributions_one_price);
//                                                Db::name('detail')
//                                                    ->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$distributions_one_price,'order_type'=>1,'order_id'=>$vr['id'],'user_id'=>$levelinfos['one_level'],'wat_id'=>$one_wallets['id'],'time'=>time()));
//
//                                            }
//                                        }
//
//                                        //二级
//                                        if($levelinfos['two_level']){
//                                            $two_wallets = Db::name('wallet')
//                                                ->where('user_id',$levelinfos['two_level'])
//                                                ->find();
//                                            if($two_wallets){
//                                                if ($num<11){
//                                                    $distributions_two_price = $distributions['two_vip'];
//                                                }else{
//                                                    $distributions_two_price = $distributions['ten_two_vip'];
//                                                }
//                                                Db::name('wallet')
//                                                    ->where('id',$two_wallets['id'])
//                                                    ->setInc('price', $distributions_two_price);
//                                                Db::name('detail')
//                                                    ->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$distributions_two_price,'order_type'=>1,'order_id'=>$vr['id'],'user_id'=>$levelinfos['two_level'],'wat_id'=>$two_wallets['id'],'time'=>time()));
//                                            }
//                                        }
//                                        //----------推荐人购买vip商品上级获得奖励end
//                                        //记录升级记录
//                                        Db::name('member_up_log')->insert([
//                                            'user_id'   => $orderzongs['user_id'],
//                                            'goods_id'  => $distributions['goods_id'],
//                                            'create_time'   => time(),
//                                        ]);
//                                    }
//                                }
                            }
                        }

                        if(count($orderes) == 1){
                            if($orderes[0]['order_type'] == 2){
                                $pinorder_id = $orderes[0]['id'];
                                $pin_type = $orderes[0]['pin_type'];
                                $pin_id = $orderes[0]['pin_id'];
                                $user_id = $orderes[0]['user_id'];

                                if($pin_type == 1){
                                    $pintuans = Db::name('pintuan')->where('id',$pin_id)->where('tz_id',$user_id)->find();
                                    $order_assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('order_id',$pinorder_id)->where('pin_type',1)->where('user_id',$user_id)->where('state',0)->where('tui_status',0)->find();
                                    Db::name('pintuan')->where('id',$pintuans['id'])->update(array('state'=>1,'tuan_num'=>1));
                                    Db::name('order_assemble')->where('id',$order_assembles['id'])->update(array('state'=>1));
                                }elseif($pin_type == 2){
                                    $pintuans = Db::name('pintuan')->where('id',$pin_id)->where('tz_id','neq',$user_id)->find();
                                    $order_assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('order_id',$pinorder_id)->where('pin_type',2)->where('user_id',$user_id)->where('state',0)->where('tui_status',0)->find();
                                    Db::name('order_assemble')->where('id',$order_assembles['id'])->update(array('state'=>1));
                                    Db::name('pintuan')->where('id',$pintuans['id'])->seInc('tuan_num',1);

                                    $tuannums = Db::name('pintuan')->lock(true)->where('id',$pintuans['id'])->field('pin_num,tuan_num')->find();
                                    if($tuannums['pin_num'] <= $tuannums['tuan_num']){
                                        Db::name('pintuan')->where('id',$pintuans['id'])->update(array('pin_status'=>1,'com_time'=>time()));
                                    }
                                }
                            }
                        }
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        $err_log = json_encode(['order_number'=>$order_sn,'err'=>$e->getMessage()]);
                        Db::name('warn_log')
                            ->insert(['order_id'=>1,'order_sn'=>$order_sn,'c_time'=>time(),'desc'=>$err_log]);
                        $result = false;
                        // 回滚事务
                        Db::rollback();
                    }
                }
            }else{
                $result = false;
            }
        }else{
            $result = false;
        }
        // 返回状态给微信服务器
        if ($result) {
            $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }else{
            $str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
        }
        echo $str;
    }

}