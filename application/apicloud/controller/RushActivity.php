<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class RushActivity extends Common{
    
    //获取秒杀时间段
    public function getrushtime(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                $time = time();
                $dctime = date('Y-m-d',$time);
                $tomtime = date('Y-m-d',$time+3600*24);
                
                $sale_times = Db::name('sale_time')->field('time')->order('time asc')->select();
                if($sale_times){
                    $rushtime = array();
                    
                    foreach ($sale_times as $k2 => $v2){
                        if($v2['time'] < 10){
                            $dcthetime = strtotime($dctime.' 0'.$v2['time'].':00:00');
                        }else{
                            $dcthetime = strtotime($dctime.' '.$v2['time'].':00:00');
                        }
                        
                        if(!empty($sale_times[$k2+1])){
                            if($sale_times[$k2+1]['time'] < 10){
                                $end_dcthetime = strtotime($dctime.' 0'.$sale_times[$k2+1]['time'].':00:00');
                            }else{
                                $end_dcthetime = strtotime($dctime.' '.$sale_times[$k2+1]['time'].':00:00');
                            }
                        }else{
                            if($sale_times[0]['time'] < 10){
                                $end_dcthetime = strtotime($tomtime.' 0'.$sale_times[0]['time'].':00:00');
                            }else{
                                $end_dcthetime = strtotime($tomtime.' '.$sale_times[0]['time'].':00:00');
                            }
                        }
                    
                        if($time >= $dcthetime){
                            $cuxiao = 1;
                        }else{
                            $cuxiao = 0;
                        }
                        $rushtime[] = array('time'=>$dcthetime,'end_time'=>$end_dcthetime,'cuxiao'=>$cuxiao,'show'=>0);
                    }
                    
                    if($rushtime){
                        foreach ($rushtime as $key => $val){
                            if($time >= $val['time'] && $time < $val['end_time']){
                                $rushtime[$key]['show'] = 1;
                                break;
                            }
                        }
                        //当活动时间还没到的时候默认选中第一个
                        //获取当前的小时
                        $time_hour = date('H');
                        if ($rushtime[0]){
                            if ($time_hour < date('H',$rushtime[0]['time']) ){
                                $rushtime[0]['show'] = 1;
                            }
                        }
                        $value = array('status'=>200,'mess'=>'获取秒杀时间段信息成功','data'=>$rushtime);
                    }else{
                        $value = array('status'=>400,'mess'=>'找不到秒杀时间段信息','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'找不到秒杀时间段信息','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);        
    }
    
    public function index(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                if(input('post.nowtime')){
                    if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                        $nowtime = input('post.nowtime');
                        $pagenum = input('post.page');
                        
                        $webconfig = $this->webconfig;
                        $perpage = $webconfig['app_goodlst_num'];
                        $offset = ($pagenum-1)*$perpage;

                        $time = time();
                        $dctime = date('Y-m-d',$time);
                        $yqtime = date('Y-m-d',$time-3600*24);
                        $tomtime = date('Y-m-d',$time+3600*24);
                    
                        $sale_times = Db::name('sale_time')->order('time asc')->field('time')->select();
                        if($sale_times){
                            $rushtime = array();
                            
                            foreach ($sale_times as $k2 => $v2){
                                if($v2['time'] < 10){
                                    $dcthetime = strtotime($dctime.' 0'.$v2['time'].':00:00');
                                }else{
                                    $dcthetime = strtotime($dctime.' '.$v2['time'].':00:00');
                                }
                                
                                if(!empty($sale_times[$k2+1])){
                                    if($sale_times[$k2+1]['time'] < 10){
                                        $end_dcthetime = strtotime($dctime.' 0'.$sale_times[$k2+1]['time'].':00:00');
                                    }else{
                                        $end_dcthetime = strtotime($dctime.' '.$sale_times[$k2+1]['time'].':00:00');
                                    }
                                }else{
                                    if($sale_times[0]['time'] < 10){
                                        $end_dcthetime = strtotime($tomtime.' 0'.$sale_times[0]['time'].':00:00');
                                    }else{
                                        $end_dcthetime = strtotime($tomtime.' '.$sale_times[0]['time'].':00:00');
                                    }
                                }
                            
                                if($time >= $dcthetime){
                                    $cuxiao = 1;
                                }else{
                                    $cuxiao = 0;
                                }
                                $rushtime[] = array('time'=>$dcthetime,'end_time'=>$end_dcthetime,'cuxiao'=>$cuxiao,'show'=>0);
                            }

                            if($rushtime){
                                $activity = 0;

                                if(in_array($nowtime, array(1,2))){
                                    $activity = 1;
                                    if($nowtime == 1){
                                        $cuxiao = 1;
                                        $show = 0;
                                        $hdtime = strtotime($yqtime);
                                        $end_time = '';
                                    }else{
                                        $cuxiao = 0;
                                        $show = 0;
                                        $hdtime = strtotime($tomtime);
                                        $end_time = '';
                                    }
                                }else{
                                    foreach ($rushtime as $key => $val){
                                        if($time >= $val['time'] && $time < $val['end_time']){
                                            $rushtime[$key]['show'] = 1;
                                            break;
                                        }
                                    }
                                    
                                    foreach ($rushtime as $ku => $vu){
                                        if($vu['time'] == $nowtime){
                                            $activity = 1;
                                            $cuxiao = $vu['cuxiao'];
                                            $show = $vu['show'];
                                            $hdtime = $nowtime;
                                            $end_time = $vu['end_time'];
                                            break;
                                        }
                                    }
                                }

                                if($activity == 1){
                                    if(in_array($nowtime, array(1,2))){
                                        $yqrushtime = array();
                                        
                                        if($nowtime == 1){
                                            foreach ($sale_times as $k3 => $v3){
                                                if($v3['time'] < 10){
                                                    $thetime = strtotime($yqtime.' 0'.$v3['time'].':00:00');
                                                }else{
                                                    $thetime = strtotime($yqtime.' '.$v3['time'].':00:00');
                                                }
                                        
                                                $yqrushtime[] = $thetime;
                                            }
                                        }elseif($nowtime == 2){
                                            foreach ($sale_times as $k3 => $v3){
                                                if($v3['time'] < 10){
                                                    $thetime = strtotime($tomtime.' 0'.$v3['time'].':00:00');
                                                }else{
                                                    $thetime = strtotime($tomtime.' '.$v3['time'].':00:00');
                                                }
                                        
                                                $yqrushtime[] = $thetime;
                                            }
                                        }
                                        
                                        if($yqrushtime){
                                            $yqrushtime = implode(',', $yqrushtime);
                                            $rushres = Db::name('rush_activity')->alias('a')->field('a.id,a.goods_id,a.goods_attr,a.price,a.num,a.sold,b.goods_name,b.thumb_url,b.shop_price,b.min_price,b.max_price,b.zs_price,b.leixing,b.shop_id')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.checked',1)->where('a.recommend',1)->where('a.is_show',1)->where('a.start_time','in',$yqrushtime)->where('a.end_time','gt',time())->where('b.onsale',1)->where('c.open_status',1)->group('a.goods_id')->order('a.apply_time asc')->limit($offset,$perpage)->select();
                                        }else{
                                            $value = array('status'=>400,'mess'=>'时间段参数错误','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }else{
                                        $rushres = Db::name('rush_activity')->alias('a')
                                            ->field('a.id,a.goods_id,a.goods_attr,a.price,a.num,a.sold,b.goods_name,b.thumb_url,b.shop_price,b.min_price,b.max_price,b.zs_price,b.leixing,b.shop_id')
                                            ->join('sp_goods b','a.goods_id = b.id','INNER')
                                            ->join('sp_shops c','a.shop_id = c.id','INNER')
                                            ->where('a.checked',1)
                                            ->where('a.recommend',1)
                                            ->where('a.is_show',1)
                                            ->where('a.start_time','lt',$nowtime)
                                            ->where('a.end_time','gt',time())
                                            ->where('b.onsale',1)
                                            ->where('c.open_status',1)
                                            ->group('a.goods_id')->order('a.apply_time asc')->limit($offset,$perpage)->select();

                                    }

                                    if($rushres){
                                        foreach ($rushres as $kc => $vc){
                                            /*if($vc['goods_attr']){
                                             $number = Db::name('product')->where('goods_id',$vc['goods_id'])->where('goods_attr',$vc['goods_attr'])->field('id,goods_number')->find();
                                             if(empty($number) || $number['goods_number'] < $vc['num']){
                                             unset($rushres[$kc]);
                                             continue;
                                             }
                                             }else{
                                             $goods_number = Db::name('product')->where('goods_id',$vc['goods_id'])->sum('goods_number');
                                             if(empty($goods_number) || $goods_number < $vc['num']){
                                             unset($rushres[$kc]);
                                             continue;
                                             }
                                             }*/
                                            
                                            $rushres[$kc]['thumb_url'] = $webconfig['weburl'].'/'.$vc['thumb_url'];
                                    
                                            if($vc['goods_attr']){
                                                $goods_attr_str = '';
                                                $gares = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_value,a.attr_price,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$vc['goods_attr'])->where('a.goods_id',$vc['goods_id'])->where('b.attr_type',1)->select();
                                                if($gares){
                                                    foreach ($gares as $kr => $vr){
                                                        if($kr == 0){
                                                            $goods_attr_str = $vr['attr_name'].':'.$vr['attr_value'];
                                                        }else{
                                                            $goods_attr_str = $goods_attr_str.' '.$vr['attr_name'].':'.$vr['attr_value'];
                                                        }
                                                        $rushres[$kc]['shop_price']+=$vr['attr_price'];
                                                    }
                                                    $rushres[$kc]['goods_name']=$rushres[$kc]['goods_name'].' '.$goods_attr_str;
                                                    $rushres[$kc]['shop_price']=sprintf("%.2f", $rushres[$kc]['shop_price']);
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }else{
                                                if($vc['min_price'] != $vc['max_price']){
                                                    $rushres[$kc]['shop_price'] = $vc['min_price'].'-'.$vc['max_price'];
                                                }else{
                                                    $rushres[$kc]['shop_price'] = $vc['min_price'];
                                                }
                                            }
                                            $rushres[$kc]['yslv'] = sprintf("%.2f",$vc['sold']/$vc['num'])*100;
                                        }
                                    }
                                    
                                    if($pagenum == 1){
                                        $goodsinfo = array('cuxiao'=>$cuxiao,'show'=>$show,'hdtime'=>$hdtime,'end_time'=>$end_time,'dqtime'=>$time,'goodres'=>$rushres);
                                    }else{
                                        $goodsinfo = array('cuxiao'=>$cuxiao,'show'=>$show,'goodres'=>$rushres);
                                    }
                                    $value = array('status'=>200,'mess'=>'获取秒杀商品信息成功','data'=>$goodsinfo);
                                }else{
                                    $value = array('status'=>400,'mess'=>'时间段参数错误','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到秒杀时间段信息','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到秒杀时间段信息','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少页面参数','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'缺少时间段参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);        
    }
    
    //获取秒杀即将开始商品详情
    public function rushgoodinfo(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                if(!empty($result['user_id'])){
                    $user_id = $result['user_id'];
                }else{
                    $user_id = 0;
                }
                
                if(input('post.goods_id')){
                    if(input('post.rush_id')){
                        $goods_id = input('post.goods_id');
                        $rush_id = input('post.rush_id');
                        
                        $goods = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.shop_price,a.goods_desc,a.fuwu,a.is_free,a.leixing,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$goods_id)->where('a.onsale',1)->where('b.open_status',1)->find();
                        if($goods){
                            if($user_id){
                                $colls = Db::name('coll_goods')->where('user_id',$user_id)->where('goods_id',$goods_id)->find();
                                if($colls){
                                    $goods['coll_goods'] = 1;
                                }else{
                                    $goods['coll_goods'] = 0;
                                }
                            }else{
                                $goods['coll_goods'] = 0;
                            }
                            
                            $rushs = Db::name('rush_activity')->where('id',$rush_id)->where('goods_id',$goods['id'])->where('shop_id',$goods['shop_id'])->where('checked',1)->where('recommend',1)->where('is_show',1)->where('start_time','gt',time())->field('id,goods_id,goods_attr,price,num,xznum,kucun,sold,start_time,end_time')->find();
                            if($rushs){
                                $sale_times = Db::name('sale_time')->order('time asc')->field('time')->select();
                                if($sale_times){
                                    $rushtime = array();
                            
                                    $dctime = date('Y-m-d',time());
                                    $tomtime = date('Y-m-d',time()+3600);
                            
                                    foreach ($sale_times as $k2 => $v2){
                                        if($v2['time'] < 10){
                                            $dcthetime = strtotime($dctime.' 0'.$v2['time'].':00:00');
                                        }else{
                                            $dcthetime = strtotime($dctime.' '.$v2['time'].':00:00');
                                        }
                            
                                        $rushtime[] = $dcthetime;
                                    }
                            
                                    foreach ($sale_times as $k3 => $v3){
                                        if($v3['time'] < 10){
                                            $thetime = strtotime($tomtime.' 0'.$v3['time'].':00:00');
                                        }else{
                                            $thetime = strtotime($tomtime.' '.$v3['time'].':00:00');
                                        }
                            
                                        $rushtime[] = $thetime;
                                    }
                            
                                    if($rushtime){
                                        if(in_array($rushs['start_time'], $rushtime)){
                                            $goods['price'] = $rushs['price'];
                                            $webconfig = $this->webconfig;
                                            $goods['thumb_url'] = $webconfig['weburl'].'/'.$goods['thumb_url'];
                                            $goods['goods_desc'] = str_replace("/public/",$webconfig['weburl']."/public/",$goods['goods_desc']);
                                            $goods['goods_desc'] = str_replace("<img","<img style='width:100%;max-height:1000px;'",$goods['goods_desc']);
                                            
                                            if($rushs['goods_attr']){
                                                $goods_attr_str = '';
                                                $gares = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_value,a.attr_price,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$rushs['goods_attr'])->where('a.goods_id',$goods['id'])->where('b.attr_type',1)->select();
                                                if($gares){
                                                    foreach ($gares as $kr => $vr){
                                                        if($kr == 0){
                                                            $goods_attr_str = $vr['attr_name'].':'.$vr['attr_value'];
                                                        }else{
                                                            $goods_attr_str = $goods_attr_str.' '.$vr['attr_name'].':'.$vr['attr_value'];
                                                        }
                                                        $goods['shop_price']+=$vr['attr_price'];
                                                    }
                                                    $goods['goods_name']=$goods['goods_name'].' '.$goods_attr_str;
                                                    $goods['shop_price']=sprintf("%.2f", $goods['shop_price']);
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                                            
                                            
                                            $pronum = $rushs['num'];
                                            
                                            $activity_info = array(
                                                'num'=>$rushs['num'],
                                                'xznum'=>$rushs['xznum'],
                                                'start_time'=>$rushs['start_time'],
                                                'end_time'=>$rushs['end_time'],
                                                'dqtime' => time()
                                            );
                                            
                                            $gpres = Db::name('goods_pic')->where('goods_id',$goods_id)->field('id,img_url,sort')->order('sort asc')->select();
                                            foreach ($gpres as $kp => $vp){
                                                $gpres[$kp]['img_url'] = $webconfig['weburl'].'/'.$vp['img_url'];
                                            }
                                            
                                            $uniattr = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id,a.attr_value,b.attr_name,b.attr_type')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.goods_id',$goods_id)->where('b.attr_type',0)->select();
                                            
                                            $goods_attr = '';
                                            
                                            $ruinfo = array('id'=>$goods['id'],'shop_id'=>$goods['shop_id']);
                                            $gongyong = new GongyongMx();
                                            $activitys = $gongyong->pdrugp($ruinfo);
                                            
                                            //邮费
                                            if($goods['is_free'] == 0){
                                                $shopinfos = Db::name('shops')->where('id',$goods['shop_id'])->field('freight,reduce')->find();
                                                $freight = '运费'.$shopinfos['freight'].' 订单满'.$shopinfos['reduce'].'免运费';
                                            }else{
                                                $freight = '包邮';
                                            }
                                            
                                            //优惠券
                                            $couponinfos = array('is_show'=>0,'infos'=>'');
                                            $couponres = Db::name('coupon')->where('shop_id',$goods['shop_id'])->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('man_price,dec_price')->order('man_price asc')->limit(3)->select();
                                            if($couponres){
                                                $couponinfos = array('is_show'=>1,'infos'=>$couponres);
                                            }
                                            
                                            //商品活动信息
                                            $huodong = array('is_show'=>0,'infos'=>'','prom_id'=>0);
                                            $promotions = Db::name('promotion')->where("find_in_set('".$goods['id']."',info_id)")->where('shop_id',$goods['shop_id'])->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,start_time,end_time')->find();
                                            if($promotions){
                                                $prom_typeres = Db::name('prom_type')->where('prom_id',$promotions['id'])->select();
                                            }else{
                                                $prom_typeres = array();
                                            }
                                            
                                            $goods_promotion = '';
                                            
                                            if(!empty($promotions) && !empty($prom_typeres)){
                                                $start_time = date('Y年m月d日 H时',$promotions['start_time']);
                                                $end_time = date('Y年m月d日 H时',$promotions['end_time']);
                                                foreach ($prom_typeres as $kcp => $vcp){
                                                    $zhekou = $vcp['discount']/10;
                                                    if($kcp == 0){
                                                        $goods_promotion = '商品满 '.$vcp['man_num'].'件 享'.$zhekou.'折';
                                                    }else{
                                                        $goods_promotion = $goods_promotion.'  满 '.$vcp['man_num'].'件 享'.$zhekou.'折';
                                                    }
                                                }
                                                $huodong = array('is_show'=>1,'infos'=>$goods_promotion,'prom_id'=>$promotions['id']);
                                            }
                                            
                                            //服务项
                                            $sertions = array('is_show'=>0,'infos'=>'');
                                            
                                            if(!empty($goods['fuwu'])){
                                                $sertionres = Db::name('sertion')->where('id','in',$goods['fuwu'])->where('is_show',1)->field('ser_name')->order('sort asc')->limit(2)->select();
                                                if($sertionres){
                                                    $sertions = array('is_show'=>1,'infos'=>$sertionres);
                                                }
                                            }
                                            
                                            $goodsinfo = array(
                                                'id'=>$goods['id'],
                                                'goods_name'=>$goods['goods_name'],
                                                'thumb_url'=>$goods['thumb_url'],
                                                'goods_desc'=>$goods['goods_desc'],
                                                'freight'=>$freight,
                                                'leixing'=>$goods['leixing'],
                                                'shop_id'=>$goods['shop_id'],
                                                'price'=>$goods['price'],
                                                'shop_price'=>$goods['shop_price'],
                                                'coll_goods'=>$goods['coll_goods']
                                            );
                                            
                                            $shopinfos = Db::name('shops')->where('id',$goods['shop_id'])->where('open_status',1)->field('id,shop_name,shop_desc,logo,goods_fen,fw_fen,wuliu_fen')->find();
                                            $shopinfos['logo'] = $webconfig['weburl'].'/'.$shopinfos['logo'];
                                            
                                            $shop_customs = Db::name('shop_custom')->where('shop_id',$goods['shop_id'])->where('type',1)->field('info_id')->find();
                                            $remgoodres = array();
                                            
                                            if($shop_customs){
                                                $remgoodres = Db::name('goods')->where('id','in',$shop_customs['info_id'])->where('shop_id',$goods['shop_id'])->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,leixing,shop_id')->order('zonghe_lv desc,id asc')->select();
                                            
                                                if($remgoodres){
                                                    foreach ($remgoodres as $k2 => $v2){
                                                        $remgoodres[$k2]['thumb_url'] = $webconfig['weburl'].'/'.$v2['thumb_url'];
                                            
                                                        $reruinfo = array('id'=>$v2['id'],'shop_id'=>$v2['shop_id']);
                                                        $regongyong = new GongyongMx();
                                                        $reactivitys = $regongyong->pdrugp($reruinfo);
                                            
                                                        if($reactivitys){
                                                            if(!empty($reactivitys['goods_attr'])){
                                                                $regoods_attr_str = '';
                                                                $regares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$reactivitys['goods_attr'])->where('a.goods_id',$v2['id'])->where('b.attr_type',1)->select();
                                                                if($regares){
                                                                    foreach ($regares as $key2 => $val2){
                                                                        if($key2 == 0){
                                                                            $regoods_attr_str = $val2['attr_name'].':'.$val2['attr_value'];
                                                                        }else{
                                                                            $regoods_attr_str = $regoods_attr_str.' '.$val2['attr_name'].':'.$val2['attr_value'];
                                                                        }
                                                                    }
                                                                    $remgoodres[$k2]['goods_name'] = $v2['goods_name'].' '.$regoods_attr_str;
                                                                }
                                                            }
                                            
                                                            $remgoodres[$k2]['zs_price'] = $reactivitys['price'];
                                                        }else{
                                                            $remgoodres[$k2]['zs_price'] = $v2['min_price'];
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            $goodinfores = array(
                                                'goodsinfo'=>$goodsinfo,
                                                'goods_attr'=>$goods_attr,
                                                'pronum'=>$pronum,
                                                'activity_info'=>$activity_info,
                                                'gpres'=>$gpres,
                                                'uniattr'=>$uniattr,
                                                'couponinfos'=>$couponinfos,
                                                'huodong'=>$huodong,
                                                'sertions'=>$sertions,
                                                'shopinfos'=>$shopinfos,
                                                'remgoodres'=>$remgoodres
                                            );
                                            
                                            $value = array('status'=>200,'mess'=>'获取商品详情信息成功','data'=>$goodinfores);
                                        }else{
                                            $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'找不到秒杀时间段信息','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'找不到秒杀时间段信息','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关活动信息','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'商品已下架或不存在','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少秒杀活动参数','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'缺少商品参数','data'=>array('status'=>400));
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