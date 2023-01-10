<?php
/**
 * Created by PhpStorm.
 * User: 云
 * Date: 2022/1/4
 * Time: 23:26
 */

namespace app\apicloud\controller;

use EasyWeChat\Kernel\Exceptions\Exception;
use think\Db;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Cache;

class WineGoodsContract extends Common
{
    public function lst()
    {
        $post = input('post.');
        $user_id = Db::name('rxin')->where('token', $post['token'])->value('user_id');

        $list = Db::name('wine_goods_contract')->where('onsale', 1)->order('id asc')->select();

        if ($user_id){
            $yesterdayStart = strtotime('yesterday');
            $yesterdayEnd = strtotime('today')-1;
            
            $wine_goods_id_arr = Db::name('wine_order_record_contract')->where('buy_id', $user_id)
                                    ->column('wine_goods_id,status');
            
            for ($i=0; $i<count($list); $i++){
                $list[$i]['status'] = $wine_goods_id_arr[$list[$i]['id']];
            }
        }
        
        $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>$list);
        
        return json($value);
    }

    public function panduanshijian(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];

                $post = input('post.');
                
                 $ahead_record_stop_common_seconds = Db::name('config')->where('ename', 'ahead_record_stop')->value('value')*60;
                 $wine_deal_area_info = Db::name('wine_deal_area_contract')->where('id', $post['wine_deal_area_id'])->where('status', 1)->field('deposit,deal_area')->find();
                 if (is_null($wine_deal_area_info)){
                    $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
                    return json($value);
                 }
                 
                 $aheadHim = trim(explode('-', $wine_deal_area_info['deal_area'])[0]);
                 $aheadHimfff = trim(explode('-', $wine_deal_area_info['deal_area'])[1]);
                 $before_time_ke_record = strtotime(date('Y-m-d').' '.$aheadHim)-$ahead_record_stop_common_seconds;
                 $after_time_ke_record = strtotime(date('Y-m-d').' '.$aheadHimfff);
                 $count = Db::name('wine_order_record_contract')->where('wine_goods_id', $post['wine_goods_id'])->where('wine_deal_area_id', $post['wine_deal_area_id'])->where('buy_id', $user_id)->where('addtime', '>=', strtotime('today'))->count();
                 if($count == 0){
                    $value = array('status'=>400,'mess'=>'请先预约','data'=>array('status'=>400));
                 }
                 elseif(time()<$before_time_ke_record || time()>=$after_time_ke_record){
                     $value = array('status'=>400,'mess'=>'不在抢购时间内','data'=>array('status'=>400));
                 }
                 else{
                     $value = array('status'=>200,'mess'=>'正常','data'=>array('status'=>200));
                 }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    public function getWineIncomeList(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                
                // 确认时间倒计时(小时)
                $confirm_countdown = Db::name('config')->where('ename', 'confirm_timeout_contract')->value('value');
                $paytime_countdown = Db::name('config')->where('ename', 'pay_timeout_contract')->value('value');
                $time = time();

                $post = input('post.');
                $where=[];$where1=[];
                if($post['status'] == 1){
                    // $status = [$post['status'], 4, 5];
                    $status = [$post['status']];
                    $where1['wob.pay_status'] = 0;
                }
                elseif($post['status'] == 2){
                    $status = [1];
                    $where1['wob.pay_status'] = 1;
                }
                elseif($post['status'] == 3){
                    $status = [2];
                }
                else{
                    $status = [$post['status']];
                }
                
                $list = Db::name('wine_order_buyer_contract')->where('wob.delete', 0)->alias('wob')
//                    ->where('wob.wine_order_saler_id', $post['id'])
                    ->where('wob.buy_id', $user_id)
                    ->where('wob.status', 'in', $status)
                    ->where($where)
                    ->where($where1)
                    ->where('wob.top_stop', 0)
                    ->where('wob.transfer', 0)
                    ->join('member m', 'wob.buy_id = m.id', 'left')
                    ->join('wine_goods wg', 'wg.id = wob.wine_goods_id', 'left')
//                        ->join('member bm', 'wos.buy_id = bm.id', 'left'),bm.phone bphone,bm.user_name buser_name
//                    ->join('bank_card b', 'b.user_id = wos.sale_id', 'left')
                    ->field('wob.*,m.phone,m.user_name, wg.best_max_day')
                    ->order('id desc')
                    ->select();
                    
                $count1 = Db::name('wine_order_buyer_contract')->where('wob.delete', 0)->alias('wob')
//                    ->where('wob.wine_order_saler_id', $post['id'])
                    ->where('wob.buy_id', $user_id)
                    ->where('wob.status', 'in', [1])
                    ->where($where)
                    ->where('wob.pay_status', 0)
                    ->where('wob.top_stop', 0)
                    ->where('wob.transfer', 0)
                    ->join('member m', 'wob.buy_id = m.id', 'left')
                    ->join('wine_goods wg', 'wg.id = wob.wine_goods_id', 'left')
                    ->count();
                    
                $count2 = Db::name('wine_order_buyer_contract')->where('wob.delete', 0)->alias('wob')
//                    ->where('wob.wine_order_saler_id', $post['id'])
                    ->where('wob.buy_id', $user_id)
                    ->where('wob.status', 'in', [1])
                    ->where($where)
                    ->where('wob.pay_status', 1)
                    ->where('wob.top_stop', 0)
                    ->where('wob.transfer', 0)
                    ->join('member m', 'wob.buy_id = m.id', 'left')
                    ->join('wine_goods wg', 'wg.id = wob.wine_goods_id', 'left')
                    ->count();
                    
                $count3 = Db::name('wine_order_buyer_contract')->where('wob.delete', 0)->alias('wob')
//                    ->where('wob.wine_order_saler_id', $post['id'])
                    ->where('wob.buy_id', $user_id)
                    ->where('wob.status', 'in', [2])
                    ->where($where)
                    ->where('wob.top_stop', 0)
                    ->where('wob.transfer', 0)
                    ->join('member m', 'wob.buy_id = m.id', 'left')
                    ->join('wine_goods wg', 'wg.id = wob.wine_goods_id', 'left')
                    ->count();
                    
                foreach ($list as $k=>$v){
                    // $deal_area = Db::name('wine_deal_area_contract')->where('id', $v['wine_deal_area_id'])->value('deal_area');
                    // $deal_area_start = trim(explode('-', $deal_area)[0]);
                    
                    $list[$k]['confirm_countdown'] = $v['paytime'] + $confirm_countdown*60*60 - time();
                    $list[$k]['paytime_countdown'] = $v['addtime'] + $paytime_countdown*60*60 - time();
                    $list[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                    // $list[$k]['sale_addtime_txt'] = date('Y-m-d', $v['sale_addtime']+$v['day']*24*60*60);
                    // $list[$k]['sale_addtime_txt'] = date('Y-m-d H:i:s', strtotime(date('Y-m-d', $v['addtime']).' '.$deal_area_start)+$v['day']*24*60*60);
                    $list[$k]['sale_addtime_txt'] = date('Y-m-d', $v['addtime']+$v['day']*24*60*60);
                    if($v['wine_deal_area_id'] == 1){
                        $list[$k]['sale_addtime_txt'] .= '  上午场';
                    }else if($v['wine_deal_area_id'] == 2){
                        $list[$k]['sale_addtime_txt'] .= '  下午场';
                    } else if($v['wine_deal_area_id'] == 3){
                        $list[$k]['sale_addtime_txt'] .= '  夜间场';
                    }
                    
                    if($v['status'] == 1){
                        $list[$k]['status_txt'] = '购买中';
                    }
                    else if($v['status'] == 2){
                        $list[$k]['status_txt'] = '已购买';
                    }
                    else if($v['status'] == 3){
                        $list[$k]['status_txt'] = '申诉';
                    }
                    else if($v['status'] == 4){
                        $list[$k]['status_txt'] = '超时';
                    }
                    else if($v['status'] == 5){
                        $list[$k]['status_txt'] = '冻结';
                    }
                }

                $value = array('status'=>200,'mess'=>'获取数据成功','data'=>$list, 'count'=>['count1'=>$count1, 'count2'=>$count2, 'count3'=>$count3]);
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    public function getIncomeWineDetail(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];

                $post = input('post.');
                $info = Db::name('wine_order_buyer_contract')->where('wob.delete', 0)->alias('wob')
                    ->where('wob.buy_id', $user_id)
                    ->where('wob.id', $post['id'])
                    ->join('member bm', 'wob.buy_id = bm.id', 'left')
                    ->join('member sm', 'wob.sale_id = sm.id', 'left')
                    ->join('wine_goods_contract wgc', 'wgc.id = wob.wine_goods_id', 'left')
                    ->field('wob.buy_amount,bm.user_name b_user_name, bm.true_name b_true_name,bm.phone b_phone, sm.user_name s_user_name, sm.true_name s_user_name, sm.phone s_phone,sm.emergency_phone s_emergency_phone,bm.emergency_phone b_emergency_phone,sm.id,wob.pay_status,wob.sale_id,wob.income_name,wob.income_phone,wob.income_card,wob.paywayindex, wob.wine_order_saler_id, wob.bank_name,wob.addtime, wgc.goods_name, wgc.thumb_url, wob.proof_qrcode')
                    ->find();
 
                    $bank = Db::name('bank_card')->where('user_id', $info['sale_id'])->find();
                    $wx = Db::name('wx_card')->where('user_id', $info['sale_id'])->find();
                    $zfb = Db::name('zfb_card')->where('user_id', $info['sale_id'])->find();

                    $paytime_countdown = Db::name('config')->where('ename', 'pay_timeout_contract')->value('value');
                    $time_countdown = $info['addtime'] + $paytime_countdown*60*60 - time();

                $value = array('status'=>200,'mess'=>'获取数据成功','data'=>$info, 'time_countdown'=>$time_countdown,
                                'card_manage'=>[
                                    'bank'=>$bank,
                                    'wx'=>$wx,
                                    'zfb'=>$zfb,
                                ]);
                                
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    public function payProof(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $post = input('post.');
                    $yzresult = $this->validate($post,'PayProof');
                    if(true !== $yzresult){
                        $value = array('status'=>400,'mess'=>$yzresult,'data'=>array('status'=>400));
                    }else{
                        $info = Db::name('wine_order_buyer_contract')->where('delete', 0)->where('id', $post['id'])->where('pay_status', 0)->where('buy_id', $user_id)->find();
                        if ($info){
                            // 匹配进货付款冻结时间已过(小时)
                            $pipei_frozen_timeout_frozen = Db::name('config')->where('ename', 'pipei_frozen_timeout_frozen')->value('value');
                            // if($info['addtime']+$pipei_frozen_timeout_frozen*60*60<time()){
                            //     $value = array('status'=>400,'mess'=>'付款时间已过','data'=>array('status'=>400));
                            //     return json($value);
                            // }
                            
                            $paypwd = Db::name('member')->where('id', $user_id)->value('paypwd');
                            if (md5($post['paypwd']) == $paypwd){
                                Db::startTrans();
                                try{
                                    $time = time();
                                    
                                    // 收款方式
                                    $sale_id = $info['sale_id'];
                                    if($post['paywayindex'] == 0){
                                        // 支付宝
                                        // if($sale_id == 1){
                                        //     $income_pay = Db::name('wine_order_saler')->where('id', $info['wine_order_saler_id'])->field('zfb_name name, zfb_telephone telephone, zfb_qrcode qrcode')->find();
                                        // }
                                        // else{
                                            $income_pay = Db::name('zfb_card')->where('user_id', $sale_id)->find();
                                        // }
                                    }
                                    elseif($post['paywayindex'] == 1){
                                        // 微信
                                        // if($sale_id == 1){
                                        //     $income_pay = Db::name('wine_order_saler')->where('id', $info['wine_order_saler_id'])->field('wx_name name, wx_telephone telephone, wx_qrcode qrcode')->find();
                                        // }
                                        // else{
                                            $income_pay = Db::name('wx_card')->where('user_id', $sale_id)->find();
                                        // }
                                    }
                                    elseif($post['paywayindex'] == 2){
                                        // 银行卡
                                        // if($sale_id == 1){
                                        //     $income_pay = Db::name('wine_order_saler')->where('id', $info['wine_order_saler_id'])->field('bank_name name, bank_telephone telephone, bank_card_number qrcode')->find();
                                        // }
                                        // else{
                                            $income_pay = Db::name('bank_card')->field('telephone,name,card_number qrcode,bank_name')->where('user_id', $sale_id)->find();
                                        // }
                                    }

                                    $res = Db::name('wine_order_buyer_contract')->where('delete', 0)->where('id', $post['id'])->where('pay_status', 0)->where('buy_id', $user_id)
                                        ->update([
                                            'pay_status' => 1,
                                            'paytime'=>$time,
                                            'proof_qrcode'=>$post['qrcode'],
                                            'paywayindex'=>$post['paywayindex'],
                                            'income_name'=>$income_pay['name'],
                                            'income_phone'=>$income_pay['telephone'],
                                            'income_card'=>$income_pay['qrcode'],
                                            'bank_name'=>isset($income_pay['bank_name']) ? $income_pay['bank_name'] : ''
                                        ]);
                                    if (!$res){
                                        throw new \Exception('更新支付信息失败');
                                    }
                                    
                                    Db::name('member')->where('id', $user_id)->setInc('bespoke_proof', 1);
                                        
//                                    $phone = Db::name('member')->where('id', $info['sale_id'])->value('phone');
//                                    order_sms_info($phone, '您的订单有新状态，请及时确认');

                                    Db::commit();
                                    $value = array('status'=>200,'mess'=>'提交成功','data'=>array('status'=>200));
                                }
                                catch (\Exception $e){
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'提交失败','data'=>array('status'=>400));
                                }
                            }
                            else{
                                $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                            }
                        }
                        else{
                            $value = array('status'=>400,'mess'=>'数据异常','data'=>array('status'=>400));
                        }
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

    public function sale_list(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];

                $post = input('post.');
                // if($post['status'] == 1)$post['status'] = 0;
                if ($post['status'] == 0){
                    // 待转让
                    $list1 = Db::name('wine_order_saler_contract')->alias('wos')->where('wos.delete', 0)->where('sale_id', $user_id)
                        ->join('member m', 'wos.sale_id = m.id', 'left')->field('wos.*,m.phone')
                        ->where('wos.status', 'in', [$post['status']])
                        ->order('id desc')->select();
                    foreach($list1 as $k=>$v){
                        $list1[$k]['buy_amount'] = $v['sale_amount'];
                        $list1[$k]['day'] = Db::name('wine_contract_day')->where('id', $v['wine_contract_day_id'])->value('day');
                    }
                        
                    // 已封仓
                    $list2 = Db::name('wine_order_buyer_contract')->alias('wob')->where('wob.delete', 0)->where('wob.buy_id', $user_id)->where('wob.transfer', 1)->where('wob.transfer_wine_contract_day_id', '>', 0)
                        ->join('member m', 'wob.buy_id = m.id', 'left')->field('wob.*,m.phone')
                        ->where('wob.status', 'in', [2])
                        ->order('id desc')->select();
                    
                    // // 待确认
                    // $list3 = Db::name('wine_order_buyer_contract')->alias('wob')
                    //     ->join('member m', 'm.id = wob.buy_id', 'left')
                    //     ->field('wob.*, m.user_name, m.emergency_phone,m.phone')
                    //     ->where('wob.delete', 0)
                    //     // ->where('wob.pay_status', 1)
                    //     ->where('wob.confirm_exchange', null)
                    //     ->where('wob.status', 'in', [1])
                    //     ->where('wob.sale_id', $user_id)
                    //     ->order('id desc')->select();
                        
                    $list = array_merge($list1, $list2);
                }
                elseif($post['status'] == 2){
                    // $list = Db::name('wine_order_saler')->where('wos.delete', 0)->alias('wos')->where('wos.sale_id', $user_id)
                    //     ->join('wine_order_buyer wob', 'wob.wine_order_saler_id = wos.id', 'left')
                    //     ->join('member m', 'wob.buy_id = m.id', 'left')->field('wos.*,m.phone')
                    //     ->where('wos.status', $post['status'])
                    //     ->order('id desc')->select();
                    $list = Db::name('wine_order_buyer_contract')->alias('wob')
                        ->join('member m', 'm.id = wob.buy_id', 'left')
                        ->field('wob.*, m.user_name, m.emergency_phone,m.phone')
                        ->where('wob.delete', 0)
                        ->where('wob.pay_status', 1)
                        ->where('wob.confirm_exchange', null)
                        ->where('wob.status', 'in', [1])
                        ->where('wob.sale_id', $user_id)
                        ->order('id desc')->select();
                }
                else if ($post['status'] == 1 || $post['status'] == 3){
                    if($post['status'] == 1){
                        $list = Db::name('wine_order_buyer_contract')->alias('wob')
                            ->join('member m', 'm.id = wob.buy_id', 'left')
                            ->field('wob.*, m.user_name, m.emergency_phone')
                            ->where('wob.delete', 0)
                            ->where('wob.pay_status', 0)
                            // ->where('wob.status', 'in', [$post['status'], 4, 5])
                            ->where('wob.status', 'in', [$post['status']])
                            ->where('wob.sale_id', $user_id)
                            ->order('id desc')->select();
                    }
                    else{
                        $list = Db::name('wine_order_buyer_contract')->alias('wob')
                            ->join('member m', 'm.id = wob.sale_id', 'left')
                            ->field('wob.*, m.user_name, m.true_name')
                            ->where('wob.delete', 0)->where('wob.status', 2)->where('wob.sale_id', $user_id)
                            ->order('id desc')->select();
                        
                        // $showTime = time()-24*60*60;
                        // $list = Db::name('wine_order_saler_contract')->where('wos.delete', 0)->alias('wos')->where('wos.sale_id', $user_id)
                        // ->join('member m', 'wos.sale_id = m.id', 'left')->field('wos.*,m.phone')
                        // // ->where('wos.status', 'in', [$post['status'], 4])
                        // ->where('wos.status', 'in', 2)
                        // // ->where('wos.wine_contract_day_id', 0)
                        // ->where('wos.addtime', '>=', $showTime)
                        // ->order('id desc')->select();
                        // // var_dump($list);exit;
                    }
                }
                
                    $count1 = Db::name('wine_order_buyer_contract')->alias('wob')
                        ->join('member m', 'm.id = wob.buy_id', 'left')
                        ->field('wob.*, m.user_name, m.emergency_phone')
                        ->where('wob.delete', 0)
                        ->where('wob.pay_status', 0)
                        ->where('wob.status', 'in', [1])
                        ->where('wob.sale_id', $user_id)
                        ->count();
                    // 待转让
                    $count00 = Db::name('wine_order_saler_contract')->alias('wos')->where('wos.delete', 0)->where('sale_id', $user_id)
                        ->join('member m', 'wos.sale_id = m.id', 'left')
                        ->where('wos.status', 'in', [0])->count();
                        
                    // 已封仓
                    $count000 = Db::name('wine_order_buyer_contract')->alias('wob')->where('wob.delete', 0)->where('wob.buy_id', $user_id)->where('wob.transfer', 1)->where('wob.transfer_wine_contract_day_id', '>', 0)
                        ->join('member m', 'wob.buy_id = m.id', 'left')
                        ->where('wob.status', 'in', [2])->count();
                    
                    // // 待确认
                    // $count3 = Db::name('wine_order_buyer_contract')->alias('wob')
                    //     ->join('member m', 'm.id = wob.buy_id', 'left')
                    //     ->where('wob.delete', 0)
                    //     // ->where('wob.pay_status', 1)
                    //     ->where('wob.confirm_exchange', null)
                    //     ->where('wob.status', 'in', [1])
                    //     ->where('wob.sale_id', $user_id)->count();
                    
                    
                    // $count4 = Db::name('wine_order_buyer_contract')->alias('wob')
                    //     ->join('member m', 'm.id = wob.sale_id', 'left')
                    //     ->where('wob.delete', 0)->where('wob.status', 2)->where('wob.sale_id', $user_id)
                    //     ->count();
                    $count0 = $count00+$count000;
                    // $count00 = $count4;
                    
                    // 确认收款
                    $count2 = Db::name('wine_order_buyer_contract')->alias('wob')
                        ->join('member m', 'm.id = wob.buy_id', 'left')
                        ->field('wob.*, m.user_name, m.emergency_phone,m.phone')
                        ->where('wob.delete', 0)
                        ->where('wob.pay_status', 1)
                        ->where('wob.confirm_exchange', null)
                        ->where('wob.status', 'in', [1])
                        ->where('wob.sale_id', $user_id)
                        ->count();

                // 确认时间倒计时(小时)
                $confirm_countdown = Db::name('config')->where('ename', 'confirm_timeout_contract')->value('value');
                $paytime_countdown = Db::name('config')->where('ename', 'pay_timeout_contract')->value('value');
                if (!empty($list)){
                    foreach ($list as $k=>$v){
                        $deal_are = Db::name('wine_contract_day')->where('day', $v['day'])->value('deal_area');
                        $star_de = trim(explode('-', $deal_are)[0]);
                        $list[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
// <<<<<<< HEAD
//                         if($v['sale_addtime'])$list[$k]['sale_addtime'] = date('Y-m-d', $v['sale_addtime']+$v['day']*24*3600).' '.$star_de;
//                         if($v['confirm_exchange'])$list[$k]['confirm_exchange'] = date('Y-m-d H:i:s', $v['confirm_exchange']);
// =======
                        if($v['confirm_exchange'])$list[$k]['confirm_exchange'] = date('Y-m-d H:i:s', $v['confirm_exchange']);
                        if($v['sale_addtime'])$list[$k]['sale_addtime'] = date('Y-m-d H:i:s', $v['sale_addtime']);

                        if(isset($v['paytime'])){
                            $list[$k]['confirm_countdown'] = $v['paytime']+$confirm_countdown*60*60 - time();
                        }
                        else{
                            $list[$k]['confirm_countdown'] = 0;
                        }
                        $list[$k]['paytime_countdown'] = $v['addtime'] + $paytime_countdown*60*60 - time();
                    }
                }

                $value = array('status'=>200,'mess'=>'获取数据成功','data'=>$list, 'count'=>['count0'=>$count0, 'count1'=>$count1, 'count2'=>$count2]);
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function getSaleWineInfo(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];

                $post = input('post.');
                $info = Db::name('wine_order_buyer_contract')->where('wob.delete', 0)->alias('wob')

                    ->where(function($query) use ($user_id){
                        $query->where('wob.sale_id', $user_id)->whereOr('wob.buy_id', $user_id);
                    })

                    ->where('wob.id', $post['id'])
                    ->join('member bm', 'wob.buy_id = bm.id', 'left')
                    ->join('member sm', 'wob.sale_id = sm.id', 'left')
                    // ->join('wine_goods_contract wgc', 'wgc.id = wob.wine_goods_id', 'left')
                    ->field('wob.buy_amount,bm.user_name b_user_name, bm.true_name b_true_name,bm.emergency_phone b_emergency_phone,bm.phone b_phone, sm.user_name s_user_name, sm.true_name s_true_name, sm.phone s_phone,sm.emergency_phone s_emergency_phone,sm.id,wob.pay_status,wob.sale_id,wob.proof_qrcode,wob.paywayindex, wob.goods_name, wob.goods_thumb')
                    ->find();

                $value = array('status'=>200,'mess'=>'获取数据成功','data'=>$info);
                                
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    public function confirmTransfer(){
        if(request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];
                    $post = input('post.');
// var_dump($user_id);exit;
                    $info = Db::name('wine_order_buyer_contract')->where('delete', 0)->where('status', 'in', [1])->where('sale_id', $user_id)->where('pay_status', 1)
                            ->where('id', $post['id'])->find();
                    if ($info) {
                        $paypwd = Db::name('member')->where('id', $user_id)->value('paypwd');
                        if (md5($post['paypwd']) == $paypwd) {
                            Db::startTrans();
                            $time = time();
                            
                            try{
                                $res = Db::name('wine_order_buyer_contract')->where('id', $info['id'])->update([
                                    'status'=>2,
                                    'confirm_exchange' => $time
                                ]);
                                if (!$res)throw new \Exception('转让失败5');


                                $wine_order_saler_info = Db::name('wine_order_saler_contract')
                                                            ->where('sale_id', $user_id)
                                                            ->where('delete', 0)
                                                            ->where('status', 1)
                                                            ->where('id', $info['wine_order_saler_id'])->find();
// var_dump($wine_order_saler_info);exit;
                                if ($wine_order_saler_info['pipei_amount'] == $wine_order_saler_info['sale_amount']){
                                     $income_total_price = Db::name('wine_order_buyer_contract')->where('delete', 0)->where('sale_id', $user_id)->where('pay_status', 1)
                                            ->where('wine_order_saler_id', $wine_order_saler_info['id'])->sum('buy_amount');

                                     if($income_total_price == $wine_order_saler_info['sale_amount']){
                                         $res = Db::name('wine_order_saler_contract')->where('id', $wine_order_saler_info['id'])->update([
                                            'status'=>2,
                                            'confirm_exchange'=>$time
                                         ]);
                                         if (!$res)throw new \Exception('转让失败4');
                                         
                                        //  Db::name('member')->where('id', $info['buy_id'])->setInc('agent_num');
                                        //  if (!$res)throw new \Exception('转让失败2');
                                     }
                                }
                                // var_dump($info['sale_id']);exit;
                                $res = Db::name('contract_record_wallet')->where('user_id', $info['buy_id'])->setInc('total_assets', $info['buy_amount']);
                                if (!$res)throw new \Exception('转让失败1');
                                
//                                $maijiashouyi = $wine_order_saler_info['sale_amount']-$wine_order_saler_info['buy_amount'];
//                                if($maijiashouyi < 0){
//                                    if (!$res)throw new \Exception('转让失败2');
//                                }
                                $res = Db::name('contract_record_wallet')->where('user_id', $info['sale_id'])
                                    ->dec('total_assets', $info['buy_amount'])
//                                    ->inc('cumulative_earnings', $maijiashouyi)
                                    ->update();
                                if (!$res)throw new \Exception('转让失败3');
                        
//                                $res = Db::name('member')->where('id', $info['buy_id'])->inc('agent_num')->inc('sale_earnings', $info['buy_amount'])->update();
//                                if(!$res){
//                                    throw new Exception('订单信息不存在');
//                                }

                                Db::commit();
                                $value = array('status' => 200, 'mess' => '转让成功', 'data' => array('status' => 200));
                            }
                            catch (\Exception $e){
                                Db::rollback();
                                $value = array('status' => 400, 'mess' => '转让失败'.$e->getMessage(), 'data' => array('status' => 400));
                            }

                        } else {
                            $value = array('status' => 400, 'mess' => '支付密码错误', 'data' => array('status' => 400));
                        }
                    } else {
                        $value = array('status' => 400, 'mess' => '数据异常', 'data' => array('status' => 400));
                    }
                } else {
                    $value = array('status' => 400, 'mess' => '缺少用户令牌', 'data' => array('status' => 400));
                }
            } else {
                $value = array('status' => 400, 'mess' => '请求方式不正确', 'data' => array('status' => 400));
            }
        }
        return json($value);
    }
    
    public function getWineGoodsTimeContract(){
        $post = input('post.');
        $user_id = Db::name('rxin')->where('token', $post['token'])->value('user_id');
        $memberInfo = Db::name('member')->where('id', $user_id)->find();
        // echo $user_id;exit;
        $list = Db::name('wine_deal_area_contract')->where('status', 1)->select();
// var_dump($list);exit;
        $ahead_time = 0;
        if($memberInfo['qiandan']==1){
            $ahead_record_stop = Db::name('config')->where('ename', 'ahead_record_stop')->value('value');
            $ahead_time = abs($ahead_record_stop*60);
        }
        
// echo $ahead_time;exit;
        if ($user_id){
            $wine_deal_area_id_arr = Db::name('wine_order_record_contract')->where('buy_id', $user_id)
                ->where('addtime', '>=', strtotime('today'))
                ->column('wine_deal_area_id,status');

            for ($i=0; $i<count($list); $i++){
                $list[$i]['status'] = isset($wine_deal_area_id_arr[$list[$i]['id']]) ? 1 : 0;
            }
        }
        
        $ymd = date('Y-m-d');
        for ($i=0; $i<count($list); $i++){
            // $time_area = explode('-', $list[$i]['deal_area']);
            $time_area = explode('-', $list[$i]['deal_area']);
            $r = explode(':', $time_area[0]);
            $start_time = ($r[0]*3600+$r[1]*60+$r[2]-$ahead_time < 0) ? strtotime('today') : strtotime($ymd .' '. $time_area[0])-$ahead_time;
            $end_time = strtotime($ymd .' '. $time_area[1]);
            
            $list[$i]['deal_area'] = date('H:i:s', $start_time) . '-' . date('H:i:s', $end_time);
            // var_dump($list);exit;
            if($start_time <= time() && time() <= $end_time){
                $list[$i]['status'] = 2;
            }
            else if(time() > $end_time){
                $list[$i]['status'] = 3;
            }
            
            $list[$i]['time_countdown'] = $start_time - time();
        }
        
        $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>$list);
        
        return json($value);
    }

    public function order_records(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                if($this->judgeEnable($user_id) === false){
                    $value = array('status'=>400,'mess'=>'请先激活账号','data'=>array('status'=>400));
                    return json($value);
                }
                
                $count = 0;
                $count1 = Db::name('bank_card')->where('user_id', $user_id)->count();
                if($count1 > 0){
                    $count++;
                }
                
                $count2 = Db::name('zfb_card')->where('user_id', $user_id)->count();
                if($count2 > 0){
                    $count++;
                }
                
                $count3 = Db::name('wx_card')->where('user_id', $user_id)->count();
                if($count3 > 0){
                    $count++;
                }
                
                if($count < 1){
                    $value = array('status'=>400,'mess'=>'最少绑定一种收款方式','data'=>array('status'=>400));
                    return json($value);
                }

                $post = input('post.');
                // if(!isset($post['wine_deal_area_id'])){
                if(!isset($post['wine_contract_day_id'])){
                    $value = array('status'=>400,'mess'=>'不存在该商品','data'=>array('status'=>400));
                }
                else{
                    // $info = Db::name('wine_deal_area_contract')->where('id', $post['wine_deal_area_id'])->find();
                    // if (is_null($info)){
                    $info = Db::name('wine_contract_day')->where('status', 1)->where('id', $post['wine_contract_day_id'])->find();
                    if (is_null($info)){
                        $value = array('status'=>400,'mess'=>'不存在该商品','data'=>array('status'=>400));
                    }
                    else{
                        $count = Db::name('wine_order_record_contract')->where('wine_goods_id', $post['wine_goods_id'])->where('wine_contract_day_id', $post['wine_contract_day_id'])->where('addtime', '>=', strtotime('today'))->where('buy_id', $user_id)->count();
                        if($count > 0){
                            $value = array('status'=>400,'mess'=>'禁止重复预约','data'=>array('status'=>400));
                            return json($value);
                        }
                        
                         $ahead_record_stop_common_seconds = Db::name('config')->where('ename', 'ahead_record_stop')->value('value')*60;
                         $wine_deal_area_info = Db::name('wine_contract_day')->where('id', $post['wine_contract_day_id'])->where('status', 1)->field('deposit,deal_area')->find();
                         if (is_null($wine_deal_area_info)){
                            $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
                            return json($value);
                         }
                         
                         $wine_deal_area_info_deposit = $wine_deal_area_info['deposit'];
                         $aheadHim = trim(explode('-', $wine_deal_area_info['deal_area'])[0]);
                         $before_time_ke_record = strtotime(date('Y-m-d').' '.$aheadHim)-$ahead_record_stop_common_seconds;
                         if(time()>=$before_time_ke_record){
                             $value = array('status'=>400,'mess'=>'预约时间已过','data'=>array('status'=>400));
                         }
                         else{
                             $wallet_info = Db::name('wallet')->where('user_id', $user_id)->find();
                            
                            //  $legoufen_frozen_most = Db::name('config')->where('ename', 'legoufen_frozen_most')->value('value');
                             if($wallet_info['point'] < $wine_deal_area_info_deposit){
                                 $value = array('status'=>400,'mess'=>'积分不足','data'=>array('status'=>400));
                             }
                             else{
                                 if(false){
                                     $value = array('status'=>400,'mess'=>'购买该商品需完成上面几款商品的购买','data'=>array('status'=>400));
                                 }
                                 else{
                                     $todaytime = strtotime('today');

                                    //  $count = Db::name('wine_order_record_contract')->where('id', $post['wine_deal_area_id'])->where('buy_id', $user_id)->where('addtime', '>=', $todaytime)->count();
                                    //  if($count > 0){
                                     if(false){
                                         $value = array('status'=>400,'mess'=>'同一款商品一天只能预约一次','data'=>array('status'=>400));
                                     }
                                     else{
                                        $memberInfo = Db::name('member')->where('id', $user_id)->find();
                                        if(empty($memberInfo['idcard']) || empty($memberInfo['true_name']) || $memberInfo['reg_enable']==0){
                                             $value = array('status'=>400,'mess'=>'请先激活账号和完成实名认证','data'=>array('status'=>400));
                                             return json($value);
                                        }
                                         
                                         $wine_goods_info = Db::name('wine_goods_contract')->where('id', $post['wine_goods_id'])->where('onsale', 1)->find();
                                         if (is_null($wine_goods_info)) {
                                             // code...
                                             $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
                                             return json($value);
                                         }
                                         Db::startTrans();
                                         try{
                                             $insertId = Db::name('wine_order_record_contract')->insertGetId([
                                                 'addtime'=>time(),
                                                 'buy_id'=>$user_id,
                                                 'goods_name'=>$wine_goods_info['goods_name'],
                                                 'goods_thumb'=>$wine_goods_info['thumb_url'],
                                                 'wine_goods_id'=>$wine_goods_info['id'],
                                                 'odd'=>uniqid(),
                                                 'wine_contract_day_id'=>$post['wine_contract_day_id'],
                                                 'frozen_point' => $wine_deal_area_info_deposit
                                             ]);
                                             if(!$insertId)throw new Exception('预约失败4');
                                             
                                             $res = Db::name('wallet')->where('user_id', $user_id)->dec('point', $wine_deal_area_info_deposit)->inc('frozen_point', $wine_deal_area_info_deposit)->update();
                                             if(!$res)throw new Exception('预约失败3');
                                             $detail = [
                                                'de_type' => 2,
                                                'zc_type' => 1000,
                                                'before_price'=> $wallet_info['point'],
                                                'price' => $wine_deal_area_info_deposit,
                                                'after_price'=> $wallet_info['point']-$wine_deal_area_info_deposit,
                                                'user_id' => $user_id,
                                                'wat_id' => $wallet_info['id'],
                                                'time' => time(),
                                                'target_id' => $insertId
                                             ];
                                             $res = $this->addDetail($detail);
//                                             $res = Db::name('detail')->insert($detail);
                                             if(!$res)throw new Exception('预约失败2');
                                            
                                             $detail = [
                                                'de_type' => 1,
                                                'sr_type' => 1000,
                                                'before_price'=> $wallet_info['frozen_point'],
                                                'price' => $wine_deal_area_info_deposit,
                                                'after_price'=> $wallet_info['frozen_point']+$wine_deal_area_info_deposit,
                                                'user_id' => $user_id,
                                                'wat_id' => $wallet_info['id'],
                                                'time' => time(),
                                                'target_id' => $insertId
                                             ];
                                             $res = $this->addDetail($detail);
//                                             $res = Db::name('detail')->insert($detail);
                                             if(!$res)throw new Exception('预约失败1');
                                             
                                             $value = array('status'=>200,'mess'=>'预约成功');
                                             
                                             Db::commit();
                                         }
                                         catch(\Exception $e){
                                             $value = array('status'=>400,'mess'=>'预约失败'.$e->getMessage());
                                             
                                             Db::rollback();
                                         }
                                     }
                                 }
                             }
                         }
                    }
                }

            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function qiangou(){
        $input = input();
        $user_id = Db::name('rxin')->where('token', $input['token'])->value('user_id');
        
        // if(!$input['wine_deal_area_id']){
        if(false){
            $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
        }
        else{
            if(!$input['wine_goods_id']){
                $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
            }
            else{
                // $wine_deal_area = Db::name('wine_deal_area_contract')->where('id', $input['wine_deal_area_id'])->where('status', 1)->find();
                // if(is_null($wine_deal_area)){
                if(false){
                    $value = array('status'=>400,'mess'=>'时间段不存在','data'=>array('status'=>400));
                }
                else{
                    $ahead_record_stop = Db::name('config')->where('ename', 'ahead_record_stop')->value('value')*60;
                    // $list = Db::name('wine_order_saler_contract')->alias('wos')
                    //         ->join('wine_goods wg', 'wg.id=wos.wine_goods_id', 'left')
                    //         ->field('wos.*, wg.value')
                    //         ->where('wos.wine_goods_id', $input['wine_goods_id'])
                    //         ->where('wos.sale_id', '<>', $user_id)
                    //         ->where('wos.status', 0)->where('wos.delete', 0)->where('wos.wine_deal_area_id', $input['wine_deal_area_id'])->limit($input['page']*$input['rows'], $input['rows'])
                    // ->select();
                    $list = Db::name('wine_contract_day')->field('id, deal_area, day_rate, day, price_area, deposit, service_cost')->where('status', 1)->select();
                    foreach ($list as $k=>&$v){
                        $v['deposit'] = intval($v['deposit']);
                        $v['service_cost'] = intval($v['service_cost']);
                        $deal_area = explode('-', $v['deal_area']);
                        // $startHis = explode(':', trim($deal_area[0]))
                        $today_start_time = strtotime(date('Y-m-d').' '.trim($deal_area[0]))-$ahead_record_stop;
                        $today_end_time = strtotime(date('Y-m-d').' '.trim($deal_area[1]));
                        $time = time();
                        $v['djs'] = $today_start_time - $time;
                        $v['djszh'] = $today_start_time+$ahead_record_stop - $time - 60;
                        if($time > $today_end_time){
                            $v['qian_status'] = 3;
                        }
                        else if($time>=$today_start_time && $time<=$today_end_time){
                            $v['qian_status'] = 2;
                        }
                        else{
                            $count = Db::name('wine_order_record_contract')->where('buy_id', $user_id)->where('addtime', '>=', strtotime('today'))->where('wine_goods_id', $input['wine_goods_id'])
                                ->where('wine_contract_day_id', $v['id'])->count();
                                
                            if($count == 0){
                                $v['qian_status'] = 0;
                            }
                            else{
                                $v['qian_status'] = 1;
                            }
                        }
                        
                        $v['countdown'] = -1;
                        
                        // $wine_order_buyer_contract_info = Db::name('wine_order_buyer_contract')->where('buy_id', $user_id)->where('wine_goods_id', $input['wine_goods_id'])->where('wine_deal_area_id', $input['wine_deal_area_id'])->where('delete', 0)->where('addtime', '>=', strtotime('today'))->find();

                        // if(is_null($wine_order_buyer_contract_info)){
                        //     $list[$k]['qian_status'] = 0;
                        // }
                        // else{
                        //     if($wine_order_buyer_contract_info['status']==2 && $v['day'] == $wine_order_buyer_contract_info['day']){
                        //         $list[$k]['qian_status'] = 2;
                        //     }
                        //     elseif($wine_order_buyer_contract_info['status']==1 && $v['day'] == $wine_order_buyer_contract_info['day']){
                        //         $list[$k]['qian_status'] = 1;
                        //     }
                        //     elseif($wine_order_buyer_contract_info['status']==6 && $v['day'] == $wine_order_buyer_contract_info['day']){
                        //         $list[$k]['qian_status'] = 6;
                        //     }
                        //     else{
                        //         $list[$k]['qian_status'] = 3;
                        //     }
                        // }
                    }
                    // $info = Db::name('wine_goods_contract')->where('onsale', 1)->find();
                    
                    // $memberInfo = Db::name('member')->where('id', $user_id)->field('qiandan')->find();
                    // var_dump($memberInfo);exit;
                    // if($memberInfo['qiandan'] == 1){
                    //     $ahead_record_stop = Db::name('config')->where('ename', 'ahead_record_stop')->value('value');
                    //     $deal_area_time = explode('-', $wine_deal_area['deal_area']);
                    //     $explodeValue = explode(':', $deal_area_time[0]);
                    //     $totalSeconds = ($explodeValue[0]*60*60+$explodeValue[1]*60+$explodeValue[2] - $ahead_record_stop*60 < 0) ? 0 : $explodeValue[0]*60*60+$explodeValue[1]*60+$explodeValue[2] - $ahead_record_stop*60;
                    //     $hours = intval($totalSeconds/3600)<10 ? '0'.intval($totalSeconds/3600) : intval($totalSeconds/3600);
                    //     $minutes = intval($totalSeconds%3600/60)<10 ? '0'.intval($totalSeconds%3600/60) : intval($totalSeconds%3600/60);
                    //     $seconds = intval($totalSeconds%60)<10 ? '0'.intval($totalSeconds%60) : intval($totalSeconds%60);
                    //     $deal_area_time[0] = $hours.':'.$minutes.':'.$seconds;
                        
                    //     $wine_deal_area['deal_area'] = implode('-', $deal_area_time);
                    // }
                    
                    // $startHis = trim(explode('-', $wine_deal_area['deal_area'])[0]);
                    // $time_countdown = strtotime(date('Y-m-d').' '.$startHis) - time();
                    
                    // echo Db::name('wine_order_saler')->getLastSql();exit();
                    
                    $goods = Db::name('wine_goods_contract')->field('goods_name,thumb_url')->where('id', 906)->find();
                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list, 'goods'=>$goods);
                }
            }
        }
        
        return json($value);
    }
    
    public function buyGoods(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $input = input();
                    
                    $info = Db::name('wine_contract_day')->where('status', 1)->where('id', $input['wine_contract_day_id'])->find();
                    
                    $ahead_time = 0;
                    // if($memberInfo['qiandan']==1){
                    //     $ahead_record_stop = Db::name('config')->where('ename', 'ahead_record_stop')->value('value');
                    //     $ahead_time = $ahead_record_stop*60;
                    // }
                    
                    // $timeContract = Db::name('wine_deal_area_contract')->where('id', $input['wine_deal_area_id'])->find();
                    $timeContract = $info;
                    $ymd = date('Y-m-d');
                    $shijian = explode('-', $timeContract['deal_area']);
                    $start_time = strtotime($ymd . $shijian[0])-$ahead_time;
                    $end_time = strtotime($ymd . $shijian[1]);
                    $cur_time = time();
                    
                    if($cur_time>=$start_time && $cur_time<=$end_time){
                        // $wine_order_saler_contract_info = Db::name('wine_order_saler_contract')->alias('wosc')->where('wosc.wine_contract_day_id', $input['wine_contract_day_id'])
                        //     ->where('wosc.wine_goods_id', $input['wine_goods_id'])
                        //     ->join('wine_goods_contract wgc', 'wgc.id = wosc.wine_goods_id', 'left')
                        //     ->field('wgc.goods_name, wgc.goods_thumb, wosc.wine_contract_day_id, wosc.wine_goods_id')
                        //     ->find();
                        $wine_order_saler_contract_info = Db::name('wine_goods_contract')->where('id', $input['wine_goods_id'])->find();
                        // if(is_null($wine_order_saler_contract_info)){
                        //     $value = array('status'=>300,'mess'=>'不好意思，您慢了一步,该合约已出售一空','data'=>array('status'=>300));
                        //     return json($value);
                        // }
                        $qiangouinfo = [
                            'goods_name' => $wine_order_saler_contract_info['goods_name'],
                            'goods_thumb' => $wine_order_saler_contract_info['thumb_url'],
                            'addtime' => time(),
                            // 'buy_amount' => $wine_order_saler_contract_info['sale_amount'],
                            'buy_id' => $user_id,
                            'odd' => uniqid(),
                            'wine_goods_id' => $input['wine_goods_id'],
                            'wine_contract_day_id' => $input['wine_contract_day_id']
                        ];
                        $canyuqinagoucount = Db::name('wine_order_qiangou_contract')->where('wine_goods_id', $qiangouinfo['wine_goods_id'])->where('wine_contract_day_id', $qiangouinfo['wine_contract_day_id'])->where('buy_id', $qiangouinfo['buy_id'])->where('addtime', '>=', strtotime('today'))->count();
                        if($canyuqinagoucount==0){
                            $rrr = Db::name('wine_order_qiangou_contract')->insert($qiangouinfo);
                            // if(!$rrr){
                            //     $value = array('status'=>400,'mess'=>'抢购失败','data'=>array('status'=>400));
                            //     return json($value);
                            // }
                        }
                           
                        $memberInfo = Db::name('member')->where('id', $user_id)->find();
                        if(empty($memberInfo['idcard']) || empty($memberInfo['true_name']) || $memberInfo['reg_enable']==0){
                            $value = array('status'=>400,'mess'=>'请先激活账号和完成实名认证','data'=>array('status'=>400));
                            return json($value);
                        }
                        
                        $sale_info_data = Db::name('wine_order_saler_contract')->where('wine_goods_id', $input['wine_goods_id'])->where('onsale', 1)->where('status', 0)->where('delete', 0)->where('wine_contract_day_id', $info['id'])->where('sale_id', '<>', $user_id)->find();
                        if(is_null($sale_info_data)){
                            $value = array('status'=>300,'mess'=>'不好意思，您慢了一步','data'=>array('status'=>300));
                            return json($value);
                        }
                        
                        $count = Db::name('wine_order_buyer_contract')->where('wine_goods_id', $input['wine_goods_id'])->where('wine_contract_day_id', $input['wine_contract_day_id'])->where('buy_id', $user_id)->where('addtime', '>=', strtotime('today'))->count();
                        if($count >= 1){
                            $value = array('status'=>400,'mess'=>'本场最多只能抢购1单','data'=>array('status'=>400));
                            return json($value);
                        }
                    
                        $wallet_info = Db::name('wallet')->where('user_id', $user_id)->find();
                        if(is_null($wallet_info)){
                            $value = array('status'=>400,'mess'=>'网络异常','data'=>array('status'=>400));
                        }
                        else{
                            $sdskkk = Db::name('wine_order_record_contract')->where('wine_goods_id', $input['wine_goods_id'])->where('wine_contract_day_id', $input['wine_contract_day_id'])
                             ->where('buy_id', $user_id)->where('addtime', '>=', strtotime('today'))->find();

                            $server_cost = $info['service_cost'];
                            if(!is_null($sdskkk)){
                                $server_cost = 0;
                            }
                            
                            if($wallet_info['point'] < $server_cost){
                                $value = array('status'=>400,'mess'=>'积分不足','data'=>array('status'=>400));
                                return json($value);
                            }
                            
                            Db::startTrans();
                            try{
                                $res = Db::name('wine_order_saler_contract')->where('onsale', 1)->where('delete', 0)->where('status', 0)->where('id', $sale_info_data['id'])->update(['status' => 1]);
                                if(!$res){
                                    throw new Exception('抢购失败3');
                                }
                                
                                $insert_data = [
                                    'goods_name' => $sale_info_data['goods_name'],
                                    'goods_thumb' => $sale_info_data['goods_thumb'],
                                    'addtime' => time(),
                                    'buy_amount' => $sale_info_data['sale_amount'],
                                    'sale_amount' => $sale_info_data['sale_amount'] + $sale_info_data['sale_amount']*$info['day_rate']/100,
                                    'buy_id' => $user_id,
                                    'sale_id' => $sale_info_data['sale_id'],
                                    'wine_goods_id' => $sale_info_data['wine_goods_id'],
                                    'status' => 1,
                                    'wine_order_saler_id'=>$sale_info_data['id'],
                                    'odd' => uniqid(),
                                    'day' => $info['day'],
                                    'wine_contract_day_id' => $info['id']
                                ];
                                $insertIds = Db::name('wine_order_buyer_contract')->insertGetId($insert_data);
                                if(!$insertIds){
                                    throw new Exception('抢购失败4');
                                }
                                if($server_cost > 0){
                                    $res = Db::name('wallet')->where('user_id', $user_id)->dec('point', $server_cost)->update();
                                    if(!$res){
                                        throw new Exception('抢购失败5');
                                    }
                                     $detail = [
                                        'de_type' => 2,
                                        'zc_type' => 1001,
                                        'before_price'=> $wallet_info['point'],
                                        'price' => $server_cost,
                                        'after_price'=> $wallet_info['point']-$server_cost,
                                        'user_id' => $user_id,
                                        'wat_id' => $wallet_info['id'],
                                        'time' => time(),
                                        'target_id' => $sale_info_data['id']
                                     ];
                                     $res = $this->addDetail($detail);
                                    if(!$res){
                                        throw new Exception('抢购失败6');
                                    }
                                }

                                
                                
                                $value = array('status'=>200,'mess'=>'抢购成功','data'=>array('status'=>200));
                                Db::commit();
                            }
                            catch(Exception $e){
                                $value = array('status'=>400,'mess'=>$e->getMessage(),'data'=>array('status'=>400));
                                Db::rollback();
                            }
                        }
                    }
                    else{
                        $value = array('status'=>400,'mess'=>'活动未开始或活动已结束','data'=>array('status'=>400));
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
    
    // 已进货酒兑换
    public function wineToInKind()
    {
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];

                $post = input('post.');
                if(!trim($post['address'])){
                    $value = array('status'=>400,'mess'=>'收货地址不存在','data'=>array('status'=>400));
                    return json($value);
                }
                
                $paypwd = Db::name('member')->where('id', $user_id)->value('paypwd');
                if (md5($post['paypwd']) != $paypwd){
                    $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                    return json($value);
                }
                
                $info = Db::name('wine_order_buyer_contract')->where('wob.pay_status', 1)->where('wob.delete', 0)->alias('wob')
                    ->where('wob.buy_id', $user_id)
                    ->where('wob.status', 2)
                    ->where('wob.transfer', 0)
                    ->where('wob.transfer_wine_contract_day_id', 0)
                    ->where('wob.id', $post['id'])
                    ->find();

                if($info){
                    Db::startTrans();
                    try{
                        $res = Db::name('wine_order_buyer_contract')->where('id', $info['id'])->update([
                            'status' => 6,
                            // 'delete' => 1
                        ]);
                        if(!$res)throw new \Exception('异常');

                        $inKindData = [
                            'goods_name' => $info['goods_name'],
                            'goods_thumb' => $info['goods_thumb'],
                            'addtime' => time(),
                            'user_id' => $info['buy_id'],
                            'wine_goods_id' => $info['wine_goods_id'],
                            'buy_amount' => $info['buy_amount'],
                            'sale_amount' => $info['sale_amount'],
                            'wine_order_buyer_id' => $info['id'],
                            'address' => $post['address'],
                            'contacts' => $post['contacts'],
                            'phone' => $post['phone'],
                            'pro_name' => $post['pro_name'],
                            'city_name' => $post['city_name'],
                            'area_name' => $post['area_name']
                        ];
                        $res = Db::name('wine_to_inkind_contract')->insert($inKindData);
                        if(!$res)throw new \Exception('异常');
                        
                        $res = Db::name('contract_record_wallet')->where('user_id', $user_id)->dec('total_assets', $info['buy_amount'])->update();
                        if(!$res)throw new \Exception('异常');
                        
                        $value = array('status'=>200,'mess'=>'提货成功','data'=>$info);
                        Db::commit();
                    }
                    catch(\Exception $e){
                        $value = array('status'=>400,'mess'=>'提货失败'.$e->getMessage(),'data'=>array('status'=>400));
                        Db::rollback();
                    }
                }
                else{
                    $value = array('status'=>400,'mess'=>'该商品不存在','data'=>array('status'=>400));
                }        
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function getWineSalerDay(){
        $list = Db::name('wine_contract_day')->where('status', 1)->column('day');
        foreach ($list as $k=>$v){
            $list[$k] = $v;
        }
        
        $value = array('status'=>200,'mess'=>'获取成功','data'=>$list);
        return json($value);
    }
    
    public function revalue(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                $h = date('H');
                if($h >= 23){
                    $value = array('status'=>400,'mess'=>'转让时间已过，请在23点前转让','data'=>array('status'=>400));
                    return json($value);
                }
                
                $input = input();
                // $paypwd = Db::name('member')->where('id', $user_id)->value('paypwd');
                // if (md5($input['paypwd']) != $paypwd){
                //     $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                //     return json($value);
                // }
                
                $info = Db::name('wine_order_buyer_contract')->where('id', $input['id'])->where('status', 2)->where('pay_status', 1)->where('delete', 0)->where('transfer_wine_contract_day_id', 0)->where('transfer', 0)->where('top_stop', 0)->where('buy_id', $user_id)->find();
                if(is_null($info)){
                    $value = array('status'=>400,'mess'=>'数据不存在','data'=>array('status'=>400));
                    return json($value);
                }
                else{
                        $day = $input['day'];
                        // $day = 1;
                        if($day <= 0){
                            $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
                            return json($value);
                        }

                        // $wine_goods = Db::name('wine_goods_contract')->where('id', $info['wine_goods_id'])->find();
                        
                        // if($wine_goods['best_max_day'] < $day){
                        //     $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
                        //     return json($value);
                        // }
                        $wine_contract_day = Db::name('wine_contract_day')->column('day,day_rate');
                        $dayArr = array_keys($wine_contract_day);
                        if(!in_array($day, $dayArr)){
                            $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
                            return json($value);
                        }
                        
                        $day_rate = $wine_contract_day[$day];
                        if($day_rate<=0){
                            $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
                            return json($value);
                        }
                        
                        $sale_amount = $info['buy_amount'] + $info['buy_amount']*$day_rate/100;
                        
                        // $wine_jishou_service_cost = Db::name('config')->where('ename', 'wine_jishou_service_cost_contract')->value('value');
                        $wine_jishou_service_cost = Db::name('config')->where('ename', 'wine_jishou_service_cost')->value('value');
                        
                    Db::startTrans();
                    try{
                        // 服务费
                        // $cost_service = $info['buy_amount']*$wine_jishou_service_cost/100;
                        $deposit = Db::name('wine_contract_day')->where('id', $info['wine_contract_day_id'])->value('deposit');
                        // $cost_service = $deposit*$wine_jishou_service_cost/100;
                        $cost_service = $deposit;
                        $wall_info = Db::name('wallet')->where('user_id', $user_id)->find();
                        $wallet_info = $wall_info;
                        // if($wall_info['point'] < $cost_service){
                        //     throw new Exception('余额不足');
                        // }
                        
                        // $res = Db::name('wallet')->where('user_id', $user_id)->setDec('point', $cost_service);
                        // if(!$res)throw new Exception('平台转让失败');
                        // $klg = [
                        //     'de_type' => 2,
                        //     'zc_type' => 1003,
                        //     'before_price'=> $wallet_info['point'],
                        //     'price' => $cost_service,
                        //     'after_price'=> $wallet_info['point']-$cost_service,
                        //     'user_id' => $user_id,
                        //     'wat_id' => $wall_info['id'],
                        //     'time' => time(),
                        //     'target_id'=>$info['id']
                        // ];
                        // $res = $this->addDetail($klg);
                        // if(!$res){
                        //     throw new Exception('平台转让失败');
                        // }
                        $profit = $sale_amount - $info['buy_amount'];
                        if($profit > 0){
                            $res = Db::name('contract_record_wallet')->where('user_id', $info['buy_id'])->inc('cumulative_earnings', $profit)->update();
                            if(!$res)throw new Exception('平台寄售失败');
                        }
                        
                        $wine_contract_day_info = Db::name('wine_contract_day')->where('day', $day)->find();
                        if(is_null($wine_contract_day_info)){
                            $value = array('status'=>400,'mess'=>'转让失败','data'=>array('status'=>400));
                            return json($value);
                        }
                        
                        $res = Db::name('wine_order_buyer_contract')->where('id', $info['id'])->where('status', 2)->where('pay_status', 1)->where('delete', 0)
                                ->where('transfer_wine_contract_day_id', 0)->where('transfer', 0)->where('buy_id', $user_id)->where('top_stop', 0)
                                // ->inc('day', $day)
                                ->update([
                                    'transfer' => 1,
                                    'sale_amount' => $sale_amount,
                                    'sale_addtime' => time(),
                                    'transfer_wine_contract_day_id' => $wine_contract_day_info['id']
                                ]);
                        if(!$res)throw new Exception('平台转让失败');

                        // 极差
                        $profit = $cost_service;
                        if($profit>0)$this->wineGoodsGradePoor($info['buy_id'], $profit, 1, [], $info['buy_id'], 0, $info['wine_goods_id'], $info['wine_contract_day_id'], 1);

                        $value = array('status'=>200,'mess'=>'平台转让成功','data'=>array('status'=>200));
                        Db::commit();
                    }
                    catch(Exception $e){
                        $value = array('status'=>400,'mess'=>$e->getMessage(),'data'=>array('status'=>400));
                        Db::rollback();
                    }
                }
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function countdown(){
        $id = input('id');
        
        // if(Cache::get('countdown'.$id) == 1){
        if(false){
            $value = array('status'=>400,'mess'=>'重复','data'=>array('status'=>400));
        }
        else{
            Cache::set('countdown'.$id, 1, 2);
            
            //  $ahead_record_stop_common_seconds = Db::name('config')->where('ename', 'ahead_record_stop')->value('value')*60;
             $wine_deal_area_info = Db::name('wine_contract_day')->where('id', $id)->where('status', 1)->field('deposit,deal_area')->find();
             if (is_null($wine_deal_area_info)){
                $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
             }
             else{
                 $aheadHim = trim(explode('-', $wine_deal_area_info['deal_area'])[0]);
                //  $before_time_ke_record = strtotime(date('Y-m-d').' '.$aheadHim)-$ahead_record_stop_common_seconds;
                 $before_time_ke_record = strtotime(date('Y-m-d').' '.$aheadHim);
                 $sytime = $before_time_ke_record - time();
                 $value = array('status'=>200,'mess'=>'获取成功','data'=>$sytime<=60 ? $sytime : 0);
             }
            
            return json($value);
        }
    }
}
