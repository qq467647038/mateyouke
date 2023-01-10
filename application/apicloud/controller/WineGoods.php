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

class WineGoods extends Common
{

    
    public function dealDealTime(){
        $list = Db::name('wine_deal_area')->order('id asc')->where('status', 1)->select();
        
        $data = [];
        foreach ($list as $k=>&$v){            
            $v['wine_goods_id'] = 906;
            if($k==0){
                $v['img'] = '/portal/static/images/bg6.png';
            }elseif($k==1){
                $v['img'] = '/portal/static/images/bg5.png';
            }elseif($k==2){
                $v['img'] = '/portal/static/images/bg4.png';
            }
            
            $hourseconds = explode('-', $v['deal_area']);
            $start = explode(':', $hourseconds[0]);
            
            $hi = date('H')*60*60 + date('i')*60;

            $time_countdown = $start[0]*60*60 + $start[1]*60;
            $v['time_countdown'] = ($time_countdown - $hi > 0) ? $time_countdown - $hi : 0;
            
            $v['time_countdown_desc'] = '';
            if($v['time_countdown'] == 0){
                $end = explode(':', $hourseconds[1]);
                $end_time = $end[0]*60*60 + $end[1]*60;
                
                if($hi > $end_time){
                    $v['time_countdown_desc'] = '已结束';
                }
                else{
                    $v['time_countdown_desc'] = '抢购中';
                }
            }
        }
        
        $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
        
        return json($value);
    }
    
