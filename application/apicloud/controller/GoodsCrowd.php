<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use app\apicloud\model\Member;
use app\apicloud\model\MemberBrowse;
use think\Db;
use app\apicloud\model\Category as CategoryModel;
use app\apicloud\model\Goods as GoodsModel;
use think\Exception;

class GoodsCrowd extends Common{
    public function lst(){
        $page = 1;
        $webconfig = $this->webconfig;
        $perpage = $webconfig['app_goodlst_num'];
        $pagenum = input('post.page', $page);
        $offset = ($pagenum-1)*$perpage;
        
        $list = Db::name('crowd_goods')->order('id desc')->limit($offset, $perpage)->where('jiesu', 0)->select();
        $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>$list);
        return json($value);
    }
    
    public function expectedIncome(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                
                $page = 1;
                $webconfig = $this->webconfig;
                $perpage = input('post.pageSize', $page);
                $pagenum = input('post.page', $page);
                $offset = ($pagenum-1)*$perpage;
                
                // $list = Db::name('crowd_order')->alias('co')->where('co.user_id', $user_id)->where('co.status', 3)->join('crowd_goods cg', 'cg.id = co.goods_id', 'inner')->where('co.receive_time', '>=', time())->order('co.id desc')->field('co.*,cg.thumb_url,cg.goods_name')->limit($offset, $perpage)->select();
                $list1 = [];
                if($pagenum==1){
                    $list1 = Db::name('crowd_order')->alias('co')->where('co.user_id', $user_id)->where('co.status', 3)->join('crowd_goods cg', 'cg.id = co.goods_id', 'inner')->order('co.id desc')->field('co.*,cg.thumb_url,cg.goods_name')->order('co.status desc, addtime desc')->select();
                }
                $list2 = Db::name('crowd_order')->alias('co')->where('co.user_id', $user_id)->where('co.status', '<>', 3)->join('crowd_goods cg', 'cg.id = co.goods_id', 'inner')->order('co.id desc')->field('co.*,cg.thumb_url,cg.goods_name')->order('addtime desc')->limit($offset, $perpage)->select();
                $list = array_merge($list1, $list2);
                // $tuikuan_value = Db::name('config')->where('ename', 'tuikuan_value')->value('value');
                foreach ($list as &$v){
                    $tuikuan_value = Db::name('crowd_goods')->where('id', $v['goods_id'])->value('static_rate');
                    $v['income'] = $v['price'] + $v['price']*$tuikuan_value/100;
                }
                
                $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list, 'data1'=>$list1);
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value); 
    }
    
    public function receivedIncome(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                $input = input('post.');
                
                if(!isset($input['id'])){
                    $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
                }
                else{
                    // $tuikuan_value = Db::name('config')->where('ename', 'tuikuan_value')->value('value');
                    $info = Db::name('crowd_order')->where('user_id', $user_id)->where('status', 3)->where('id', $input['id'])->find();
                    $crowd_goods_info = Db::name('crowd_goods')->where('id', $info['goods_id'])->find();
                    $last_crowd_goods_info = Db::name('crowd_goods')->where('crowd_mark', $crowd_goods_info['crowd_mark'])->order('cur_qi desc')->find();
                    if($info['receive_time'] < time() && $last_crowd_goods_info['status']!=1){
                        $value = array('status'=>400,'mess'=>'领取时间已过','data'=>array('status'=>400));
                        return json($value);
                    }
                    
                    if(is_null($info)){
                        $value = array('status'=>400,'mess'=>'领取失败','data'=>array('status'=>400));
                    }
                    else{
                        $tuikuan_value = Db::name('crowd_goods')->where('id', $info['goods_id'])->value('static_rate');
                        $sprice = $info['price'] + $info['price']*$tuikuan_value/100;
                        $wallet_info = Db::name('wallet')->where('user_id', $info['user_id'])->find();
                        
                        Db::startTrans();
                        try{
                            $res = Db::name('wallet')->where('user_id', $info['user_id'])->inc('point_ticket', $sprice)->update();
                            if(!$res){
                                throw new Exception('领取失败');
                            }
                            
                            $detal = [
                                'de_type' => 1,
                                'sr_type' => 105,
                                'before_price'=> $wallet_info['point_ticket'],
                                'price' => $sprice,
                                'after_price'=> $wallet_info['point_ticket']+$sprice,
                                'user_id' => $info['user_id'],
                                'wat_id' => $wallet_info['id'],
                                'time' => time(),
                                'target_id'=>$info['id']
                            ];
                            $res = $this->addDetail($detal);
                            if(!$res){
                                throw new Exception('领取失败');
                            }
                            
                            $res = Db::name('crowd_order')->where('user_id', $user_id)->where('status', 3)->where('id', $input['id'])->update(['status'=>4]);
                            if(!$res){
                                throw new Exception('领取失败');
                            }
                            
                            $value = array('status'=>200,'mess'=>'领取成功','data'=>array('status'=>200));
                            Db::commit();
                        }
                        catch(Exception $e){
                            $value = array('status'=>400,'mess'=>'领取失败','data'=>array('status'=>400));
                            Db::rollback();
                        }
                    }
                }
                
                // $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function crowdBuy(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                
                if(input('post.goods_id')){
                    $goods_id = input('post.goods_id');
                    $price = (float)input('post.price');
                    if(!$goods_id || !$price || $price<=0){
                        $value = array('status'=>400,'mess'=>'参数异常','data'=>array('status'=>400));
                        return json($value);
                    }
                    else{
                        // if($price%100!=0){
                        //     $value = array('status'=>400,'mess'=>'购买价格只能是100的倍数','data'=>array('status'=>400));
                        //     return json($value);
                        // }
                    }
                    
                    $crowd_goods_info = Db::name('crowd_goods')->where('id', $goods_id)->find();
                    if(!is_null($crowd_goods_info)){
                        $goodsYmd = date('Y-m-d', $crowd_goods_info['addtime']);
                        $curYmd = date('Y-m-d');
                        $adjut = 0;
                        $strtime = strtotime($goodsYmd);
                        // if($goodsYmd == $curYmd){
                        //     $adjut = $strtime+122400;
                        // }
                        // else{
                        //     $adjut = $strtime+36000;
                        // }
                        $adjut = $strtime+122400;
                        
                        $wallet_info = Db::name('wallet')->where('user_id', $user_id)->find();
                        if(!is_null($wallet_info)){
                            if($wallet_info['point_ticket']>=$price && $wallet_info['ticket_burn']>=$ticket_burn){
                                $time = time();
                                Db::startTrans();
                                try{
                                    $pre_sale_num = ceil($crowd_goods_info['crowd_value']*0.7);
                                    $sy_pre_sale = (float)($pre_sale_num - $crowd_goods_info['pre_sale']);
                                    
                                    $sy_total_sale = $crowd_goods_info['crowd_value'] - $crowd_goods_info['cur_crowd_num'];
                                    
                                    if($time>=$adjut){
                                        // 。。。
                                        if($price<$sy_total_sale && $price%100!=0){
                                            throw new Exception('购买只能是100的倍数');
                                        }
                                        else{
                                            $count = Db::name('crowd_order')->where('goods_id', $goods_id)->count();
                                            $endtime = $adjut + 172800 + $count*3600;
                                            if($time > $endtime){
                                                throw new Exception('购买时间已过');
                                            }
                                            
                                            if($price > $sy_total_sale){
                                                // throw new Exception('剩余量不足');
                                                $price = $sy_total_sale;
                                            }
                                            
                                            $buy_data = [
                                                'price' => $price,
                                                'goods_id' => $goods_id,
                                                'addtime' => time(),
                                                'type' => 2,
                                                'user_id' => $user_id,
                                                'qi'=>$crowd_goods_info['cur_qi']
                                            ];
                                            $res = Db::name('crowd_order')->insert($buy_data);
                                            if(!$res){
                                                throw new Exception('购买失败');
                                            }
                                            
                                            $res = Db::name('crowd_goods')->where('id', $goods_id)->inc('cur_crowd_num', $price)->update();
                                            if(!$res){
                                                throw new Exception('购买失败');
                                            }
                                        }
                                    }
                                    else{
                                        // 预约
                                        if($crowd_goods_info['pre_sale'] >= $pre_sale_num){
                                            throw new Exception('预约数已销售一空');
                                        }
                                        else{
                                            if(true){
                                                if($price<$sy_pre_sale && $price%100!=0){
                                                    throw new Exception('购买只能是100的倍数');
                                                }
                                                else{
                                                    if($price > $sy_pre_sale){
                                                        // throw new Exception('剩余量不足');
                                                        $price = $sy_pre_sale;
                                                    }
                                                    
                                                    $buy_data = [
                                                        'price' => $price,
                                                        'goods_id' => $goods_id,
                                                        'addtime' => time(),
                                                        'type' => 1,
                                                        'user_id' => $user_id,
                                                        'qi'=>$crowd_goods_info['cur_qi']
                                                    ];
                                                    $res = Db::name('crowd_order')->insert($buy_data);
                                                    if(!$res){
                                                        throw new Exception('购买失败');
                                                    }
                                                    
                                                    $res = Db::name('crowd_goods')->where('id', $goods_id)->inc('pre_sale', $price)->inc('cur_crowd_num', $price)->update();
                                                    if(!$res){
                                                        throw new Exception('购买失败');
                                                    }
                                                }
                                            }
                                            else{
                                                throw new Exception('剩余预约数不足');
                                            }
                                        }
                                    }
                                    
                                     $detail = [
                                        'de_type' => 2,
                                        'zc_type' => 100,
                                        'before_price'=> $wallet_info['point_ticket'],
                                        'price' => $price,
                                        'after_price'=> $wallet_info['point_ticket']-$price,
                                        'user_id' => $user_id,
                                        'wat_id' => $wallet_info['id'],
                                        'time' => time()
                                     ];
                                     $res = $this->addDetail($detail);
                                     
                                    $ticket_burn = $price * 0.02;
                                     $detail = [
                                        'de_type' => 2,
                                        'zc_type' => 108,
                                        'before_price'=> $wallet_info['ticket_burn'],
                                        'price' => $ticket_burn,
                                        'after_price'=> $wallet_info['ticket_burn']-$ticket_burn,
                                        'user_id' => $user_id,
                                        'wat_id' => $wallet_info['id'],
                                        'time' => time()
                                     ];
                                     $res = $this->addDetail($detail);
                                     
                                     $res = Db::name('wallet')->where('user_id', $user_id)->dec('point_ticket', $price)->dec('ticket_burn', $ticket_burn)->update();
                                    if(!$res){
                                        throw new Exception('购买失败');
                                    }
                                    
                                    $value = array('status'=>200,'mess'=>'购买成功','data'=>array('status'=>200));
                                    Db::commit();
                                }
                                catch(Exception $e){
                                    $value = array('status'=>400,'mess'=>$e->getMessage(),'data'=>array('status'=>400));
                                    Db::rollback();
                                }
                            }
                            else{
                                $value = array('status'=>400,'mess'=>'积分不足或者门票不足','data'=>array('status'=>400));
                            }
                        }
                        else{
                            $value = array('status'=>400,'mess'=>'钱包异常','data'=>array('status'=>400));
                        }
                    }
                    else{
                        $value = array('status'=>400,'mess'=>'商品不存在','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'商品错误','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);   
    }
    
    public function goodsinfo(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            $is_vip = 0;
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                
                if(input('post.goods_id')){
                    $goods_id = input('post.goods_id');
                    $goods = Db::name('crowd_goods')->alias('a')->field('a.sale_num,a.fictitious_sale_num,a.cate_id,a.id,a.vip_price,a.goods_name,a.thumb_url,a.shop_price,a.min_market_price,a.max_market_price,a.min_price,a.max_price,a.zs_price,a.goods_desc,a.fuwu,a.is_free,a.leixing,a.is_activity,a.shop_id,a.crowd_value,a.cur_crowd_num,a.goods_id,a.crowd_value,a.cur_crowd_num,a.addtime,a.cur_qi,a.crowd_mark')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$goods_id)->find();
                    if($goods){
                        $webconfig = $this->webconfig;
                        $goods['thumb_url'] = $goods['thumb_url'];
                        $goods['goods_desc'] = str_replace("/public/",$webconfig['weburl']."/public/",$goods['goods_desc']);
                        $goods['goods_desc'] = str_replace("<img","<img style='width:100%;'",$goods['goods_desc']);
                        

                        $gpres = Db::name('goods_pic')->where('goods_id',$goods['goods_id'])->field('id,img_url,sort')->order('sort asc')->select();

                        $goodsinfo = array(
                            'id'=>$goods['id'],
                            'goods_name'=>$goods['goods_name'],
                            'thumb_url'=>$goods['thumb_url'],
                            'goods_desc'=>$goods['goods_desc'],
                            'crowd_value'=>$goods['crowd_value'],
                            'cur_crowd_num'=>$goods['cur_crowd_num'],
                            'endtime'=>strtotime(date('Y-m-d', $goods['addtime']))+34*60*60
                        );
                        
                        $history_data = [];
                        $bonusPool = 0;
                        $nftPool = 0;
                        // var_dump($goods);exit;
                        if($goods['cur_qi'] > 1){
                            $history_data = Db::name('crowd_goods')->alias('a')->field('a.sale_num,a.fictitious_sale_num,a.cate_id,a.id,a.vip_price,a.goods_name,a.thumb_url,a.shop_price,a.min_market_price,a.max_market_price,a.min_price,a.max_price,a.zs_price,a.goods_desc,a.fuwu,a.is_free,a.leixing,a.is_activity,a.shop_id,a.crowd_value,a.cur_crowd_num,a.goods_id,a.crowd_value,a.cur_crowd_num,a.addtime,a.cur_qi,a.status')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.crowd_mark', $goods['crowd_mark'])->where('a.cur_qi', '<', $goods['cur_qi'])->order('a.cur_qi asc')->select();
                            
                            $total_crowd_value = Db::name('crowd_goods')->where('crowd_mark', $goods['crowd_mark'])->where('cur_qi', '<', $goods['cur_qi'])->sum('crowd_value');
                            $bonusPool = sprintf("%.2f",$total_crowd_value*0.02);
                            $nftPool = sprintf("%.2f",$total_crowd_value*0.02);
                        }
                        
                        $goodinfores = array(
                            'goodsinfo'=>$goodsinfo,
                            'gpres'=>$gpres,
                            'history_data'=>$history_data,
                            'bonusPool'=>$bonusPool,
                            'nftPool'=>$nftPool
                        );
                        $value = array('status'=>200,'mess'=>'获取商品详情信息成功','data'=>$goodinfores);
                    }else{
                        $value = array('status'=>400,'mess'=>'商品已下架或不存在','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);   
    }
    
    public function orderLst(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            $is_vip = 0;
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                
                if(true){
                    $page = 1;
                    $webconfig = $this->webconfig;
                    $perpage = $webconfig['app_goodlst_num'];
                    $pagenum = input('post.page', $page);
                    $offset = ($pagenum-1)*$perpage;
                    
                    $list = Db::name('crowd_order')->alias('co')
                        ->join('crowd_goods cg', 'cg.id = co.goods_id', 'inner')
                        ->field('co.*,cg.goods_name,cg.thumb_url,cg.crowd_value,cg.cur_qi,cg.cur_crowd_num,cg.pre_sale')
                        ->where('co.user_id', $user_id)->limit($offset, $perpage)->order('co.id desc')->select();
                    
                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
                }else{
                    $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);   
    }
    
    public function pointTicketRecord(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                
                if(true){
                    $page = 1;
                    $webconfig = $this->webconfig;
                    $perpage = $webconfig['app_goodlst_num'];
                    $pagenum = input('post.page', $page);
                    $offset = ($pagenum-1)*$perpage;
                    
                    $list = Db::name('detail')->alias('d')
                        ->where('d.sr_type', 'in', [110])
                        ->whereOr('d.zc_type', 'in', [22,100])
                        ->field('d.*,m.nick_name')
                        ->join('member m', 'm.id = d.user_id', 'left')->limit($offset, $perpage)->order('d.id desc')->select();
                    foreach ($list as &$value) {
                        // code...
                        if($value['sr_type'] == 110){
                            $value['remark'] = '购买商品赠送';
                        }
                        else if($value['zc_type'] == 100){
                            $value['remark'] = '预约或购买';
                        }
                        
                    }
                    
                    
                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
                }else{
                    $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
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
