<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use app\apicloud\model\AliPayHelper;
use app\common\model\Member as MemberModel;
use app\common\service\MiniWxPay;
use app\common\service\ComWxPay;
use app\common\service\PortalMiniWxPay;
use app\common\logic\OrderAfterLogic;
use app\apicloud\model\SignSet as SignSetmodel;
use app\common\service\RateService;
use think\Db;

class Order extends Common{
    //购物车购买确认订单接口
    public function cartbuy(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.cart_idres') && !is_array(input('post.cart_idres'))){
                        $cart_idres = trim(input('post.cart_idres'));
                        $cart_idres = str_replace('，', ',', $cart_idres);
                        $cart_idres = rtrim($cart_idres,',');
                        
                        if($cart_idres){
                            $cart_idres = explode(',', $cart_idres);
                            $cart_idres = array_unique($cart_idres);
                            
                            if($cart_idres && is_array($cart_idres)){
                            
                                foreach($cart_idres as $v){
                                    if(!empty($v)){
                                        $carts = Db::name('cart')->alias('a')->field('a.*,b.goods_name,b.shop_price,b.thumb_url,c.shop_name')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.id',$v)->where('a.user_id',$user_id)->where('b.onsale',1)->where('c.open_status',1)->find();
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
                                                        $value = array('status'=>400,'mess'=>'存在拼团商品，确认订单失败','data'=>array('status'=>400));
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
                            
                                            if($carts['num'] <= 0 || $carts['num'] > $goods_number){
                                                $value = array('status'=>400,'mess'=>$carts['goods_name'].'库存不足','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'购物车存在信息参数错误','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'购物车存在信息参数错误','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                            
                                $cart_infos = implode(',', $cart_idres);
                                $value = array('status'=>200,'mess'=>'操作成功','data'=>array('cart_idres'=>$cart_infos));
                            }else{
                                $value = array('status'=>400,'mess'=>'购物车信息参数错误','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'购物车信息参数错误','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少购物车信息参数','data'=>array('status'=>400));
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
    
    //购物车购买确认订单详情接口
    public function cartsure(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.cart_idres') && !is_array(input('post.cart_idres'))){
                        $wallets = Db::name('wallet')->where('user_id',$user_id)->find();
                        
                        $cart_idres = trim(input('post.cart_idres'));
                        $cart_idres = str_replace('，', ',', $cart_idres);
                        $cart_idres = rtrim($cart_idres,',');
                        
                        if($cart_idres){
                            $cart_idres = explode(',', $cart_idres);
                            $cart_idres = array_unique($cart_idres);
                            
                            if($cart_idres && is_array($cart_idres)){
                                $zong_num = 0;
                                $zsprice = 0;
                                $goodinfores = array();
                            
                                $webconfig = $this->webconfig;
                                $rateService = new RateService($user_id);
                                foreach($cart_idres as $v){
                                    if(!empty($v)){
                                        $carts = Db::name('cart')->alias('a')->field('a.*,b.goods_name,b.shop_price,b.thumb_url,b.is_free,c.shop_name, b.type')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.id',$v)->where('a.user_id',$user_id)->where('b.onsale',1)->where('c.open_status',1)->find();
                                        if($carts){
                                            // $carts['thumb_url'] = $webconfig['weburl'].'/'.$carts['thumb_url'];
                            
                                            $ruinfo = array('id'=>$carts['goods_id'],'shop_id'=>$carts['shop_id']);
                                            $ru_attr = $carts['goods_attr'];
                            
                                            $gongyong = new GongyongMx();
                                            $activitys = $gongyong->pdrugp($ruinfo,$ru_attr);
                            
                                            if($activitys){
                                                if($activitys['ac_type'] == 1){
                                                    $goods_number = $activitys['kucun'];
                                                }else{
                                                    if($activitys['ac_type'] == 3){
                                                        $value = array('status'=>400,'mess'=>'拼团商品不支持购物车提交订单','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                    
                                                    $prores = Db::name('product')->where('goods_id',$carts['goods_id'])->where('goods_attr',$carts['goods_attr'])->field('goods_number')->find();
                                                    if($prores){
                                                        $goods_number = $prores['goods_number'];
                                                    }else{
                                                        $goods_number = 0;
                                                    }
                                                }
                            
                                                if($carts['num'] > 0 && $carts['num'] <= $goods_number){
                                                    if($carts['goods_attr']){
                                                        $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$carts['goods_attr'])->where('a.goods_id',$carts['goods_id'])->where('b.attr_type',1)->select();
                                                        $goods_attr_str = '';
                                                        if($gares){
                                                            foreach ($gares as $key => $val){
                                                                if($key == 0){
                                                                    $goods_attr_str = $val['attr_name'].':'.$val['attr_value'];
                                                                }else{
                                                                    $goods_attr_str = $goods_attr_str.' '.$val['attr_name'].':'.$val['attr_value'];
                                                                }
                                                            }
                                                        }
                                                    }else{
                                                        $gares = array();
                                                        $goods_attr_str = '';
                                                    }
                            
                                                    $carts['shop_price'] = $activitys['price'];
                                                    $goodinfores[] = array('id'=>$carts['goods_id'],'goods_name'=>$carts['goods_name'],'thumb_url'=>$carts['thumb_url'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$carts['shop_price'],'goods_num'=>$carts['num'],'is_free'=>$carts['is_free'],'shop_id'=>$carts['shop_id'],'shop_name'=>$carts['shop_name']);
                                                }else{
                                                    $value = array('status'=>400,'mess'=>$carts['goods_name'].'库存不足','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }else{
                                                $prores = Db::name('product')->where('goods_id',$carts['goods_id'])->where('goods_attr',$carts['goods_attr'])->field('goods_number')->find();
                                                if($prores){
                                                    $goods_number = $prores['goods_number'];
                                                }else{
                                                    $goods_number = 0;
                                                }
                                                
                                                if($carts['num'] > 0 && $carts['num'] <= $goods_number){
                                                    if($carts['goods_attr']){
                                                        $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$carts['goods_attr'])->where('a.goods_id',$carts['goods_id'])->where('b.attr_type',1)->select();
                                                        $goods_attr_str = '';
                                                        if($gares){
                                                            foreach ($gares as $key => $val){
                                                                $carts['shop_price']+=$val['attr_price'];
                                                                if($key == 0){
                                                                    $goods_attr_str = $val['attr_name'].':'.$val['attr_value'];
                                                                }else{
                                                                    $goods_attr_str = $goods_attr_str.' '.$val['attr_name'].':'.$val['attr_value'];
                                                                }
                                                            }
                                                            $carts['shop_price']=sprintf("%.2f", $carts['shop_price']);
                                                        }
                                                    }else{
                                                        $goods_attr_str = '';
                                                    }
                                                    
                                                    // 会员折扣
                                                    if($rateService->isRate($carts['shop_id'])){
                                                        $rate = $rateService->findUserRate();
                                                        $carts['shop_price'] = $carts['shop_price']*$rate;
                                                    }
                                                    $goodinfores[] = array('id'=>$carts['goods_id'],'goods_name'=>$carts['goods_name'],'thumb_url'=>$carts['thumb_url'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$carts['shop_price'],'goods_num'=>$carts['num'],'is_free'=>$carts['is_free'],'shop_id'=>$carts['shop_id'],'shop_name'=>$carts['shop_name']);
                                                    
                                                }else{
                                                    $value = array('status'=>400,'mess'=>$carts['goods_name'].'库存不足','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'购物车存在信息参数错误','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'购物车存在信息参数错误','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                            
                                if($goodinfores){
                                    $hqgoodsinfos = array();
                            
                                    foreach ($goodinfores as $kd => $vd){
                                        $hqgoodsinfos[$vd['shop_id']]['goodres'][] = $vd;
                                    }
                            
                                    if($hqgoodsinfos){
                                        foreach ($hqgoodsinfos as $kc => $vc){
                                            $hqgoodsinfos[$kc]['coupon_str'] = '';
                                            $hqgoodsinfos[$kc]['cxhuodong'] = array();
                                            $hqgoodsinfos[$kc]['youhui_price'] = 0;
                                            $hqgoodsinfos[$kc]['freight'] = 0;
                                            $hqgoodsinfos[$kc]['xiaoji_price'] = 0;
                            
                                            $xiaoji = 0;
                                            $shopgoods_num = 0;

 
                                            foreach ($vc['goodres'] as $vp){
                                                $xiaoji+=sprintf("%.2f", $vp['shop_price']*$vp['goods_num']);
                                                $shopgoods_num+=$vp['goods_num'];
                                            }
                            
                                            $coupons = Db::name('coupon')->where('shop_id',$kc)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id,man_price,dec_price')->order('man_price asc')->find();
                                            if($coupons){
                                                $couinfos = Db::name('member_coupon')->alias('a')->field('a.*,b.man_price,b.dec_price')->join('sp_coupon b','a.coupon_id = b.id','INNER')->where('a.user_id',$user_id)->where('a.is_sy',0)->where('a.shop_id',$kc)->where('b.start_time','elt',time())->where('b.end_time','gt',time()-3600*24)->where('b.onsale',1)->where('b.man_price','elt',$xiaoji)->order('b.man_price desc')->find();
                            
                                                if($couinfos){
                                                    $hqgoodsinfos[$kc]['youhui_price']+=$couinfos['dec_price'];
                                                    $hqgoodsinfos[$kc]['coupon_str'] = '满'.$couinfos['man_price'].'减'.$couinfos['dec_price'].'   已优惠'.$couinfos['dec_price'];
                                                }
                                            }
                            
                                            $promotionres = Db::name('promotion')->where('shop_id',$kc)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,start_time,end_time,info_id')->select();
                                            if($promotionres){
                                                foreach ($promotionres as $prv){
                                                    $prom_typeres = Db::name('prom_type')->where('prom_id',$prv['id'])->select();
                                                    if($prom_typeres){
                                                        $promo_num = 0;
                                                        $cuxiaogoods = array();
                                                        $prohdsort = array();
                                                        
                                                        foreach ($vc['goodres'] as $vp){
                                                            if(strpos(','.$prv['info_id'].',',','.$vp['id'].',') !== false){
                                                                $promo_num+=$vp['goods_num'];
                                                                $cuxiaogoods[] = array('id'=>$vp['id'],'shop_price'=>$vp['shop_price'],'goods_num'=>$vp['goods_num']);
                                                            }
                                                        }
                                                        
                                                        if($promo_num){
                                                            foreach ($prom_typeres as $krp => $vrp){
                                                                if($promo_num && $promo_num >= $vrp['man_num']){
                                                                    $prohdsort[] = $vrp;
                                                                }
                                                            }
                                                            
                                                            if($prohdsort){
                                                                $prohdsort = arraySort($prohdsort, 'man_num');
                                                                $promhdinfo = $prohdsort[0];
                                                                $cxcd_price = 0;
                                                                
                                                                $zhekou = $promhdinfo['discount']/100;
                                                                foreach ($cuxiaogoods as $cx){
                                                                    $zhekouprice = sprintf("%.2f", $cx['shop_price']*$zhekou);
                                                                    $youhui_price = ($cx['shop_price']-$zhekouprice)*$cx['goods_num'];
                                                                    $hqgoodsinfos[$kc]['youhui_price']+=sprintf("%.2f", $youhui_price);
                                                                    $cxcd_price+=sprintf("%.2f", $youhui_price);
                                                                }
                                                                
                                                                $cxcd_price = sprintf("%.2f",$cxcd_price);
                                                                $zhe = $promhdinfo['discount']/10;
                                                                $hqgoodsinfos[$kc]['cxhuodong'][] = '部分商品满'.$promhdinfo['man_num'].'件'.$zhe.'折   已优惠'.$cxcd_price;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                            
                            
                                            $hqgoodsinfos[$kc]['youhui_price'] = sprintf("%.2f",$hqgoodsinfos[$kc]['youhui_price']);
                            
                                            $hqgoodsinfos[$kc]['shopgoods_num'] = $shopgoods_num;
                            
                                            $hqgoodsinfos[$kc]['xiaoji_price'] = sprintf("%.2f",$xiaoji-$hqgoodsinfos[$kc]['youhui_price']);
                            
                                            //邮费
                                            $baoyou = 1;
                                            $hqgoodsinfos[$kc]['freight_str'] = '普通配送 快递免邮';
                                            foreach ($vc['goodres'] as $vp){
                                                if($vp['is_free'] == 0){
                                                    $baoyou = 0;
                                                    break;
                                                }
                                            }
                            
                                            if($baoyou == 0){
                                                $shopinfos = Db::name('shops')->where('id',$kc)->field('freight,reduce')->find();
                                                $hqgoodsinfos[$kc]['freight_str'] = '普通配送 运费'.$shopinfos['freight'].'订单满'.$shopinfos['reduce'].'免运费';
                                                
                                                if($hqgoodsinfos[$kc]['xiaoji_price'] < $shopinfos['reduce']){
                                                    $hqgoodsinfos[$kc]['freight'] = $shopinfos['freight'];
                                                    $hqgoodsinfos[$kc]['xiaoji_price'] = sprintf("%.2f",$hqgoodsinfos[$kc]['xiaoji_price']+$shopinfos['freight']);
                                                }
                                            }
                            
                                            $zong_num+=$hqgoodsinfos[$kc]['shopgoods_num'];
                            
                                            $zsprice+=$hqgoodsinfos[$kc]['xiaoji_price'];
                                        }
                            
                                        $hqgoodsinfos = array_values($hqgoodsinfos);
                            
                                        $zsprice = sprintf("%.2f", $zsprice);
                            
                                        $dizis = Db::name('address')->alias('a')->field('a.id,a.contacts,a.phone,a.address,b.pro_name,c.city_name,d.area_name')->join('sp_province b','a.pro_id = b.id','LEFT')->join('sp_city c','a.city_id = c.id','LEFT')->join('sp_area d','a.area_id = d.id','LEFT')->where('a.user_id',$user_id)->where('a.moren',1)->find();
                                        if(!$dizis){
                                            $dizis = '';
                                        }
                            
                                        $cart_infos = implode(',', $cart_idres);
                                        $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>array('goodinfo'=>$hqgoodsinfos,'zong_num'=>$zong_num,'zsprice'=>$zsprice,'address'=>$dizis,'wallet_price'=>$wallets['price'],'cart_idres'=>$cart_infos, 'type'=>$carts['type']));
                                    }else{
                                        $value = array('status'=>400,'mess'=>'商品信息参数错误','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'商品信息参数错误','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'购物车信息参数错误','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'购物车信息参数错误','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少购物车信息参数','data'=>array('status'=>400));
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
    
    //立即购买确认订单接口
    public function purbuy(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.goods_id') && input('post.num')){
                        if(input('post.fangshi')){
                            $goods_id = input('post.goods_id');
                            $num = input('post.num');
                            $fangshi = input('post.fangshi');
                            $assem_number = '';
                            
                            if(preg_match("/^\\+?[1-9][0-9]*$/", $num)){
                                $goods = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$goods_id)->where('a.onsale',1)->where('b.open_status',1)->find();
                                if($goods){
                                    $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.goods_id',$goods['id'])->where('b.attr_type',1)->select();
                                    if($radiores){
                                        if(input('post.goods_attr') && !is_array(input('post.goods_attr'))){
                                            $gattr = trim(input('post.goods_attr'));
                                            $gattr = str_replace('，', ',', $gattr);
                                            $gattr = rtrim($gattr,',');
                            
                                            if($gattr){
                                                $gattr = explode(',', $gattr);
                                                $gattr = array_unique($gattr);
                            
                                                if($gattr && is_array($gattr)){
                                                    $radioattr = array();
                                                    foreach ($radiores as $va){
                                                        $radioattr[$va['attr_id']][] = $va['id'];
                                                    }
                            
                                                    $gattres = array();
                            
                                                    foreach ($gattr as $ga){
                                                        if(!empty($ga)){
                                                            $goodsxs = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id',$ga)->where('a.goods_id',$goods['id'])->where('b.attr_type',1)->find();
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
                                        if(!input('post.goods_attr')){
                                            $goods_attr = '';
                                        }else{
                                            $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }
                            
                                    $ruinfo = array('id'=>$goods['id'],'shop_id'=>$goods['shop_id']);
                                    $ru_attr = $goods_attr;
                            
                                    $gongyong = new GongyongMx();
                                    $activitys = $gongyong->pdrugp($ruinfo,$ru_attr);
                            
                                    if((!$activitys) || ($activitys && $activitys['ac_type'] == 3 && $fangshi == 1)){
                                        $prores = Db::name('product')->where('goods_id',$goods['id'])->where('goods_attr',$goods_attr)->field('goods_number')->find();
                                        if($prores){
                                            $goods_number = $prores['goods_number'];
                                        }else{
                                            $goods_number = 0;
                                        }
                                    }else{
                                        if($activitys['ac_type'] == 1){
                                            if($num > $activitys['xznum']){
                                                $value = array('status'=>400,'mess'=>'商品限购'.$activitys['xznum'].'件','data'=>array('status'=>400));
                                                return json($value);
                                            }
                            
                                            $goods_number = $activitys['kucun'];
                                        }else{
                                            $prores = Db::name('product')->where('goods_id',$goods['id'])->where('goods_attr',$goods_attr)->field('goods_number')->find();
                                            if($prores){
                                                $goods_number = $prores['goods_number'];
                                            }else{
                                                $goods_number = 0;
                                            }
                                        }
                            
                                        if($num > 0 && $num <= $goods_number){
                                            if($activitys['ac_type'] == 3){
                                                $assem_type = 1;
                                                $zhuangtai = 0;
                            
                                                if(input('post.pin_number')){
                                                    $assem_number = input('post.pin_number');
                                                    $pintuans = Db::name('pintuan')->where('assem_number',$assem_number)->where('state',1)->where('pin_status','in','0,1')->where('hd_id',$activitys['id'])->find();
                                                    if($pintuans){
                                                        $order_assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('user_id',$user_id)->where('state',1)->where('tui_status',0)->find();
                                                        if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                                            if($order_assembles){
                                                                $assem_type = 3;
                                                                $zhuangtai = 1;
                                                            }else{
                                                                $assem_type = 2;
                                                            }
                                                        }elseif($pintuans['pin_status'] == 1){
                                                            if($order_assembles){
                                                                $zhuangtai = 2;
                                                            }
                                                        }
                                                    }else{
                                                        if(!empty($activitys['goods_attr'])){
                                                            $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$goods['id'])->where('goods_attr',$goods_attr)->where('shop_id',$goods['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                                        }else{
                                                            $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$goods['id'])->where('shop_id',$goods['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                                        }
                                                        if($order_assembles){
                                                            $pintuans = Db::name('pintuan')->where('id',$order_assembles['pin_id'])->where('state',1)->where('pin_status','in','0,1')->where('hd_id',$activitys['id'])->find();
                                                            if($pintuans){
                                                                if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                                                    $assem_type = 3;
                                                                    $zhuangtai = 1;
                                                                }elseif($pintuans['pin_status'] == 1){
                                                                    $zhuangtai = 2;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }else{
                                                    if(!empty($activitys['goods_attr'])){
                                                        $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$goods['id'])->where('goods_attr',$goods_attr)->where('shop_id',$goods['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                                    }else{
                                                        $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$goods['id'])->where('shop_id',$goods['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                                    }
                                                    if($order_assembles){
                                                        $pintuans = Db::name('pintuan')->where('id',$order_assembles['pin_id'])->where('state',1)->where('pin_status','in','0,1')->where('hd_id',$activitys['id'])->find();
                                                        if($pintuans){
                                                            if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                                                $assem_type = 3;
                                                                $zhuangtai = 1;
                                                            }elseif($pintuans['pin_status'] == 1){
                                                                $zhuangtai = 2;
                                                            }
                                                        }
                                                    }
                                                }
                            
                                                if($assem_type == 3){
                                                    $value = array('status'=>400,'mess'=>'您已参与商品拼团，下单失败','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                                        }
                                    }
                            
                                    if($num <= 0 || $num > $goods_number){
                                        //$value = array('status'=>400,'mess'=>$goods['goods_name'].'库存不足或您已经下过订单，请前往订单中心完成支付','data'=>array('status'=>400));
                                        $value = array('status'=>400,'mess'=>$goods['goods_name'].'库存不足','data'=>array('status'=>400));
                                        return json($value);
                                    }
                            
                                    $purchs = Db::name('purch')->where('user_id',$user_id)->find();
                                    if($purchs){
                                        $count = Db::name('purch')->where('id',$purchs['id'])->where('user_id',$user_id)->update(array('goods_id'=>$goods['id'],'goods_attr'=>$goods_attr,'num'=>$num,'shop_id'=>$goods['shop_id']));
                                        if($count !== false){
                                            $value = array('status'=>200,'mess'=>'操作成功','data'=>array('pur_id'=>$purchs['id'],'fangshi'=>$fangshi,'pin_number'=>$assem_number));
                                        }else{
                                            $value = array('status'=>400,'mess'=>'操作失败，请重试','data'=>array('status'=>400));
                                        }
                                    }else{
                                        $pur_id = Db::name('purch')->insertGetId(array('goods_id'=>$goods['id'],'goods_attr'=>$goods_attr,'num'=>$num,'user_id'=>$user_id,'shop_id'=>$goods['shop_id']));
                                        if($pur_id){
                                            $value = array('status'=>200,'mess'=>'操作成功','data'=>array('pur_id'=>$pur_id,'fangshi'=>$fangshi,'pin_number'=>$assem_number));
                                        }else{
                                            $value = array('status'=>400,'mess'=>'操作失败，请重试','data'=>array('status'=>400));
                                        }
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'商品已下架或不存在','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'商品数量参数格式错误','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少购买方式参数','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少购买商品参数','data'=>array('status'=>400));
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
    
    //立即购买确认订单详情接口
    public function pursure(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.pur_id')){
                        if(input('post.fangshi')){
                            $webconfig = $this->webconfig;
                            
                            $wallets = Db::name('wallet')->where('user_id',$user_id)->find();
                            $pur_id = input('post.pur_id');
                            $fangshi = input('post.fangshi');
                            $assem_number = '';
                            
                            $purchs = Db::name('purch')->alias('a')->field('a.*,b.goods_name,b.thumb_url,b.shop_price,b.is_free,c.shop_name,w.zkj w_zkj, b.type,b.zkj')
                                ->join('wallet w', 'w.user_id = a.user_id', 'LEFT')
                                ->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.id',$pur_id)->where('a.user_id',$user_id)->where('b.onsale',1)->where('c.open_status',1)->find();
                            if($purchs){
                                $goodinfos = array();
                                if($webconfig['cos_file'] == '开启'){
                                    $domain = config('tengxunyun')['cos_domain'];
                                }else{
                                    $domain = $webconfig['weburl'];
                                }
                                // $purchs['thumb_url'] = $domain.'/'.$purchs['thumb_url'];
                            
                                $ruinfo = array('id'=>$purchs['goods_id'],'shop_id'=>$purchs['shop_id']);
                                $ru_attr = $purchs['goods_attr'];
                            
                                $gongyong = new GongyongMx();
                                $activitys = $gongyong->pdrugp($ruinfo,$ru_attr);
                                if((!$activitys) || ($activitys && $activitys['ac_type'] == 3 && $fangshi == 1)){
                                    $prores = Db::name('product')->where('goods_id',$purchs['goods_id'])->where('goods_attr',$purchs['goods_attr'])->field('goods_number')->find();
                                    if($prores){
                                        $goods_number = $prores['goods_number'];
                                    }else{
                                        $goods_number = 0;
                                    }
                            
                                    if($purchs['num'] > 0 && $purchs['num'] <= $goods_number){
                                        if(!empty($purchs['goods_attr'])){
                                            $gasxres = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$purchs['goods_attr'])->where('a.goods_id',$purchs['goods_id'])->where('b.attr_type',1)->select();
                                            $goods_attr_str = '';
                                            if($gasxres){
                                                foreach ($gasxres as $k => $v){
                                                    $purchs['shop_price']+=$v['attr_price'];
                                                    if($k == 0){
                                                        $goods_attr_str = $v['attr_name'].':'.$v['attr_value'];
                                                    }else{
                                                        $goods_attr_str = $goods_attr_str.' '.$v['attr_name'].':'.$v['attr_value'];
                                                    }
                                                }

                                                $purchs['shop_price']=sprintf("%.2f", $purchs['shop_price']);                                                
                                            }
                                        }else{
                                            $goods_attr_str = '';
                                        }
                                        // 会员折扣
                                        // $rateService = new RateService($user_id);
                                        // if($rateService->isRate($purchs['shop_id'])){
                                        //     $rate = $rateService->findUserRate();
                                        //     $purchs['shop_price'] = $purchs['shop_price']*$rate;
                                        // }
                                       
                                        $goodinfos = array('id'=>$purchs['goods_id'],'goods_name'=>$purchs['goods_name'],'thumb_url'=>$purchs['thumb_url'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$purchs['shop_price'],'goods_num'=>$purchs['num'],'is_free'=>$purchs['is_free'],'shop_id'=>$purchs['shop_id'],'shop_name'=>$purchs['shop_name'],'type'=>$purchs['type']);
                                    }else{
                                        $value = array('status'=>400,'mess'=>$purchs['goods_name'].'库存不足','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }else{
                                    if($activitys['ac_type'] == 1){
                                        $goods_number = $activitys['kucun'];
                                    }else{
                                        $prores = Db::name('product')->where('goods_id',$purchs['goods_id'])->where('goods_attr',$purchs['goods_attr'])->field('goods_number')->find();
                                        if($prores){
                                            $goods_number = $prores['goods_number'];
                                        }else{
                                            $goods_number = 0;
                                        }
                                    }
                            
                                    if($purchs['num'] > 0 && $purchs['num'] <= $goods_number){
                                        if(!empty($purchs['goods_attr'])){
                                            $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$purchs['goods_attr'])->where('a.goods_id',$purchs['goods_id'])->where('b.attr_type',1)->select();
                                            $goods_attr_str = '';
                                            if($gares){
                                                foreach ($gares as $key => $val){
                                                    if($key == 0){
                                                        $goods_attr_str = $val['attr_name'].':'.$val['attr_value'];
                                                    }else{
                                                        $goods_attr_str = $goods_attr_str.' '.$val['attr_name'].':'.$val['attr_value'];
                                                    }
                                                }
                                            }
                                        }else{
                                            $gares = array();
                                            $goods_attr_str = '';
                                        }
                            
                                        $purchs['shop_price'] = $activitys['price'];
                            
                                        if($activitys['ac_type'] == 3){
                                            $assem_type = 1;
                                            $zhuangtai = 0;
                            
                                            if(input('post.pin_number')){
                                                $assem_number = input('post.pin_number');
                                                $pintuans = Db::name('pintuan')->where('assem_number',$assem_number)->where('state',1)->where('pin_status','in','0,1')->where('hd_id',$activitys['id'])->find();
                                                if($pintuans){
                                                    $order_assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('user_id',$user_id)->where('state',1)->where('tui_status',0)->find();
                                                    if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                                        if($order_assembles){
                                                            $assem_type = 3;
                                                            $zhuangtai = 1;
                                                        }else{
                                                            $assem_type = 2;
                                                        }
                                                    }elseif($pintuans['pin_status'] == 1){
                                                        if($order_assembles){
                                                            $zhuangtai = 2;
                                                        }
                                                    }
                                                }else{
                                                    if(!empty($activitys['goods_attr'])){
                                                        $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$purchs['goods_id'])->where('goods_attr',$purchs['goods_attr'])->where('shop_id',$purchs['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                                    }else{
                                                        $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$purchs['goods_id'])->where('shop_id',$purchs['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                                    }
                                                    if($order_assembles){
                                                        $pintuans = Db::name('pintuan')->where('id',$order_assembles['pin_id'])->where('state',1)->where('pin_status','in','0,1')->where('hd_id',$activitys['id'])->find();
                                                        if($pintuans){
                                                            if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                                                $assem_type = 3;
                                                                $zhuangtai = 1;
                                                            }elseif($pintuans['pin_status'] == 1){
                                                                $zhuangtai = 2;
                                                            }
                                                        }
                                                    }
                                                }
                                            }else{
                                                if(!empty($activitys['goods_attr'])){
                                                    $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$purchs['goods_id'])->where('goods_attr',$purchs['goods_attr'])->where('shop_id',$purchs['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                                }else{
                                                    $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$purchs['goods_id'])->where('shop_id',$purchs['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                                }
                                                if($order_assembles){
                                                    $pintuans = Db::name('pintuan')->where('id',$order_assembles['pin_id'])->where('state',1)->where('pin_status','in','0,1')->where('hd_id',$activitys['id'])->find();
                                                    if($pintuans){
                                                        if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                                            $assem_type = 3;
                                                            $zhuangtai = 1;
                                                        }elseif($pintuans['pin_status'] == 1){
                                                            $zhuangtai = 2;
                                                        }
                                                    }
                                                }
                                            }
                            
                                            if($assem_type == 3){
                                                $value = array('status'=>400,'mess'=>'您已参与商品拼团，下单失败','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                        }
                            
                                        $goodinfos = array('id'=>$purchs['goods_id'],'goods_name'=>$purchs['goods_name'],'thumb_url'=>$purchs['thumb_url'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$purchs['shop_price'],'goods_num'=>$purchs['num'],'is_free'=>$purchs['is_free'],'shop_id'=>$purchs['shop_id'],'shop_name'=>$purchs['shop_name'],'type'=>$purchs['type']);
                                    }else{
                                        $value = array('status'=>400,'mess'=>$purchs['goods_name'].'库存不足','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                                
                                $ordouts = Db::name('order_timeout')->where('id',1)->find();
                            
                                if($activitys && $activitys['ac_type'] == 3 && $fangshi == 2){
                                    $assem_zt = array('is_show'=>1,'time_out'=>$ordouts['assem_timeout']);
                                }else{
                                    $assem_zt = array('is_show'=>0,'time_out'=>'');
                                }
                            
                                if($goodinfos){
                                    $goodinfos['coupon_str'] = '';
                                    $goodinfos['cxhuodong'] = array();
                                    $goodinfos['youhui_price'] = 0;
                                    $goodinfos['freight'] = 0;
                                    $goodinfos['xiaoji_price'] = 0;

                                    $xiaoji = sprintf("%.2f", $goodinfos['shop_price']*$goodinfos['goods_num']);
                            
                                    if((!$activitys) || (in_array($activitys['ac_type'], array(1,2))) || ($activitys['ac_type'] == 3 && $fangshi == 1)){
                                        $coupons = Db::name('coupon')->where('shop_id',$goodinfos['shop_id'])->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id,man_price,dec_price')->order('man_price asc')->find();
                                        if($coupons){
                                            $couinfos = Db::name('member_coupon')->alias('a')->field('a.*,b.man_price,b.dec_price')->join('sp_coupon b','a.coupon_id = b.id','INNER')->where('a.user_id',$user_id)->where('a.is_sy',0)->where('a.shop_id',$goodinfos['shop_id'])->where('b.start_time','elt',time())->where('b.end_time','gt',time()-3600*24)->where('b.onsale',1)->where('b.man_price','elt',$xiaoji)->order('b.man_price desc')->find();
                            
                                            if($couinfos){
                                                $goodinfos['youhui_price']+=$couinfos['dec_price'];
                                                $goodinfos['coupon_str'] = '满'.$couinfos['man_price'].'减'.$couinfos['dec_price'].'  已优惠'.$couinfos['dec_price'];
                                            }
                                        }
                            
                                        $promotionres = Db::name('promotion')->where('shop_id',$goodinfos['shop_id'])->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,start_time,end_time,info_id')->select();
                                        if($promotionres){
                                            foreach ($promotionres as $prv){
                                                $prom_typeres = Db::name('prom_type')->where('prom_id',$prv['id'])->select();
                                                if($prom_typeres){
                                                    $prohdsort = array();
                            
                                                    if(strpos(','.$prv['info_id'].',',','.$goodinfos['id'].',') !== false){
                                                        foreach ($prom_typeres as $krp => $vrp){
                                                            if($goodinfos['goods_num'] && $goodinfos['goods_num'] >= $vrp['man_num']){
                                                                $prohdsort[] = $vrp;
                                                            }
                                                        }
                            
                                                        if($prohdsort){
                                                            $prohdsort = arraySort($prohdsort, 'man_num');
                                                            $promhdinfo = $prohdsort[0];
                            
                                                            $zhekou = $promhdinfo['discount']/100;
                                                            $zhekouprice = sprintf("%.2f", $goodinfos['shop_price']*$zhekou);
                                                            $youhui_price = ($goodinfos['shop_price']-$zhekouprice)*$goodinfos['goods_num'];
                                                            $youhui_price = sprintf("%.2f", $youhui_price);
                                                            $goodinfos['youhui_price']+=$youhui_price;
                            
                                                            $zhe = $promhdinfo['discount']/10;
                                                            $goodinfos['cxhuodong'][] = '部分商品满'.$promhdinfo['man_num'].'件'.$zhe.'折  已优惠'.$youhui_price;
                                                        }
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                            
                                    $goodinfos['youhui_price'] = sprintf("%.2f",$goodinfos['youhui_price']);
                            
                                    $goodinfos['xiaoji_price'] = sprintf("%.2f",$xiaoji-$goodinfos['youhui_price']);
                            
                                    //邮费
                                    $baoyou = 1;
                                    $goodinfos['freight_str'] = '普通配送 快递免邮';
                            
                                    if($goodinfos['is_free'] == 0){
                                        $baoyou = 0;
                                    }
                            
                                    if($baoyou == 0){
                                        $shopinfos = Db::name('shops')->where('id',$goodinfos['shop_id'])->field('freight,reduce')->find();
                                        $goodinfos['freight_str'] = '普通配送 运费'.$shopinfos['freight'].'订单满'.$shopinfos['reduce'].'免运费';
                                        if($goodinfos['xiaoji_price'] < $shopinfos['reduce']){
                                            $goodinfos['freight'] = $shopinfos['freight'];
                                            $goodinfos['xiaoji_price'] = sprintf("%.2f",$goodinfos['xiaoji_price']+$shopinfos['freight']);
                                        }
                                    }
                            
                                    $zong_num = $goodinfos['goods_num'];
                                    $purchs['zkj'] = $purchs['zkj'] * $zong_num;

                                    $zsprice = $goodinfos['xiaoji_price'];
                                    if(in_array($purchs['type'], [2,3]) && $purchs['w_zkj']>=$purchs['zkj']){
                                        $zsprice = $goodinfos['xiaoji_price'] - $purchs['zkj'];
                                        $zsprice = round($zsprice, 2);
                                        $zkj = $purchs['zkj'];
                                    }
                            
                                    $goodinfores = array();
                                    $hqgoodsinfos = array();
                            
                                    $goodinfores[] = $goodinfos;
                            
                                    foreach ($goodinfores as $kd => $vd){
                                        $hqgoodsinfos[$vd['shop_id']]['goodres'][] = array('id'=>$vd['id'],'goods_name'=>$vd['goods_name'],'thumb_url'=>$vd['thumb_url'],'goods_attr_str'=>$vd['goods_attr_str'],'shop_price'=>$vd['shop_price'],'goods_num'=>$vd['goods_num'],'is_free'=>$vd['is_free'],'shop_id'=>$vd['shop_id'],'shop_name'=>$vd['shop_name']);
                                        $hqgoodsinfos[$vd['shop_id']]['coupon_str'] = $vd['coupon_str'];
                                        $hqgoodsinfos[$vd['shop_id']]['cxhuodong'] = $vd['cxhuodong'];
                                        $hqgoodsinfos[$vd['shop_id']]['youhui_price'] = $vd['youhui_price'];
                                        $hqgoodsinfos[$vd['shop_id']]['freight'] = $vd['freight'];
                                        $hqgoodsinfos[$vd['shop_id']]['shopgoods_num'] = $vd['goods_num'];
                                        $hqgoodsinfos[$vd['shop_id']]['xiaoji_price'] = $vd['xiaoji_price'];
                                    }
                            
                                    $hqgoodsinfos = array_values($hqgoodsinfos);
                            
                                    $dizis = Db::name('address')->alias('a')->field('a.id,a.contacts,a.phone,a.address,b.pro_name,c.city_name,d.area_name')->join('sp_province b','a.pro_id = b.id','LEFT')->join('sp_city c','a.city_id = c.id','LEFT')->join('sp_area d','a.area_id = d.id','LEFT')->where('a.user_id',$user_id)->where('a.moren',1)->find();
                                    if(!$dizis){
                                        $dizis = '';
                                    }
                            
                                    $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>array('goodinfo'=>$hqgoodsinfos,'zong_num'=>$zong_num,'zsprice'=>$zsprice,'address'=>$dizis,'wallet_price'=>$wallets['price'],'pur_id'=>$pur_id,'assem_zt'=>$assem_zt,'fangshi'=>$fangshi,'pin_number'=>$assem_number, 'zkj'=>$zkj, 'type'=>$goodinfos['type']));
                                }else{
                                    $value = array('status'=>400,'mess'=>'找不到相关商品信息','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关商品信息','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少购买方式参数','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少购买商品参数','data'=>array('status'=>400));
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
    
    //判断支付密码设置与否
    public function pdpaypwd(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $zhifupwd = Db::name('member')->where('id',$user_id)->value('paypwd');
                    if($zhifupwd){
                        $zhifupwd = 1;
                    }else{
                        $zhifupwd = 0;
                    }
                    $value = array('status'=>200,'mess'=>'获取支付密码设置与否状态信息成功','data'=>array('zhifupwd'=>$zhifupwd));
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
    
    //购物车购买创建订单接口
    public function addorder(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.cart_idres') && !is_array(input('post.cart_idres'))){
                        if(input('post.dz_id')){
                            if(input('post.zf_type') && in_array(input('post.zf_type'),array(1,2,3,4,5))){
                                $zf_type = input('post.zf_type');
                                
                                $dizis = Db::name('address')->alias('a')->field('a.*,b.pro_name,c.city_name,d.area_name')->join('sp_province b','a.pro_id = b.id','LEFT')->join('sp_city c','a.city_id = c.id','LEFT')->join('sp_area d','a.area_id = d.id','LEFT')->where('a.id',input('post.dz_id'))->where('a.user_id',$user_id)->find();
                                if($dizis){
                                    $cart_idres = trim(input('post.cart_idres'));
                                    $cart_idres = str_replace('，', ',', $cart_idres);
                                    $cart_idres = rtrim($cart_idres,',');
                                    
                                    if($cart_idres){
                                        $cart_idres = explode(',', $cart_idres);
                                        $cart_idres = array_unique($cart_idres);
                                        
                                        if($cart_idres && is_array($cart_idres)){
                                            $total_price = 0;
                                        
                                            $goodinfores = array();
                                            $rateService = new RateService($user_id);
                                            foreach($cart_idres as $v){
                                                if(!empty($v)){
                                                    $carts = Db::name('cart')->alias('a')->field('a.*,b.goods_name,b.thumb_url,b.shop_price,b.is_free,c.shop_name')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.id',$v)->where('a.user_id',$user_id)->where('b.onsale',1)->where('c.open_status',1)->find();
                                                    if($carts){
                                        
                                                        $ruinfo = array('id'=>$carts['goods_id'],'shop_id'=>$carts['shop_id']);
                                                        $ru_attr = $carts['goods_attr'];
                                        
                                                        $gongyong = new GongyongMx();
                                                        $activitys = $gongyong->pdrugp($ruinfo,$ru_attr);
                                        
                                                        if($activitys){
                                                            $carts['hd_type'] = $activitys['ac_type'];
                                                            $carts['hd_id'] = $activitys['id'];
                                        
                                                            if($activitys['ac_type'] == 1){
                                                                $goods_number = $activitys['kucun'];
                                                            }else{
                                                                if($activitys['ac_type'] == 3){
                                                                    $value = array('status'=>400,'mess'=>'拼团商品不支持购物车提交订单','data'=>array('status'=>400));
                                                                    return json($value);
                                                                }
                                                                
                                                                $prores = Db::name('product')->where('goods_id',$carts['goods_id'])->where('goods_attr',$carts['goods_attr'])->field('goods_number')->find();
                                                                if($prores){
                                                                    $goods_number = $prores['goods_number'];
                                                                }else{
                                                                    $goods_number = 0;
                                                                }
                                                            }
                                        
                                                            if($carts['num'] > 0 && $carts['num'] <= $goods_number){
                                                                if($carts['goods_attr']){
                                                                    $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$carts['goods_attr'])->where('a.goods_id',$carts['goods_id'])->where('b.attr_type',1)->select();
                                                                    $goods_attr_str = '';
                                                                    if($gares){
                                                                        foreach ($gares as $key => $val){
                                                                            if($key == 0){
                                                                                $goods_attr_str = $val['attr_name'].':'.$val['attr_value'];
                                                                            }else{
                                                                                $goods_attr_str = $goods_attr_str.' '.$val['attr_name'].':'.$val['attr_value'];
                                                                            }
                                                                        }
                                                                    }
                                                                }else{
                                                                    $gares = array();
                                                                    $goods_attr_str = '';
                                                                }
                                        
                                                                $carts['shop_price'] = $activitys['price'];
                                                                $goodinfores[] = array('id'=>$carts['goods_id'],'goods_name'=>$carts['goods_name'],'thumb_url'=>$carts['thumb_url'],'goods_attr_id'=>$carts['goods_attr'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$carts['shop_price'],'goods_num'=>$carts['num'],'is_free'=>$carts['is_free'],'hd_type'=>$carts['hd_type'],'hd_id'=>$carts['hd_id'],'shop_id'=>$carts['shop_id']);
                                                            }else{
                                                                $value = array('status'=>400,'mess'=>$carts['goods_name'].'库存不足','data'=>array('status'=>400));
                                                                return json($value);
                                                            }
                                                        }else{
                                                            $carts['hd_type'] = 0;
                                                            $carts['hd_id'] = 0;
                                                            
                                                            $prores = Db::name('product')->where('goods_id',$carts['goods_id'])->where('goods_attr',$carts['goods_attr'])->field('goods_number')->find();
                                                            if($prores){
                                                                $goods_number = $prores['goods_number'];
                                                            }else{
                                                                $goods_number = 0;
                                                            }
                                                            
                                                            if($carts['num'] > 0 && $carts['num'] <= $goods_number){
                                                                if($carts['goods_attr']){
                                                                    $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$carts['goods_attr'])->where('a.goods_id',$carts['goods_id'])->where('b.attr_type',1)->select();
                                                                    $goods_attr_str = '';
                                                                    if($gares){
                                                                        foreach ($gares as $key => $val){
                                                                            $carts['shop_price']+=$val['attr_price'];
                                                                            if($key == 0){
                                                                                $goods_attr_str = $val['attr_name'].':'.$val['attr_value'];
                                                                            }else{
                                                                                $goods_attr_str = $goods_attr_str.' '.$val['attr_name'].':'.$val['attr_value'];
                                                                            }
                                                                        }
                                                                        $carts['shop_price']=sprintf("%.2f", $carts['shop_price']);
                                                                    }
                                                                }else{
                                                                    $goods_attr_str = '';
                                                                }
                                                                // 会员折扣
                                                                if($rateService->isRate($carts['shop_id'])){
                                                                    $rate = $rateService->findUserRate();
                                                                    $carts['shop_price'] = $carts['shop_price']*$rate;
                                                                }
                                                                $goodinfores[] = array('id'=>$carts['goods_id'],'goods_name'=>$carts['goods_name'],'thumb_url'=>$carts['thumb_url'],'goods_attr_id'=>$carts['goods_attr'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$carts['shop_price'],'goods_num'=>$carts['num'],'is_free'=>$carts['is_free'],'hd_type'=>$carts['hd_type'],'hd_id'=>$carts['hd_id'],'shop_id'=>$carts['shop_id']);
                                                            }else{
                                                                $value = array('status'=>400,'mess'=>$carts['goods_name'].'库存不足','data'=>array('status'=>400));
                                                                return json($value);
                                                            }
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'购物车存在信息参数错误','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'购物车存在信息参数错误','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                                        
                                            $ordouts = Db::name('order_timeout')->where('id',1)->find();
                                            if($ordouts){
                                                if($goodinfores){
                                                    $hqgoodsinfos = array();
                                                
                                                    foreach ($goodinfores as $kd => $vd){
                                                        $hqgoodsinfos[$vd['shop_id']]['goodres'][] = $vd;
                                                    }
                                                
                                                    if($hqgoodsinfos){
                                                        foreach ($hqgoodsinfos as $kc => $vc){
                                                            $hqgoodsinfos[$kc]['coupon_id'] = 0;
                                                            $hqgoodsinfos[$kc]['coupon_price'] = 0;
                                                            $hqgoodsinfos[$kc]['coupon_str'] = '';
                                                            $hqgoodsinfos[$kc]['youhui_price'] = 0;
                                                            $hqgoodsinfos[$kc]['freight'] = 0;
                                                            $hqgoodsinfos[$kc]['xiaoji_price'] = 0;
                                                
                                                            $xiaoji = 0;
                                                            
                                                            foreach ($vc['goodres'] as $vp){
                                                                $xiaoji+=sprintf("%.2f", $vp['shop_price']*$vp['goods_num']);
                                                            }
                                                
                                                            $coupons = Db::name('coupon')->where('shop_id',$kc)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id,man_price,dec_price')->order('man_price asc')->find();
                                                            if($coupons){
                                                                $couinfos = Db::name('member_coupon')->alias('a')->field('a.*,b.man_price,b.dec_price')->join('sp_coupon b','a.coupon_id = b.id','INNER')->where('a.user_id',$user_id)->where('a.is_sy',0)->where('a.shop_id',$kc)->where('b.start_time','elt',time())->where('b.end_time','gt',time()-3600*24)->where('b.onsale',1)->where('b.man_price','elt',$xiaoji)->order('b.man_price desc')->find();
                                                
                                                                if($couinfos){
                                                                    $hqgoodsinfos[$kc]['coupon_id'] = $couinfos['coupon_id'];
                                                                    $hqgoodsinfos[$kc]['coupon_price'] = $couinfos['dec_price'];
                                                                    $hqgoodsinfos[$kc]['coupon_str'] = '满'.$couinfos['man_price'].'减'.$couinfos['dec_price'];
                                                                    $hqgoodsinfos[$kc]['youhui_price']+=$couinfos['dec_price'];
                                                                }
                                                            }
                                                
                                                            $promotionres = Db::name('promotion')->where('shop_id',$kc)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,start_time,end_time,info_id')->select();
                                                            $cxgoodres = array();
                                                
                                                            if($promotionres){
                                                                foreach ($promotionres as $prv){
                                                                    $prom_typeres = Db::name('prom_type')->where('prom_id',$prv['id'])->select();
                                                                    if($prom_typeres){
                                                                        $promo_num = 0;
                                                                        $cuxiaogoods = array();
                                                                        $prohdsort = array();
                                                                        $cxgds = array();
                                                
                                                                        foreach ($vc['goodres'] as $vp){
                                                                            if(strpos(','.$prv['info_id'].',',','.$vp['id'].',') !== false){
                                                                                $promo_num+=$vp['goods_num'];
                                                                                $cuxiaogoods[] = array('id'=>$vp['id'],'shop_price'=>$vp['shop_price'],'goods_num'=>$vp['goods_num']);
                                                                                $cxgds[] = $vp['id'];
                                                                            }
                                                                        }
                                                
                                                                        if($promo_num){
                                                                            foreach ($prom_typeres as $krp => $vrp){
                                                                                if($promo_num && $promo_num >= $vrp['man_num']){
                                                                                    $prohdsort[] = $vrp;
                                                                                }
                                                                            }
                                                
                                                                            if($prohdsort){
                                                                                $prohdsort = arraySort($prohdsort, 'man_num');
                                                                                $promhdinfo = $prohdsort[0];
                                                
                                                                                $zhekou = $promhdinfo['discount']/100;
                                                                                foreach ($cuxiaogoods as $cx){
                                                                                    $zhekouprice = sprintf("%.2f", $cx['shop_price']*$zhekou);
                                                                                    $youhui_price = ($cx['shop_price']-$zhekouprice)*$cx['goods_num'];
                                                                                    $hqgoodsinfos[$kc]['youhui_price']+=sprintf("%.2f", $youhui_price);
                                                                                }
                                                
                                                                                $cxgoodres[] = array('promo_id'=>$prv['id'],'man_num'=>$promhdinfo['man_num'],'discount'=>$promhdinfo['discount'],'cxgds'=>$cxgds);
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                
                                                            $hqgoodsinfos[$kc]['goods_price'] = $xiaoji;
                                                            $hqgoodsinfos[$kc]['youhui_price'] = sprintf("%.2f",$hqgoodsinfos[$kc]['youhui_price']);
                                                            $hqgoodsinfos[$kc]['xiaoji_price'] = sprintf("%.2f",$xiaoji-$hqgoodsinfos[$kc]['youhui_price']);
                                                
                                                            //邮费
                                                            $baoyou = 1;
                                                            foreach ($vc['goodres'] as $vp){
                                                                if($vp['is_free'] == 0){
                                                                    $baoyou = 0;
                                                                    break;
                                                                }
                                                            }
                                                
                                                            if($baoyou == 0){
                                                                $shopinfos = Db::name('shops')->where('id',$kc)->field('freight,reduce')->find();
                                                                if($hqgoodsinfos[$kc]['xiaoji_price'] < $shopinfos['reduce']){
                                                                    $hqgoodsinfos[$kc]['freight'] = $shopinfos['freight'];
                                                                    $hqgoodsinfos[$kc]['xiaoji_price'] = sprintf("%.2f",$hqgoodsinfos[$kc]['xiaoji_price']+$shopinfos['freight']);
                                                                }
                                                            }
                                                
                                                            $total_price+=$hqgoodsinfos[$kc]['xiaoji_price'];
                                                        }
                                                
                                                        $total_price = sprintf("%.2f", $total_price);
                                                
                                                        $order_number = 'Z'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                                        $dingdan = Db::name('order_zong')->where('order_number',$order_number)->find();
                                                        if(!$dingdan){
                                                            $datainfo = array();
                                                            $datainfo['order_number'] = $order_number;
                                                            $datainfo['total_price'] = $total_price;
                                                            $datainfo['state'] = 0;
                                                            $datainfo['zf_type'] = 0;
                                                            $datainfo['user_id'] = $user_id;
                                                            $datainfo['addtime'] = time();
                                                            $datainfo['time_out'] = 0;
                                                
                                                            // 启动事务
                                                            Db::startTrans();
                                                            try{
                                                                $zong_id = Db::name('order_zong')->insertGetId($datainfo);
                                                                if($zong_id){
                                                                    $outarr = array();
                                                
                                                                    foreach ($hqgoodsinfos as $qkey => $qval){
                                                                        $time_out = time()+$ordouts['normal_out_order']*3600;
                                                                         
                                                                        foreach ($qval['goodres'] as $cvp){
                                                                            if($cvp['hd_type'] == 1){
                                                                                $time_out = time()+$ordouts['rushactivity_out_order']*60;
                                                                                break;
                                                                            }elseif($cvp['hd_type'] == 2){
                                                                                $time_out = time()+$ordouts['group_out_order']*60;
                                                                            }
                                                                        }
                                                
                                                                        $outarr[] = $time_out;
                                                                         
                                                                        $shop_ordernum = 'D'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                                                         
                                                                        $order_id = Db::name('order')->insertGetId(array(
                                                                            'ordernumber'=>$shop_ordernum,
                                                                            'contacts'=>$dizis['contacts'],
                                                                            'telephone'=>$dizis['phone'],
                                                                            'pro_id'=>$dizis['pro_id'],
                                                                            'city_id'=>$dizis['city_id'],
                                                                            'area_id'=>$dizis['area_id'],
                                                                            'province'=>$dizis['pro_name'],
                                                                            'city'=>$dizis['city_name'],
                                                                            'area'=>$dizis['area_name'],
                                                                            'address'=>$dizis['address'],
                                                                            'dz_id'=>$dizis['id'],
                                                                            'goods_price'=>$qval['goods_price'],
                                                                            'freight'=>$qval['freight'],
                                                                            'coupon_id'=>$qval['coupon_id'],
                                                                            'coupon_price'=>$qval['coupon_price'],
                                                                            'coupon_str'=>$qval['coupon_str'],
                                                                            'youhui_price'=>$qval['youhui_price'],
                                                                            'total_price'=>$qval['xiaoji_price'],
                                                                            'state'=>0,
                                                                            'zf_type'=>0,
                                                                            'fh_status'=>0,
                                                                            'order_status'=>0,
                                                                            'user_id'=>$user_id,
                                                                            'zong_id'=>$zong_id,
                                                                            'order_type'=>1,
                                                                            'pin_type'=>0,
                                                                            'pin_id'=>0,
                                                                            'shop_id'=>$qkey,
                                                                            'addtime'=>time(),
                                                                            'time_out'=>$time_out
                                                                        ));
                                                                         
                                                                        if($qval['coupon_id']){
                                                                            Db::name('member_coupon')->where('user_id',$user_id)->where('coupon_id',$qval['coupon_id'])->where('is_sy',0)->where('shop_id',$qkey)->update(array('is_sy'=>1));
                                                                            $goodyh_price = sprintf("%.2f",$qval['goods_price']-$qval['coupon_price']);
                                                                        }
                                                                         
                                                                        foreach ($qval['goodres'] as $rkey => $rval){
                                                                            $goodzs_price = $rval['shop_price'];
                                                                            $jian_price = 0;
                                                                            $prom_id = 0;
                                                                            $prom_str = '';
                                                                             
                                                                            if($qval['coupon_id']){
                                                                                $dan_price = sprintf("%.2f",($goodyh_price/$qval['goods_price'])*$rval['shop_price']);
                                                                                $goodzs_price = $dan_price;
                                                                                $jian_price = sprintf("%.2f",$rval['shop_price']-$dan_price);
                                                                            }
                                                                             
                                                                            if(!empty($cxgoodres)){
                                                                                foreach($cxgoodres as $cxval){
                                                                                    if(in_array($rval['id'], $cxval['cxgds'])){
                                                                                        $zklv = $cxval['discount']/100;
                                                                                        $zkprice = sprintf("%.2f",$rval['shop_price']*$zklv);
                                                                                        $goodzs_price = sprintf("%.2f",$zkprice-$jian_price);
                                                                                        $prom_id = $cxval['promo_id'];
                                                                                        $zhenum = $cxval['discount']/10;
                                                                                        $prom_str = '满'.$cxval['man_num'].'件'.$zhenum.'折';
                                                                                        break;
                                                                                    }
                                                                                }
                                                                            }
                                                                             
                                                                            $orgoods_id = Db::name('order_goods')->insertGetId(array(
                                                                                'goods_id'=>$rval['id'],
                                                                                'goods_name'=>$rval['goods_name'],
                                                                                'thumb_url'=>$rval['thumb_url'],
                                                                                'goods_attr_id'=>$rval['goods_attr_id'],
                                                                                'goods_attr_str'=>$rval['goods_attr_str'],
                                                                                'real_price'=>$rval['shop_price'],
                                                                                'price'=>$goodzs_price,
                                                                                'goods_num'=>$rval['goods_num'],
                                                                                'hd_type'=>$rval['hd_type'],
                                                                                'hd_id'=>$rval['hd_id'],
                                                                                'prom_id'=>$prom_id,
                                                                                'prom_str'=>$prom_str,
                                                                                'is_free'=>$rval['is_free'],
                                                                                'shop_id'=>$qkey,
                                                                                'order_id'=>$order_id
                                                                            ));
                                                                             
                                                                            if(in_array($rval['hd_type'],array(0,2,3))){
                                                                                $prokcs = Db::name('product')->lock(true)->where('goods_id',$rval['id'])->where('goods_attr',$rval['goods_attr_id'])->find();
                                                                                if($prokcs){
                                                                                    Db::name('product')->where('goods_id',$rval['id'])->where('goods_attr',$rval['goods_attr_id'])->setDec('goods_number', $rval['goods_num']);
                                                                                }
                                                                            }elseif($rval['hd_type'] == 1){
                                                                                $hdactivitys = Db::name('rush_activity')->lock(true)->where('id',$rval['hd_id'])->find();
                                                                                if($hdactivitys){
                                                                                    Db::name('rush_activity')->where('id',$rval['hd_id'])->setDec('kucun',$rval['goods_num']);
                                                                                    Db::name('rush_activity')->where('id',$rval['hd_id'])->setInc('sold',$rval['goods_num']);
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                
                                                                    $order_time_out = min($outarr);
                                                                    Db::name('order_zong')->update(array('id'=>$zong_id,'time_out'=>$order_time_out));
                                                                    Db::name('cart')->where('id','in',$cart_idres)->where('user_id',$user_id)->delete();
                                                                }
                                                
                                                                // 提交事务
                                                                Db::commit();
                                                                $orderinfos = array('order_number'=>$order_number,'zf_type'=>$zf_type);
                                                                $value = array('status'=>200,'mess'=>'创建订单成功','data'=>$orderinfos);
                                                            } catch (\Exception $e) {
                                                                // 回滚事务
                                                                Db::rollback();
                                                                $value = array('status'=>400,'mess'=>'创建订单失败','data'=>array('status'=>400));
                                                            }
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>'创建订单失败','data'=>array('status'=>400));
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'商品信息参数错误','data'=>array('status'=>400));
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'商品信息参数错误','data'=>array('status'=>400));
                                                }
                                            }else{
                                                $value = array('status'=>400,'mess'=>'创建订单失败','data'=>array('status'=>400));
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'购物车信息参数错误','data'=>array('status'=>400));
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'购物车信息参数错误','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'地址信息错误','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'支付方式参数错误','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少地址信息','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少购物车信息参数','data'=>array('status'=>400));
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
    
    
    //立即购买创建订单接口
    public function puraddorder(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.pur_id')){
                        if(input('post.fangshi') && in_array(input('post.fangshi'), array(1,2))){
                            if(input('post.dz_id')){
                                if(input('post.zf_type') && in_array(input('post.zf_type'), array(1,2,3,4,5,6,7,10,11))){
                                    $zf_type = input('post.zf_type');
                                    $fangshi = input('post.fangshi');
                            
                                    /*if(input('post.beizhu')){
                                     if(mb_strlen(input('post.beizhu'),'utf8') <= 100){
                                     $beizhu = input('post.beizhu');
                                     }else{
                                     $value = array('status'=>400,'mess'=>'备注信息在100个字符内','data'=>array('status'=>400));
                                     return json($value);
                                     }
                                     }else{
                                     $beizhu = '';
                                    }*/
                            
                                    $dizis = Db::name('address')->alias('a')->field('a.*,b.pro_name,c.city_name,d.area_name')->join('sp_province b','a.pro_id = b.id','LEFT')->join('sp_city c','a.city_id = c.id','LEFT')->join('sp_area d','a.area_id = d.id','LEFT')->where('a.id',input('post.dz_id'))->where('a.user_id',$user_id)->find();
                                    if($dizis){
                                        $pur_id = input('post.pur_id');
                            
                                        $purchs = Db::name('purch')->alias('a')->field('a.*,b.goods_name,b.thumb_url,b.shop_price,b.is_free,c.shop_name,b.type,b.zkj,w.zkj w_zkj')->join('sp_goods b','a.goods_id = b.id','INNER')
                                            ->join('wallet w', 'w.user_id = a.user_id', 'LEFT')
                                            ->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.id',$pur_id)->where('a.user_id',$user_id)->where('b.onsale',1)->where('c.open_status',1)->find();
                                        if($purchs){
                                            $total_price = 0;
                                            $order_type = 1;
                                            $pin_type = 0;
                                            $goodinfos = array();
                            
                                            $ruinfo = array('id'=>$purchs['goods_id'],'shop_id'=>$purchs['shop_id']);
                                            $ru_attr = $purchs['goods_attr'];
                            
                                            $gongyong = new GongyongMx();
                                            $activitys = $gongyong->pdrugp($ruinfo,$ru_attr);
                            
                                            if((!$activitys) || ($activitys && $activitys['ac_type'] == 3 && $fangshi == 1)){
                                                $purchs['hd_type'] = 0;
                                                $purchs['hd_id'] = 0;
                            
                                                $prores = Db::name('product')->where('goods_id',$purchs['goods_id'])->where('goods_attr',$purchs['goods_attr'])->field('goods_number')->find();
                                                if($prores){
                                                    $goods_number = $prores['goods_number'];
                                                }else{
                                                    $goods_number = 0;
                                                }
                            
                                                if($purchs['num'] > 0 && $purchs['num'] <= $goods_number){
                                                    if($purchs['goods_attr']){
                                                        $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$purchs['goods_attr'])->where('a.goods_id',$purchs['goods_id'])->where('b.attr_type',1)->select();
                                                        $goods_attr_str = '';
                                                        if($gares){
                                                            foreach ($gares as $key => $val){
                                                                $purchs['shop_price']+=$val['attr_price'];
                                                                if($key == 0){
                                                                    $goods_attr_str = $val['attr_name'].':'.$val['attr_value'];
                                                                }else{
                                                                    $goods_attr_str = $goods_attr_str.' '.$val['attr_name'].':'.$val['attr_value'];
                                                                }
                                                            }
                                                            $purchs['shop_price']=sprintf("%.2f", $purchs['shop_price']);
                                                        }
                                                    }else{
                                                        $goods_attr_str = '';
                                                    }

                                                    // 会员折扣
                                                    $rateService = new RateService($user_id);
                                                    if($rateService->isRate($purchs['shop_id'])){
                                                        $rate = $rateService->findUserRate();
                                                        $purchs['shop_price'] = $purchs['shop_price']*$rate;
                                                    }
                            
                                                    $goodinfos = array('id'=>$purchs['goods_id'],'goods_name'=>$purchs['goods_name'],'thumb_url'=>$purchs['thumb_url'],'goods_attr_id'=>$purchs['goods_attr'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$purchs['shop_price'],'goods_num'=>$purchs['num'],'is_free'=>$purchs['is_free'],'hd_type'=>$purchs['hd_type'],'hd_id'=>$purchs['hd_id'],'shop_id'=>$purchs['shop_id'], 'type'=>$purchs['type'], '
                                                   zkj'=>$purchs['zkj'], 'w_zkj'=>$purchs['w_zkj']);
                                                }else{
                                                    $value = array('status'=>400,'mess'=>$purchs['goods_name'].'库存不足','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }else{
                                                $purchs['hd_type'] = $activitys['ac_type'];
                                                $purchs['hd_id'] = $activitys['id'];
                            
                                                if($activitys['ac_type'] == 1){
                                                    $goods_number = $activitys['kucun'];
                                                }else{
                                                    $prores = Db::name('product')->where('goods_id',$purchs['goods_id'])->where('goods_attr',$purchs['goods_attr'])->field('goods_number')->find();
                                                    if($prores){
                                                        $goods_number = $prores['goods_number'];
                                                    }else{
                                                        $goods_number = 0;
                                                    }
                                                }
                            
                                                if($purchs['num'] > 0 && $purchs['num'] <= $goods_number){
                                                    if(!empty($purchs['goods_attr'])){
                                                        $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$purchs['goods_attr'])->where('a.goods_id',$purchs['goods_id'])->where('b.attr_type',1)->select();
                                                        $goods_attr_str = '';
                                                        if($gares){
                                                            foreach ($gares as $key => $val){
                                                                if($key == 0){
                                                                    $goods_attr_str = $val['attr_name'].':'.$val['attr_value'];
                                                                }else{
                                                                    $goods_attr_str = $goods_attr_str.' '.$val['attr_name'].':'.$val['attr_value'];
                                                                }
                                                            }
                                                        }
                                                    }else{
                                                        $gares = array();
                                                        $goods_attr_str = '';
                                                    }
                            
                                                    $purchs['shop_price'] = $activitys['price'];
                            
                                                    if($activitys['ac_type'] == 3){
                                                        $assem_type = 1;
                                                        $zhuangtai = 0;
                            
                                                        if(input('post.pin_number')){
                                                            $assem_number = input('post.pin_number');
                                                            $pintuans = Db::name('pintuan')->where('assem_number',$assem_number)->where('state',1)->where('pin_status','in','0,1')->where('hd_id',$activitys['id'])->find();
                                                            if($pintuans){
                                                                $order_assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('user_id',$user_id)->where('state',1)->where('tui_status',0)->find();
                                                                if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                                                    if($order_assembles){
                                                                        $assem_type = 3;
                                                                        $zhuangtai = 1;
                                                                    }else{
                                                                        $assem_type = 2;
                                                                    }
                                                                }elseif($pintuans['pin_status'] == 1){
                                                                    if($order_assembles){
                                                                        $zhuangtai = 2;
                                                                    }
                                                                }
                                                            }else{
                                                                if(!empty($activitys['goods_attr'])){
                                                                    $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$purchs['goods_id'])->where('goods_attr',$purchs['goods_attr'])->where('shop_id',$purchs['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                                                }else{
                                                                    $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$purchs['goods_id'])->where('shop_id',$purchs['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                                                }
                                                                if($order_assembles){
                                                                    $pintuans = Db::name('pintuan')->where('id',$order_assembles['pin_id'])->where('state',1)->where('pin_status','in','0,1')->where('hd_id',$activitys['id'])->find();
                                                                    if($pintuans){
                                                                        if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                                                            $assem_type = 3;
                                                                            $zhuangtai = 1;
                                                                        }elseif($pintuans['pin_status'] == 1){
                                                                            $zhuangtai = 2;
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }else{
                                                            if(!empty($activitys['goods_attr'])){
                                                                $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$purchs['goods_id'])->where('goods_attr',$purchs['goods_attr'])->where('shop_id',$purchs['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                                            }else{
                                                                $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$purchs['goods_id'])->where('shop_id',$purchs['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                                            }
                                                            if($order_assembles){
                                                                $pintuans = Db::name('pintuan')->where('id',$order_assembles['pin_id'])->where('state',1)->where('pin_status','in','0,1')->where('hd_id',$activitys['id'])->find();
                                                                if($pintuans){
                                                                    if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                                                        $assem_type = 3;
                                                                        $zhuangtai = 1;
                                                                    }elseif($pintuans['pin_status'] == 1){
                                                                        $zhuangtai = 2;
                                                                    }
                                                                }
                                                            }
                                                        }
                            
                                                        if($assem_type == 3){
                                                            $value = array('status'=>400,'mess'=>'您已参与商品拼团，下单失败','data'=>array('status'=>400));
                                                            return json($value);
                                                        }
                                                    }
                            
                                                    $goodinfos = array('id'=>$purchs['goods_id'],'goods_name'=>$purchs['goods_name'],'thumb_url'=>$purchs['thumb_url'],'goods_attr_id'=>$purchs['goods_attr'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$purchs['shop_price'],'goods_num'=>$purchs['num'],'is_free'=>$purchs['is_free'],'hd_type'=>$purchs['hd_type'],'hd_id'=>$purchs['hd_id'],'shop_id'=>$purchs['shop_id'], 'type'=>$purchs['type'], '
                                                   zkj'=>$purchs['zkj'], 'w_zkj'=>$purchs['w_zkj']);
                                                }else{
                                                    $value = array('status'=>400,'mess'=>$purchs['goods_name'].'库存不足','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                            
                                            $ordouts = Db::name('order_timeout')->where('id',1)->find();
                                            if($ordouts){
                                                if($goodinfos){
                                                    if($goodinfos['hd_type'] == 3 && $fangshi == 2){
                                                        if($assem_type == 1){
                                                            $order_type = 2;
                                                            $pin_type = 1;
                                                        }elseif($assem_type == 2){
                                                            $order_type = 2;
                                                            $pin_type = 2;
                                                        }
                                                    }
                                                
                                                    $goodinfos['coupon_id'] = 0;
                                                    $goodinfos['coupon_price'] = 0;
                                                    $goodinfos['coupon_str'] = '';
                                                    $goodinfos['youhui_price'] = 0;
                                                    $goodinfos['freight'] = 0;
                                                    $goodinfos['xiaoji_price'] = 0;
                                                    $cxgoods = array();

                                                    $xiaoji = sprintf("%.2f", $goodinfos['shop_price']*$goodinfos['goods_num']);
                                                
                                                    if((!$activitys) || (in_array($activitys['ac_type'], array(1,2))) || ($activitys['ac_type'] == 3 && $fangshi == 1)){
                                                        $coupons = Db::name('coupon')->where('shop_id',$goodinfos['shop_id'])->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id,man_price,dec_price')->order('man_price asc')->find();
                                                        if($coupons){
                                                            $couinfos = Db::name('member_coupon')->alias('a')->field('a.*,b.man_price,b.dec_price')->join('sp_coupon b','a.coupon_id = b.id','INNER')->where('a.user_id',$user_id)->where('a.is_sy',0)->where('a.shop_id',$goodinfos['shop_id'])->where('b.start_time','elt',time())->where('b.end_time','gt',time()-3600*24)->where('b.onsale',1)->where('b.man_price','elt',$xiaoji)->order('b.man_price desc')->find();
                                                
                                                            if($couinfos){
                                                                $goodinfos['coupon_id'] = $couinfos['coupon_id'];
                                                                $goodinfos['coupon_price'] = $couinfos['dec_price'];
                                                                $goodinfos['coupon_str'] = '满'.$couinfos['man_price'].'减'.$couinfos['dec_price'];
                                                                $goodinfos['youhui_price']+=$couinfos['dec_price'];
                                                            }
                                                        }
                                                
                                                        $promotionres = Db::name('promotion')->where('shop_id',$goodinfos['shop_id'])->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,start_time,end_time,info_id')->select();
                                                
                                                        if($promotionres){
                                                            foreach ($promotionres as $prv){
                                                                $prom_typeres = Db::name('prom_type')->where('prom_id',$prv['id'])->select();
                                                                if($prom_typeres){
                                                                    $prohdsort = array();
                                                
                                                                    if(strpos(','.$prv['info_id'].',',','.$goodinfos['id'].',') !== false){
                                                                        foreach ($prom_typeres as $krp => $vrp){
                                                                            if($goodinfos['goods_num'] && $goodinfos['goods_num'] >= $vrp['man_num']){
                                                                                $prohdsort[] = $vrp;
                                                                            }
                                                                        }
                                                
                                                                        if($prohdsort){
                                                                            $prohdsort = arraySort($prohdsort, 'man_num');
                                                                            $promhdinfo = $prohdsort[0];
                                                
                                                                            $zhekou = $promhdinfo['discount']/100;
                                                                            $zhekouprice = sprintf("%.2f", $goodinfos['shop_price']*$zhekou);
                                                                            $youhui_price = ($goodinfos['shop_price']-$zhekouprice)*$goodinfos['goods_num'];
                                                                            $youhui_price = sprintf("%.2f", $youhui_price);
                                                                            $goodinfos['youhui_price']+=$youhui_price;
                                                
                                                                            $cxgoods = array('promo_id'=>$prv['id'],'man_num'=>$promhdinfo['man_num'],'discount'=>$promhdinfo['discount'],'cxgds'=>$goodinfos['id']);
                                                                        }
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                
                                                    $goodinfos['goods_price'] = $xiaoji;
                                                    $goodinfos['youhui_price'] = sprintf("%.2f",$goodinfos['youhui_price']);
                                                    $goodinfos['xiaoji_price'] = sprintf("%.2f",$xiaoji-$goodinfos['youhui_price']);
                                                    // if($goodinfos['type']==3 && $goodinfos['w_zkj']>=$purchs['zkj']){
                                                    //     $goodinfos['xiaoji_price'] = $goodinfos['xiaoji_price'] - $purchs['zkj'];
                                                    //     $goodinfos['kouchu_zkj'] = $purchs['zkj'];
                                                    // }
                                                
                                                    //邮费
                                                    $baoyou = 1;
                                                
                                                    if($goodinfos['is_free'] == 0){
                                                        $baoyou = 0;
                                                    }
                                                
                                                    if($baoyou == 0){
                                                        $shopinfos = Db::name('shops')->where('id',$goodinfos['shop_id'])->field('freight,reduce')->find();
                                                        if($goodinfos['xiaoji_price'] < $shopinfos['reduce']){
                                                            $goodinfos['freight'] = $shopinfos['freight'];
                                                            $goodinfos['xiaoji_price'] = sprintf("%.2f",$goodinfos['xiaoji_price']+$shopinfos['freight']);
                                                        }
                                                    }
                                                
                                                    $total_price = sprintf("%.2f", $goodinfos['xiaoji_price']);
                                                
                                                    $order_number = 'Z'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                                    $dingdan = Db::name('order_zong')->where('order_number',$order_number)->find();
                                                    if(!$dingdan){
                                                        $datainfo = array();
                                                        $datainfo['order_number'] = $order_number;
                                                        $datainfo['total_price'] = $total_price;
                                                        $datainfo['state'] = 0;
                                                        $datainfo['zf_type'] = 0;
                                                        $datainfo['user_id'] = $user_id;
                                                        $datainfo['addtime'] = time();
                                                        $datainfo['time_out'] = 0;
                                                
                                                        // 启动事务
                                                        Db::startTrans();
                                                        try{
                                                            $zong_id = Db::name('order_zong')->insertGetId($datainfo);
                                                            if($zong_id){
                                                                $time_out = time()+$ordouts['normal_out_order']*3600;
                                                                 
                                                                if($goodinfos['hd_type'] == 1){
                                                                    $time_out = time()+$ordouts['rushactivity_out_order']*60;
                                                                }elseif($goodinfos['hd_type'] == 2){
                                                                    $time_out = time()+$ordouts['group_out_order']*60;
                                                                }elseif($goodinfos['hd_type'] == 3){
                                                                    $time_out = time()+$ordouts['assemorder_timeout']*60;
                                                                }
                                                
                                                                $shop_ordernum = 'D'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);


                                                                $order_id = Db::name('order')->insertGetId(array(
                                                                    'ordernumber'=>$shop_ordernum,
                                                                    'contacts'=>$dizis['contacts'],
                                                                    'telephone'=>$dizis['phone'],
                                                                    // 'kouchu_zkj'=>$goodinfos['kouchu_zkj'],
                                                                    'pro_id'=>$dizis['pro_id'],
                                                                    'city_id'=>$dizis['city_id'],
                                                                    'area_id'=>$dizis['area_id'],
                                                                    'province'=>$dizis['pro_name'],
                                                                    'city'=>$dizis['city_name'],
                                                                    'area'=>$dizis['area_name'],
                                                                    'address'=>$dizis['address'],
                                                                    'dz_id'=>$dizis['id'],
                                                                    'goods_price'=>$goodinfos['goods_price'],
                                                                    'freight'=>$goodinfos['freight'],
                                                                    'coupon_id'=>$goodinfos['coupon_id'],
                                                                    'coupon_price'=>$goodinfos['coupon_price'],
                                                                    'coupon_str'=>$goodinfos['coupon_str'],
                                                                    'youhui_price'=>$goodinfos['youhui_price'],
                                                                    'total_price'=>$goodinfos['xiaoji_price'],
                                                                    'state'=>0,
                                                                    'zf_type'=>0,
                                                                    'fh_status'=>0,
                                                                    'order_status'=>0,
                                                                    'user_id'=>$user_id,
                                                                    'zong_id'=>$zong_id,
                                                                    'order_type'=>$order_type,
                                                                    'pin_type'=>$pin_type,
                                                                    'pin_id'=>0,
                                                                    'shop_id'=>$goodinfos['shop_id'],
                                                                    'addtime'=>time(),
                                                                    'time_out'=>$time_out,
                                                                    'type'=>$goodinfos['type']
                                                                ));

                                                                //------------分销信息begin--------

                                                                // $distributions = Db::name('distribution')
                                                                //     ->where('id',1)
                                                                //     ->find();
                                                                // $shops = Db::name('shops')
                                                                //     ->where('id',$goodinfos['shop_id'])
                                                                //     ->field('id,indus_id,fenxiao')
                                                                //     ->find();
                                                                // if($distributions['is_open'] == 1 && $shops['fenxiao'] == 1){
                                                                //     $levelinfos = Db::name('member')
                                                                //         ->where('id',$user_id)
                                                                //         ->field('id,one_level,two_level')
                                                                //         ->find();
                                                                //     if($levelinfos['one_level']){
                                                                //         $onefen_price = sprintf("%.2f",$goodinfos['xiaoji_price']*($distributions['one_profit']/100));
                                                                //         Db::name('order')
                                                                //             ->where('id',$order_id)
                                                                //             ->update(array('onefen_id'=>$levelinfos['one_level'],'onefen_price'=>$onefen_price));
                                                                //     }
                                                                //     if($levelinfos['two_level']){
                                                                //         $twofen_price = sprintf("%.2f",$goodinfos['xiaoji_price']*($distributions['two_profit']/100));
                                                                //         Db::name('order')
                                                                //             ->where('id',$order_id)
                                                                //             ->update(array('twofen_id'=>$levelinfos['two_level'],'twofen_price'=>$twofen_price));
                                                                //     }

                                                                // }
                                                                //------------分销信息end--------
                                                                if($goodinfos['coupon_id']){
                                                                    Db::name('member_coupon')->where('user_id',$user_id)->where('coupon_id',$goodinfos['coupon_id'])->where('is_sy',0)->where('shop_id',$goodinfos['shop_id'])->update(array('is_sy'=>1));
                                                                    $goodyh_price = sprintf("%.2f",$goodinfos['goods_price']-$goodinfos['coupon_price']);
                                                                }
                                                                 
                                                                $goodzs_price = $goodinfos['shop_price'];
                                                                $jian_price = 0;
                                                                $prom_id = 0;
                                                                $prom_str = '';
                                                                 
                                                                if($goodinfos['coupon_id']){
                                                                    $dan_price = sprintf("%.2f",($goodyh_price/$goodinfos['goods_price'])*$goodinfos['shop_price']);
                                                                    $goodzs_price = $dan_price;
                                                                    $jian_price = sprintf("%.2f",$goodinfos['shop_price']-$dan_price);
                                                                }
                                                                 
                                                                if(!empty($cxgoods)){
                                                                    if($goodinfos['id'] == $cxgoods['cxgds']){
                                                                        $zklv = $cxgoods['discount']/100;
                                                                        $zkprice = sprintf("%.2f",$goodinfos['shop_price']*$zklv);
                                                                        $goodzs_price = sprintf("%.2f",$zkprice-$jian_price);
                                                                        $prom_id = $cxgoods['promo_id'];
                                                                        $zhenum = $cxgoods['discount']/10;
                                                                        $prom_str = '满'.$cxgoods['man_num'].'件'.$zhenum.'折';
                                                                    }
                                                                }
                                                                 
                                                                $orgoods_id = Db::name('order_goods')->insertGetId(array(
                                                                    'goods_id'=>$goodinfos['id'],
                                                                    'goods_name'=>$goodinfos['goods_name'],
                                                                    'thumb_url'=>$goodinfos['thumb_url'],
                                                                    'goods_attr_id'=>$goodinfos['goods_attr_id'],
                                                                    'goods_attr_str'=>$goodinfos['goods_attr_str'],
                                                                    'real_price'=>$goodinfos['shop_price'],
                                                                    'price'=>$goodzs_price,
                                                                    'goods_num'=>$goodinfos['goods_num'],
                                                                    'hd_type'=>$goodinfos['hd_type'],
                                                                    'hd_id'=>$goodinfos['hd_id'],
                                                                    'prom_id'=>$prom_id,
                                                                    'prom_str'=>$prom_str,
                                                                    'is_free'=>$goodinfos['is_free'],
                                                                    'shop_id'=>$goodinfos['shop_id'],
                                                                    'order_id'=>$order_id
                                                                ));
                                                                 
                                                                if(in_array($goodinfos['hd_type'],array(0,2,3))){
                                                                    $prokcs = Db::name('product')->lock(true)->where('goods_id',$goodinfos['id'])->where('goods_attr',$goodinfos['goods_attr_id'])->find();
                                                                    if($prokcs){
                                                                        Db::name('product')->where('goods_id',$goodinfos['id'])->where('goods_attr',$goodinfos['goods_attr_id'])->setDec('goods_number', $goodinfos['goods_num']);
                                                                    }
                                                                }elseif($goodinfos['hd_type'] == 1){
                                                                    $hdactivitys = Db::name('rush_activity')->lock(true)->where('id',$goodinfos['hd_id'])->find();
                                                                    if($hdactivitys){
                                                                        Db::name('rush_activity')->where('id',$goodinfos['hd_id'])->setDec('kucun',$goodinfos['goods_num']);
                                                                        Db::name('rush_activity')->where('id',$goodinfos['hd_id'])->setInc('sold',$goodinfos['goods_num']);
                                                                    }
                                                                }
                                                
                                                                Db::name('order_zong')->update(array('id'=>$zong_id,'time_out'=>$time_out));
                                                                Db::name('purch')->where('id',$pur_id)->where('user_id',$user_id)->delete();
                                                
                                                                if($goodinfos['hd_type'] == 3 && $fangshi == 2){
                                                                    if($assem_type == 1 || $assem_type == 2){
                                                                        if($assem_type == 1){
                                                                            $assem_number = 'P'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                                                            $assem_timeout = time()+$ordouts['assem_timeout']*3600;
                                                                            $pin_id = Db::name('pintuan')->insertGetId(array(
                                                                                'assem_number'=>$assem_number,
                                                                                'state'=>0,
                                                                                'pin_num'=>$activitys['pin_num'],
                                                                                'tuan_num'=>0,
                                                                                'goods_id'=>$goodinfos['id'],
                                                                                'pin_status'=>0,
                                                                                'tz_id'=>$user_id,
                                                                                'hd_id'=>$goodinfos['hd_id'],
                                                                                'shop_id'=>$goodinfos['shop_id'],
                                                                                'time'=>time(),
                                                                                'timeout'=>$assem_timeout
                                                                            ));
                                                
                                                                            if($pin_id){
                                                                                Db::name('order_assemble')->insert(array(
                                                                                'pin_type'=>1,
                                                                                'goods_id'=>$goodinfos['id'],
                                                                                'goods_attr'=>$goodinfos['goods_attr_id'],
                                                                                'shop_id'=>$goodinfos['shop_id'],
                                                                                'user_id'=>$user_id,
                                                                                'hd_id'=>$goodinfos['hd_id'],
                                                                                'pin_id'=>$pin_id,
                                                                                'order_id'=>$order_id,
                                                                                'state'=>0,
                                                                                'tui_status'=>0,
                                                                                'addtime'=>time()
                                                                                ));
                                                
                                                                                Db::name('order')->update(array('id'=>$order_id,'pin_id'=>$pin_id));
                                                                            }
                                                                        }elseif($assem_type == 2){
                                                                            Db::name('order_assemble')->insert(array(
                                                                            'pin_type'=>2,
                                                                            'goods_id'=>$goodinfos['id'],
                                                                            'goods_attr'=>$goodinfos['goods_attr_id'],
                                                                            'shop_id'=>$goodinfos['shop_id'],
                                                                            'user_id'=>$user_id,
                                                                            'hd_id'=>$goodinfos['hd_id'],
                                                                            'pin_id'=>$pintuans['id'],
                                                                            'order_id'=>$order_id,
                                                                            'state'=>0,
                                                                            'tui_status'=>0,
                                                                            'addtime'=>time()
                                                                            ));
                                                
                                                                            Db::name('order')->update(array('id'=>$order_id,'pin_id'=>$pintuans['id']));
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            //直接通过商品id 去判断，如果存在购物车里的信息，则删除
                                                            $tdata = Db::name('cart')->where('goods_id',$purchs['goods_id'])->where('user_id',$user_id)->find();
                                                            if($tdata){
                                                                Db::name('cart')->where('goods_id',$purchs['goods_id'])->where('user_id',$user_id)->delete();
                                                            }
                                                            // 提交事务
                                                            Db::commit();
                                                            $orderinfos = array('order_number'=>$order_number,'zf_type'=>$zf_type);
                                                            $value = array('status'=>200,'mess'=>'创建订单成功','data'=>$orderinfos);
                                                        } catch (\Exception $e) {
                                                            // 回滚事务
                                                            Db::rollback();
                                                            $value = array('status'=>400,'mess'=>'创建订单失败'.$e->getMessage(),'data'=>array('status'=>400));
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'创建订单失败','data'=>array('status'=>400));
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'商品信息参数错误','data'=>array('status'=>400));
                                                }
                                            }else{
                                                $value = array('status'=>400,'mess'=>'创建订单失败','data'=>array('status'=>400));
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'找不到相关商品信息','data'=>array('status'=>400));
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'地址信息错误','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'支付方式参数错误','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'缺少地址信息','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少购买方式参数','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少立即购买商品参数','data'=>array('status'=>400));
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

    
    //提交支付
    public function zhifu(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $userInfo = MemberModel::findById($user_id);
                    if(input('post.order_number')){
                        if(input('post.zf_type')){
                            $order_number = input('post.order_number');
                            $zf_type = input('post.zf_type');
                            $orderinfos = Db::name('order_zong')->where('order_number',$order_number)->where('state',0)->where('user_id',$user_id)->field('id,order_number,total_price,time_out')->find();
                            if($orderinfos){
                                $orderes = Db::name('order')->where('zong_id',$orderinfos['id'])->field('id,ordernumber,state,fh_status,order_status,order_type,pin_type,pin_id,time_out,type')->select();
                                if($orderes){
                                    foreach ($orderes as $val2){
                                        $order_goods = Db::name('order_goods')->alias('og')->where('og.order_id', $val2['id'])
                                            ->field('og.goods_id, g.id, g.type, g.zkj, og.goods_num,og.id og_id')
                                            ->join('goods g', 'g.id = og.goods_id', 'INNER')->find();
                                   
                                        // if($val2['state'] != 0 || $val2['fh_status'] != 0 || $val2['order_status'] != 0){
                                        //     $value = array('status'=>400,'mess'=>'订单类型信息错误，支付失败','data'=>array('status'=>400));
                                        //     return json($value);
                                        // }
                                        
                                        $leixing = 0;
                                        $zforder_num = '';
        
                                        if(count($orderes) == 1){
                                            if($orderes[0]['order_type'] == 1){
                                                $leixing = 1;
                                            }elseif($orderes[0]['order_type'] == 2){
                                                $leixing = 2;
                                                $pinorder_id = $orderes[0]['id'];
                                                $pin_type = $orderes[0]['pin_type'];
                                                $pin_id = $orderes[0]['pin_id'];
                                            }
                                            $zforder_num = $orderes[0]['ordernumber'];
                                        }
        
                                        if($leixing == 2){
                                            if($pin_type == 1){
                                                $pintuans = Db::name('pintuan')->where('id',$pin_id)->where('tz_id',$user_id)->where('state',0)->find();
                                                if($pintuans){
                                                    $order_assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('order_id',$pinorder_id)->where('pin_type',1)->where('user_id',$user_id)->where('state',0)->where('tui_status',0)->find();
                                                    if($order_assembles){
                                                        if($pintuans['pin_status'] == 1 || $pintuans['pin_num'] == $pintuans['tuan_num']){
                                                            $value = array('status'=>400,'mess'=>'参数错误，支付失败','data'=>array('status'=>400));
                                                            return json($value);
                                                        }elseif(($pintuans['pin_status'] == 2) || ($pintuans['pin_status'] == 0 && $pintuans['timeout'] <= time())){
                                                            $value = array('status'=>400,'mess'=>'参数错误，支付失败','data'=>array('status'=>400));
                                                            return json($value);
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'参数错误，支付失败','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'参数错误，支付失败','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }elseif($pin_type == 2){
                                                $pintuans = Db::name('pintuan')->where('id',$pin_id)->where('tz_id','neq',$user_id)->where('state',1)->find();
                                                if($pintuans){
                                                    $order_assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('order_id',$pinorder_id)->where('pin_type',2)->where('user_id',$user_id)->where('state',0)->where('tui_status',0)->find();
                                                    if($order_assembles){
                                                        if($pintuans['pin_status'] == 1 || $pintuans['pin_num'] == $pintuans['tuan_num']){
                                                            $value = array('status'=>400,'mess'=>'该团已拼团成功，参团并支付失败','data'=>array('status'=>400));
                                                            return json($value);
                                                        }elseif(($pintuans['pin_status'] == 2) || ($pintuans['pin_status'] == 0 && $pintuans['timeout'] <= time())){
                                                            $value = array('status'=>400,'mess'=>'该团已拼团失败，参团并支付失败','data'=>array('status'=>400));
                                                            return json($value);
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'参数错误，支付失败','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'参数错误，支付失败','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                                        }
        
                                        $nowtime = time();
                                        $input = input();
                                        if($nowtime < $orderinfos['time_out']){
                                            $webconfig = $this->webconfig;
                                            switch($zf_type){
                                                case 1:
                                                    //获取支付宝支付配置信息返回
                                                    //获取订单号
                                                    $reoderSn = $orderinfos['order_number'];
                                                    //获取支付金额
                                                    $money = $orderinfos['total_price'];
                                                    $zkj = $order_goods['zkj'] * $order_goods['goods_num'];
                                                    if(in_array($order_goods['type'], [2,3])){
                                                        $money = $orderinfos['total_price'];
                                                    }
                                                    $wallets = Db::name('wallet')->where('user_id',$user_id)->find();
                                                    $wallet_info = $wallets;
                                                    if(in_array($order_goods['type'], [2,3]) && $wallet_info['zkj']>=$zkj){
                                                        if(isset($input['quick_pay'])){
                                                            $sdfdd = Db::name('detail')->where('de_type', 2)->where('zc_type', 501)->where('order_type', 1)->where('order_id', $order_goods['og_id'])->where('user_id', $user_id)->find();
                                                            if(!$sdfdd){
                                                                $zkj = 0;
                                                            }
                                                        }
                                                        
                                                        $money = $money - $zkj;
                                 
                                                        $input = input();

                                                        if($zkj>0 && !isset($input['quick_pay'])){
                                                            $resss = Db::name('wallet')->where('user_id',$user_id)->setDec('zkj', $zkj);
                                                            if($resss){
                                                                $detailsss = [
                                                                    'de_type'=>2,
                                                                    'zc_type'=>501,
                                                                    'before_price'=> $wallet_info['zkj'],
                                                                    'price'=>$zkj,
                                                                    'after_price'=> $wallet_info['zkj']-$zkj,
                                                                    'order_type'=>1,
                                                                    // 'order_id'=>$orderinfos['id'],
                                                                    'order_id'=>$order_goods['og_id'],
                                                                    'user_id'=>$user_id,
                                                                    'wat_id'=>$wallet_info['id'],
                                                                    'time'=>time()
                                                                ];
                                                                $this->addDetail($detailsss);
                                                            }else{
                                                                $value = array('status'=>400,'mess'=>'订单唤起支付超时，支付失败','data'=>array('status'=>400));
                                                                return json($value);
                                                            }
                                                        }
                                                    }
                                                    elseif(in_array($order_goods['type'], [2,3])){
                                                        $input = input();
                                                        if(isset($input['quick_pay'])){
                                                            $sdfdd = Db::name('detail')->where('de_type', 2)->where('zc_type', 501)->where('order_type', 1)->where('order_id', $order_goods['og_id'])->where('user_id', $user_id)->find();
                                                            if(!$sdfdd){
                                                                $zkj = 0;
                                                            }
                                                        }
                                                        else{
                                                            if($wallet_info['zkj']<$zkj){
                                                                $zkj = 0;
                                                            }
                                                        }
                                                        $money = $money - $zkj;
                                                    }
                                                    $notify_url = $webconfig['weburl']."/apicloud/AliPay/aliNotify";
                                                    $AliPayHelper = new AliPayHelper();
                                                    $data = $AliPayHelper->getPrePayOrder('商品支付',$money,$reoderSn,$notify_url);
                                                    $value = array('status'=>200,'mess'=>'获取成功成功','data'=>array('order_number'=>$reoderSn,'infos'=>$data));
                                                    //$value = array('status'=>400,'mess'=>'支付宝支付暂未开通','data'=>array('status'=>400));
                                                    return json($value);
                                                    break;
                                                case 2:
                                                    $quxiao_time = $orderinfos['time_out']-$nowtime;
                                                    if($quxiao_time > 60){
                                                        //获取订单号
                                                        $reoderSn = $orderinfos['order_number'];
                                                        //获取支付金额
                                                        $money = $orderinfos['total_price'];
        
                                                        // $wx = new Wxpay();
                                                        $wx = new MiniWxPay();
                                                         
                                                        $body = '商品支付';//支付说明
        
                                                        $out_trade_no = $reoderSn;//订单号
        
                                                        $total_fee = $money * 100;//支付金额(乘以100)
        
                                                        $time_start = $nowtime;
        
                                                        $time_expire = $orderinfos['time_out'];
        
                                                        $notify_url = $webconfig['weburl'].'/apicloud/Wxpaynotify/notify';//回调地址
        
                                                        $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url, $userInfo);//调用微信支付的方法
                                                        if($order['prepay_id']){
                                                            //判断返回参数中是否有prepay_id
                                                            $order['out_trade_no'] = $out_trade_no;
                                                            $order1 = $wx->getOrder($order);//执行二次签名返回参数
                                                            $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$orderinfos['order_number'],'infos'=>$order1));
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>$order['err_code_des'],'data'=>array('status'=>400));
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'订单唤起支付超时，支付失败','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                    break;
                                                case 4:
                                                    $quxiao_time = $orderinfos['time_out']-$nowtime;
                                                    if($quxiao_time > 60){
                                                        //获取订单号
                                                        $reoderSn = $orderinfos['order_number'];
                                                        //获取支付金额
                                                        $money = $orderinfos['total_price'];
        
                                                        $wx = new Wxpay();
                                                            
                                                        $body = '商品支付';//支付说明
        
                                                        $out_trade_no = $reoderSn;//订单号
        
                                                        $total_fee = $money * 100;//支付金额(乘以100)
        
                                                        $time_start = $nowtime;
        
                                                        $time_expire = $orderinfos['time_out'];
        
                                                        $notify_url = $webconfig['weburl'].'/apicloud/Wxpaynotify/notify';//回调地址
        
                                                        $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url, $userInfo);//调用微信支付的方法
                                                        if($order['prepay_id']){
                                                            //判断返回参数中是否有prepay_id
                                                            $order['out_trade_no'] = $out_trade_no;
                                                            $order1 = $wx->getOrder($order);//执行二次签名返回参数
                                                            $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$orderinfos['order_number'],'infos'=>$order1));
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>$order['err_code_des'],'data'=>array('status'=>400));
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'订单唤起支付超时，支付失败','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                    break;
                                                case 5:
                                                    $quxiao_time = $orderinfos['time_out']-$nowtime;
                                                    if($quxiao_time > 60){
                                                        //获取订单号
                                                        $reoderSn = $orderinfos['order_number'];
                                                        //获取支付金额
                                                        $money = $orderinfos['total_price'];
        
                                                        $wx = new ComWxPay();
                                                            
                                                        $body = '商品支付';//支付说明
        
                                                        $out_trade_no = $reoderSn;//订单号
        
                                                        $total_fee = $money * 100;//支付金额(乘以100)
        
                                                        $time_start = $nowtime;
        
                                                        $time_expire = $orderinfos['time_out'];
        
                                                        $notify_url = $webconfig['weburl'].'/apicloud/Wxpaynotify/notify';//回调地址
        
                                                        $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url, $userInfo);//调用微信支付的方法
                                                        if($order['prepay_id']){
                                                            //判断返回参数中是否有prepay_id
                                                            $order['out_trade_no'] = $out_trade_no;
                                                            $order1 = $wx->getOrder($order);//执行二次签名返回参数
                                                            $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$orderinfos['order_number'],'infos'=>$order1));
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>$order['err_code_des'],'data'=>array('status'=>400));
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'订单唤起支付超时，支付失败','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                    break;
                                                
                                                case 6:
                                                    $quxiao_time = $orderinfos['time_out']-$nowtime;
                                                    if($quxiao_time > 60){
                                                        //获取订单号
                                                        $reoderSn = $orderinfos['order_number'];
                                                        //获取支付金额
                                                        $money = $orderinfos['total_price'];
        
                                                        // $wx = new Wxpay();
                                                        $wx = new PortalMiniWxPay();
                                                            
                                                        $body = '商品支付';//支付说明
        
                                                        $out_trade_no = $reoderSn;//订单号
        
                                                        $total_fee = $money * 100;//支付金额(乘以100)
        
                                                        $time_start = $nowtime;
        
                                                        $time_expire = $orderinfos['time_out'];
        
                                                        $notify_url = $webconfig['weburl'].'/apicloud/Wxpaynotify/notify';//回调地址
        
                                                        $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url, $userInfo);//调用微信支付的方法
                                                        if($order['prepay_id']){
                                                            //判断返回参数中是否有prepay_id
                                                            $order['out_trade_no'] = $out_trade_no;
                                                            $order1 = $wx->getOrder($order);//执行二次签名返回参数
                                                            $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$orderinfos['order_number'],'infos'=>$order1));
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>$order['err_code_des'],'data'=>array('status'=>400));
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'订单唤起支付超时，支付失败','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                    break;
                                                case 10:
                                                    // 积分券
                                                    // 暂时取消支付密码
                                                    // $paypwd = Db::name('member')->where('id',$user_id)->value('paypwd');
                                                    $paypwd = true;
                                                    if($paypwd){
                                                        $pay_password = input('post.pay_password');
                                                        // if($pay_password && preg_match("/^\\d{6}$/", $pay_password)){
                                                        if(true){
                                                            // if($paypwd == md5($pay_password)){
                                                            if(true){
                                                                $wallets = Db::name('wallet')->where('user_id',$user_id)->find();
                                                                $wallet_info = $wallets;
                                                                
                                                                
                                                                if($wallets['point_ticket'] >= $orderinfos['total_price']){
                                                                    $sheng_price = $wallets['point_ticket']-$orderinfos['total_price'];
        
                                                                    // 启动事务
                                                                    Db::startTrans();
                                                                    try{
                                                                        Db::name('wallet')->update(array('point_ticket'=>$sheng_price,'id'=>$wallets['id']));
                                                                        
                                                                        $remark = "购买积分券商品";
                                                                        
                                                                        $detail = [
                                                                            'de_type'=>2,
                                                                            'zc_type'=>22,
                                                                            'remark' => $remark,
                                                                            'before_price'=> $wallet_info['point_ticket'],
                                                                            'price'=>$orderinfos['total_price'],
                                                                            'after_price'=> $wallet_info['point_ticket']-$orderinfos['total_price'],
                                                                            'order_type'=>1,
                                                                            'order_id'=>$order_goods['og_id'],
                                                                            'user_id'=>$user_id,
                                                                            'wat_id'=>$wallets['id'],
                                                                            'time'=>time()
                                                                        ];
                                                                        $this->addDetail($detail);
        
                                                                        Db::name('order_zong')->update(array('id'=>$orderinfos['id'],'state'=>1,'zf_type'=>10,'pay_time'=>time()));
        
                                                                        foreach ($orderes as $vr){
                                                                            Db::name('order')->update(array('id'=>$vr['id'],'state'=>1,'zf_type'=>10,'pay_time'=>time()));
                                                                            $goodinfos = Db::name('order_goods')->where('order_id',$vr['id'])->field('id,goods_id,goods_num,hd_type,hd_id,shop_id')->select();
        
                                                                            foreach ($goodinfos as $kd => $vd){
                                                                                $goodhds = Db::name('goods')->where('id',$vd['goods_id'])->field('id')->find();
                                                                                if($goodhds){
                                                                                    Db::name('goods')->where('id',$vd['goods_id'])->setInc('sale_num',$vd['goods_num']);
                                                                                }
                                                                                $shophds = Db::name('shops')->where('id',$vd['shop_id'])->field('id')->find();
                                                                                if($shophds){
                                                                                    Db::name('shops')->where('id',$vd['shop_id'])->setInc('sale_num',$vd['goods_num']);
                                                                                }
                                                                            }
                                                                        }
        
                                                                        if($leixing == 2){
                                                                            if($pin_type == 1){
                                                                                Db::name('pintuan')->where('id',$pintuans['id'])->update(array('state'=>1,'tuan_num'=>1));
                                                                                Db::name('order_assemble')->where('id',$order_assembles['id'])->update(array('state'=>1));
                                                                            }elseif($pin_type == 2){
                                                                                Db::name('order_assemble')->where('id',$order_assembles['id'])->update(array('state'=>1));
                                                                                Db::name('pintuan')->where('id',$pintuans['id'])->setInc('tuan_num',1);
        
                                                                                $tuannums = Db::name('pintuan')->lock(true)->where('id',$pintuans['id'])->field('pin_num,tuan_num')->find();
                                                                                if($tuannums['pin_num'] <= $tuannums['tuan_num']){
                                                                                    Db::name('pintuan')->where('id',$pintuans['id'])->update(array('pin_status'=>1,'com_time'=>time()));
                                                                                }
                                                                            }
                                                                        }
                                                                        
                                                                        // 提交事务
                                                                        Db::commit();
                                                                        try {
                                                                            (new OrderAfterLogic())->payOrderOp($zforder_num);
                                                                        } catch (\Throwable $th) {
                                                                            //throw $th;
                                                                        }
                                                                        $zfinfos = array('leixing'=>$leixing,'order_num'=>$zforder_num);
                                                                        $value = array('status'=>200,'mess'=>'支付成功','data'=>$zfinfos);
                                                                    } catch (\Exception $e) {
                                                                        // 回滚事务
                                                                        Db::rollback();
                                                                        $value = array('status'=>400,'mess'=>'钱包积分券支付失败','data'=>array('status'=>400));
                                                                    }
                                                                }else{
                                                                    $value = array('status'=>400,'mess'=>'钱包积分券不足，支付失败','data'=>array('status'=>400));
                                                                }
                                                            }else{
                                                                $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                                                            }
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'请先设置支付密码','data'=>array('status'=>400));
                                                    }
                                                    break;
                                                case 11:
                                                    // 积分信用
                                                    // 暂时取消支付密码
                                                    // $paypwd = Db::name('member')->where('id',$user_id)->value('paypwd');
                                                    $paypwd = true;
                                                    if($paypwd){
                                                        $pay_password = input('post.pay_password');
                                                        // if($pay_password && preg_match("/^\\d{6}$/", $pay_password)){
                                                        if(true){
                                                            // if($paypwd == md5($pay_password)){
                                                            if(true){
                                                                $wallets = Db::name('wallet')->where('user_id',$user_id)->find();
                                                                $wallet_info = $wallets;
                                                                
                                                                
                                                                if($wallets['point_credit'] >= $orderinfos['total_price']){
                                                                    $sheng_price = $wallets['point_credit']-$orderinfos['total_price'];
        
                                                                    // 启动事务
                                                                    Db::startTrans();
                                                                    try{
                                                                        Db::name('wallet')->update(array('point_credit'=>$sheng_price,'id'=>$wallets['id']));
                                                                        
                                                                        $remark = "购买积分信用商品";
                                                                        
                                                                        $detail = [
                                                                            'de_type'=>2,
                                                                            'zc_type'=>33,
                                                                            'remark' => $remark,
                                                                            'before_price'=> $wallet_info['point_credit'],
                                                                            'price'=>$orderinfos['total_price'],
                                                                            'after_price'=> $wallet_info['point_credit']-$orderinfos['total_price'],
                                                                            'order_type'=>1,
                                                                            'order_id'=>$order_goods['og_id'],
                                                                            'user_id'=>$user_id,
                                                                            'wat_id'=>$wallets['id'],
                                                                            'time'=>time()
                                                                        ];
                                                                        $this->addDetail($detail);
        
                                                                        Db::name('order_zong')->update(array('id'=>$orderinfos['id'],'state'=>1,'zf_type'=>11,'pay_time'=>time()));
        
                                                                        foreach ($orderes as $vr){
                                                                            Db::name('order')->update(array('id'=>$vr['id'],'state'=>1,'zf_type'=>11,'pay_time'=>time()));
                                                                            $goodinfos = Db::name('order_goods')->where('order_id',$vr['id'])->field('id,goods_id,goods_num,hd_type,hd_id,shop_id')->select();
        
                                                                            foreach ($goodinfos as $kd => $vd){
                                                                                $goodhds = Db::name('goods')->where('id',$vd['goods_id'])->field('id')->find();
                                                                                if($goodhds){
                                                                                    Db::name('goods')->where('id',$vd['goods_id'])->setInc('sale_num',$vd['goods_num']);
                                                                                }
                                                                                $shophds = Db::name('shops')->where('id',$vd['shop_id'])->field('id')->find();
                                                                                if($shophds){
                                                                                    Db::name('shops')->where('id',$vd['shop_id'])->setInc('sale_num',$vd['goods_num']);
                                                                                }
                                                                            }
                                                                        }
        
                                                                        if($leixing == 2){
                                                                            if($pin_type == 1){
                                                                                Db::name('pintuan')->where('id',$pintuans['id'])->update(array('state'=>1,'tuan_num'=>1));
                                                                                Db::name('order_assemble')->where('id',$order_assembles['id'])->update(array('state'=>1));
                                                                            }elseif($pin_type == 2){
                                                                                Db::name('order_assemble')->where('id',$order_assembles['id'])->update(array('state'=>1));
                                                                                Db::name('pintuan')->where('id',$pintuans['id'])->setInc('tuan_num',1);
        
                                                                                $tuannums = Db::name('pintuan')->lock(true)->where('id',$pintuans['id'])->field('pin_num,tuan_num')->find();
                                                                                if($tuannums['pin_num'] <= $tuannums['tuan_num']){
                                                                                    Db::name('pintuan')->where('id',$pintuans['id'])->update(array('pin_status'=>1,'com_time'=>time()));
                                                                                }
                                                                            }
                                                                        }
                                                                        
                                                                        // 提交事务
                                                                        Db::commit();
                                                                        try {
                                                                            (new OrderAfterLogic())->payOrderOp($zforder_num);
                                                                        } catch (\Throwable $th) {
                                                                            //throw $th;
                                                                        }
                                                                        $zfinfos = array('leixing'=>$leixing,'order_num'=>$zforder_num);
                                                                        $value = array('status'=>200,'mess'=>'支付成功','data'=>$zfinfos);
                                                                    } catch (\Exception $e) {
                                                                        // 回滚事务
                                                                        Db::rollback();
                                                                        $value = array('status'=>400,'mess'=>'钱包积分信用支付失败','data'=>array('status'=>400));
                                                                    }
                                                                }else{
                                                                    $value = array('status'=>400,'mess'=>'钱包积分信用不足，支付失败','data'=>array('status'=>400));
                                                                }
                                                            }else{
                                                                $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                                                            }
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'请先设置支付密码','data'=>array('status'=>400));
                                                    }
                                                    break;
                                                case 3:
                                                    // 暂时取消支付密码
                                                    // $paypwd = Db::name('member')->where('id',$user_id)->value('paypwd');
                                                    $paypwd = true;
                                                    if($paypwd){
                                                        $pay_password = input('post.pay_password');
                                                        // if($pay_password && preg_match("/^\\d{6}$/", $pay_password)){
                                                        if(true){
                                                            // if($paypwd == md5($pay_password)){
                                                            if(true){
                                                                $wallets = Db::name('wallet')->where('user_id',$user_id)->find();
                                                                $wallet_info = $wallets;
                                                                
                                                                $z_point = 0;
                                                                $cul_zkj = 0;
                                                                $order_goods['zkj'] = $order_goods['zkj'] * $order_goods['goods_num'];
                                                                if(in_array($order_goods['type'], [2,3])){
                                                                    $z_point = $orderinfos['total_price'];
                                                                }
                                                                if(in_array($order_goods['type'], [2,3]) && $wallet_info['zkj']>=$order_goods['zkj']){
                                                                    $orderinfos['total_price'] = $orderinfos['total_price'] - $order_goods['zkj'];
                                                                    
                                                                    $input = input();

                                                                    if(!isset($input['quick_pay'])){
                                                                        $cul_zkj = $order_goods['zkj'];
                                                                    }
                                                                    // $cul_zkj = $order_goods['zkj'];
                                                                }
                                                                
                                                                if($wallets['price'] >= $orderinfos['total_price']){
                                                                    $sheng_price = $wallets['price']-$orderinfos['total_price'];
                                                                    $sheng_zkj = $wallets['zkj']-$cul_zkj;
        
                                                                    $gift_point_ticket = 0;
                                                                    if($order_goods['type'] === 0){
                                                                        $gift_point_ticket = $orderinfos['total_price']*0.88;
                                                                    }
                                                                    // 启动事务
                                                                    Db::startTrans();
                                                                    try{
                                                                        Db::name('wallet')->inc('point', $z_point)->inc('point_ticket', $gift_point_ticket)->update(array('price'=>$sheng_price,'zkj'=>$sheng_zkj,'id'=>$wallets['id']));
                                                                        
                                                                        $remark = "购买商品";
                                                                        if($order_goods['type']==3){
                                                                            $remark = "购买积分商品";
                                                                        }
                                                                        if($order_goods['type']==2){
                                                                            $remark = "购买激活商品";
                                                                        }
                                                                        if($order_goods['type']==1){
                                                                            $remark = "购买解锁商品";
                                    									    Db::name('member')->where('id', $user_id)->update([
                                    									       'sale_earnings' => 0,
                                    									       'wash_amount' => 0
                                    									    ]);
                                                                        }
                                                                        
                                                                        if($order_goods['type']==2 || $order_goods['type']==3){
                                                                            $total_price = $orderinfos['total_price'];
                                    									    $count1 = Db::name('wx_card')->where('user_id', $user_id)->count();
                                    									    $count2 = Db::name('zfb_card')->where('user_id', $user_id)->count();
                                    									    $count3 = Db::name('bank_card')->where('user_id', $user_id)->count();
                                    									    if($count1 || $count2 || $count3){
                                            									$res = Db::name('member')->where('id', $user_id)->where('true_name', '<>', '')->inc('reg_enable_deposit', $total_price)->update([
                                            									    'reg_enable'=>1,
                                            									    'reg_enable_deposit_count'=>0
                                            									]);
                                            									
                                            									if(!$res){
                                            									    Db::name('member')->where('id', $user_id)->inc('reg_enable_deposit', $total_price)->inc('reg_enable_deposit_count', 1)->update();
                                            									}
                                            									else{
                                            									    // 升级
                                        									        $this->wineGoodsUpgrade($user_id);
                                            									}
                                    									    }
                                    									    else{
                                    									        Db::name('member')->where('id', $user_id)->inc('reg_enable_deposit', $total_price)->inc('reg_enable_deposit_count', 1)->update();
                                    									    }
                                                                        }
                                                                        
                                                                        if($gift_point_ticket > 0){
                                                                            $detail = [
                                                                                'de_type'=>1,
                                                                                'sr_type'=>110,
                                                                                'remark' => $remark,
                                                                                'before_price'=> $wallet_info['point_ticket'],
                                                                                'price'=>$gift_point_ticket,
                                                                                'after_price'=> $wallet_info['point_ticket']+$gift_point_ticket,
                                                                                'order_type'=>1,
                                                                                'order_id'=>$order_goods['og_id'],
                                                                                'user_id'=>$user_id,
                                                                                'wat_id'=>$wallets['id'],
                                                                                'time'=>time()
                                                                            ];
                                                                            $this->addDetail($detail);
                                                                        }
                                                                        
                                                                        $detail = [
                                                                            'de_type'=>2,
                                                                            'zc_type'=>2,
                                                                            'remark' => $remark,
                                                                            'before_price'=> $wallet_info['price'],
                                                                            'price'=>$orderinfos['total_price'],
                                                                            'after_price'=> $wallet_info['price']-$orderinfos['total_price'],
                                                                            'order_type'=>1,
                                                                            'order_id'=>$order_goods['og_id'],
                                                                            'user_id'=>$user_id,
                                                                            'wat_id'=>$wallets['id'],
                                                                            'time'=>time()
                                                                        ];
                                                                        $this->addDetail($detail);
                                                                        
                                                                        
                                                                        if($z_point > 0){
                                                                            $detail = [
                                                                                'de_type'=>1,
                                                                                'sr_type'=>500,
                                                                                'before_price'=> $wallet_info['point'],
                                                                                'price'=>$z_point,
                                                                                'after_price'=> $wallet_info['point']+$z_point,
                                                                                'order_type'=>1,
                                                                                'order_id'=>$order_goods['og_id'],
                                                                                'user_id'=>$user_id,
                                                                                'wat_id'=>$wallets['id'],
                                                                                'time'=>time()
                                                                            ];
                                                                            $this->addDetail($detail);
                                                                        }
                                                                        
                                                                        if($cul_zkj > 0){
                                                                            $detail = [
                                                                                'de_type'=>2,
                                                                                'zc_type'=>501,
                                                                                'before_price'=> $wallet_info['zkj'],
                                                                                'price'=>$cul_zkj,
                                                                                'after_price'=> $wallet_info['zkj']-$cul_zkj,
                                                                                'order_type'=>1,
                                                                                'order_id'=>$order_goods['og_id'],
                                                                                'user_id'=>$user_id,
                                                                                'wat_id'=>$wallets['id'],
                                                                                'time'=>time()
                                                                            ];
                                                                            $this->addDetail($detail);
                                                                        }
    //                                                                    Db::name('detail')->insert($detail);
        
                                                                        Db::name('order_zong')->update(array('id'=>$orderinfos['id'],'state'=>1,'zf_type'=>3,'pay_time'=>time()));
        
                                                                        foreach ($orderes as $vr){
                                                                            Db::name('order')->update(array('id'=>$vr['id'],'state'=>1,'zf_type'=>3,'pay_time'=>time()));
                                                                            $goodinfos = Db::name('order_goods')->where('order_id',$vr['id'])->field('id,goods_id,goods_num,hd_type,hd_id,shop_id')->select();
        
                                                                            foreach ($goodinfos as $kd => $vd){
                                                                                $goodhds = Db::name('goods')->where('id',$vd['goods_id'])->field('id')->find();
                                                                                if($goodhds){
                                                                                    Db::name('goods')->where('id',$vd['goods_id'])->setInc('sale_num',$vd['goods_num']);
                                                                                }
                                                                                $shophds = Db::name('shops')->where('id',$vd['shop_id'])->field('id')->find();
                                                                                if($shophds){
                                                                                    Db::name('shops')->where('id',$vd['shop_id'])->setInc('sale_num',$vd['goods_num']);
                                                                                }
                                                                            }
                                                                        }
        
                                                                        if($leixing == 2){
                                                                            if($pin_type == 1){
                                                                                Db::name('pintuan')->where('id',$pintuans['id'])->update(array('state'=>1,'tuan_num'=>1));
                                                                                Db::name('order_assemble')->where('id',$order_assembles['id'])->update(array('state'=>1));
                                                                            }elseif($pin_type == 2){
                                                                                Db::name('order_assemble')->where('id',$order_assembles['id'])->update(array('state'=>1));
                                                                                Db::name('pintuan')->where('id',$pintuans['id'])->setInc('tuan_num',1);
        
                                                                                $tuannums = Db::name('pintuan')->lock(true)->where('id',$pintuans['id'])->field('pin_num,tuan_num')->find();
                                                                                if($tuannums['pin_num'] <= $tuannums['tuan_num']){
                                                                                    Db::name('pintuan')->where('id',$pintuans['id'])->update(array('pin_status'=>1,'com_time'=>time()));
                                                                                }
                                                                            }
                                                                        }
                                                                        
                                                                        // 提交事务
                                                                        Db::commit();
                                                                        try {
                                                                            (new OrderAfterLogic())->payOrderOp($zforder_num);
                                                                        } catch (\Throwable $th) {
                                                                            //throw $th;
                                                                        }
                                                                        $zfinfos = array('leixing'=>$leixing,'order_num'=>$zforder_num);
                                                                        $value = array('status'=>200,'mess'=>'支付成功','data'=>$zfinfos);
                                                                    } catch (\Exception $e) {
                                                                        // 回滚事务
                                                                        Db::rollback();
                                                                        $value = array('status'=>400,'mess'=>'钱包余额支付失败','data'=>array('status'=>400));
                                                                    }
                                                                }else{
                                                                    $value = array('status'=>400,'mess'=>'钱包余额不足，支付失败','data'=>array('status'=>400));
                                                                }
                                                            }else{
                                                                $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                                                            }
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'请先设置支付密码','data'=>array('status'=>400));
                                                    }
                                                    break;
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'订单已过期，支付失败','data'=>array('status'=>400));
                                        }
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'找不到相关订单信息','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关类型订单'.$order_number,'data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'支付方式参数错误','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少订单号','data'=>array('status'=>400));
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

    /**
     * @function创建日行一善订单
     * @author Feifan.Chen <1057286925@qq.com>
     * @return \think\response\Json
     */
    public function createGoodOrder(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $date = input('post.date');
                    $date_arr = explode('-',$date);
                    if ($date || count($date_arr) == 3){
                        $year = date("Y");
                        $month = date("m");
                        $day = date("d");
//                        $start = mktime(0,0,0,$month,$day,$year);//当天开始时间戳
                        $end= mktime(23,59,59,$month,$day,$year);//当天结束时间戳
                        $date_timestramp = strtotime($date);
                        if ($date_timestramp > $end){
                            return json($value = array('status'=>400,'mess'=>'请选择早于今天的日期','data'=>array('status'=>400)));
                        }
                        $model = new SignSetmodel;
                        $is_sign = $model->isSign($date);
                        if (is_null($is_sign)){
                            return json($value = array('status'=>400,'mess'=>'日期格式错误','data'=>array('status'=>400)));
                        }
                        if ($is_sign === true){
                            return json($value = array('status'=>400,'mess'=>'您已签到，请勿重复签到','data'=>array('status'=>400)));
                        }
//                        $day_length = strlen($date_arr[1]);
//                        if ($day_length == 1){
//                            $new_date = $date_arr[0].'-'.'0'.$date_arr[1].'-'.$date_arr[2];
//                        }
                        $user_id = $result['user_id'];
                        $shop_ordernum = 'D'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                        $order = Db::name('good_order')->where('order_number',$shop_ordernum)->find();
                        $ordouts = Db::name('order_timeout')->where('id',1)->find();
                        if (!$order && $ordouts){
                            // 启动事务
                            Db::startTrans();
                            try{
                                $shop_ordernum = 'D'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                Db::name('good_order')->insertGetId(array(
                                    'order_number'=>$shop_ordernum,
                                    'total_price'=>1,
                                    'state'=>0,
                                    'date'  => $date,
                                    'zf_type'=>1,
                                    'time_out'  => time()+$ordouts['normal_out_order']*3600,
                                    'user_id'=>$user_id,
                                    'create_time'=>time(),
                                ));
                                // 提交事务
                                Db::commit();
                                $orderinfos = array('order_number'=>$shop_ordernum);

                                $value = array('status'=>200,'mess'=>'创建订单成功','data'=>$orderinfos);
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status'=>400,'mess'=>'创建订单失败','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status' => 400 ,'mess'=>'创建订单失败','data'=>array('status'=>400));
                        }
                    }else{
                        $value = ['status'=>400,'mess'=>'日期错误','data'=>['status'=>400]];
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

    public function goodPay(){

        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $userInfo = MemberModel::findById($user_id);
                    if(input('post.order_number')){
                        if(input('post.zf_type')){
                            $order_number = input('post.order_number');
                            $zf_type = input('post.zf_type');

                            $orderinfos = Db::name('good_order')->where('order_number',$order_number)->find();

                                if($orderinfos){
                                    if($orderinfos['state'] != 0 ){
                                        $value = array('status'=>400,'mess'=>'订单类型信息错误，支付失败','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                    $leixing = 0;
                                    $zforder_num = '';

                                    $nowtime = time();
                                    if($nowtime < $orderinfos['time_out']){
                                        $webconfig = $this->webconfig;
                                        switch($zf_type){
                                            case 2:
                                                $quxiao_time = $orderinfos['time_out']-$nowtime;
                                                if($quxiao_time > 60){
                                                    //获取订单号
                                                    $reoderSn = $orderinfos['order_number'];
                                                    //获取支付金额
                                                    $money = $orderinfos['total_price'];

                                                    // $wx = new Wxpay();
                                                    $wx = new MiniWxPay();

                                                    $body = '商品支付';//支付说明

                                                    $out_trade_no = $reoderSn;//订单号

                                                    $total_fee = $money * 100;//支付金额(乘以100)

                                                    $time_start = $nowtime;

                                                    $time_expire = $orderinfos['time_out'];

                                                    $notify_url = $webconfig['weburl'].'/apicloud/Wxnotify/notify';//回调地址

                                                    $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url, $userInfo);//调用微信支付的方法
                                                    if($order['prepay_id']){
                                                        //判断返回参数中是否有prepay_id
                                                        $order['out_trade_no'] = $out_trade_no;
                                                        $order1 = $wx->getOrder($order);//执行二次签名返回参数
                                                        $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$orderinfos['order_number'],'infos'=>$order1));
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$order['err_code_des'],'data'=>array('status'=>400));
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'订单唤起支付超时，支付失败','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                                break;
                                            case 4:
                                                $quxiao_time = $orderinfos['time_out']-$nowtime;
                                                if($quxiao_time > 60){
                                                    //获取订单号
                                                    $reoderSn = $orderinfos['order_number'];
                                                    //获取支付金额
                                                    $money = $orderinfos['total_price'];

                                                    $wx = new Wxpay();

                                                    $body = '商品支付';//支付说明

                                                    $out_trade_no = $reoderSn;//订单号

                                                    $total_fee = $money * 100;//支付金额(乘以100)

                                                    $time_start = $nowtime;

                                                    $time_expire = $orderinfos['time_out'];

                                                    $notify_url = $webconfig['weburl'].'/apicloud/Wxnotify/notify';//回调地址

                                                    $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url, $userInfo);//调用微信支付的方法
                                                    if($order['prepay_id']){
                                                        //判断返回参数中是否有prepay_id
                                                        $order['out_trade_no'] = $out_trade_no;
                                                        $order1 = $wx->getOrder($order);//执行二次签名返回参数
                                                        $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$orderinfos['order_number'],'infos'=>$order1));
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$order['err_code_des'],'data'=>array('status'=>400));
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'订单唤起支付超时，支付失败','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                                break;
                                            case 5:
                                                $quxiao_time = $orderinfos['time_out']-$nowtime;

                                                if($quxiao_time > 60){
                                                    //获取订单号
                                                    $reoderSn = $orderinfos['order_number'];
                                                    //获取支付金额
                                                    $money = $orderinfos['total_price'];

                                                    $wx = new ComWxPay();

                                                    $body = '商品支付';//支付说明

                                                    $out_trade_no = $reoderSn;//订单号

                                                    $total_fee = $money * 100;//支付金额(乘以100)

                                                    $time_start = $nowtime;

                                                    $time_expire = $orderinfos['time_out'];

                                                    $notify_url = $webconfig['weburl'].'/apicloud/Wxnotify/notify';//回调地址

                                                    $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url, $userInfo);//调用微信支付的方法

                                                    if($order['prepay_id']){
                                                        //判断返回参数中是否有prepay_id
                                                        $order['out_trade_no'] = $out_trade_no;
                                                        $order1 = $wx->getOrder($order);//执行二次签名返回参数
                                                        $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$orderinfos['order_number'],'infos'=>$order1));
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$order['err_code_des'],'data'=>array('status'=>400));
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'订单唤起支付超时，支付失败','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                                break;

                                            case 6:
                                                $quxiao_time = $orderinfos['time_out']-$nowtime;
                                                if($quxiao_time > 60){
                                                    //获取订单号
                                                    $reoderSn = $orderinfos['order_number'];
                                                    //获取支付金额
                                                    $money = $orderinfos['total_price'];

                                                    // $wx = new Wxpay();
                                                    $wx = new PortalMiniWxPay();

                                                    $body = '商品支付';//支付说明

                                                    $out_trade_no = $reoderSn;//订单号

                                                    $total_fee = $money * 100;//支付金额(乘以100)

                                                    $time_start = $nowtime;

                                                    $time_expire = $orderinfos['time_out'];

                                                    $notify_url = $webconfig['weburl'].'/apicloud/Wxnotify/notify';//回调地址

                                                    $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url, $userInfo);//调用微信支付的方法
                                                    if($order['prepay_id']){
                                                        //判断返回参数中是否有prepay_id
                                                        $order['out_trade_no'] = $out_trade_no;
                                                        $order1 = $wx->getOrder($order);//执行二次签名返回参数
                                                        $value = array('status'=>200,'mess'=>'创建订单成功','data'=>array('order_number'=>$orderinfos['order_number'],'infos'=>$order1));
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$order['err_code_des'],'data'=>array('status'=>400));
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'订单唤起支付超时，支付失败','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                                break;
                                            case 3:
                                                // 暂时取消支付密码
                                                // $paypwd = Db::name('member')->where('id',$user_id)->value('paypwd');
                                                $paypwd = true;
                                                if($paypwd){
                                                    $pay_password = input('post.pay_password');
                                                    // if($pay_password && preg_match("/^\\d{6}$/", $pay_password)){
                                                    if(true){
                                                        // if($paypwd == md5($pay_password)){
                                                        if(true){
                                                            $wallets = Db::name('wallet')->where('user_id',$user_id)->find();
                                                            $wallet_info = $wallets;
                                                            if($wallets['price'] >= $orderinfos['total_price']){
                                                                $sheng_price = $wallets['price']-$orderinfos['total_price'];

                                                                // 启动事务
                                                                Db::startTrans();
                                                                try{
                                                                    Db::name('wallet')->update(array('price'=>$sheng_price,'id'=>$wallets['id']));

                                                                    $detail = [
                                                                        'de_type'=>2,
                                                                        'zc_type'=>2,
                                                                        'before_price'=> $wallet_info['price'],
                                                                        'price'=>$orderinfos['total_price'],
                                                                        'after_price'=> $wallet_info['price']-$orderinfos['total_price'],
                                                                        'order_type'=>1,
                                                                        'order_id'=>$orderinfos['id'],
                                                                        'user_id'=>$user_id,
                                                                        'wat_id'=>$wallets['id'],
                                                                        'time'=>time()
                                                                    ];
                                                                    $this->addDetail($detail);
//                                                                    Db::name('detail')->insert($detail);

                                                                    Db::name('order_zong')->update(array('id'=>$orderinfos['id'],'state'=>1,'zf_type'=>3,'pay_time'=>time()));

                                                                    foreach ($orderes as $vr){
                                                                        Db::name('order')->update(array('id'=>$vr['id'],'state'=>1,'zf_type'=>3,'pay_time'=>time()));
                                                                        $goodinfos = Db::name('order_goods')->where('order_id',$vr['id'])->field('id,goods_id,goods_num,hd_type,hd_id,shop_id')->select();

                                                                        foreach ($goodinfos as $kd => $vd){
                                                                            $goodhds = Db::name('goods')->where('id',$vd['goods_id'])->field('id')->find();
                                                                            if($goodhds){
                                                                                Db::name('goods')->where('id',$vd['goods_id'])->setInc('sale_num',$vd['goods_num']);
                                                                            }
                                                                            $shophds = Db::name('shops')->where('id',$vd['shop_id'])->field('id')->find();
                                                                            if($shophds){
                                                                                Db::name('shops')->where('id',$vd['shop_id'])->setInc('sale_num',$vd['goods_num']);
                                                                            }
                                                                        }
                                                                    }

                                                                    if($leixing == 2){
                                                                        if($pin_type == 1){
                                                                            Db::name('pintuan')->where('id',$pintuans['id'])->update(array('state'=>1,'tuan_num'=>1));
                                                                            Db::name('order_assemble')->where('id',$order_assembles['id'])->update(array('state'=>1));
                                                                        }elseif($pin_type == 2){
                                                                            Db::name('order_assemble')->where('id',$order_assembles['id'])->update(array('state'=>1));
                                                                            Db::name('pintuan')->where('id',$pintuans['id'])->setInc('tuan_num',1);

                                                                            $tuannums = Db::name('pintuan')->lock(true)->where('id',$pintuans['id'])->field('pin_num,tuan_num')->find();
                                                                            if($tuannums['pin_num'] <= $tuannums['tuan_num']){
                                                                                Db::name('pintuan')->where('id',$pintuans['id'])->update(array('pin_status'=>1,'com_time'=>time()));
                                                                            }
                                                                        }
                                                                    }

                                                                    // 提交事务
                                                                    Db::commit();
                                                                    try {
                                                                        (new OrderAfterLogic())->payOrderOp($zforder_num);
                                                                    } catch (\Throwable $th) {
                                                                        //throw $th;
                                                                    }
                                                                    $zfinfos = array('leixing'=>$leixing,'order_num'=>$zforder_num);
                                                                    $value = array('status'=>200,'mess'=>'支付成功','data'=>$zfinfos);
                                                                } catch (\Exception $e) {
                                                                    // 回滚事务
                                                                    Db::rollback();
                                                                    $value = array('status'=>400,'mess'=>'钱包余额支付失败','data'=>array('status'=>400));
                                                                }
                                                            }else{
                                                                $value = array('status'=>400,'mess'=>'钱包余额不足，支付失败','data'=>array('status'=>400));
                                                            }
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'支付密码错误','data'=>array('status'=>400));
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'请先设置支付密码','data'=>array('status'=>400));
                                                }
                                                break;
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'订单已过期，支付失败','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'找不到相关订单信息','data'=>array('status'=>400));
                                }
                        }else{
                            $value = array('status'=>400,'mess'=>'支付方式参数错误','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少订单号','data'=>array('status'=>400));
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