    public function getWineGoodsTime(){
        $post = input('post.');
        $user_id = Db::name('rxin')->where('token', $post['token'])->value('user_id');
        $memberInfo = Db::name('member')->where('id', $user_id)->find();
        // $ahead_record_stop_common = Db::name('config')->where('ename', 'ahead_record_stop_common')->value('value');
        // echo $user_id;exit;
        $list = Db::name('wine_deal_area')->where('status', 1)->select();
// var_dump($list);exit;
        $ahead_time = $this->tiqian;
        // if(isset($post['type']) && $post['type']=='new'){
        //     $ccount = Db::name('wine_order_buyer')->where('buy_id', $user_id)->count();
        //     if($ccount == 0){
        //         $value = Db::name('config')->where('ename', 'ahead_buy_minutes')->value('value');
        //         $ahead_time = abs($value*60);
        //     }
        // }
        // else if(isset($post['type']) && $post['type']=='vip'){
        //     $vip_time = Db::name('member')->where('id', $user_id)->value('vip_time');
        //     if($vip_time > time()){
        //         $value = Db::name('config')->where('ename', 'ahead_buy_minutes')->value('value');
        //         $ahead_time = abs($value*60);
        //     }
        // }
        // else if($memberInfo['qiandan'] == 1){
        //     $ahead_buy_minutes = Db::name('config')->where('ename', 'ahead_buy_minutes')->value('value');
        //     $ahead_time = abs($ahead_buy_minutes*60);
        // }
        
// echo $ahead_time;exit;
        if ($user_id){
            $wine_deal_area_id_arr = Db::name('wine_order_record')->where('buy_id', $user_id)
                                    ->where('addtime', '>=', strtotime('today'))
                                    ->column('wine_deal_area_id,status');

            for ($i=0; $i<count($list); $i++){
                $list[$i]['status'] = isset($wine_deal_area_id_arr[$list[$i]['id']]) ? 1 : 0;
                $count = Db::name('wine_order_record')->where('buy_id', $user_id)->where('wine_deal_area_id', $list[$i]['id'])->where('addtime', '>=', strtotime('today'))->count();
                $count1 = Db::name('wine_order_qiangou')->where('buy_id', $user_id)->where('wine_deal_area_id', $list[$i]['id'])->where('addtime', '>=', strtotime('today'))->count();
                if($count && $count1){
                    $list[$i]['canyu'] = 1;
                }
                else{
                    $list[$i]['canyu'] = 0;
                }
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
    
    public function buyGoods(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $input = input();
                    
                    $info = Db::name('wine_order_saler')->alias('wos')
                        ->where('wos.isshow', 1)
                        ->join('wine_goods wg', 'wg.id = wos.wine_goods_id', 'inner')
                        ->join('wine_deal_area wda', 'wda.id = wos.wine_deal_area_id', 'inner')
                        ->where('wos.id', $input['wine_order_saler_id'])
                        ->field('wda.deal_area, wda.odd_num, wg.deposit, wg.goods_name, wg.thumb_url, wos.sale_amount, wg.rate, wos.sale_id, wos.wine_goods_id, wos.id wine_order_saler_id, wos.odd, wos.wine_deal_area_id, wda.id wda_id')
                        ->find();
                    
                    $ahead_time = 0;
                    // $qiandan = Db::name('member')->where('id', $user_id)->value('qiandan');
                    // if($qiandan==1){
                    //     $ahead_buy_minutes = Db::name('config')->where('ename', 'ahead_buy_minutes')->value('value');
                    //     $ahead_time = $ahead_buy_minutes*60;
                    // }
                    
                    $ymd = date('Y-m-d');
                    $shijian = explode('-', $info['deal_area']);
                    $start_time = strtotime($ymd . $shijian[0])-$ahead_time;
                    $end_time = strtotime($ymd . $shijian[1]);
                    $cur_time = time();
                    
                    $canyuqinagoucount = Db::name('wine_order_qiangou')->where('wine_goods_id', $info['wine_goods_id'])->where('wine_deal_area_id', $info['wine_deal_area_id'])->where('buy_id', $user_id)->where('addtime', '>=', strtotime('today'))->count();
                    $generate_dan = $canyuqinagoucount;
                        
                    if($cur_time>=$start_time-10){
                        $qiangouinfo = [
                            'goods_name' => $info['goods_name'],
                            'goods_thumb' => $info['thumb_url'],
                            'addtime' => time(),
                            'buy_amount' => $info['sale_amount'],
                            'buy_id' => $user_id,
                            'odd' => uniqid(),
                            'wine_goods_id' => $info['wine_goods_id'],
                            'wine_deal_area_id' => $info['wine_deal_area_id']
                        ];
                        
                        if($canyuqinagoucount==0){
                            $rrr = Db::name('wine_order_qiangou')->insert($qiangouinfo);
                            // if(!$rrr){
                            //     $value = array('status'=>400,'mess'=>'抢购失败','data'=>array('status'=>400));
                            //     return json($value);
                            // }
                            if($rrr){
                                $generate_dan = 1;
                            }
                        }
                        else if($canyuqinagoucount > 0){
                            $generate_dan = 1;
                        }
                    }
                    
                    if($cur_time>=$start_time && $cur_time<=$end_time){
                        $sdskkk = Db::name('wine_order_record')->where('wine_deal_area_id', $info['wine_deal_area_id'])
                         ->where('buy_id', $user_id)->where('addtime', '>=', strtotime('today'))->find();
                        if(is_null($sdskkk) && $info['wda_id']!=10){
                            $value = array('status'=>400,'mess'=>'很抱歉，您没有预约不能参加抢购','data'=>array('status'=>400));
                            return json($value);
                        }
                        
                        $memberInfo = Db::name('member')->where('id', $user_id)->find();
                        if($info['wda_id'] == 10){
                            if($memberInfo['agent_type']<=1 && $memberInfo['false_agent_type']<=1){
                                $value = array('status'=>400,'mess'=>'只有区代以上级别才可进行预约购买','data'=>array('status'=>400));
                                return json($value);
                            }
                        }
                        
                        // if($generate_dan==1){
                        if(false){
                            if($memberInfo['generate_price']>0 && !empty($memberInfo['generate_phone'])){
                                $generate_member_info = Db::name('member')->where('phone', $memberInfo['generate_phone'])->find();
                                if(!is_null($generate_member_info)){
                                    $yi_generate_count = Db::name('wine_order_buyer')->where('addtime', '>=', strtotime('today'))->where('buy_id', $user_id)->where('wine_goods_id', $info['wine_goods_id'])->where('wine_deal_area_id', $info['wine_deal_area_id'])->where('generate_buyer_dan', 1)->count();
                                    
                                    if($yi_generate_count == 0){
                                        $timer = time();
                                        $generate_saler = [
                                            'goods_name' => $info['goods_name'],
                                            'addtime' => $timer,
                                            'goods_rate' => $info['rate'],
                                            'goods_thumb' => $info['thumb_url'],
                                            'pipei_amount' => $memberInfo['generate_price'],
                                            'sale_amount' => $memberInfo['generate_price'],
                                            'sale_id' => $generate_member_info['id'],
                                            'odd' => uniqid(),
                                            'wine_goods_id' => $info['wine_goods_id'],
                                            'status' => 1,
                                            'wine_deal_area_id' => $info['wine_deal_area_id'],
                                            'generate' => 1
                                        ];
                                        
                                        $insertGetId = Db::name('wine_order_saler')->insertGetId($generate_saler);
                                        if($insertGetId){
                                            $generate_buyer = [
                                                'goods_name' => $info['goods_name'],
                                                'addtime' => $timer,
                                                'buy_amount' => $memberInfo['generate_price'],
                                                'goods_thumb' => $info['thumb_url'],
                                                'sale_amount' => $memberInfo['generate_price'] + $memberInfo['generate_price']*$info['rate']/100,
                                                'buy_id' => $user_id,
                                                'sale_id' => $generate_member_info['id'],
                                                'wine_goods_id' => $info['wine_goods_id'],
                                                'status' => 1,
                                                'wine_order_saler_id' => $insertGetId,
                                                'wine_order_record_id' => $sdskkk['id'],
                                                'wine_deal_area_id' => $info['wine_deal_area_id'],
                                                'odd' => uniqid(),
                                                'day' => 0,
                                                'date' => date('Y-m-d'),
                                                'generate_buyer_dan' => 1
                                            ];
                                            
                                            $res = Db::name('wine_order_buyer')->insert($generate_buyer);
                                            if(!$res){
                                                Db::name('wine_order_saler')->where('id', $insertGetId)->update([
                                                    'delete' => 1
                                                ]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                         
                        if($info['sale_id'] == $user_id){
                            $value = array('status'=>400,'mess'=>'不能购买自己的商品','data'=>array('status'=>400));
                            return json($value);
                        }
                        
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
                        
                        $count = Db::name('wine_order_buyer')->where('wine_deal_area_id', $info['wine_deal_area_id'])->where('buy_id', $user_id)->where('addtime', '>=', strtotime('today'))->count();
                        if($count >= $info['odd_num']){
                            $value = array('status'=>400,'mess'=>'不好意思，您慢了一步','data'=>array('status'=>400));
                            return json($value);
                        }
                    
                        $wallet_info = Db::name('wallet')->where('user_id', $user_id)->find();
                        if(is_null($wallet_info)){
                            $value = array('status'=>400,'mess'=>'网络异常','data'=>array('status'=>400));
                        }
                        else{
                            $info = Db::name('wine_order_saler')->alias('wos')
                                ->join('wine_goods wg', 'wg.id = wos.wine_goods_id', 'inner')
                                ->join('wine_deal_area wda', 'wda.id = wos.wine_deal_area_id', 'inner')
                                ->where('wos.id', $input['wine_order_saler_id'])
                                ->where('wos.status', 0)
                                ->where('wos.delete', 0)
                                ->where('wg.onsale', 1)
                                ->where('wos.onsale', 1)
                                ->field('wda.deal_area, wda.odd_num, wg.deposit, wg.goods_name, wg.thumb_url, wos.sale_amount, wg.rate, wos.sale_id, wos.wine_goods_id, wos.id wine_order_saler_id, wos.odd, wos.wine_deal_area_id, wda.id wda_id')
                                ->find();
                            if(is_null($info)){
                                $value = array('status'=>400,'mess'=>'不好意思，您慢了一步','data'=>array('status'=>400));
                            }
                            else{
                                Db::startTrans();
                                try{
                                    $insert_data = [
                                        'goods_name' => $info['goods_name'],
                                        'goods_thumb' => $info['thumb_url'],
                                        'addtime' => time(),
                                        'buy_amount' => $info['sale_amount'],
                                        'sale_amount' => $info['sale_amount'] + $info['sale_amount']*$info['rate']/100,
                                        'buy_id' => $user_id,
                                        'sale_id' => $info['sale_id'],
                                        'wine_goods_id' => $info['wine_goods_id'],
                                        'status' => 1,
                                        'wine_order_saler_id'=>$info['wine_order_saler_id'],
                                        'odd' => uniqid(),
                                        'day' => 0,
                                        'wine_deal_area_id' => $info['wine_deal_area_id'],
                                        'date' => date('Y-m-d')
                                    ];
                                    $res = Db::name('wine_order_buyer')->insertGetId($insert_data);
                                    if(!$res){
                                        throw new Exception('抢购失败');
                                    }
                
                                    $res = Db::name('wine_order_saler')->where('id', $info['wine_order_saler_id'])->where('status', 0)->where('delete', 0)->update([
                                        'status' => 1
                                    ]);
                                    if(!$res){
                                        throw new Exception('抢购失败2');
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
                        
                    }
                    else{
                        $value = array('status'=>400,'mess'=>'活动未开始或活动已结束','data'=>array('status'=>400));
                        // die();
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

    // public function buyGoods(){
    //     if(request()->isPost()){
    //         if(input('post.token')){
    //             $gongyong = new GongyongMx();
    //             $result = $gongyong->apivalidate();
    //             if($result['status'] == 200){
    //                 $user_id = $result['user_id'];
    //                 $input = input();
                    
    //                 $info = Db::name('wine_order_saler')->alias('wos')
    //                     ->join('wine_goods wg', 'wg.id = wos.wine_goods_id', 'inner')
    //                     ->join('wine_deal_area wda', 'wda.id = wos.wine_deal_area_id', 'inner')
    //                     ->where('wos.id', $input['wine_order_saler_id'])
    //                     ->where('wos.status', 0)
    //                     ->where('wos.delete', 0)
    //                     ->where('wg.onsale', 1)
    //                     ->where('wos.onsale', 1)
    //                     ->field('wda.deal_area, wda.odd_num, wg.deposit, wg.goods_name, wg.thumb_url, wos.sale_amount, wg.rate, wos.sale_id, wos.wine_goods_id, wos.id wine_order_saler_id, wos.odd, wos.wine_deal_area_id, wda.id wda_id')
    //                     ->find();
                        
    //                 $qiangouinfo = [
    //                     'goods_name' => $info['goods_name'],
    //                     'goods_thumb' => $info['thumb_url'],
    //                     'addtime' => time(),
    //                     'buy_amount' => $info['sale_amount'],
    //                     'buy_id' => $user_id,
    //                     'odd' => uniqid(),
    //                     'wine_goods_id' => $info['wine_goods_id'],
    //                     'wine_deal_area_id' => $info['wine_deal_area_id']
    //                 ];
    //                 $canyuqinagoucount = Db::name('wine_order_qiangou')->where('wine_goods_id', $qiangouinfo['wine_goods_id'])->where('wine_deal_area_id', $qiangouinfo['wine_deal_area_id'])->where('buy_id', $qiangouinfo['buy_id'])->where('addtime', '>=', strtotime('today'))->count();
    //                 if($canyuqinagoucount==0){
    //                     $rrr = Db::name('wine_order_qiangou')->insert($qiangouinfo);
    //                     // if(!$rrr){
    //                     //     $value = array('status'=>400,'mess'=>'抢购失败','data'=>array('status'=>400));
    //                     //     return json($value);
    //                     // }
    //                 }
                    
    //                 if($this->judgeEnable($user_id) === false){
    //                     $value = array('status'=>400,'mess'=>'请先激活账号','data'=>array('status'=>400));
    //                     return json($value);
    //                 }
                    
    //                 $count = 0;
    //                 $count1 = Db::name('bank_card')->where('user_id', $user_id)->count();
    //                 if($count1 > 0){
    //                     $count++;
    //                 }
                    
    //                 $count2 = Db::name('zfb_card')->where('user_id', $user_id)->count();
    //                 if($count2 > 0){
    //                     $count++;
    //                 }
                    
    //                 $count3 = Db::name('wx_card')->where('user_id', $user_id)->count();
    //                 if($count3 > 0){
    //                     $count++;
    //                 }
                    
    //                 if($count < 1){
    //                     $value = array('status'=>400,'mess'=>'最少绑定一种收款方式','data'=>array('status'=>400));
    //                     return json($value);
    //                 }
                        
    //                  $memberInfo = Db::name('member')->where('id', $user_id)->find();
                     
    //                  if($info['wda_id'] == 10){
    //                      if($memberInfo['agent_type']<=1 && $memberInfo['false_agent_type']<=1){
    //                          $value = array('status'=>400,'mess'=>'只有区代以上级别才可进行预约购买','data'=>array('status'=>400));
    //                          return json($value);
    //                      }
    //                  }
                        
    //                 if(is_null($info)){
    //                     $value = array('status'=>400,'mess'=>'不好意思，您慢了一步','data'=>array('status'=>400));
    //                 }
    //                 else{
    //                     if($info['sale_id'] == $user_id){
    //                         $value = array('status'=>400,'mess'=>'不能购买自己的商品','data'=>array('status'=>400));
    //                         return json($value);
    //                     }
                        
    //                     $ahead_time = 0;

    //                     $qiandan = Db::name('member')->where('id', $user_id)->value('qiandan');
    //                     if($qiandan==1){
    //                         $ahead_buy_minutes = Db::name('config')->where('ename', 'ahead_buy_minutes')->value('value');
    //                         $ahead_time = $ahead_buy_minutes*60;
    //                     }
                        
    //                     $ymd = date('Y-m-d');
    //                     $shijian = explode('-', $info['deal_area']);
    //                     $start_time = strtotime($ymd . $shijian[0])-$ahead_time;
    //                     $end_time = strtotime($ymd . $shijian[1]);
    //                     $cur_time = time();
                        
    //                     if($cur_time>=$start_time && $cur_time<=$end_time){
                        
    //                         $count = Db::name('wine_order_buyer')->where('wine_deal_area_id', $info['wine_deal_area_id'])->where('buy_id', $user_id)->where('addtime', '>=', strtotime('today'))->count();
    //                         if($count >= $info['odd_num']){
    //                             $value = array('status'=>400,'mess'=>'本场最多只能抢购'.$info['odd_num'].'单','data'=>array('status'=>400));
    //                             return json($value);
    //                         }
                        
    //                         $wallet_info = Db::name('wallet')->where('user_id', $user_id)->find();
    //                         if(is_null($wallet_info)){
    //                             $value = array('status'=>400,'mess'=>'网络异常','data'=>array('status'=>400));
    //                         }
    //                         else{
    //                             $sdskkk = Db::name('wine_order_record')->where('wine_deal_area_id', $info['wine_deal_area_id'])
    //                              ->where('buy_id', $user_id)->where('addtime', '>=', strtotime('today'))->find();
    //                             if(is_null($sdskkk) && $info['wda_id']!=10){
    //                                 $value = array('status'=>400,'mess'=>'很抱歉，您没有预约不能参加抢购','data'=>array('status'=>400));
    //                                 return json($value);
    //                             }

    //                             Db::startTrans();
    //                             try{
    //                                 $insert_data = [
    //                                     'goods_name' => $info['goods_name'],
    //                                     'goods_thumb' => $info['thumb_url'],
    //                                     'addtime' => time(),
    //                                     'buy_amount' => $info['sale_amount'],
    //                                     'sale_amount' => $info['sale_amount'] + $info['sale_amount']*$info['rate']/100,
    //                                     'buy_id' => $user_id,
    //                                     'sale_id' => $info['sale_id'],
    //                                     'wine_goods_id' => $info['wine_goods_id'],
    //                                     'status' => 1,
    //                                     'wine_order_saler_id'=>$info['wine_order_saler_id'],
    //                                     'odd' => uniqid(),
    //                                     'day' => 0,
    //                                     'wine_deal_area_id' => $info['wine_deal_area_id'],
    //                                     'date' => date('Y-m-d')
    //                                 ];
    //                                 $res = Db::name('wine_order_buyer')->insertGetId($insert_data);
    //                                 if(!$res){
    //                                     throw new Exception('抢购失败');
    //                                 }

                                     
    //                                 $res = Db::name('wine_order_saler')->where('id', $info['wine_order_saler_id'])->where('status', 0)->where('delete', 0)->update([
    //                                     'status' => 1
    //                                 ]);
    //                                 if(!$res){
    //                                     throw new Exception('抢购失败2');
    //                                 }
                                    
    //                                 $value = array('status'=>200,'mess'=>'抢购成功','data'=>array('status'=>200));
                                    
    //                                 Db::commit();
    //                             }
    //                             catch(Exception $e){
    //                                 $value = array('status'=>400,'mess'=>$e->getMessage(),'data'=>array('status'=>400));
    //                                 Db::rollback();
    //                             }
    //                         }
                            
    //                     }
    //                     else{
    //                         $value = array('status'=>400,'mess'=>'活动未开始或活动已结束','data'=>array('status'=>400));
    //                     }
    //                 }
    //             }else{
    //                 $value = $result;
    //             }
    //         }else{
    //             $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
    //         }
    //     }else{
    //         $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
    //     }
    //     return json($value);   
    // }
    
    public function lst()
    {
        $post = input('post.');
        $user_id = Db::name('rxin')->where('token', $post['token'])->value('user_id');

        $list = Db::name('wine_goods')->where('onsale', 1)->order('id asc')->select();

        if ($user_id){
            $yesterdayStart = strtotime('yesterday');
            $yesterdayEnd = strtotime('today')-1;
            
            $wine_goods_id_arr = Db::name('wine_order_record')->where('buy_id', $user_id)
                                    ->column('wine_goods_id,status');
            
            for ($i=0; $i<count($list); $i++){
                $list[$i]['status'] = $wine_goods_id_arr[$list[$i]['id']];
            }
        }
        
        $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>$list);
        
        return json($value);
    }

    public function yuyueReturnRecord(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                
                if(Cache::get('yuyueReturnRecord_'.$user_id) == 1){
                    $value = array('status'=>400,'mess'=>'缓存','data'=>array('status'=>400));
                }
                else{
                    Cache::set('yuyueReturnRecord_'.$user_id, 1, 2);
                    
                    $post = input('post.');
                    if($post['wine_deal_area_id'] == 10){
                        return ;
                    }
                    $isExist = Db::name('wine_yuyue_return')->where('wine_deal_area_id', $post['wine_deal_area_id'])->where('wine_goods_id', $post['wine_goods_id'])->where('user_id', $user_id)->where('addtime', '>=', strtotime('today'))->count();
                    if($isExist){
                        $value = array('status'=>400,'mess'=>'数据已存在','data'=>array('status'=>400));
                    }
                    else{
                        if(!$post['wine_goods_id'] || !$post['wine_deal_area_id']){
                            $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
                        }
                        else{
                            $value = Db::name('config')->where('ename', 'enter_return_deposit_page_ahead_time')->value('value')*60*60;
                            
                            $deal_area = Db::name('wine_deal_area')->where('id', $post['wine_deal_area_id'])->value('deal_area');
                            $deal_area_explode = explode('-', $deal_area);
                            
                            $start_time = strtotime(date('Y-m-d').' '.$deal_area_explode[0])-$value;
                            $end_time = strtotime(date('Y-m-d').' '.$deal_area_explode[1]);
                            $time = time();
                            if($time>=$start_time && $time<=$end_time){
                                $yuyue_return = [
                                    'wine_deal_area_id' => $post['wine_deal_area_id'],
                                    'addtime' => $time,
                                    'wine_goods_id' => $post['wine_goods_id'],
                                    'user_id' => $user_id
                                ];
                                
                                $res = Db::name('wine_yuyue_return')->insert($yuyue_return);
                                if(!$res){
                                    $value = array('status'=>400,'mess'=>'失败','data'=>array('status'=>400));
                                }
                                else{
                                    $value = array('status'=>200,'mess'=>'成功','data'=>array('status'=>200));
                                }
                            }
                            else{
                                $value = array('status'=>400,'mess'=>'不在时间段内','data'=>array('status'=>400));
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
        // return json($value);
    }
    
    public function getTime(){
        $input = input();
        $user_id = Db::name('rxin')->where('token', $input['token'])->value('user_id');
        
        if(!$input['wine_deal_area_id']){
            $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
        }
        else{
            $wine_deal_area = Db::name('wine_deal_area')->where('id', $input['wine_deal_area_id'])->where('status', 1)->find();
            if(is_null($wine_deal_area)){
                $value = array('status'=>400,'mess'=>'时间段不存在','data'=>array('status'=>400));
            }
            else{
                $memberInfo = Db::name('member')->where('id', $user_id)->field('qiandan')->find();
                if($memberInfo['qiandan'] == 1){
                    $ahead_buy_minutes = Db::name('config')->where('ename', 'ahead_buy_minutes')->value('value');
                    $deal_area_time = explode('-', $wine_deal_area['deal_area']);
                    $explodeValue = explode(':', $deal_area_time[0]);
                    $totalSeconds = ($explodeValue[0]*60*60+$explodeValue[1]*60+$explodeValue[2] - $ahead_buy_minutes*60 < 0) ? 0 : $explodeValue[0]*60*60+$explodeValue[1]*60+$explodeValue[2] - $ahead_buy_minutes*60;
                    $hours = intval($totalSeconds/3600)<10 ? '0'.intval($totalSeconds/3600) : intval($totalSeconds/3600);
                    $minutes = intval($totalSeconds%3600/60)<10 ? '0'.intval($totalSeconds%3600/60) : intval($totalSeconds%3600/60);
                    $seconds = intval($totalSeconds%60)<10 ? '0'.intval($totalSeconds%60) : intval($totalSeconds%60);
                    $deal_area_time[0] = $hours.':'.$minutes.':'.$seconds;
                    
                    $wine_deal_area['deal_area'] = implode('-', $deal_area_time);
                }
                
                $startHis = trim(explode('-', $wine_deal_area['deal_area'])[0]);
                $time_countdown = strtotime(date('Y-m-d').' '.$startHis) - time();
                
                $value = array('status'=>200,'mess'=>'获取信息成功', 'list'=>['time_countdown'=>$time_countdown]);
            }
        }
        
        return json($value);
    }
    
    public function qiangou(){
        $input = input();
        $user_id = Db::name('rxin')->where('token', $input['token'])->value('user_id');
        
        if(!$input['wine_deal_area_id']){
            $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
        }
        else{
            if(!$input['wine_goods_id']){
                $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
            }
            else{
                $wine_deal_area = Db::name('wine_deal_area')->where('id', $input['wine_deal_area_id'])->where('status', 1)->find();
                if(is_null($wine_deal_area)){
                    $value = array('status'=>400,'mess'=>'时间段不存在','data'=>array('status'=>400));
                }
                else{
                    $list = Db::name('wine_order_saler')->alias('wos')
                            ->join('wine_goods wg', 'wg.id=wos.wine_goods_id', 'left')
                            ->field('wos.*, wg.value')
                            ->where('wos.wine_goods_id', $input['wine_goods_id'])
                            ->where('wos.sale_id', '<>', $user_id)
                            ->where('assign_buyer_id', 0)
                            ->where('wos.isshow', 1)
                            ->where('wos.status', 0)->where('wos.delete', 0)->where('wos.onsale', 1)->where('wos.wine_deal_area_id', $input['wine_deal_area_id'])->limit($input['page']*$input['rows'], $input['rows'])
                    // ->where('addtime', '>=', strtotime('today'))
                    ->select();
                    
                    $memberInfo = Db::name('member')->where('id', $user_id)->field('qiandan')->find();
                    if($memberInfo['qiandan'] == 1){
                        $ahead_buy_minutes = Db::name('config')->where('ename', 'ahead_buy_minutes')->value('value');
                        $deal_area_time = explode('-', $wine_deal_area['deal_area']);
                        $explodeValue = explode(':', $deal_area_time[0]);
                        $totalSeconds = ($explodeValue[0]*60*60+$explodeValue[1]*60+$explodeValue[2] - $ahead_buy_minutes*60 < 0) ? 0 : $explodeValue[0]*60*60+$explodeValue[1]*60+$explodeValue[2] - $ahead_buy_minutes*60;
                        $hours = intval($totalSeconds/3600)<10 ? '0'.intval($totalSeconds/3600) : intval($totalSeconds/3600);
                        $minutes = intval($totalSeconds%3600/60)<10 ? '0'.intval($totalSeconds%3600/60) : intval($totalSeconds%3600/60);
                        $seconds = intval($totalSeconds%60)<10 ? '0'.intval($totalSeconds%60) : intval($totalSeconds%60);
                        $deal_area_time[0] = $hours.':'.$minutes.':'.$seconds;
                        
                        $wine_deal_area['deal_area'] = implode('-', $deal_area_time);
                    }
                    
                    $startHis = trim(explode('-', $wine_deal_area['deal_area'])[0]);
                    $time_countdown = strtotime(date('Y-m-d').' '.$startHis) - time();
                    // $startHisArr = explode(':', $startHis);
                    // $startHisArr[0]*3600 + $startHisArr[1]*60 + $startHisArr[2]
                    
                    // echo Db::name('wine_order_saler')->getLastSql();exit();
                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list, 'time'=>explode('-', $wine_deal_area['deal_area']), 'time_countdown'=>$time_countdown);
                }
            }
        }
        
        return json($value);
    }
    
    public function lsts()
    {
        $post = input('post.');
        $user_id = Db::name('rxin')->where('token', $post['token'])->value('user_id');

        $list = Db::name('wine_goods')->where('id', '>', 907)->where('onsale', 1)->order('id asc')->select();

        if ($user_id){
            $yesterdayStart = strtotime('yesterday');
            $yesterdayEnd = strtotime('today')-1;

            $wine_goods_id_arr = Db::name('wine_order_record')->where('buy_id', $user_id)
//                                    ->where('addtime', '>=', $yesterdayStart)
//                                    ->where('addtime', '<=', $yesterdayEnd)
                                    // ->where('status', 0)
                                    // ->column('wine_goods_id');
                                    // ->field('wine_goods_id, status')
                                    ->column('wine_goods_id,status');

            for ($i=0; $i<count($list); $i++){
                // if (in_array($list[$i]['id'], $wine_goods_id_arr)){
                //     $list[$i]['checked'] = 1;
                // }
                // else{
                //     $list[$i]['checked'] = 0;
                // }
                // Db::name('wine_order_buyer')->where('delete', 0)->where('wine_order_record_id', $list[$i][''])
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
                
                 if($post['wine_deal_area_id']==10){
                     $value = array('status'=>200,'mess'=>'正常','data'=>array('status'=>200));
                    return json($value);
                 }
                        
                //  $ahead_record_stop_common_seconds = Db::name('config')->where('ename', 'ahead_record_stop_common')->value('value')*60;
                 $ahead_record_stop_common_seconds = $this->tiqian;
                 $wine_deal_area_info = Db::name('wine_deal_area')->where('id', $post['wine_deal_area_id'])->where('status', 1)->field('deposit,deal_area')->find();
                 if (is_null($wine_deal_area_info)){
                    $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
                    return json($value);
                 }
                 
                 $aheadHim = trim(explode('-', $wine_deal_area_info['deal_area'])[0]);
                 $aheadHimfff = trim(explode('-', $wine_deal_area_info['deal_area'])[1]);
                 $before_time_ke_record = strtotime(date('Y-m-d').' '.$aheadHim)-$ahead_record_stop_common_seconds;
                 $after_time_ke_record = strtotime(date('Y-m-d').' '.$aheadHimfff);
                 $count = Db::name('wine_order_record')->where('wine_goods_id', $post['wine_goods_id'])->where('wine_deal_area_id', $post['wine_deal_area_id'])->where('buy_id', $user_id)->where('addtime', '>=', strtotime('today'))->count();
                 
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
                if(!isset($post['wine_deal_area_id'])){
                    $value = array('status'=>400,'mess'=>'不存在该商品','data'=>array('status'=>400));
                }
                else{
                    $info = Db::name('wine_deal_area')->where('id', $post['wine_deal_area_id'])->find();
                    if (is_null($info)){
                        $value = array('status'=>400,'mess'=>'不存在该商品','data'=>array('status'=>400));
                    }
                    else{
                        $count = Db::name('wine_order_record')->where('wine_deal_area_id', $post['wine_deal_area_id'])->where('addtime', '>=', strtotime('today'))->where('buy_id', $user_id)->count();
                        if($count > 0){
                            $value = array('status'=>400,'mess'=>'禁止重复预约','data'=>array('status'=>400));
                            return json($value);
                        }
                        
                         $ahead_record_stop_common_seconds = Db::name('config')->where('ename', 'ahead_record_stop_common')->value('value')*60;
                         $wine_deal_area_info = Db::name('wine_deal_area')->where('id', $post['wine_deal_area_id'])->where('status', 1)->field('deposit,deal_area')->find();
                         $wine_deal_area_info_deposit = $wine_deal_area_info['deposit'];
                         if (is_null($wine_deal_area_info)){
                            $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
                            return json($value);
                         }
                         
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

                                     $count = Db::name('wine_order_record')->where('id', $post['wine_deal_area_id'])->where('buy_id', $user_id)->where('addtime', '>=', $todaytime)->count();
                                     if($count > 0){
                                         $value = array('status'=>400,'mess'=>'同一款商品一天只能预约一次','data'=>array('status'=>400));
                                     }
                                     else{
                                        $memberInfo = Db::name('member')->where('id', $user_id)->find();
                                        if(empty($memberInfo['idcard']) || empty($memberInfo['true_name']) || $memberInfo['reg_enable']==0){
                                             $value = array('status'=>400,'mess'=>'请先激活账号和完成实名认证','data'=>array('status'=>400));
                                             return json($value);
                                        }
                                        
                                         $memberInfo = Db::name('member')->where('id', $user_id)->find();
                                         
                                         if($post['wine_deal_area_id'] == 10){
                                             if($memberInfo['agent_type']<=1 && $memberInfo['false_agent_type']<=1){
                                                 $value = array('status'=>400,'mess'=>'只有区代以上级别才可进行预约购买','data'=>array('status'=>400));
                                                 return json($value);
                                             }
                                         }
                                         
                                         $wine_goods_info = Db::name('wine_goods')->where('id', $post['wine_goods_id'])->where('onsale', 1)->find();
                                         if (is_null($wine_goods_info)) {
                                             // code...
                                             $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
                                             return json($value);
                                         }
                                         Db::startTrans();
                                         try{
                                             $insertId = Db::name('wine_order_record')->insertGetId([
                                                 'addtime'=>time(),
                                                 'buy_id'=>$user_id,
                                                 'goods_name'=>$wine_goods_info['goods_name'],
                                                 'goods_thumb'=>$wine_goods_info['thumb_url'],
                                                 'wine_goods_id'=>$wine_goods_info['id'],
                                                 'odd'=>uniqid(),
                                                 'wine_deal_area_id'=>$post['wine_deal_area_id'],
                                                 'frozen_point' => $wine_deal_area_info_deposit
                                             ]);
                                             if(!$insertId)throw new Exception('预约失败');
                                             
                                             $res = Db::name('wallet')->where('user_id', $user_id)->dec('point', $wine_deal_area_info_deposit)->inc('frozen_point', $wine_deal_area_info_deposit)->update();
                                             if(!$res)throw new Exception('预约失败');
                                             $detail = [
                                                'de_type' => 2,
                                                'zc_type' => 70,
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
                                             if(!$res)throw new Exception('预约失败');
                                            
                                             $detail = [
                                                'de_type' => 1,
                                                'sr_type' => 70,
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
                                             if(!$res)throw new Exception('预约失败');
                                             
                                             $value = array('status'=>200,'mess'=>'预约成功');
                                             
                                             Db::commit();
                                         }
                                         catch(\Exception $e){
                                             $value = array('status'=>400,'mess'=>'预约失败');
                                             
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
    
    public function order_record(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];
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
                
                if($count < 2){
                    $value = array('status'=>400,'mess'=>'最少绑定两种收款方式','data'=>array('status'=>400));
                    return json($value);
                }
                // if ($count1 == 0){
                //     $count2 = Db::name('zfb_card')->where('user_id', $user_id)->count();
                //     if($count2 == 0){
                //         $count3 = Db::name('wx_card')->where('user_id', $user_id)->count();
                //         if($count3 == 0){
                //             $value = array('status'=>400,'mess'=>'请先绑定收款方式','data'=>array('status'=>400));
                //             return json($value);
                //         }
                //     }
                // }

                $post = input('post.');
                
                // 验证购买限制
                // $bool = $this->wineBuyLimit($post['wine_goods_id'], $user_id);
                // if($bool['status'] != 200){
                    // $value = array('status'=>400,'mess'=>$bool['mess'],'data'=>array('status'=>400));
                    // return json($value);
                // }
                
                if(!isset($post['wine_goods_id'])){
                    $value = array('status'=>400,'mess'=>'不存在该商品','data'=>array('status'=>400));
                }
                else{
                    $info = Db::name('wine_goods')->where('id', $post['wine_goods_id'])->where('onsale', 1)->find();
                    if (is_null($info)){
                        $value = array('status'=>400,'mess'=>'不存在该商品','data'=>array('status'=>400));
                    }
                    else{
                        $count = Db::name('wine_order_record')->where('wine_goods_id', $post['wine_goods_id'])->where('buy_id', $user_id)->where('status', 0)->count();
                        if($count > 0){
                            $value = array('status'=>400,'mess'=>'禁止重复订货','data'=>array('status'=>400));
                            return json($value);
                        }
                        
                        $adopt = explode('-', trim($info['adopt']));
                        $start = explode(':', trim($adopt[0]));
                        $starttime = $start[0]*60*60+$start[1]*60;

                        $end = explode(':', trim($adopt[1]));
                        $endtime = $end[0]*60*60+$end[1]*60;

                         $curtime = date('H')*60*60+date('i')*60;
                        //  if($curtime>$endtime || $curtime<$starttime){
                         if(false){
                             $value = array('status'=>400,'mess'=>'不在购买时间','data'=>array('status'=>400));
                         }
                         else{
                             $wallet_info = Db::name('wallet')->where('user_id', $user_id)->find();
                            //  $value = Db::name('config')->where('ename', 'brand_ remain_num')->value('value');
                             if($wallet_info['brand']<$info['goods_desc']){
                                 $value = array('status'=>400,'mess'=>'品牌使用费不足','data'=>array('status'=>400));
                             }
                             else{
                                //  $wallet = Db::name('wallet')->where('user_id', $user_id)->find();
                                //  if($wallet['brand'] < $info['deposit']){
                                //      $value = array('status'=>400,'mess'=>'品牌使用费不足','data'=>array('status'=>400));
                                //      return json($value);
                                //  }
                                 
                                 $id_arr = Db::name('wine_goods')->where('id', '<', $post['wine_goods_id'])->where('onsale', 1)->column('id');

                                 $count = Db::name('wine_order_buyer')->whereIn('wine_goods_id', $id_arr)->where('buy_id', $user_id)->group('wine_goods_id')->count();

                                //  if($count != count($id_arr)){
                                 if(false){
                                     $value = array('status'=>400,'mess'=>'购买该商品需完成上面几款商品的购买','data'=>array('status'=>400));
                                 }
                                 else{
                                     $todaytime = strtotime('today');

                                     $count = Db::name('wine_order_record')->where('wine_goods_id', $post['wine_goods_id'])->where('buy_id', $user_id)->where('addtime', '>=', $todaytime)->count();
                                    //  if($count > 0){
                                     if(false){
                                         $value = array('status'=>400,'mess'=>'同一款商品一天只能预定一次','data'=>array('status'=>400));
                                     }
                                     else{
                                         Db::startTrans();
                                         try{
                                             $res = Db::name('wine_order_record')->insert([
                                                 'goods_name'=>$info['goods_name'],
                                                 'addtime'=>time(),
    //                                             'goods_rate'=>$info['rate'],
                                                 'goods_thumb'=>$info['thumb_url'],
                                                 'buy_id'=>$user_id,
                                                 'odd'=>uniqid(),
                                                 'wine_goods_id'=>$info['id']
                                             ]);
                                             if(!$res)throw new Exception('预定失败');
                                             
                                             
                                             $res = Db::name('wallet')->where('user_id', $user_id)->setDec('brand', $info['deposit']);
                                             if(!$res)throw new Exception('扣除品牌使用费失败');
                                             
                                             $detail = [
                                                'de_type' => 2,
                                                'zc_type' => 10,
                                                'before_price'=> $wallet_info['brand'],
                                                'price' => $info['deposit'],
                                                 'after_price'=> $wallet_info['brand']-$info['deposit'],
                                                'user_id' => $user_id,
                                                'wat_id' => $wallet_info['id'],
                                                'time' => time()
                                             ];
                                             $res = $this->addDetail($detail);
//                                             $res = Db::name('detail')->insert($detail);
                                             if(!$res)throw new Exception('预定失败');
                                             
                                             $value = array('status'=>200,'mess'=>'预定成功');
                                             
                                             Db::commit();
                                         }
                                         catch(\Exception $e){
                                             $value = array('status'=>400,'mess'=>'预定失败');
                                             
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

    public function sale(){exit;
        if(request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if ($result['status'] == 200) {
                $user_id = $result['user_id'];
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
                
                if($count < 2){
                    $value = array('status'=>400,'mess'=>'最少绑定两种收款方式','data'=>array('status'=>400));
                    return json($value);
                }
                
                $manager_to_wine = Db::name('config')->where('ename', 'manager_to_wine')->value('value');
                $doneToWine = Db::name('detail')->where('de_type', 2)->where('zc_type', 4)->where('user_id', $user_id)->where('time', '>=', strtotime('today'))->count();
                if($doneToWine >= $manager_to_wine){
                    $value = array('status'=>400,'mess'=>'已达到上限，每天最多可兑换'.$manager_to_wine.'次','data'=>array('status'=>400));
                    return json($value);
                }
                // $count1 = Db::name('bank_card')->where('user_id', $user_id)->count();
                // if ($count1 == 0){
                //     $count2 = Db::name('zfb_card')->where('user_id', $user_id)->count();
                //     if($count2 == 0){
                //         $count3 = Db::name('wx_card')->where('user_id', $user_id)->count();
                //         if($count3 == 0){
                //             $value = array('status'=>400,'mess'=>'请先绑定收款方式','data'=>array('status'=>400));
                //             return json($value);
                //         }
                //     }
                // }

                $post = input('post.');
                if($post['wine_goods_id']){
                    $info = Db::name('wine_goods')->where('onsale', 1)->where('id', '>', 907)->where('id', $post['wine_goods_id'])->find();
                    if (!is_null($info)){
                        $value = explode('-', $info['value']);
                        if ($value[0]<=$post['managerWard'] && $post['managerWard']<=$value[1]){
                            $paypwd = Db::name('member')->where('id', $user_id)->value('paypwd');
                            if (md5($post['paypwd']) == $paypwd){
                                $wallet = Db::name('wallet')->where('user_id', $user_id)->field('manager_reward,id, brand')->find();
                                $manager_reward = $wallet['manager_reward'];
                                // $value = Db::name('config')->where('ename', 'manage_to_yuliu_manage')->value('value');
                                $deposit = $info['manage_to_yuliu'];
                                if($manager_reward<$post['managerWard']+$deposit){
                                    $value = array('status'=>400,'mess'=>'你的账户管理奖不足以兑换,需要'.($post['managerWard']+$deposit).'管理奖','data'=>array('status'=>400));
                                }
                                else{
                                    $expends = explode('/', $info['expends']);
                                    // $demands_brand_num = Db::name('config')->where('ename', 'manage_to_yuliu_brand')->value('value');
                                    if ($wallet['brand'] < $deposit){
                                        $value = array('status'=>400,'mess'=>'需要预留品牌服务费'.$deposit.'消耗品牌服务费'.$expends[0],'data'=>array('status'=>400));
                                    }
                                    else{
                                        Db::startTrans();
                                        try{
                                            $stock = (int)($post['managerWard']*$info['rate']/100)+$post['managerWard'];
                                            $time = time();
                                            Db::name('wallet')->where('user_id', $user_id)
                                                ->dec('manager_reward', $post['managerWard'])
                                                ->dec('brand', $expends[0])
                                                // ->inc('total_stock', $stock)
                                                ->update();

                                            $data = [
                                                'goods_name'=>$info['goods_name'],
                                                'addtime'=>$time,
                                                'goods_rate'=>$info['rate'],
                                                'goods_thumb'=>$info['thumb_url'],
                                                'sale_amount'=>$stock,
                                                'sale_id'=>$user_id,
                                                'odd'=>uniqid(),
                                                'sort'=>1,
                                                'wine_goods_id'=>$post['wine_goods_id']
                                            ];
                                            $id = Db::name('wine_order_saler')->insertGetId($data);

                                            $detail_manage = [
                                                'de_type' => 2,
                                                'zc_type' => 4,
                                                'price' => $data['sale_amount'],
                                                'order_id' => $id,
                                                'user_id' => $user_id,
                                                'wat_id' => $wallet['id'],
                                                'time' => $time
                                            ];

                                            $this->addDetail($detail_manage);
//                                            Db::name('detail')->insert($detail_manage);

                                            $detail_brand = [
                                                'de_type' => 2,
                                                'zc_type' => 8,
                                                'price' => $expends[0],
                                                'order_id' => $id,
                                                'user_id' => $user_id,
                                                'wat_id' => $wallet['id'],
                                                'time' => $time
                                            ];

                                            $this->addDetail($detail_brand);
//                                            Db::name('detail')->insert($detail_brand);

                                            Db::commit();
                                            $value = array('status'=>200,'mess'=>'兑换成功','data'=>array('status'=>200));
                                        }
                                        catch (\Exception $e){
                                            Db::rollback();
                                            $value = array('status'=>400,'mess'=>'兑换失败','data'=>array('status'=>400));
                                        }
                                    }
                                }
                            }
                            else{
                                $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                            }
                        }
                        else{
                            $value = array('status'=>400,'mess'=>'输入的出售金额有误','data'=>array('status'=>400));
                        }
                    }
                    else{
                        $value = array('status'=>400,'mess'=>'商品不存在','data'=>array('status'=>400));
                    }
                }
                else{
                    $value = array('status'=>400,'mess'=>'商品不存在','data'=>array('status'=>400));
                }
            }
            else{
                $value = $result;
            }
        }
        else{
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
                    $list1 = Db::name('wine_order_saler')->where('wos.delete', 0)->alias('wos')->where('sale_id', $user_id)
                        ->join('wine_deal_area wda', 'wda.id = wos.wine_deal_area_id', 'left')
                        ->join('member m', 'wos.sale_id = m.id', 'left')->field('wos.*,m.phone, wda.desc')
                        // ->where('wos.status', 'in', [$post['status'], 4])
                        ->where('wos.status', 'in', [$post['status']])
                        ->order('wos.addtime desc')->select();
                        
                    $list2 = Db::name('wine_order_buyer')->where('wob.delete', 0)->alias('wob')->where('buy_id', $user_id)
                        ->join('wine_deal_area wda', 'wda.id = wob.wine_deal_area_id', 'left')
                        ->join('member m', 'wob.buy_id = m.id', 'left')->field('wob.*,m.phone, wda.desc')
                        ->where('wob.day', '>', 0)
                        ->where('wob.status', '2')
                        ->order('wob.addtime desc')->select();
                        
                    $list = array_merge($list1, $list2);
                }
                elseif($post['status'] == 2){
                    // $list = Db::name('wine_order_saler')->where('wos.delete', 0)->alias('wos')->where('wos.sale_id', $user_id)
                    //     ->join('wine_order_buyer wob', 'wob.wine_order_saler_id = wos.id', 'left')
                    //     ->join('member m', 'wob.buy_id = m.id', 'left')->field('wos.*,m.phone')
                    //     ->where('wos.status', $post['status'])
                    //     ->order('id desc')->select();
                    $list = Db::name('wine_order_buyer')->alias('wob')
                        ->join('member m', 'm.id = wob.buy_id', 'left')
                        ->join('wine_deal_area wda', 'wda.id = wob.wine_deal_area_id', 'left')
                        ->field('wob.*, m.user_name, m.emergency_phone,m.phone,wda.desc')
                        ->where('wob.delete', 0)
                        ->where('wob.pay_status', 1)
                        ->where('wob.confirm_exchange', null)
                        ->where('wob.status', 'in', [1])
                        ->where('wob.sale_id', $user_id)
                        ->order('id desc')->select();
                }
                else if ($post['status'] == 1 || $post['status'] == 3){
                    if($post['status'] == 1){
                        $list = Db::name('wine_order_buyer')->alias('wob')
                            ->join('wine_deal_area wda', 'wda.id = wob.wine_deal_area_id', 'left')
                            ->join('member m', 'm.id = wob.buy_id', 'left')
                            ->field('wob.*, m.user_name, m.emergency_phone, wda.desc')
                            ->where('wob.delete', 0)
                            ->where('wob.pay_status', 0)
                            // ->where('wob.status', 'in', [$post['status'], 4, 5])
                            ->where('wob.status', 'in', [$post['status'], 9])
                            ->where('wob.sale_id', $user_id)
                            ->order('id desc')->select();
                    }
                    else{
                        // $list = Db::name('wine_order_buyer')->alias('wob')
                        //     ->join('member m', 'm.id = wob.buy_id', 'left')
                        //     ->field('wob.*, m.user_name')
                        //     ->where('wob.delete', 0)->where('wob.status', $post['status'])->where('wob.sale_id', $user_id)
                        //     ->order('id desc')->select();
                        $list = Db::name('wine_order_saler')->where('wos.delete', 0)->alias('wos')->where('sale_id', $user_id)
                        ->join('member m', 'wos.sale_id = m.id', 'left')->field('wos.*,m.phone')
                        // ->where('wos.status', 'in', [$post['status'], 4])
                        ->where('wos.status', 'in', 2)
                        ->order('id desc')->select();
                    }
                }
                
                $count00 = Db::name('wine_order_saler')->where('wos.delete', 0)->alias('wos')->where('sale_id', $user_id)
                    ->join('member m', 'wos.sale_id = m.id', 'left')->field('wos.*,m.phone')
                    // ->where('wos.status', 'in', [$post['status'], 4])
                    ->where('wos.status', 'in', [0])
                    ->order('wos.addtime desc')->count();
                    
                $count000 = Db::name('wine_order_buyer')->where('wob.delete', 0)->alias('wob')->where('buy_id', $user_id)
                    ->join('member m', 'wob.buy_id = m.id', 'left')->field('wob.*,m.phone')
                    ->where('wob.day', '>', 0)
                    ->where('wob.status', '2')
                    ->order('wob.addtime desc')->count();
                $count0 = $count00+$count000;
                
                $count1 = Db::name('wine_order_buyer')->alias('wob')
                        ->join('member m', 'm.id = wob.buy_id', 'left')
                        ->where('wob.delete', 0)
                        ->where('wob.pay_status', 0)
                        ->where('wob.status', 'in', [1, 9])
                        ->where('wob.sale_id', $user_id)
                        ->count();
                
                $count2 = Db::name('wine_order_buyer')->alias('wob')
                    ->join('member m', 'm.id = wob.buy_id', 'left')
                    ->field('wob.*, m.user_name, m.emergency_phone,m.phone')
                    ->where('wob.delete', 0)
                    ->where('wob.pay_status', 1)
                    ->where('wob.confirm_exchange', null)
                    ->where('wob.status', 'in', [1])
                    ->where('wob.sale_id', $user_id)
                    ->count();
// var_dump($list);exit;
                // 确认时间倒计时(小时)
                // $confirm_countdown = Db::name('config')->where('ename', 'confirm_countdown')->value('value');
                // $paytime_countdown = Db::name('config')->where('ename', 'paytime_countdown')->value('value');
                $confirm_countdown = Db::name('config')->where('ename', 'confirm_timeout')->value('value');
                $paytime_countdown = Db::name('config')->where('ename', 'pay_timeout')->value('value');
                if (!empty($list)){
                    foreach ($list as $k=>$v){
                        $list[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
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

//     public function getSaleWineInfo(){
//         if(request()->isPost()){
//             $gongyong = new GongyongMx();
//             $result = $gongyong->apivalidate(1);
//             if($result['status'] == 200){
//                 $user_id = $result['user_id'];

//                 $post = input('post.');
// //                var_dump($post);exit;
//                 $list = Db::name('wine_order_saler')->alias('wos')->where('wos.delete', 0)->where('wos.id', $post['id'])
//                         ->join('member sm', 'wos.sale_id = sm.id', 'left')
// //                        ->join('member bm', 'wos.buy_id = bm.id', 'left'),bm.phone bphone,bm.user_name buser_name
//                         ->join('bank_card b', 'b.user_id = wos.sale_id', 'left')
//                         ->field('wos.*,sm.phone sphone,sm.user_name suser_name,b.bank_name,b.card_number,b.name account_name')
//                         ->find();
// //                foreach ($list as $k=>$v){
// //                    $list[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
// //                }
// //                var_dump($list);exit;

//                 $value = array('status'=>200,'mess'=>'获取数据成功','data'=>$list);
//             }else{
//                 $value = $result;
//             }
//         }else{
//             $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
//         }
//         return json($value);
//     }
    
    public function getSaleWineInfo(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];

                $post = input('post.');
                $info = Db::name('wine_order_buyer')->where('wob.delete', 0)->alias('wob')
                    ->where('wob.sale_id', $user_id)
                    ->where('wob.id', $post['id'])
                    ->join('member bm', 'wob.buy_id = bm.id', 'left')
                    ->join('member sm', 'wob.sale_id = sm.id', 'left')
                    ->field('wob.buy_amount,bm.user_name b_user_name,bm.emergency_phone b_emergency_phone,bm.phone b_phone, sm.user_name s_user_name, sm.phone s_phone,sm.emergency_phone s_emergency_phone,sm.id,wob.pay_status,wob.sale_id,wob.proof_qrcode,wob.paywayindex')
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
    
    public function getSaleWineInfoss(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];

                $post = input('post.');
                $info = Db::name('wine_order_buyer')->where('wob.delete', 0)->alias('wob')
                    // ->where('wob.sale_id', $user_id)
                    ->where('wob.id', $post['id'])
                    ->join('member bm', 'wob.buy_id = bm.id', 'left')
                    ->join('member sm', 'wob.sale_id = sm.id', 'left')
                    ->field('wob.sale_appeal_question,wob.sale_appeal_proof,wob.buyer_appeal_question,wob.buyer_appeal_proof,wob.buy_amount,bm.user_name b_user_name,bm.phone b_phone, sm.user_name s_user_name, sm.phone s_phone,sm.id,wob.pay_status,wob.sale_id,wob.proof_qrcode,wob.paywayindex')
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
    
    public function getSaleWineInfodd(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];

                $post = input('post.');
                $info = Db::name('wine_order_buyer')->where('wob.delete', 0)->alias('wob')
                    ->where('wob.buy_id', $user_id)
                    ->where('wob.id', $post['id'])
                    ->join('member bm', 'wob.buy_id = bm.id', 'left')
                    ->join('member sm', 'wob.sale_id = sm.id', 'left')
                    ->field('wob.sale_appeal_question,wob.sale_appeal_proof,wob.buy_amount,bm.user_name b_user_name,bm.phone b_phone, sm.user_name s_user_name, sm.phone s_phone,sm.id,wob.pay_status,wob.sale_id,wob.proof_qrcode,wob.paywayindex')
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
    
    public function getWineIncomeListsss(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                
                // 确认时间倒计时(小时)
                // $confirm_countdown = Db::name('config')->where('ename', 'confirm_countdown')->value('value');
                // $paytime_countdown = Db::name('config')->where('ename', 'paytime_countdown')->value('value');
                // $time = time();

                $post = input('post.');
                // if($post['status'] == 1){
                    // $status = [$post['status'], 4, 5];
                // }
                // else{
                    // $status = [$post['status']];
                // }
                
                $list = Db::name('wine_order_buyer')
                    // ->where('wob.delete', 0)
                    ->alias('wob')
//                    ->where('wob.wine_order_saler_id', $post['id'])
                    ->where('wob.sale_id', $user_id)
                    // ->where('wob.status', 'in', $status)
                    ->join('member m', 'wob.buy_id = m.id', 'left')
                    ->join('member sm', 'wob.sale_id = sm.id', 'left')
                    ->where('wob.wine_order_saler_id', $post['id'])
//                        ->join('member bm', 'wos.buy_id = bm.id', 'left'),bm.phone bphone,bm.user_name buser_name
//                    ->join('bank_card b', 'b.user_id = wos.sale_id', 'left')
                    ->field('wob.*,m.phone,m.user_name,m.emergency_phone,sm.phone as sm_phone,sm.user_name sm_user_name, sm.emergency_phone sm_emergency_phone')
                    ->order('id desc')
                    ->select();
                    // var_dump(Db::name('wine_order_buyer')->getLastSql());exit;
                foreach ($list as $k=>$v){
                    // $list[$k]['confirm_countdown'] = $v['paytime'] + $confirm_countdown*60*60 - time();
                    // $list[$k]['paytime_countdown'] = $v['addtime'] + $paytime_countdown*60*60 - time();
                    $list[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                }

                $value = array('status'=>200,'mess'=>'获取数据成功','data'=>$list);
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    public function getWineIncomeListTopStop(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                
                $list = Db::name('wine_order_buyer')->where('wob.delete', 0)->alias('wob')
                    ->where('wob.buy_id', $user_id)
                    ->where('wob.status', 2)
                    ->where('wob.top_stop', 1)
                    ->join('member m', 'wob.buy_id = m.id', 'left')
                    ->field('wob.*,m.phone,m.user_name')
                    ->order('id desc')
                    ->select();
                foreach ($list as $k=>$v){
                    $list[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                    if($v['top_stop'] == 1){
                        $list[$k]['status_txt'] = '封顶';
                    }
                }

                $value = array('status'=>200,'mess'=>'获取数据成功','data'=>$list);
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    

    
    public function revalue(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                $h = date('H');
                // if($h >= 23){
                //     $value = array('status'=>400,'mess'=>'寄售时间已过','data'=>array('status'=>400));
                //     return json($value);
                // }
                
                $input = input();
                $memberInfo = Db::name('member')->where('id', $user_id)->find();
                 if($memberInfo['sale_earnings'] >= 2500){
                     $value = array('status'=>400,'mess'=>'需要在解锁商品区购买一款商品,完成一笔交易','data'=>array('status'=>400));
                     return json($value);
                 }
                    
                $info = Db::name('wine_order_buyer')->where('id', $input['id'])->where('status', 2)->where('pay_status', 1)->where('delete', 0)->where('day', 0)->where('top_stop', 0)->where('buy_id', $user_id)->find();
                if(is_null($info)){
                    $value = array('status'=>400,'mess'=>'数据不存在','data'=>array('status'=>400));
                    return json($value);
                }
                else{
                        // $day = $input['day'];
                        $day = 1;
                        if($day <= 0){
                            $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
                            return json($value);
                        }
                        
                        $wine_goods = Db::name('wine_goods')->where('id', $info['wine_goods_id'])->find();
                        
                        if($wine_goods['best_max_day'] < $day){
                            $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
                            return json($value);
                        }
                        
                        $sale_amount = $info['buy_amount'] + $info['buy_amount']*$wine_goods['rate']/100*$day;
                        
                        $wine_jishou_service_cost = Db::name('config')->where('ename', 'wine_jishou_service_cost')->value('value');
                        
                    Db::startTrans();
                    try{
                            // 服务费
                            $cost_service = $info['buy_amount']*$wine_jishou_service_cost/100;
                            $wall_info = Db::name('wallet')->where('user_id', $user_id)->find();
                            $wallet_info = $wall_info;
                            if($wall_info['point'] < $cost_service){
                                throw new Exception('积分不足');
                            }

                            $profit = $sale_amount - $info['buy_amount'];
                            
                            $res = Db::name('wallet')->where('user_id', $user_id)->setDec('point', $cost_service);
                            if(!$res)throw new Exception('平台寄售失败');
                            $klg = [
                                'de_type' => 2,
                                'zc_type' => 110,
                                'before_price'=> $wallet_info['point'],
                                'price' => $cost_service,
                                'after_price'=> $wallet_info['point']-$cost_service,
                                'user_id' => $user_id,
                                'wat_id' => $wall_info['id'],
                                'time' => time(),
                                'target_id'=>$info['id']
                            ];
                            $res = $this->addDetail($klg);
//                            $res = Db::name('detail')->insert($klg);
                            if(!$res){
                                throw new Exception('平台寄售失败');
                            }
                            
                            $res = Db::name('wine_order_buyer')->where('id', $info['id'])->where('status', 2)->where('pay_status', 1)->where('delete', 0)
                                    ->where('day', 0)->where('buy_id', $user_id)->where('top_stop', 0)
                                    ->inc('day', $day)
                                    ->update([
                                        'sale_amount' => $sale_amount,
                                        'sale_addtime' => time()
                                    ]);
                            if(!$res)throw new Exception('平台寄售失败');

                        // 极差
                        $profit = $cost_service;
                        $this->wineGoodsGradePoor($info['buy_id'], $profit, 1, [], $info['buy_id'], 0, $info['wine_goods_id'], $info['wine_deal_area_id'], 0);

                        $value = array('status'=>200,'mess'=>'平台寄售成功','data'=>array('status'=>200));
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

    public function getWineIncomeList(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                
                // 确认时间倒计时(小时)
                $confirm_countdown = Db::name('config')->where('ename', 'confirm_countdown')->value('value');
                // $paytime_countdown = Db::name('config')->where('ename', 'paytime_countdown')->value('value');
                $paytime_countdown = Db::name('config')->where('ename', 'pay_timeout')->value('value');
                $time = time();

                $post = input('post.');
                $where=[];
                if($post['status'] == 1){
                    // $status = [$post['status'], 4, 5];
                    $status = [$post['status']];
                    $where['wob.pay_status'] = 0;
                }
                elseif($post['status'] == 2){
                    $status = [1];
                    $where['wob.pay_status'] = 1;
                    $where['wob.confirm_exchange'] = null;
                }
                elseif($post['status'] == 3){
                    $status = [2];
                    $where['wob.day'] = 0;
                    // $where['day'] = ['>', 0];
                    // $whereday[] = ['day', '>', 0];
                }
                else{
                    $status = [$post['status']];
                }
                
                $count1 = Db::name('wine_order_buyer')->where('wob.delete', 0)->alias('wob')
                    ->where('wob.buy_id', $user_id)
                    ->where('wob.status', 'in', [1])
                    ->where('wob.pay_status', 0)
                    ->where('wob.top_stop', 0)
                    ->join('member m', 'wob.buy_id = m.id', 'left')
                    ->join('wine_goods wg', 'wg.id = wob.wine_goods_id', 'left')
                    ->count();
                
                $count2 = Db::name('wine_order_buyer')->where('wob.delete', 0)->alias('wob')
                    ->where('wob.buy_id', $user_id)
                    ->where('wob.status', 'in', [1])
                    ->where('wob.pay_status', 1)
                    ->where('wob.confirm_exchange', null)
                    ->where('wob.top_stop', 0)
                    ->join('member m', 'wob.buy_id = m.id', 'left')
                    ->join('wine_goods wg', 'wg.id = wob.wine_goods_id', 'left')
                    ->count();
                    
                $count3 = Db::name('wine_order_buyer')->where('wob.delete', 0)->alias('wob')
                    ->where('wob.buy_id', $user_id)
                    ->where('wob.status', 'in', [2])
                    ->where('wob.day', 0)
                    ->where('wob.top_stop', 0)
                    ->join('member m', 'wob.buy_id = m.id', 'left')
                    ->join('wine_goods wg', 'wg.id = wob.wine_goods_id', 'left')
                    ->count();
                
                $list = Db::name('wine_order_buyer')->where('wob.delete', 0)->alias('wob')
                    ->join('wine_deal_area wda', 'wda.id = wob.wine_deal_area_id', 'left')
//                    ->where('wob.wine_order_saler_id', $post['id'])
                    ->where('wob.buy_id', $user_id)
                    ->where('wob.status', 'in', $status)
                    ->where($where)
                    ->where('wob.top_stop', 0)
                    ->join('member m', 'wob.buy_id = m.id', 'left')
                    ->join('wine_goods wg', 'wg.id = wob.wine_goods_id', 'left')
//                        ->join('member bm', 'wos.buy_id = bm.id', 'left'),bm.phone bphone,bm.user_name buser_name
//                    ->join('bank_card b', 'b.user_id = wos.sale_id', 'left')
                    ->field('wob.*,m.phone,m.user_name, wg.best_max_day, wda.desc')
                    ->order('id desc')
                    ->select();
                foreach ($list as $k=>$v){
                    $list[$k]['bank'] = Db::name('bank_card')->where('user_id', $v['sale_id'])->find();
                    $list[$k]['zfb'] = Db::name('zfb_card')->where('user_id', $v['sale_id'])->find();
                    $list[$k]['wx'] = Db::name('wx_card')->where('user_id', $v['sale_id'])->find();
                    
                    $list[$k]['confirm_countdown'] = $v['paytime'] + $confirm_countdown*60*60 - time();
                    $list[$k]['paytime_countdown'] = $v['addtime'] + $paytime_countdown*60*60 - time();
                    $list[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                    $list[$k]['sale_addtime_txt'] = date('Y-m-d', $v['sale_addtime']+$v['day']*24*60*60);
                    if($v['wine_deal_area_id'] == 1){
                        $list[$k]['sale_addtime_txt'] .= '  上午场';
                    }else if($v['wine_deal_area_id'] == 2){
                        $list[$k]['sale_addtime_txt'] .= '  下午场';
                    } else if($v['wine_deal_area_id'] == 3){
                        $list[$k]['sale_addtime_txt'] .= '  晚场';
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

    public function getIncomeWineDetailssss(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                // $total_team_id = Db::name('member')
                //  ->where('team_id', 'like', '%,'.$user_id)->whereOr('team_id', 'like', '%,'.$user_id.',%')
                //  ->whereOr('jiedian_team_id', 'like', '%,'.$user_id)->whereOr('jiedian_team_id', 'like', '%,'.$user_id.',%')
                //  ->column('id');

                $post = input('post.');
                $info = Db::name('wine_order_buyer')->alias('wob')
                    ->where('wob.delete', 0)
                    // ->where('wob.buy_id', 'in', $total_team_id)
                    ->where('wob.id', $post['id'])
                    ->join('member bm', 'wob.buy_id = bm.id', 'left')
                    ->join('member sm', 'wob.sale_id = sm.id', 'left')
                    ->field('wob.buy_amount,bm.user_name b_user_name,bm.phone b_phone, sm.user_name s_user_name, sm.phone s_phone,sm.emergency_phone s_emergency_phone,bm.emergency_phone b_emergency_phone,sm.id,wob.pay_status,wob.sale_id,wob.income_name,wob.income_phone,wob.income_card,wob.paywayindex, wob.wine_order_saler_id, wob.bank_name,wob.wine_deal_area_id, wob.addtime,wob.proof_qrcode')
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

    public function getIncomeWineDetail(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(1);
            if($result['status'] == 200){
                $user_id = $result['user_id'];

                $post = input('post.');
                $info = Db::name('wine_order_buyer')->where('wob.delete', 0)->alias('wob')
                    ->where('wob.buy_id', $user_id)
                    ->where('wob.id', $post['id'])
                    ->join('member bm', 'wob.buy_id = bm.id', 'left')
                    ->join('member sm', 'wob.sale_id = sm.id', 'left')
                    ->field('wob.buy_amount,bm.user_name b_user_name,bm.true_name b_true_name,bm.phone b_phone, sm.user_name s_user_name, sm.true_name s_true_name, sm.phone s_phone,sm.emergency_phone s_emergency_phone,bm.emergency_phone b_emergency_phone,sm.id,wob.pay_status,wob.sale_id,wob.income_name,wob.income_phone,wob.income_card,wob.paywayindex, wob.wine_order_saler_id, wob.bank_name,wob.wine_deal_area_id, wob.addtime,wob.proof_qrcode')
                    ->find();
                    
                    $wine_deal_area = Db::name('wine_deal_area')->where('id', $info['wine_deal_area_id'])->find();
                    
                    
                    $startHis = trim(explode('-', $wine_deal_area['deal_area'])[0]);
                    $time_countdown = strtotime(date('Y-m-d').' '.$startHis) - time();
// var_dump($info);exit;
                // if($info['sale_id'] == 1){
                //     $wine_order_saler_info = Db::name('wine_order_saler')->where('id', $info['wine_order_saler_id'])->find();
                    
                //     // 银行卡
                //     if(trim($wine_order_saler_info['bank_card_number'])){
                //         $bank = [
                //             'name' => $wine_order_saler_info['bank_name'],
                //             'telephone' => $wine_order_saler_info['bank_telephone'],
                //             'card_number' => $wine_order_saler_info['bank_card_number'],
                //             'bank_name' => $wine_order_saler_info['bank_card_name']
                //         ];
                //     }
                //     else{
                //         $bank = [];
                //     }
                    
                //     // 支付宝
                //     if(trim($wine_order_saler_info['zfb_qrcode'])){
                //         $zfb = [
                //             'name' => $wine_order_saler_info['zfb_name'],
                //             'telephone' => $wine_order_saler_info['zfb_telephone'],
                //             'qrcode' => $wine_order_saler_info['zfb_qrcode']
                //         ];
                //     }
                //     else{
                //         $zfb = [];
                //     }
                    
                //     // 微信
                //     if(trim($wine_order_saler_info['wx_qrcode'])){
                //         $wx = [
                //             'name' => $wine_order_saler_info['wx_name'],
                //             'telephone' => $wine_order_saler_info['wx_telephone'],
                //             'qrcode' => $wine_order_saler_info['wx_qrcode']
                //         ];
                //     }
                //     else{
                //         $wx = [];
                //     }
                // }
                // else{
                    $bank = Db::name('bank_card')->where('user_id', $info['sale_id'])->find();
                    $wx = Db::name('wx_card')->where('user_id', $info['sale_id'])->find();
                    $zfb = Db::name('zfb_card')->where('user_id', $info['sale_id'])->find();
                // }
                    $paytime_countdown = Db::name('config')->where('ename', 'pay_timeout')->value('value');
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
                        $info = Db::name('wine_order_buyer')->where('delete', 0)->where('id', $post['id'])->where('pay_status', 0)->where('buy_id', $user_id)->find();
                        if ($info){
                            // 匹配进货付款冻结时间已过(小时)
                            // $pipei_frozen_timeout_frozen = Db::name('config')->where('ename', 'pipei_frozen_timeout_frozen')->value('value');
                            $pipei_frozen_timeout_frozen = Db::name('config')->where('ename', 'pay_timeout')->value('value');
                            if($info['addtime']+$pipei_frozen_timeout_frozen*60*60<time()){
                                $value = array('status'=>400,'mess'=>'付款时间已过','data'=>array('status'=>400));
                                return json($value);
                            }
                            
                            $paypwd = Db::name('member')->where('id', $user_id)->value('paypwd');
                            if (md5($post['paypwd']) == $paypwd){
                                Db::startTrans();
                                try{
                                    $time = time();
                                    // 增加2信用分
                                    // $credit_value = 2;
                                    // $res = Db::name('wallet')->where('user_id', $user_id)->inc('credit_value', $credit_value)->inc('total_stock', $info['buy_amount'])->update();
                                    // if (!$res){
                                    //     throw new \Exception('增加信用分失败');
                                    // }
                                    
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

                                    $res = Db::name('wine_order_buyer')->where('delete', 0)->where('id', $post['id'])->where('pay_status', 0)->where('buy_id', $user_id)
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

    public function confirmTransferAuto(){exit;
        if(request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];
                    $post = input('post.');

                    $info = Db::name('wine_order_buyer')->where('delete', 0)->where('status', 'in', [1, 4])->where('sale_id', $user_id)->where('pay_status', 1)
                            ->where('id', $post['id'])->find();
                    if ($info) {
                        // $paypwd = Db::name('member')->where('id', $user_id)->value('paypwd');
                        // if (md5($post['paypwd']) == $paypwd) {
                        if (true) {
                                
                            Db::startTrans();
                            $time = time();
                            
                            try{
                                $res = Db::name('wine_order_buyer')->where('id', $info['id'])->update([
                                    'status'=>2,
                                    'confirm_exchange' => $time
                                ]);
                                if (!$res)throw new \Exception('转让失败');


                                $wine_order_saler_info = Db::name('wine_order_saler')
                                                            ->where('sale_id', $user_id)
                                                            ->where('delete', 0)
                                                            ->where('status', 1)
                                                            ->where('id', $info['wine_order_saler_id'])->find();

                                if ($wine_order_saler_info['pipei_amount'] == $wine_order_saler_info['sale_amount']){
                                     $income_total_price = Db::name('wine_order_buyer')->where('delete', 0)->where('sale_id', $user_id)->where('pay_status', 1)
                                            ->where('wine_order_saler_id', $wine_order_saler_info['id'])->sum('buy_amount');

                                     if($income_total_price == $wine_order_saler_info['sale_amount']){
                                         $res = Db::name('wine_order_saler')->where('id', $wine_order_saler_info['id'])->update([
                                            'status'=>2,
                                            'confirm_exchange'=>$time
                                         ]);
                                         if (!$res)throw new \Exception('转让失败');

                                         Db::name('member')->where('id', $info['buy_id'])->setInc('agent_num');
                                        //  if (!$res)throw new \Exception('转让失败2');
                                     }
                                }

                                Db::commit();
                                $value = array('status' => 200, 'mess' => '转让成功', 'data' => array('status' => 200));
                            }
                            catch (\Exception $e){
                                Db::rollback();
                                $value = array('status' => 400, 'mess' => '转让失败', 'data' => array('status' => 400));
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

    public function confirmTransfer(){
        if(request()->isPost()) {
            if (input('post.token')) {
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if ($result['status'] == 200) {
                    $user_id = $result['user_id'];
                    $post = input('post.');
// var_dump($user_id);exit;
                    $info = Db::name('wine_order_buyer')->where('delete', 0)->where('status', 'in', [1, 4])->where('sale_id', $user_id)->where('pay_status', 1)
                            ->where('id', $post['id'])->find();
                    if ($info) {
                        $paypwd = Db::name('member')->where('id', $user_id)->value('paypwd');
                        if (md5($post['paypwd']) == $paypwd) {
                            Db::startTrans();
                            $time = time();
                            
                            try{
                                $res = Db::name('wine_order_buyer')->where('id', $info['id'])->update([
                                    'status'=>2,
                                    'confirm_exchange' => $time
                                ]);
                                if (!$res)throw new \Exception('转让失败');


                                $wine_order_saler_info = Db::name('wine_order_saler')
                                                            ->where('sale_id', $user_id)
                                                            ->where('delete', 0)
                                                            ->where('status', 1)
                                                            ->where('id', $info['wine_order_saler_id'])->find();

                                if ($wine_order_saler_info['pipei_amount'] == $wine_order_saler_info['sale_amount']){
                                     $income_total_price = Db::name('wine_order_buyer')->where('delete', 0)->where('sale_id', $user_id)->where('pay_status', 1)
                                            ->where('wine_order_saler_id', $wine_order_saler_info['id'])->sum('buy_amount');

                                     if($income_total_price == $wine_order_saler_info['sale_amount']){
                                         $res = Db::name('wine_order_saler')->where('id', $wine_order_saler_info['id'])->update([
                                            'status'=>2,
                                            'confirm_exchange'=>$time
                                         ]);
                                         if (!$res)throw new \Exception('转让失败');
                                         
                                         Db::name('member')->where('id', $info['buy_id'])->setInc('agent_num');
                                        //  if (!$res)throw new \Exception('转让失败2');
                                     }
                                }
                                
                                $attech_zeren = $wine_order_saler_info['attech_zeren'];
                                $fili_point = 0;
                                if(in_array($attech_zeren, [2,3])){
                                    // $fili_point = 50;
                                }
                        
                                $wallet_info = Db::name('wallet')->where('user_id', $info['buy_id'])->find();
                                $wallet_id = $wallet_info['id'];
                                if(!$wallet_info){
                                    throw new Exception('信息不存在1');
                                }

                                 $infosdf = Db::name('wine_order_buyer')->where('wine_order_saler_id', $wine_order_saler_info['id'])->order('id desc')->find();
                                if(!is_null($infosdf)){
                                    // $profit = $infosdf['sale_amount'] - $infosdf['buy_amount'];
                                    $profit = $infosdf['buy_amount'] * 0.02;
                                    if($profit > 0){
                                        $res = Db::name('member')->where('id', $wine_order_saler_info['sale_id'])->inc('sale_earnings', $profit)->update();
                                        if(!$res){
                                            throw new Exception('订单信息不存在2');
                                        }
                                    }
                                }
                                
                                if($fili_point>0){
                                    $res = Db::name('wallet')->where('user_id', $info['buy_id'])->inc('point', $fili_point)->update();
                                    if(!$res){
                                        throw new Exception('订单信息不存在3');
                                    }
                                    
                                    $point = [
                                        'de_type' => 1,
                                        'sr_type' => 1006,
                                        'before_price'=> $wallet_info['point'],
                                        'price' => $fili_point,
                                        'after_price'=> $wallet_info['point']+$fili_point,
                                        'user_id' => $info['buy_id'],
                                        'wat_id' => $wallet_id,
                                        'time' => time(),
                                        'remark'=>'购买福利场商品奖励',
                                        'target_id'=>$wine_order_saler_info['id']
                                    ];
                                    $res = $this->addDetail($point);
                                    if(!$res){
                                        throw new Exception('转让失败');
                                    }
                                }

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
    
    public function submitProof(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $post = input('post.');
                    
                    if(!$post['qrcode'] || !$post['odd'] || !$post['wine_order_buyer_id']){
                         $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                    }
                    else{
                        $info = Db::name('wine_order_buyer')->where('odd', $post['odd'])->where('id', $post['wine_order_buyer_id'])->where('sale_id', $user_id)->where('delete', 0)->where('status', 'in', [1, 3, 5, 4])->find();
                        if(is_null($info)){
                            $value = array('status'=>400,'mess'=>'信息不存在','data'=>array('status'=>400));
                        }
                        else{
                            Db::name('wine_order_buyer')->where('id', $info['id'])->update([
                                'status' => 3,
                                'sale_appeal_proof' => $post['qrcode'],
                                'sale_appeal_question' => $post['question']
                            ]);
                            
                            $value = array('status'=>200,'mess'=>'申诉成功','data'=>array('status'=>200));
                        }
                    }
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function submitProofuuu(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $post = input('post.');
                    
                    if(!$post['qrcode'] || !$post['odd'] || !$post['wine_order_buyer_id']){
                         $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                    }
                    else{
                        $info = Db::name('wine_order_buyer')->where('odd', $post['odd'])->where('id', $post['wine_order_buyer_id'])->where('buy_id', $user_id)->where('delete', 0)->where('status', 'in', [1, 3, 5])->find();
                        if(is_null($info)){
                            $value = array('status'=>400,'mess'=>'信息不存在','data'=>array('status'=>400));
                        }
                        else{
                            Db::name('wine_order_buyer')->where('id', $info['id'])->update([
                                'status' => 3,
                                'buyer_appeal_proof' => $post['qrcode'],
                                'buyer_appeal_question' => $post['question']
                            ]);
                            
                            $value = array('status'=>200,'mess'=>'申诉成功','data'=>array('status'=>200));
                        }
                    }
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
                
                $info = Db::name('wine_order_buyer')->where('wob.pay_status', 1)->where('wob.delete', 0)->alias('wob')
                    ->where('wob.buy_id', $user_id)
                    ->where('wob.status', 2)
                    ->where('wob.id', $post['id'])
                    ->find();

                if($info){
                    Db::startTrans();
                    try{
                        $res = Db::name('wine_order_buyer')->where('id', $info['id'])->update([
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
                        $res = Db::name('wine_to_inkind')->insert($inKindData);
                        if(!$res)throw new \Exception('异常');
                        
                        $value = array('status'=>200,'mess'=>'提货成功','data'=>$info);
                        Db::commit();
                    }
                    catch(\Exception $e){
                        $value = array('status'=>400,'mess'=>'提货失败','data'=>array('status'=>400));
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
}
