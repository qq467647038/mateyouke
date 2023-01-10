<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class Cart extends Common{
   
    //加入购物车接口
    public function addcart(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $data = input('post.');
                    
                    if(!empty($data['goods_id']) && !empty($data['num'])){
                        $goods_id = $data['goods_id'];
                        $num = $data['num'];
                
                        if(preg_match("/^\\+?[1-9][0-9]*$/", $num)){
                            $goods = Db::name('goods')->alias('a')->field('a.id,a.shop_price,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$goods_id)->where('a.onsale',1)->where('b.open_status',1)->find();
                            if($goods){
                                $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.goods_id',$goods_id)->where('b.attr_type',1)->select();
                                if($radiores){
                                    if(!empty($data['goods_attr']) && !is_array($data['goods_attr'])){
                                        $data['goods_attr'] = trim($data['goods_attr']);
                                        $data['goods_attr'] = str_replace('，', ',', $data['goods_attr']);
                                        $data['goods_attr'] = rtrim($data['goods_attr'],',');
                                        
                                        if($data['goods_attr']){
                                            $gattr = explode(',', $data['goods_attr']);
                                            $gattr = array_unique($gattr);
                                            
                                            if($gattr && is_array($gattr)){
                                                $radioattr = array();
                                                foreach ($radiores as $va){
                                                    $radioattr[$va['attr_id']][] = $va['id'];
                                                }
                                            
                                                $gattres = array();
                                            
                                                foreach ($gattr as $ga){
                                                    if(!empty($ga)){
                                                        $goodsxs = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id',$ga)->where('a.goods_id',$goods_id)->where('b.attr_type',1)->find();
                                                        if($goodsxs){
                                                            $gattres[$goodsxs['attr_id']] = $goodsxs['id'];
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                                            return json($value);
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                                            
                                                foreach ($radioattr as $key => $val){
                                                    if(empty($gattres[$key]) || !in_array($gattres[$key],$val)){
                                                        $value = array('status'=>400,'mess'=>'请选择商品属性','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                                            
                                                foreach ($gattres as $key2 => $val2){
                                                    if(empty($radioattr[$key2]) || !in_array($val2, $radioattr[$key2]) ){
                                                        $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                                            
                                                $goods_attr = implode(',', $gattr);
                                            
                                            }else{
                                                $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'请选择商品属性','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }else{
                                    if(empty($data['goods_attr'])){
                                        $goods_attr = '';
                                    }else{
                                        $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                
                
                                $ruinfo = array('id'=>$goods_id,'shop_id'=>$goods['shop_id']);
                                $ru_attr = $goods_attr;
                
                                $gongyong = new GongyongMx();
                                $activitys = $gongyong->pdrugp($ruinfo,$ru_attr);
                
                                if($activitys){
                                    if($activitys['ac_type'] == 1){
                                        $goods_number = $activitys['kucun'];
                                    }else{
                                        if($activitys['ac_type'] == 3){
                                            $value = array('status'=>400,'mess'=>'拼团活动商品不允许加入购物车','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                        
                                        $prores = Db::name('product')->where('goods_id',$goods_id)->where('goods_attr',$goods_attr)->field('goods_number')->find();
                
                                        if($prores){
                                            $goods_number = $prores['goods_number'];
                                        }else{
                                            $goods_number = 0;
                                        }
                                    }
                                }else{
                                    $prores = Db::name('product')->where('goods_id',$goods_id)->where('goods_attr',$goods_attr)->field('goods_number')->find();

                                    if($prores){
                                        $goods_number = $prores['goods_number'];
                                    }else{
                                        $goods_number = 0;
                                    }
                                }
                
                                if($goods_number > 0){
                                    if($num > 0 && $num <= $goods_number){
                                        $cgoods = Db::name('cart')->where('user_id',$user_id)->where('goods_id',$goods_id)->where('goods_attr',$goods_attr)->where('shop_id',$goods['shop_id'])->find();
                                        $datainfo = array();
                
                                        if(!$cgoods){
                                            if($activitys && $activitys['ac_type'] == 1){
                                                if($num > $activitys['xznum']){
                                                    $value = array('status'=>400,'mess'=>'该秒杀商品限购'.$activitys['xznum'].'件','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                                            
                                            $datainfo['goods_id'] = $goods_id;
                                            $datainfo['goods_attr'] = $goods_attr;
                                            $datainfo['num'] = $num;
                                            $datainfo['shop_id'] = $goods['shop_id'];
                                            $datainfo['user_id'] = $user_id;
                                            $datainfo['add_time'] = time();
                                            $lastId = Db::name('cart')->insert($datainfo);
                                            if($lastId){
                                                $value = array('status'=>200,'mess'=>'加入购物车成功','data'=>array('status'=>200));
                                            }else{
                                                $value = array('status'=>400,'mess'=>'操作失败，请重试','data'=>array('status'=>400));
                                            }
                                        }else{
                                            if($cgoods['num']+$num <= $goods_number){
                                                if($activitys && $activitys['ac_type'] == 1){
                                                    if($cgoods['num']+$num > $activitys['xznum']){
                                                        $value = array('status'=>400,'mess'=>'该秒杀商品限购'.$activitys['xznum'].'件','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                                                
                                                
                                                $datainfo['num'] = $cgoods['num']+$num;
                                                $datainfo['id'] = $cgoods['id'];
                                                $count = Db::name('cart')->update($datainfo);
                                                if($count>0){
                                                    $value = array('status'=>200,'mess'=>'加入购物车成功','data'=>array('status'=>200));
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'操作失败，请重试','data'=>array('status'=>400));
                                                }
                                            }else{
                                                $value = array('status'=>400,'mess'=>'商品库存不足','data'=>array('status'=>400));
                                            }
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'商品库存不足','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'商品库存不足','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'商品已下架或不存在','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'商品数量参数格式错误，加入购物车失败','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少参数，加入购物车失败','data'=>array('status'=>400));
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
    
    
    //获取购物车商品列表接口
    public function index(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
//                    $is_vip = checkVIP($user_id);
                    if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                        $webconfig = $this->webconfig;
                        $perpage = 20;
                        $offset = (input('post.page')-1)*$perpage;

                        $cartres = Db::name('cart')
                            ->alias('a')
                            ->field('b.vip_price,cg.id as cart_id,a.id,a.goods_id,a.goods_attr,a.num,a.shop_id,b.goods_name,b.shop_price,b.thumb_url,c.shop_name')
                            ->join('sp_goods b','a.goods_id = b.id','INNER')
                            ->join('sp_shops c','a.shop_id = c.id','INNER')
                            ->join('sp_coll_goods cg','a.goods_id = cg.goods_id and a.user_id = cg.user_id','left')
                            ->where('a.user_id',$user_id)
                            ->where('b.onsale',1)
                            ->where('c.open_status',1)
                            ->order('a.add_time desc')
                            ->limit($offset,$perpage)
                            ->select();
                        $cartinfores = array();

                        if($cartres){
                            foreach ($cartres as $k => $v){
                                $cartres[$k]['icon'] = 0;
                                // $cartres[$k]['thumb_url'] = $webconfig['weburl'].'/'.$v['thumb_url'];
                                $ruinfo = array('id'=>$v['goods_id'],'shop_id'=>$v['shop_id']);
                                $ru_attr = $v['goods_attr'];
                                
                                $gongyong = new GongyongMx();
                                $activitys = $gongyong->pdrugp($ruinfo,$ru_attr);
                                if($activitys){
                                    if($activitys['ac_type'] == 3){
                                        unset($cartres[$k]);
                                        continue;
                                    }
                                    
                                    $cartres[$k]['is_activity'] = $activitys['ac_type'];
                                    
                                    if($activitys['ac_type'] == 1){
                                        $cartres[$k]['xznum'] = $activitys['xznum'];
                                    }
                                    
                                    $cartres[$k]['sytime'] = time2string($activitys['end_time']-time());
                                    
                                    if($v['goods_attr']){
                                        $cartres[$k]['goods_attr_str'] = '';
                                        $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$v['goods_attr'])->where('a.goods_id',$v['goods_id'])->where('b.attr_type',1)->select();
                                        if($gares){
                                            foreach ($gares as $key => $val){
                                                if($key == 0){
                                                    $cartres[$k]['goods_attr_str'] = $val['attr_name'].':'.$val['attr_value'];
                                                }else{
                                                    $cartres[$k]['goods_attr_str'] = $cartres[$k]['goods_attr_str'].' '.$val['attr_name'].':'.$val['attr_value'];
                                                }
                                            }
                                        }
                                    }else{
                                        $gares = array();
                                        $cartres[$k]['goods_attr_str'] = '';
                                    }

                                    $cartres[$k]['shop_price'] = $activitys['price'];
                                }else{
                                    $cartres[$k]['is_activity'] = 0;
                                    
                                    if($v['goods_attr']){
                                        $cartres[$k]['goods_attr_str'] = '';
                                        // $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$v['goods_attr'])->where('a.goods_id',$v['goods_id'])->where('b.attr_type',1)->select();
                                        $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$v['goods_attr'])->where('a.goods_id',$v['goods_id'])->where('b.attr_type',1)->find();
                                        if($gares){
                                            // foreach ($gares as $key => $val){
                                            //     $cartres[$k]['shop_price']+=$val['attr_price'];
                                            //     if($key == 0){
                                            //         $cartres[$k]['goods_attr_str'] = $val['attr_name'].':'.$val['attr_value'];
                                            //     }else{
                                            //         $cartres[$k]['goods_attr_str'] = $cartres[$k]['goods_attr_str'].' '.$val['attr_name'].':'.$val['attr_value'];
                                            //     }
                                            // }
                                            // $cartres[$k]['shop_price']=sprintf("%.2f", $cartres[$k]['shop_price']);
                                            $cartres[$k]['shop_price']=$gares['attr_price'];
                                            $cartres[$k]['goods_attr_str'] = $gares['attr_name'].':'.$gares['attr_value'];
                                        }
                                    }else{
                                        $gares = array();
                                        $cartres[$k]['goods_attr_str'] = '';
                                    }
//                                    if ($is_vip == 1){
//                                        $cartres[$k]['vip_price'] = empty($cartres[$k]['vip_price']) ? 100 : $cartres[$k]['vip_price'];
//                                        $cartres[$k]['shop_price'] = sprintf("%.2f",$cartres[$k]['shop_price']*($cartres[$k]['vip_price']/100));
//                                    }
                                }
                            }

                            foreach ($cartres as $cr){
                                $cartinfores[$cr['shop_id']]['goodres'][] = $cr;
                            }
                            
                            foreach ($cartinfores as $kc => $vc){
                                $cartinfores[$kc]['couponinfos'] = array('is_show'=>0,'infos'=>'');
                                $cartinfores[$kc]['promotions'] = array('is_show'=>0,'infos'=>'');
                                $cartinfores[$kc]['icon'] = 0;
                                
                                $coupons = Db::name('coupon')->where('shop_id',$kc)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id,man_price,dec_price')->order('man_price asc')->find();
                                if($coupons){
                                    $cartinfores[$kc]['couponinfos'] = array('is_show'=>1,'infos'=>'用优惠券可享满'.$coupons['man_price'].'减'.$coupons['dec_price']);
                                }
                                
                                $proarr = array();

                                foreach ($vc['goodres'] as $vp){
                                    $promotions = Db::name('promotion')->where("find_in_set('".$vp['goods_id']."',info_id)")->where('shop_id',$vp['shop_id'])->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,start_time,end_time')->find();
                                    if($promotions){
                                        $prom_typeres = Db::name('prom_type')->where('prom_id',$promotions['id'])->select();
                                        if($prom_typeres){
                                            foreach ($prom_typeres as $kcp => $vcp){
                                                $zhekou = $vcp['discount']/10;
                                                if($kcp == 0){
                                                    $proarr[$promotions['id']] = '部分商品满 '.$vcp['man_num'].'件 享'.$zhekou.'折';
                                                }else{
                                                    $proarr[$promotions['id']] = $proarr[$promotions['id']].'  满 '.$vcp['man_num'].'件 享'.$zhekou.'折';
                                                }
                                            }
                                        }
                                    }
                                }
                                
                                if($proarr){
                                    $proarr = array_values($proarr);
                                    $cartinfores[$kc]['promotions'] = array('is_show'=>1,'infos'=>$proarr);
                                }
                            }
                            
                            $cartinfores = array_values($cartinfores);
                            
                            /*foreach ($cartinfores as $kc => $vc){
                                $coupons = Db::name('coupon')->where('shop_id',$kc)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id,man_price,dec_price')->order('man_price asc')->find();
                                if($coupons){
                                    $shprice = 0;
                                    foreach ($vc as $vp){
                                        $shprice+=$vp['shop_price'];
                                    }
                                    
                                    $couinfos = Db::name('coupon')->where('shop_id',$kc)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->where('man_price','elt',$shprice)->field('id,man_price,dec_price')->order('man_price desc')->find();
                                    if($couinfos){
                                        $cartinfores[$kc]['couponinfos'] = array('show'=>1,'infos'=>$couinfos['dec_price'].'元店铺优惠券 （满'.$couinfos['man_price'].'元）','dec_price'=>$couinfos['dec_price']);
                                    }else{
                                        $cartinfores[$kc]['couponinfos'] = array('show'=>1,'infos'=>'','dec_price'=>0);
                                    }
                                }else{
                                    $cartinfores[$kc]['couponinfos'] = array('show'=>0,'infos'=>'','dec_price'=>0);
                                }
                            }*/
                        }
                        $value = array('status'=>200,'mess'=>'获取购物车信息成功','data'=>$cartinfores);
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

     //直播间获取购物车商品列表接口
     public function roomCartGoods(){
        //  echo 111;die;
        if(!request()->isPost()){
                $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
                return json($value);
        }
        if(input('post.token')){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                $shop_id = input('post.shop_id');
                if(empty($shop_id)){
                    datamsg(LOSE,'缺少店铺id参数');
                }
                if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                    $webconfig = $this->webconfig;
                    $perpage = 20;
                    $offset = (input('post.page')-1)*$perpage;

                    $cartres = Db::name('cart')->alias('a')->field('a.id,a.goods_id,a.goods_attr,a.num,a.shop_id,b.goods_name,b.shop_price,b.thumb_url,c.shop_name')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.user_id',$user_id)->where('b.onsale',1)->where('c.open_status',1)->where('a.shop_id',$shop_id)->order('a.add_time desc')->limit($offset,$perpage)->select();
                    
                    $cartinfores = array();
                    
                    if($cartres){
                        foreach ($cartres as $k => $v){
                            $cartres[$k]['icon'] = 0;
                            // dump($webconfig);die;
                            if($webconfig['cos_file'] = '开启'){
                                $domain = config('tengxunyun')['cos_domain'];
                            }else{
                                $domain = $webconfig['weburl'];
                            }
                            $domain = $webconfig['weburl'];
                            // echo $domain;die;
                            $cartres[$k]['thumb_url'] = $domain.'/'.$v['thumb_url'];
                            
                            $ruinfo = array('id'=>$v['goods_id'],'shop_id'=>$v['shop_id']);
                            $ru_attr = $v['goods_attr'];
                            
                            $gongyong = new GongyongMx();
                            $activitys = $gongyong->pdrugp($ruinfo,$ru_attr);
                            
                            if($activitys){
                                if($activitys['ac_type'] == 3){
                                    unset($cartres[$k]);
                                    continue;
                                }
                                
                                $cartres[$k]['is_activity'] = $activitys['ac_type'];
                                
                                if($activitys['ac_type'] == 1){
                                    $cartres[$k]['xznum'] = $activitys['xznum'];
                                }
                                
                                $cartres[$k]['sytime'] = time2string($activitys['end_time']-time());
                                
                                if($v['goods_attr']){
                                    $cartres[$k]['goods_attr_str'] = '';
                                    $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$v['goods_attr'])->where('a.goods_id',$v['goods_id'])->where('b.attr_type',1)->select();
                                    if($gares){
                                        foreach ($gares as $key => $val){
                                            if($key == 0){
                                                $cartres[$k]['goods_attr_str'] = $val['attr_name'].':'.$val['attr_value'];
                                            }else{
                                                $cartres[$k]['goods_attr_str'] = $cartres[$k]['goods_attr_str'].' '.$val['attr_name'].':'.$val['attr_value'];
                                            }
                                        }
                                    }
                                }else{
                                    $gares = array();
                                    $cartres[$k]['goods_attr_str'] = '';
                                }

                                $cartres[$k]['shop_price'] = $activitys['price'];
                            }else{
                                $cartres[$k]['is_activity'] = 0;
                                
                                if($v['goods_attr']){
                                    $cartres[$k]['goods_attr_str'] = '';
                                    $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$v['goods_attr'])->where('a.goods_id',$v['goods_id'])->where('b.attr_type',1)->select();
                                    if($gares){
                                        foreach ($gares as $key => $val){
                                            $cartres[$k]['shop_price']+=$val['attr_price'];
                                            if($key == 0){
                                                $cartres[$k]['goods_attr_str'] = $val['attr_name'].':'.$val['attr_value'];
                                            }else{
                                                $cartres[$k]['goods_attr_str'] = $cartres[$k]['goods_attr_str'].' '.$val['attr_name'].':'.$val['attr_value'];
                                            }
                                        }
                                        $cartres[$k]['shop_price']=sprintf("%.2f", $cartres[$k]['shop_price']);
                                    }
                                }else{
                                    $gares = array();
                                    $cartres[$k]['goods_attr_str'] = '';
                                }
                            }
                        }

                        foreach ($cartres as $cr){
                            $cartinfores[$cr['shop_id']]['goodres'][] = $cr;
                        }
                        
                        foreach ($cartinfores as $kc => $vc){
                            $cartinfores[$kc]['couponinfos'] = array('is_show'=>0,'infos'=>'');
                            $cartinfores[$kc]['promotions'] = array('is_show'=>0,'infos'=>'');
                            $cartinfores[$kc]['icon'] = 0;
                            
                            $coupons = Db::name('coupon')->where('shop_id',$kc)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id,man_price,dec_price')->order('man_price asc')->find();
                            if($coupons){
                                $cartinfores[$kc]['couponinfos'] = array('is_show'=>1,'infos'=>'用优惠券可享满'.$coupons['man_price'].'减'.$coupons['dec_price']);
                            }
                            
                            $proarr = array();

                            foreach ($vc['goodres'] as $vp){
                                $promotions = Db::name('promotion')->where("find_in_set('".$vp['goods_id']."',info_id)")->where('shop_id',$vp['shop_id'])->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,start_time,end_time')->find();
                                if($promotions){
                                    $prom_typeres = Db::name('prom_type')->where('prom_id',$promotions['id'])->select();
                                    if($prom_typeres){
                                        foreach ($prom_typeres as $kcp => $vcp){
                                            $zhekou = $vcp['discount']/10;
                                            if($kcp == 0){
                                                $proarr[$promotions['id']] = '部分商品满 '.$vcp['man_num'].'件 享'.$zhekou.'折';
                                            }else{
                                                $proarr[$promotions['id']] = $proarr[$promotions['id']].'  满 '.$vcp['man_num'].'件 享'.$zhekou.'折';
                                            }
                                        }
                                    }
                                }
                            }
                            
                            if($proarr){
                                $proarr = array_values($proarr);
                                $cartinfores[$kc]['promotions'] = array('is_show'=>1,'infos'=>$proarr);
                            }
                        }
                        
                        $cartinfores = array_values($cartinfores);
                        
                        /*foreach ($cartinfores as $kc => $vc){
                            $coupons = Db::name('coupon')->where('shop_id',$kc)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id,man_price,dec_price')->order('man_price asc')->find();
                            if($coupons){
                                $shprice = 0;
                                foreach ($vc as $vp){
                                    $shprice+=$vp['shop_price'];
                                }
                                
                                $couinfos = Db::name('coupon')->where('shop_id',$kc)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->where('man_price','elt',$shprice)->field('id,man_price,dec_price')->order('man_price desc')->find();
                                if($couinfos){
                                    $cartinfores[$kc]['couponinfos'] = array('show'=>1,'infos'=>$couinfos['dec_price'].'元店铺优惠券 （满'.$couinfos['man_price'].'元）','dec_price'=>$couinfos['dec_price']);
                                }else{
                                    $cartinfores[$kc]['couponinfos'] = array('show'=>1,'infos'=>'','dec_price'=>0);
                                }
                            }else{
                                $cartinfores[$kc]['couponinfos'] = array('show'=>0,'infos'=>'','dec_price'=>0);
                            }
                        }*/
                    }
                    $value = array('status'=>200,'mess'=>'获取购物车信息成功','data'=>$cartinfores);
                }else{
                    $value = array('status'=>400,'mess'=>'缺少页数参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
        }
         
        return json($value);
    }
    
    //修改购物车商品信息
    public function editcart(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.cart_id')){
                        if(input('post.num')){
                            $cart_id = input('post.cart_id');
                            $num = input('post.num');
                            if(preg_match("/^\\+?[1-9][0-9]*$/", $num)){
                                $carts = Db::name('cart')->alias('a')->field('a.*')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.id',$cart_id)->where('a.user_id',$user_id)->where('b.onsale',1)->where('c.open_status',1)->find();
                                if($carts){
                                    
                                    $ruinfo = array('id'=>$carts['goods_id'],'shop_id'=>$carts['shop_id']);
                                    $ru_attr = $carts['goods_attr'];
                                    
                                    $gongyong = new GongyongMx();
                                    $activitys = $gongyong->pdrugp($ruinfo,$ru_attr);
                                    
                                    if($activitys){
                                        if($activitys['ac_type'] == 1){
                                            $goods_number = $activitys['kucun'];
                                        }else{
                                            if($activitys['ac_type'] == 3){
                                                $value = array('status'=>400,'mess'=>'拼团活动商品不允许操作购物车','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                            
                                            $prores = Db::name('product')->where('goods_id',$carts['goods_id'])->where('goods_attr',$carts['goods_attr'])->field('goods_number')->find();
                                    
                                            if($prores){
                                                $goods_number = $prores['goods_number'];
                                            }else{
                                                $goods_number = 0;
                                            }
                                        }
                                    }else{
                                        $prores = Db::name('product')->where('goods_id',$carts['goods_id'])->where('goods_attr',$carts['goods_attr'])->field('goods_number')->find();
                                        
                                        if($prores){
                                            $goods_number = $prores['goods_number'];
                                        }else{
                                            $goods_number = 0;
                                        }
                                    }
    
                                    if($num < $carts['num']){
                                        $count = Db::name('cart')->where('id',$cart_id)->where('user_id',$user_id)->update(array('num'=>$num));
                                        if($count > 0){
                                            $value = array('status'=>200,'mess'=>'操作成功','data'=>array('status'=>200));
                                        }else{
                                            $value = array('status'=>400,'mess'=>'操作失败','data'=>array('status'=>400));
                                        }
                                    }elseif($num == $carts['num']){
                                        $value = array('status'=>400,'mess'=>'操作失败','data'=>array('status'=>400));
                                    }elseif($num > $carts['num']){
                                        if($num <= $goods_number){
                                            if($activitys && $activitys['ac_type'] == 1){
                                                if($num > $activitys['xznum']){
                                                    $value = array('status'=>400,'mess'=>'该秒杀商品限购'.$activitys['xznum'].'件','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                                            
                                            $count = Db::name('cart')->where('id',$cart_id)->where('user_id',$user_id)->update(array('num'=>$num));
                                            if($count > 0){
                                                $value = array('status'=>200,'mess'=>'操作成功','data'=>array('status'=>200));
                                            }else{
                                                $value = array('status'=>400,'mess'=>'操作失败','data'=>array('status'=>400));
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'商品库存不足','data'=>array('status'=>400));
                                        }
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'找不到相关购物车信息','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'商品数量参数格式错误','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'商品数量参数错误','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少购物车参数','data'=>array('status'=>400));
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
    
    //删除购物车信息
    public function delcart(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.cart_id') && !is_array(input('post.cart_id'))){
                        $cart_id = input('post.cart_id');
                        $cart_id = trim($cart_id);
                        $cart_id = str_replace('，', ',', $cart_id);
                        $cart_id = rtrim($cart_id,',');
                        
                        if($cart_id){
                            if(strpos($cart_id, ',') !== false){
                                $cartres = explode(',', $cart_id);
                                $cartres = array_unique($cartres);
                                
                                if($cartres && is_array($cartres)){
                                    foreach ($cartres as $v){
                                        if(!empty($v)){
                                            $carts = Db::name('cart')->where('id',$v)->where('user_id',$user_id)->find();
                                            if(!$carts){
                                                $value = array('status'=>400,'mess'=>'购物车参数错误','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'购物车参数错误','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }
                                    
                                    $cartstr = implode(',', $cartres);
                                    $count = Db::name('cart')->where('id','in',$cartstr)->delete();
                                }else{
                                    $value = array('status'=>400,'mess'=>'购物车参数错误','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }else{
                                $carts = Db::name('cart')->where('id',$cart_id)->where('user_id',$user_id)->find();
                                if($carts){
                                    $count = Db::name('cart')->where('id',$cart_id)->delete();
                                }else{
                                    $value = array('status'=>400,'mess'=>'购物车参数错误','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }
                            
                            if($count > 0){
                                $value = array('status'=>200,'mess'=>'删除成功','data'=>array('status'=>200));
                            }else{
                                $value = array('status'=>400,'mess'=>'删除失败','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'购物车参数错误','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少购物车参数','data'=>array('status'=>400));
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
    
    
    
    //获取购物车商品数量
    public function getnum(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $countnum = Db::name('cart')->alias('a')->field('a.*')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.user_id',$user_id)->where('b.onsale',1)->where('c.open_status',1)->group('a.goods_id,a.goods_attr')->count();
                    $value = array('status'=>200,'mess'=>'获取购物车数量信息成功','data'=>array('countnum'=>$countnum));
                }else{
                    $value = $result;
                }
            }else{
//                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    // 客服加入购物车
    public function addCartByService(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $data = input('post.');
                    
                    if(!empty($data['goods_id']) && !empty($data['num'])){
                        $goods_id = $data['goods_id'];
                        $num = $data['num'];
                
                        if(preg_match("/^\\+?[1-9][0-9]*$/", $num)){
                            $goods = Db::name('goods')->alias('a')->field('a.id,a.shop_price,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$goods_id)->where('a.onsale',1)->where('b.open_status',1)->find();
                            if($goods){
                                $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.goods_id',$goods_id)->where('b.attr_type',1)->select();
                                if($radiores){
                                    if(!empty($data['goods_attr']) && !is_array($data['goods_attr'])){
                                        $data['goods_attr'] = trim($data['goods_attr']);
                                        $data['goods_attr'] = str_replace('，', ',', $data['goods_attr']);
                                        $data['goods_attr'] = rtrim($data['goods_attr'],',');
                                        
                                        if($data['goods_attr']){
                                            $gattr = explode(',', $data['goods_attr']);
                                            $gattr = array_unique($gattr);
                                            
                                            if($gattr && is_array($gattr)){
                                                $radioattr = array();
                                                foreach ($radiores as $va){
                                                    $radioattr[$va['attr_id']][] = $va['id'];
                                                }
                                            
                                                $gattres = array();
                                            
                                                foreach ($gattr as $ga){
                                                    if(!empty($ga)){
                                                        $goodsxs = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id',$ga)->where('a.goods_id',$goods_id)->where('b.attr_type',1)->find();
                                                        if($goodsxs){
                                                            $gattres[$goodsxs['attr_id']] = $goodsxs['id'];
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                                            return json($value);
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                                            
                                                foreach ($radioattr as $key => $val){
                                                    if(empty($gattres[$key]) || !in_array($gattres[$key],$val)){
                                                        $value = array('status'=>400,'mess'=>'请选择商品属性','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                                            
                                                foreach ($gattres as $key2 => $val2){
                                                    if(empty($radioattr[$key2]) || !in_array($val2, $radioattr[$key2]) ){
                                                        $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                                            
                                                $goods_attr = implode(',', $gattr);
                                            
                                            }else{
                                                $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'请选择商品属性','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }else{
                                    if(empty($data['goods_attr'])){
                                        $goods_attr = '';
                                    }else{
                                        $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                
                
                                $ruinfo = array('id'=>$goods_id,'shop_id'=>$goods['shop_id']);
                                $ru_attr = $goods_attr;
                
                                $gongyong = new GongyongMx();
                                $activitys = $gongyong->pdrugp($ruinfo,$ru_attr);
                
                                if($activitys){
                                    if($activitys['ac_type'] == 1){
                                        $goods_number = $activitys['kucun'];
                                    }else{
                                        if($activitys['ac_type'] == 3){
                                            $value = array('status'=>400,'mess'=>'拼团活动商品不允许加入购物车','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                        
                                        $prores = Db::name('product')->where('goods_id',$goods_id)->where('goods_attr',$goods_attr)->field('goods_number')->find();
                
                                        if($prores){
                                            $goods_number = $prores['goods_number'];
                                        }else{
                                            $goods_number = 0;
                                        }
                                    }
                                }else{
                                    $prores = Db::name('product')->where('goods_id',$goods_id)->where('goods_attr',$goods_attr)->field('goods_number')->find();
                
                                    if($prores){
                                        $goods_number = $prores['goods_number'];
                                    }else{
                                        $goods_number = 0;
                                    }
                                }
                
                                if($goods_number > 0){
                                    if($num > 0 && $num <= $goods_number){
                                        $cgoods = Db::name('cart')->where('user_id',$user_id)->where('goods_id',$goods_id)->where('goods_attr',$goods_attr)->where('shop_id',$goods['shop_id'])->find();
                                        $datainfo = array();
                
                                        if(!$cgoods){
                                            if($activitys && $activitys['ac_type'] == 1){
                                                if($num > $activitys['xznum']){
                                                    $value = array('status'=>400,'mess'=>'该秒杀商品限购'.$activitys['xznum'].'件','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                                            
                                            $datainfo['goods_id'] = $goods_id;
                                            $datainfo['goods_attr'] = $goods_attr;
                                            $datainfo['num'] = $num;
                                            $datainfo['shop_id'] = $goods['shop_id'];
                                            $datainfo['user_id'] = $user_id;
                                            $datainfo['add_time'] = time();
                                            $lastId = Db::name('cart')->insert($datainfo);
                                            if($lastId){
                                                $value = array('status'=>200,'mess'=>'加入购物车成功','data'=>array('status'=>200));
                                            }else{
                                                $value = array('status'=>400,'mess'=>'操作失败，请重试','data'=>array('status'=>400));
                                            }
                                        }else{
                                            if($cgoods['num']+$num <= $goods_number){
                                                if($activitys && $activitys['ac_type'] == 1){
                                                    if($cgoods['num']+$num > $activitys['xznum']){
                                                        $value = array('status'=>400,'mess'=>'该秒杀商品限购'.$activitys['xznum'].'件','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                                                
                                                
                                                $datainfo['num'] = $cgoods['num']+$num;
                                                $datainfo['id'] = $cgoods['id'];
                                                $count = Db::name('cart')->update($datainfo);
                                                if($count>0){
                                                    $value = array('status'=>200,'mess'=>'加入购物车成功','data'=>array('status'=>200));
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'操作失败，请重试','data'=>array('status'=>400));
                                                }
                                            }else{
                                                $value = array('status'=>400,'mess'=>'商品库存不足','data'=>array('status'=>400));
                                            }
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'商品库存不足','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'商品库存不足','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'商品已下架或不存在','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'商品数量参数格式错误，加入购物车失败','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少参数，加入购物车失败','data'=>array('status'=>400));
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

     //客服添加商品并加入购物车接口
     public function addGoodsToCartByService(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $pic = input('post.pic');
                    $goods_name = input('post.goods_name');
                    $price = input('post.price');
                    $service_fee = input('post.service_fee');
                    $shop_id = input('post.shop_id');
                    $shop = db('shops')->find($shop_id);
                    $user_id = input('post.user_id'); // 咨询者
                    $user = db('member')->find($user_id);
                    
                    if(empty($shop_id)){
                        datamsg(LOSE,'缺少直播店铺id参数');
                    }else{
                        if(empty($shop)){
                            datamsg(LOSE,'直播店铺id参数有误');
                        }
                    }
                    if(empty($user_id)){
                        datamsg(LOSE,'缺少咨询者id参数');
                    }else{
                        if(empty($user)){
                            datamsg(LOSE,'咨询者id参数有误');
                        }
                    }
                    if(empty($pic)){
                        datamsg(LOSE,'请上传产品照片');
                    }
                    if(empty($goods_name)){
                        datamsg(LOSE,'请填写产品编号');
                    }
                    if(empty($price)){
                        datamsg(LOSE,'请填写产品价格');
                    }
                    if($price <= 0){
                        datamsg(LOSE,'产品价格大于0');
                    }
                   
                    // if(empty($service_fee)){
                    //     datamsg(LOSE,'请填写代购费');
                    // }
                    
                    $goodsData['goods_name'] = $goods_name;
                    $goodsData['search_keywords'] = $goods_name;
                    $goodsData['thumb_url'] = $pic;
                    $goodsData['market_price'] = $price;
                    $goodsData['shop_price'] = $price;
                    $goodsData['cate_id'] = 92;
                    $goodsData['type_id'] = 41; // 41为文玩珠宝
                    $goodsData['addtime'] = time();
                    $goodsData['shop_id'] = $shop_id;
                    $goodsData['leixing'] = 2;
                    $goodsData['goods_desc'] = date('Y-m-d').'添加编号:'.$goods_name.'商品';
                    $goodsData['create_user'] = $result['user_id'];
                    $goodsData['service_fee'] = (int)$service_fee;

                    $goodsRes = db('goods')->insertGetId($goodsData);
                    if($goodsRes){
                        $stockData['goods_id'] = $goodsRes;
                        $stockData['goods_number'] = 1;
                        $stockData['shop_id'] = $shop_id;
                        $stockRes = db('product')->insertGetId($stockData);
                    }else{
                        datamsg(LOSE,'商品保存失败');
                    }

                    // if($goodsRes && $stockRes){
                    //     echo 1;die;
                    // }else{
                    //     echo 2;die;
                    // }


                    

                    $user_id = $user_id;
                    $data = array(
                        'goods_id' => $goodsRes,
                        'num' => 1,
                    );
                    
                    if(!empty($data['goods_id']) && !empty($data['num'])){
                        $goods_id = $data['goods_id'];
                        $num = $data['num'];
                
                        if(preg_match("/^\\+?[1-9][0-9]*$/", $num)){
                            $goods = Db::name('goods')->alias('a')->field('a.id,a.shop_price,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$goods_id)->where('a.onsale',1)->where('b.open_status',1)->find();
                            if($goods){
                                $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.goods_id',$goods_id)->where('b.attr_type',1)->select();
                                if($radiores){
                                    if(!empty($data['goods_attr']) && !is_array($data['goods_attr'])){
                                        $data['goods_attr'] = trim($data['goods_attr']);
                                        $data['goods_attr'] = str_replace('，', ',', $data['goods_attr']);
                                        $data['goods_attr'] = rtrim($data['goods_attr'],',');
                                        
                                        if($data['goods_attr']){
                                            $gattr = explode(',', $data['goods_attr']);
                                            $gattr = array_unique($gattr);
                                            
                                            if($gattr && is_array($gattr)){
                                                $radioattr = array();
                                                foreach ($radiores as $va){
                                                    $radioattr[$va['attr_id']][] = $va['id'];
                                                }
                                            
                                                $gattres = array();
                                            
                                                foreach ($gattr as $ga){
                                                    if(!empty($ga)){
                                                        $goodsxs = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id',$ga)->where('a.goods_id',$goods_id)->where('b.attr_type',1)->find();
                                                        if($goodsxs){
                                                            $gattres[$goodsxs['attr_id']] = $goodsxs['id'];
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                                            return json($value);
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                                            
                                                foreach ($radioattr as $key => $val){
                                                    if(empty($gattres[$key]) || !in_array($gattres[$key],$val)){
                                                        $value = array('status'=>400,'mess'=>'请选择商品属性','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                                            
                                                foreach ($gattres as $key2 => $val2){
                                                    if(empty($radioattr[$key2]) || !in_array($val2, $radioattr[$key2]) ){
                                                        $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                                            
                                                $goods_attr = implode(',', $gattr);
                                            
                                            }else{
                                                $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'请选择商品属性','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }else{
                                    if(empty($data['goods_attr'])){
                                        $goods_attr = '';
                                    }else{
                                        $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                
                
                                $ruinfo = array('id'=>$goods_id,'shop_id'=>$goods['shop_id']);
                                $ru_attr = $goods_attr;
                
                                $gongyong = new GongyongMx();
                                $activitys = $gongyong->pdrugp($ruinfo,$ru_attr);
                
                                if($activitys){
                                    if($activitys['ac_type'] == 1){
                                        $goods_number = $activitys['kucun'];
                                    }else{
                                        if($activitys['ac_type'] == 3){
                                            $value = array('status'=>400,'mess'=>'拼团活动商品不允许加入购物车','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                        
                                        $prores = Db::name('product')->where('goods_id',$goods_id)->where('goods_attr',$goods_attr)->field('goods_number')->find();
                
                                        if($prores){
                                            $goods_number = $prores['goods_number'];
                                        }else{
                                            $goods_number = 0;
                                        }
                                    }
                                }else{
                                    $prores = Db::name('product')->where('goods_id',$goods_id)->where('goods_attr',$goods_attr)->field('goods_number')->find();
                
                                    if($prores){
                                        $goods_number = $prores['goods_number'];
                                    }else{
                                        $goods_number = 0;
                                    }
                                }
                
                                if($goods_number > 0){
                                    if($num > 0 && $num <= $goods_number){
                                        $cgoods = Db::name('cart')->where('user_id',$user_id)->where('goods_id',$goods_id)->where('goods_attr',$goods_attr)->where('shop_id',$goods['shop_id'])->find();
                                        $datainfo = array();
                
                                        if(!$cgoods){
                                            if($activitys && $activitys['ac_type'] == 1){
                                                if($num > $activitys['xznum']){
                                                    $value = array('status'=>400,'mess'=>'该秒杀商品限购'.$activitys['xznum'].'件','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                                            
                                            $datainfo['goods_id'] = $goods_id;
                                            $datainfo['goods_attr'] = $goods_attr;
                                            $datainfo['num'] = $num;
                                            $datainfo['shop_id'] = $goods['shop_id'];
                                            $datainfo['user_id'] = $user_id;
                                            $datainfo['add_time'] = time();
                                            $lastId = Db::name('cart')->insert($datainfo);
                                            if($lastId){
                                                $value = array('status'=>200,'mess'=>'加入购物车成功','data'=>array('status'=>200));
                                            }else{
                                                $value = array('status'=>400,'mess'=>'操作失败，请重试','data'=>array('status'=>400));
                                            }
                                        }else{
                                            if($cgoods['num']+$num <= $goods_number){
                                                if($activitys && $activitys['ac_type'] == 1){
                                                    if($cgoods['num']+$num > $activitys['xznum']){
                                                        $value = array('status'=>400,'mess'=>'该秒杀商品限购'.$activitys['xznum'].'件','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                                                
                                                
                                                $datainfo['num'] = $cgoods['num']+$num;
                                                $datainfo['id'] = $cgoods['id'];
                                                $count = Db::name('cart')->update($datainfo);
                                                if($count>0){
                                                    $value = array('status'=>200,'mess'=>'加入购物车成功','data'=>array('status'=>200));
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'操作失败，请重试','data'=>array('status'=>400));
                                                }
                                            }else{
                                                $value = array('status'=>400,'mess'=>'商品库存不足','data'=>array('status'=>400));
                                            }
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'商品库存不足','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'商品库存不足','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'商品已下架或不存在','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'商品数量参数格式错误，加入购物车失败','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少参数，加入购物车失败','data'=>array('status'=>400));
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


}