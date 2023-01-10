<?php
namespace app\index\controller;
use app\index\controller\Common;
use app\index\model\Gongyong as GongyongMx;
use think\Db;

class Order extends Common{
    //购物车购买确认订单接口
    public function cartbuy(){
        if(request()->isPost()){
            $user_id = $this->user_id;
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
                                            if($carts['goods_attr']){
                                                $prores = Db::name('product')->where('goods_attr',$carts['goods_attr'])->where('goods_id',$carts['goods_id'])->field('goods_number')->find();
                                            }else{
                                                $prores = Db::name('product')->where('goods_id',$carts['goods_id'])->where('goods_attr','')->field('goods_number')->find();
                                            }

                                            if($prores){
                                                $goods_number = $prores['goods_number'];
                                            }else{
                                                $goods_number = 0;
                                            }
                                        }
                                    }else{
                                        if($carts['goods_attr']){
                                            $prores = Db::name('product')->where('goods_attr',$carts['goods_attr'])->where('goods_id',$carts['goods_id'])->field('goods_number')->find();
                                        }else{
                                            $prores = Db::name('product')->where('goods_id',$carts['goods_id'])->where('goods_attr','')->field('goods_number')->find();
                                        }

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
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);        
    }
    
    //购物车购买确认订单详情接口
    public function cartsure(){
        if(request()->isPost()){
            $user_id = $this->user_id;
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

                        foreach($cart_idres as $v){
                            if(!empty($v)){
                                $carts = Db::name('cart')->alias('a')->field('a.*,b.goods_name,b.shop_price,b.thumb_url,b.is_free,c.shop_name')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.id',$v)->where('a.user_id',$user_id)->where('b.onsale',1)->where('c.open_status',1)->find();
                                if($carts){
                                    $carts['thumb_url'] = $webconfig['weburl'].'/'.$carts['thumb_url'];

                                    $ruinfo = array('id'=>$carts['goods_id'],'shop_id'=>$carts['shop_id']);
                                    $ru_attr = $carts['goods_attr'];

                                    $gongyong = new GongyongMx();
                                    $activitys = $gongyong->pdrugp($ruinfo,$ru_attr);

                                    if($activitys){
                                        if($activitys['ac_type'] == 1){
                                            $goods_number = $activitys['kucun'];
                                        }else{
                                            if($carts['goods_attr']){
                                                $prores = Db::name('product')->where('goods_attr',$carts['goods_attr'])->where('goods_id',$carts['goods_id'])->field('goods_number')->find();
                                            }else{
                                                $prores = Db::name('product')->where('goods_id',$carts['goods_id'])->where('goods_attr','')->field('goods_number')->find();
                                            }

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

                                            //$zsdan_price = $carts['shop_price']*$carts['num'];
                                            //$zsdan_price = sprintf("%.2f", $zsdan_price);
                                            //$zsprice+=$zsdan_price;

                                            $goodinfores[] = array('id'=>$carts['goods_id'],'goods_name'=>$carts['goods_name'],'thumb_url'=>$carts['thumb_url'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$carts['shop_price'],'goods_num'=>$carts['num'],'is_free'=>$carts['is_free'],'shop_id'=>$carts['shop_id'],'shop_name'=>$carts['shop_name']);
                                        }else{
                                            $this->error($carts['goods_name'].'库存不足', $this->gourl);
                                        }
                                    }else{
                                        if($carts['goods_attr']){
                                            $prores = Db::name('product')->where('goods_attr',$carts['goods_attr'])->where('goods_id',$carts['goods_id'])->field('goods_number')->find();
                                            if($prores){
                                                $goods_number = $prores['goods_number'];
                                            }else{
                                                $goods_number = 0;
                                            }

                                            if($carts['num'] > 0 && $carts['num'] <= $goods_number){
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

                                                //$zsdan_price = $carts['shop_price']*$carts['num'];
                                                //$zsdan_price = sprintf("%.2f", $zsdan_price);
                                                //$zsprice+=$zsdan_price;

                                                $goodinfores[] = array('id'=>$carts['goods_id'],'goods_name'=>$carts['goods_name'],'thumb_url'=>$carts['thumb_url'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$carts['shop_price'],'goods_num'=>$carts['num'],'is_free'=>$carts['is_free'],'shop_id'=>$carts['shop_id'],'shop_name'=>$carts['shop_name']);
                                            }else{
                                                $this->error($carts['goods_name'].'库存不足', $this->gourl);
                                            }
                                        }else{
                                            $prores = Db::name('product')->where('goods_id',$carts['goods_id'])->where('goods_attr','')->field('goods_number')->find();
                                            if($prores){
                                                $goods_number = $prores['goods_number'];
                                            }else{
                                                $goods_number = 0;
                                            }
                                            if($carts['num'] > 0 && $carts['num'] <= $goods_number){
                                                $goods_attr_str = '';

                                                //$zsdan_price = $carts['shop_price']*$carts['num'];
                                                //$zsdan_price = sprintf("%.2f", $zsdan_price);
                                                //$zsprice+=$zsdan_price;

                                                $goodinfores[] = array('id'=>$carts['goods_id'],'goods_name'=>$carts['goods_name'],'thumb_url'=>$carts['thumb_url'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$carts['shop_price'],'goods_num'=>$carts['num'],'is_free'=>$carts['is_free'],'shop_id'=>$carts['shop_id'],'shop_name'=>$carts['shop_name']);
                                            }else{
                                                $this->error($carts['goods_name'].'库存不足', $this->gourl);
                                            }
                                        }
                                    }
                                }else{
                                    $this->error('购物车存在信息参数错误', $this->gourl);
                                }
                            }else{
                                $this->error('购物车存在信息参数错误', $this->gourl);
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
                                $pur_goods = array(
                                        'goodinfo'=>$hqgoodsinfos,
                                        'zong_num'=>$zong_num,
                                        'zsprice'=>$zsprice,
                                        'address'=>$dizis,
                                        'wallet_price'=>$wallets['price'],
                                        'cart_idres'=>$cart_infos
                                    );
                                $buy_obj = new Buy();
                                $addressList = $buy_obj->getAddress($user_id);
                                $morenAddress = $buy_obj->getAddress($user_id, 1);
                                $has_address = false;
                                if (!empty($addressList)) {
                                    $has_address = true;
                                }
                                $is_moren = false;
                                if (!empty($morenAddress)) {
                                    $is_moren = true;
                                }
                                $province = Db::name('province')->field('id,pro_name,zm')->where('checked',1)->where('pro_zs',1)->order('sort asc')->select();
                                
                                $this->assign('addressList', $addressList);
                                $this->assign('is_moren', $is_moren);
                                $this->assign('has_address', $has_address);
                                $this->assign('morenAddress', $morenAddress);
                                $this->assign('province', $province);
                                $this->assign('pur_goods', $pur_goods);
                                return $this->fetch('buy/buy_step_one');
                            }else{
                                $mess = '商品信息参数错误';
                            }
                        }else{
                            $mess = '商品信息参数错误';
                        }
                    }else{
                        $mess = '购物车信息参数错误';
                    }
                }else{
                    $mess = '购物车信息参数错误';
                }
            }else{
                $mess = '缺少购物车信息参数';
            }
                
        }else{
            $mess = '请求方式不正确';
        }
        $this->error($mess, $this->gourl);
    }
    
     //购物车购买创建订单接口
    public function addorder(){
        if(request()->isPost()){
            $user_id = $this->user_id;
            if(input('post.cart_idres') && !is_array(input('post.cart_idres'))){
                if(input('post.dz_id')){
                    $zf_type = !empty(input('post.zf_type')) ? input('post.zf_type') : 1;
                    if($zf_type && in_array($zf_type, array(1,2,3))){
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
                                                        if($carts['goods_attr']){
                                                            $prores = Db::name('product')->where('goods_attr',$carts['goods_attr'])->where('goods_id',$carts['goods_id'])->field('goods_number')->find();
                                                        }else{
                                                            $prores = Db::name('product')->where('goods_id',$carts['goods_id'])->where('goods_attr','')->field('goods_number')->find();
                                                        }

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

                                                        //$zsdan_price = $carts['shop_price']*$carts['num'];
                                                        //$zsdan_price = sprintf("%.2f", $zsdan_price);
                                                        //$zsprice+=$zsdan_price;

                                                        $goodinfores[] = array('id'=>$carts['goods_id'],'goods_name'=>$carts['goods_name'],'thumb_url'=>$carts['thumb_url'],'goods_attr_id'=>$carts['goods_attr'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$carts['shop_price'],'goods_num'=>$carts['num'],'is_free'=>$carts['is_free'],'hd_type'=>$carts['hd_type'],'hd_id'=>$carts['hd_id'],'shop_id'=>$carts['shop_id']);
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$carts['goods_name'].'库存不足','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }else{
                                                    $carts['hd_type'] = 0;
                                                    $carts['hd_id'] = 0;

                                                    if($carts['goods_attr']){
                                                        $prores = Db::name('product')->where('goods_attr',$carts['goods_attr'])->where('goods_id',$carts['goods_id'])->field('goods_number')->find();
                                                        if($prores){
                                                            $goods_number = $prores['goods_number'];
                                                        }else{
                                                            $goods_number = 0;
                                                        }

                                                        if($carts['num'] > 0 && $carts['num'] <= $goods_number){
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

                                                            //$zsdan_price = $carts['shop_price']*$carts['num'];
                                                            //$zsdan_price = sprintf("%.2f", $zsdan_price);
                                                            //$zsprice+=$zsdan_price;

                                                            $goodinfores[] = array('id'=>$carts['goods_id'],'goods_name'=>$carts['goods_name'],'thumb_url'=>$carts['thumb_url'],'goods_attr_id'=>$carts['goods_attr'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$carts['shop_price'],'goods_num'=>$carts['num'],'is_free'=>$carts['is_free'],'hd_type'=>$carts['hd_type'],'hd_id'=>$carts['hd_id'],'shop_id'=>$carts['shop_id']);
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>$carts['goods_name'].'库存不足','data'=>array('status'=>400));
                                                            return json($value);
                                                        }
                                                    }else{
                                                        $prores = Db::name('product')->where('goods_id',$carts['goods_id'])->where('goods_attr','')->field('goods_number')->find();
                                                        if($prores){
                                                            $goods_number = $prores['goods_number'];
                                                        }else{
                                                            $goods_number = 0;
                                                        }
                                                        if($carts['num'] > 0 && $carts['num'] <= $goods_number){
                                                            $goods_attr_str = '';

                                                            //$zsdan_price = $carts['shop_price']*$carts['num'];
                                                            //$zsdan_price = sprintf("%.2f", $zsdan_price);
                                                            //$zsprice+=$zsdan_price;

                                                            $goodinfores[] = array('id'=>$carts['goods_id'],'goods_name'=>$carts['goods_name'],'thumb_url'=>$carts['thumb_url'],'goods_attr_id'=>$carts['goods_attr'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$carts['shop_price'],'goods_num'=>$carts['num'],'is_free'=>$carts['is_free'],'hd_type'=>$carts['hd_type'],'hd_id'=>$carts['hd_id'],'shop_id'=>$carts['shop_id']);
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>$carts['goods_name'].'库存不足','data'=>array('status'=>400));
                                                            return json($value);
                                                        }
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

                                    $webconfig = $this->webconfig;

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

                                            $order_number = 'D'.date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
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
                                                $datainfo['product_id'] =  md5(uniqid(microtime(true), true));

                                                // 启动事务
                                                Db::startTrans();
                                                try{
                                                    $zong_id = Db::name('order_zong')->insertGetId($datainfo);
                                                    if($zong_id){
                                                        $outarr = array();

                                                        foreach ($hqgoodsinfos as $qkey => $qval){
                                                            $time_out = time()+$webconfig['normal_out_order']*3600;

                                                            foreach ($qval['goodres'] as $cvp){
                                                                if($cvp['hd_type'] == 1){
                                                                    $time_out = time()+$webconfig['rushactivity_out_order']*60;
                                                                    break;
                                                                }elseif($cvp['hd_type'] == 2){
                                                                    $time_out = time()+$webconfig['group_out_order']*60;
                                                                }
                                                            }

                                                            $outarr[] = $time_out;

                                                            $shop_ordernum = $order_number.substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);

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

                                                                if(in_array($rval['hd_type'],array(0,2))){
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
                        if(input('post.fangshi') && in_array(input('post.fangshi'), array(1,2))){
                            $goods_id = input('post.goods_id');
                            $num = input('post.num');
                            $fangshi = input('post.fangshi');
                            
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
                            
                                    if($activitys){
                                        if($activitys['ac_type'] == 1){
                                            if($num > $activitys['xznum']){
                                                $value = array('status'=>400,'mess'=>'商品限购'.$activitys['xznum'].'件','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                        
                                            $goods_number = $activitys['kucun'];
                                        }else{
                                            if(!empty($goods_attr)){
                                                $prores = Db::name('product')->where('goods_attr',$goods_attr)->where('goods_id',$goods['id'])->field('goods_number')->find();
                                            }else{
                                                $prores = Db::name('product')->where('goods_id',$goods['id'])->where('goods_attr','')->field('goods_number')->find();
                                            }
                                            
                                            if($prores){
                                                $goods_number = $prores['goods_number'];
                                            }else{
                                                $goods_number = 0;
                                            }
                                        }
                                    }else{
                                        if(!empty($goods_attr)){
                                            $prores = Db::name('product')->where('goods_attr',$goods_attr)->where('goods_id',$goods['id'])->field('goods_number')->find();
                                        }else{
                                            $prores = Db::name('product')->where('goods_id',$goods['id'])->where('goods_attr','')->field('goods_number')->find();
                                        }
                                        
                                        if($prores){
                                            $goods_number = $prores['goods_number'];
                                        }else{
                                            $goods_number = 0;
                                        }
                                    }
                            
                                    if($num <= 0 || $num > $goods_number){
                                        $value = array('status'=>400,'mess'=>$goods['goods_name'].'库存不足','data'=>array('status'=>400));
                                        return json($value);
                                    }
                            
                                    $purchs = Db::name('purch')->where('user_id',$user_id)->find();
                                    if($purchs){
                                        $count = Db::name('purch')->where('id',$purchs['id'])->where('user_id',$user_id)->update(array('goods_id'=>$goods['id'],'goods_attr'=>$goods_attr,'num'=>$num,'shop_id'=>$goods['shop_id']));
                                        if($count !== false){
                                            $value = array('status'=>200,'mess'=>'操作成功','data'=>array('pur_id'=>$purchs['id']));
                                        }else{
                                            $value = array('status'=>400,'mess'=>'操作失败，请重试','data'=>array('status'=>400));
                                        }
                                    }else{
                                        $pur_id = Db::name('purch')->insertGetId(array('goods_id'=>$goods['id'],'goods_attr'=>$goods_attr,'num'=>$num,'user_id'=>$user_id,'shop_id'=>$goods['shop_id']));
                                        if($pur_id){
                                            $value = array('status'=>200,'mess'=>'操作成功','data'=>array('pur_id'=>$pur_id,'fangshi'=>$fangshi));
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
                            $value = array('status'=>400,'mess'=>'缺少订单类型参数','data'=>array('status'=>400));
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
                        if(input('post.fangshi') && in_array(input('post.fangshi'), array(1,2))){
                            $wallets = Db::name('wallet')->where('user_id',$user_id)->find();
                            $pur_id = input('post.pur_id');
                            $fangshi = input('post.fangshi');
                            
                            $purchs = Db::name('purch')->alias('a')->field('a.*,b.goods_name,b.thumb_url,b.shop_price,b.is_free,c.shop_name')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.id',$pur_id)->where('a.user_id',$user_id)->where('b.onsale',1)->where('c.open_status',1)->find();
                            if($purchs){
                                $goodinfos = array();
                            
                                $webconfig = $this->webconfig;
                                $purchs['thumb_url'] = $webconfig['weburl'].'/'.$purchs['thumb_url'];
                            
                                $ruinfo = array('id'=>$purchs['goods_id'],'shop_id'=>$purchs['shop_id']);
                                $ru_attr = $purchs['goods_attr'];
                            
                                $gongyong = new GongyongMx();
                                $activitys = $gongyong->pdrugp($ruinfo,$ru_attr);
                                
                                if($activitys){
                                    if($activitys['ac_type'] == 1){
                                        $goods_number = $activitys['kucun'];
                                    }else{
                                        if(!empty($purchs['goods_attr'])){
                                            $prores = Db::name('product')->where('goods_attr',$purchs['goods_attr'])->where('goods_id',$purchs['goods_id'])->field('goods_number')->find();
                                        }else{
                                            $prores = Db::name('product')->where('goods_id',$purchs['goods_id'])->where('goods_attr','')->field('goods_number')->find();
                                        }
                                        
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
                                    
                                        //$zsdan_price = $purchs['shop_price']*$purchs['num'];
                                        //$zsdan_price = sprintf("%.2f", $zsdan_price);
                                        //$zsprice+=$zsdan_price;
                                    
                                        $goodinfos = array('id'=>$purchs['goods_id'],'goods_name'=>$purchs['goods_name'],'thumb_url'=>$purchs['thumb_url'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$purchs['shop_price'],'goods_num'=>$purchs['num'],'is_free'=>$purchs['is_free'],'shop_id'=>$purchs['shop_id'],'shop_name'=>$purchs['shop_name']);
                                    }else{
                                        $value = array('status'=>400,'mess'=>$purchs['goods_name'].'库存不足','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }else{
                                    if(!empty($purchs['goods_attr'])){
                                        $prores = Db::name('product')->where('goods_attr',$purchs['goods_attr'])->where('goods_id',$purchs['goods_id'])->field('goods_number')->find();
                                        if($prores){
                                            $goods_number = $prores['goods_number'];
                                        }else{
                                            $goods_number = 0;
                                        }
                                        if($purchs['num'] > 0 && $purchs['num'] <= $goods_number){
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
                                    
                                            //$zsdan_price = $purchs['shop_price']*$purchs['num'];
                                            //$zsdan_price = sprintf("%.2f", $zsdan_price);
                                            //$zsprice = $zsdan_price;
                                    
                                            $goodinfos = array('id'=>$purchs['goods_id'],'goods_name'=>$purchs['goods_name'],'thumb_url'=>$purchs['thumb_url'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$purchs['shop_price'],'goods_num'=>$purchs['num'],'is_free'=>$purchs['is_free'],'shop_id'=>$purchs['shop_id'],'shop_name'=>$purchs['shop_name']);
                                        }else{
                                            $value = array('status'=>400,'mess'=>$purchs['goods_name'].'库存不足','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }else{
                                        $prores = Db::name('product')->where('goods_id',$purchs['goods_id'])->where('goods_attr','')->field('goods_number')->find();
                                        if($prores){
                                            $goods_number = $prores['goods_number'];
                                        }else{
                                            $goods_number = 0;
                                        }
                                        if($purchs['num'] > 0 && $purchs['num'] <= $goods_number){
                                            $goods_attr_str = '';
                                    
                                            //$zsdan_price = $purchs['shop_price']*$purchs['num'];
                                            //$zsdan_price = sprintf("%.2f", $zsdan_price);
                                            //$zsprice = $zsdan_price;
                                    
                                            $goodinfos = array('id'=>$purchs['goods_id'],'goods_name'=>$purchs['goods_name'],'thumb_url'=>$purchs['thumb_url'],'goods_attr_str'=>$goods_attr_str,'shop_price'=>$purchs['shop_price'],'goods_num'=>$purchs['num'],'is_free'=>$purchs['is_free'],'shop_id'=>$purchs['shop_id'],'shop_name'=>$purchs['shop_name']);
                                        }else{
                                            $value = array('status'=>400,'mess'=>$purchs['goods_name'].'库存不足','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }
                                }
                            
                                if($goodinfos){
                                    $goodinfos['coupon_str'] = '';
                                    $goodinfos['cxhuodong'] = array();
                                    $goodinfos['youhui_price'] = 0;
                                    $goodinfos['freight'] = 0;
                                    $goodinfos['xiaoji_price'] = 0;
                            
                                    $xiaoji = sprintf("%.2f", $goodinfos['shop_price']*$goodinfos['goods_num']);
                            
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
                            
                                    $zsprice = $goodinfos['xiaoji_price'];
                            
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
                            
                                    $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>array('goodinfo'=>$hqgoodsinfos,'zong_num'=>$zong_num,'zsprice'=>$zsprice,'address'=>$dizis,'wallet_price'=>$wallets['price'],'pur_id'=>$pur_id));
                                }else{
                                    $value = array('status'=>400,'mess'=>'找不到相关商品信息','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关商品信息','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少订单类型参数','data'=>array('status'=>400));
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
    
    //立即购买提交订单接口
    public function puraddorder() {
        if (request()->isPost()) {
            $user_id = $this->user_id;
            if (input('post.pur_id')) {
                $fangshi = !empty(input('post.fangshi')) ? input('post.fangshi') : 1;
                if ($fangshi && in_array($fangshi, array(1, 2))) {
                    if (input('post.dz_id')) {
                        $zf_type = !empty(input('post.zf_type')) ? input('post.zf_type') : 1;
                        if ($zf_type && in_array($zf_type, array(1, 2, 3))) {
                            if(input('post.beizhu')){
                                if(mb_strlen(input('post.beizhu'),'utf8') <= 100){
                                    $beizhu = input('post.beizhu');
                                }else{
                                    $value = array('status'=>400,'mess'=>'备注信息在100个字符内','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }else{
                                $beizhu = '';
                            }

                            $dizis = Db::name('address')->alias('a')->field('a.*,b.pro_name,c.city_name,d.area_name')->join('sp_province b', 'a.pro_id = b.id', 'LEFT')->join('sp_city c', 'a.city_id = c.id', 'LEFT')->join('sp_area d', 'a.area_id = d.id', 'LEFT')->where('a.id', input('post.dz_id'))->where('a.user_id', $user_id)->find();
                            if ($dizis) {
                                $pur_id = input('post.pur_id');

                                $purchs = Db::name('purch')->alias('a')->field('a.*,b.goods_name,b.thumb_url,b.shop_price,b.is_free,c.shop_name')->join('sp_goods b', 'a.goods_id = b.id', 'INNER')->join('sp_shops c', 'a.shop_id = c.id', 'INNER')->where('a.id', $pur_id)->where('a.user_id', $user_id)->where('b.onsale', 1)->where('c.open_status', 1)->find();
                                if ($purchs) {
                                    $total_price = 0;
                                    $goodinfos = array();

                                    $ruinfo = array('id' => $purchs['goods_id'], 'shop_id' => $purchs['shop_id']);
                                    $ru_attr = $purchs['goods_attr'];

                                    $gongyong = new GongyongMx();
                                    $activitys = $gongyong->pdrugp($ruinfo, $ru_attr);

                                    if($activitys){
                                        $purchs['hd_type'] = $activitys['ac_type'];
                                        $purchs['hd_id'] = $activitys['id'];

                                        if ($activitys['ac_type'] == 1) {
                                            $goods_number = $activitys['kucun'];
                                        } else {
                                            if (!empty($purchs['goods_attr'])) {
                                                $prores = Db::name('product')->where('goods_attr', $purchs['goods_attr'])->where('goods_id', $purchs['goods_id'])->field('goods_number')->find();
                                            } else {
                                                $prores = Db::name('product')->where('goods_id', $purchs['goods_id'])->where('goods_attr', '')->field('goods_number')->find();
                                            }

                                            if ($prores) {
                                                $goods_number = $prores['goods_number'];
                                            } else {
                                                $goods_number = 0;
                                            }
                                        }

                                        if ($purchs['num'] > 0 && $purchs['num'] <= $goods_number) {
                                            if (!empty($purchs['goods_attr'])) {
                                                $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', 'in', $purchs['goods_attr'])->where('a.goods_id', $purchs['goods_id'])->where('b.attr_type', 1)->select();
                                                $goods_attr_str = '';
                                                if ($gares) {
                                                    foreach ($gares as $key => $val) {
                                                        if ($key == 0) {
                                                            $goods_attr_str = $val['attr_name'] . ':' . $val['attr_value'];
                                                        } else {
                                                            $goods_attr_str = $goods_attr_str . ' ' . $val['attr_name'] . ':' . $val['attr_value'];
                                                        }
                                                    }
                                                }
                                            } else {
                                                $gares = array();
                                                $goods_attr_str = '';
                                            }

                                            $purchs['shop_price'] = $activitys['price'];

                                            //$zsdan_price = $purchs['shop_price']*$purchs['num'];
                                            //$zsdan_price = sprintf("%.2f", $zsdan_price);
                                            //$zsprice+=$zsdan_price;

                                            $goodinfos = array('id' => $purchs['goods_id'], 'goods_name' => $purchs['goods_name'], 'thumb_url' => $purchs['thumb_url'], 'goods_attr_id' => $purchs['goods_attr'], 'goods_attr_str' => $goods_attr_str, 'shop_price' => $purchs['shop_price'], 'goods_num' => $purchs['num'], 'is_free' => $purchs['is_free'], 'hd_type' => $purchs['hd_type'], 'hd_id' => $purchs['hd_id'], 'shop_id' => $purchs['shop_id']);
                                        } else {
                                            $value = array('status' => 400, 'mess' => $purchs['goods_name'] . '库存不足', 'data' => array('status' => 400));
                                            return json($value);
                                        }
                                    } else {
                                        $purchs['hd_type'] = 0;
                                        $purchs['hd_id'] = 0;

                                        if ($purchs['goods_attr']) {
                                            $prores = Db::name('product')->where('goods_attr', $purchs['goods_attr'])->where('goods_id', $purchs['goods_id'])->field('goods_number')->find();
                                            if ($prores) {
                                                $goods_number = $prores['goods_number'];
                                            } else {
                                                $goods_number = 0;
                                            }

                                            if ($purchs['num'] > 0 && $purchs['num'] <= $goods_number) {
                                                $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', 'in', $purchs['goods_attr'])->where('a.goods_id', $purchs['goods_id'])->where('b.attr_type', 1)->select();
                                                $goods_attr_str = '';
                                                if ($gares) {
                                                    foreach ($gares as $key => $val) {
                                                        $purchs['shop_price'] += $val['attr_price'];
                                                        if ($key == 0) {
                                                            $goods_attr_str = $val['attr_name'] . ':' . $val['attr_value'];
                                                        } else {
                                                            $goods_attr_str = $goods_attr_str . ' ' . $val['attr_name'] . ':' . $val['attr_value'];
                                                        }
                                                    }
                                                    $purchs['shop_price'] = sprintf("%.2f", $purchs['shop_price']);
                                                }

                                                /* $dan_price = $purchs['shop_price']*$purchs['num'];
                                                  $dan_price = sprintf("%.2f", $dan_price);
                                                  $total_goods_price = $dan_price; */

                                                $goodinfos = array('id' => $purchs['goods_id'], 'goods_name' => $purchs['goods_name'], 'thumb_url' => $purchs['thumb_url'], 'goods_attr_id' => $purchs['goods_attr'], 'goods_attr_str' => $goods_attr_str, 'shop_price' => $purchs['shop_price'], 'goods_num' => $purchs['num'], 'is_free' => $purchs['is_free'], 'hd_type' => $purchs['hd_type'], 'hd_id' => $purchs['hd_id'], 'shop_id' => $purchs['shop_id']);
                                            } else {
                                                $value = array('status' => 400, 'mess' => $purchs['goods_name'] . '库存不足', 'data' => array('status' => 400));
                                                return json($value);
                                            }
                                        } else {
                                            $prores = Db::name('product')->where('goods_id', $purchs['goods_id'])->where('goods_attr', '')->field('goods_number')->find();
                                            if ($prores) {
                                                $goods_number = $prores['goods_number'];
                                            } else {
                                                $goods_number = 0;
                                            }

                                            if ($purchs['num'] > 0 && $purchs['num'] <= $goods_number) {
                                                $goods_attr_str = '';

                                                /* $dan_price = $purchs['shop_price']*$purchs['num'];
                                                  $dan_price = sprintf("%.2f", $dan_price);
                                                  $total_goods_price = $dan_price; */

                                                $goodinfos = array('id' => $purchs['goods_id'], 'goods_name' => $purchs['goods_name'], 'thumb_url' => $purchs['thumb_url'], 'goods_attr_id' => $purchs['goods_attr'], 'goods_attr_str' => $goods_attr_str, 'shop_price' => $purchs['shop_price'], 'goods_num' => $purchs['num'], 'is_free' => $purchs['is_free'], 'hd_type' => $purchs['hd_type'], 'hd_id' => $purchs['hd_id'], 'shop_id' => $purchs['shop_id']);
                                            } else {
                                                $value = array('status' => 400, 'mess' => $purchs['goods_name'] . '库存不足', 'data' => array('status' => 400));
                                                return json($value);
                                            }
                                        }
                                    }

                                    $webconfig = $this->webconfig;

                                    if ($goodinfos) {
                                        $goodinfos['coupon_id'] = 0;
                                        $goodinfos['coupon_price'] = 0;
                                        $goodinfos['coupon_str'] = '';
                                        $goodinfos['youhui_price'] = 0;
                                        $goodinfos['freight'] = 0;
                                        $goodinfos['xiaoji_price'] = 0;

                                        $xiaoji = sprintf("%.2f", $goodinfos['shop_price'] * $goodinfos['goods_num']);

                                        $coupons = Db::name('coupon')->where('shop_id', $goodinfos['shop_id'])->where('start_time', 'elt', time())->where('end_time', 'gt', time() - 3600 * 24)->where('onsale', 1)->field('id,man_price,dec_price')->order('man_price asc')->find();
                                        if ($coupons) {
                                            $couinfos = Db::name('member_coupon')->alias('a')->field('a.*,b.man_price,b.dec_price')->join('sp_coupon b', 'a.coupon_id = b.id', 'INNER')->where('a.user_id', $user_id)->where('a.is_sy', 0)->where('a.shop_id', $goodinfos['shop_id'])->where('b.start_time', 'elt', time())->where('b.end_time', 'gt', time() - 3600 * 24)->where('b.onsale', 1)->where('b.man_price', 'elt', $xiaoji)->order('b.man_price desc')->find();

                                            if ($couinfos) {
                                                $goodinfos['coupon_id'] = $couinfos['coupon_id'];
                                                $goodinfos['coupon_price'] = $couinfos['dec_price'];
                                                $goodinfos['coupon_str'] = '满' . $couinfos['man_price'] . '减' . $couinfos['dec_price'];
                                                $goodinfos['youhui_price'] += $couinfos['dec_price'];
                                            }
                                        }

                                        $promotionres = Db::name('promotion')->where('shop_id', $goodinfos['shop_id'])->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,start_time,end_time,info_id')->select();
                                        $cxgoods = array();

                                        if ($promotionres) {
                                            foreach ($promotionres as $prv) {
                                                $prom_typeres = Db::name('prom_type')->where('prom_id', $prv['id'])->select();
                                                if ($prom_typeres) {
                                                    $prohdsort = array();

                                                    if (strpos(',' . $prv['info_id'] . ',', ',' . $goodinfos['id'] . ',') !== false) {
                                                        foreach ($prom_typeres as $krp => $vrp) {
                                                            if ($goodinfos['goods_num'] && $goodinfos['goods_num'] >= $vrp['man_num']) {
                                                                $prohdsort[] = $vrp;
                                                            }
                                                        }

                                                        if ($prohdsort) {
                                                            $prohdsort = arraySort($prohdsort, 'man_num');
                                                            $promhdinfo = $prohdsort[0];

                                                            $zhekou = $promhdinfo['discount'] / 100;
                                                            $zhekouprice = sprintf("%.2f", $goodinfos['shop_price'] * $zhekou);
                                                            $youhui_price = ($goodinfos['shop_price'] - $zhekouprice) * $goodinfos['goods_num'];
                                                            $youhui_price = sprintf("%.2f", $youhui_price);
                                                            $goodinfos['youhui_price'] += $youhui_price;

                                                            $cxgoods = array('promo_id' => $prv['id'], 'man_num' => $promhdinfo['man_num'], 'discount' => $promhdinfo['discount'], 'cxgds' => $goodinfos['id']);
                                                        }
                                                        break;
                                                    }
                                                }
                                            }
                                        }

                                        $goodinfos['goods_price'] = $xiaoji;
                                        $goodinfos['youhui_price'] = sprintf("%.2f", $goodinfos['youhui_price']);
                                        $goodinfos['xiaoji_price'] = sprintf("%.2f", $xiaoji - $goodinfos['youhui_price']);

                                        //邮费
                                        $baoyou = 1;

                                        if ($goodinfos['is_free'] == 0) {
                                            $baoyou = 0;
                                        }

                                        if ($baoyou == 0) {
                                            $shopinfos = Db::name('shops')->where('id', $goodinfos['shop_id'])->field('freight,reduce')->find();
                                            if ($goodinfos['xiaoji_price'] < $shopinfos['reduce']) {
                                                $goodinfos['freight'] = $shopinfos['freight'];
                                                $goodinfos['xiaoji_price'] = sprintf("%.2f", $goodinfos['xiaoji_price'] + $shopinfos['freight']);
                                            }
                                        }

                                        $total_price = sprintf("%.2f", $goodinfos['xiaoji_price']);

                                        $order_number = 'D' . date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                        $dingdan = Db::name('order_zong')->where('order_number', $order_number)->find();
                                        if (!$dingdan) {
                                            $datainfo = array();
                                            $datainfo['order_number'] = $order_number;
                                            $datainfo['total_price'] = $total_price;
                                            $datainfo['state'] = 0;
                                            $datainfo['zf_type'] = 0;
                                            $datainfo['user_id'] = $user_id;
                                            $datainfo['addtime'] = time();
                                            $datainfo['time_out'] = 0;
                                            $datainfo['product_id'] =  md5(uniqid(microtime(true), true));

                                            // 启动事务
                                            Db::startTrans();
                                            try {
                                                $zong_id = Db::name('order_zong')->insertGetId($datainfo);
                                                if ($zong_id) {
                                                    $time_out = time() + $webconfig['normal_out_order'] * 3600;

                                                    if ($goodinfos['hd_type'] == 1) {
                                                        $time_out = time() + $webconfig['rushactivity_out_order'] * 60;
                                                    } elseif ($goodinfos['hd_type'] == 2) {
                                                        $time_out = time() + $webconfig['group_out_order'] * 60;
                                                    }

                                                    $shop_ordernum = $order_number . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);

                                                    $order_id = Db::name('order')->insertGetId(array(
                                                        'ordernumber' => $shop_ordernum,
                                                        'contacts' => $dizis['contacts'],
                                                        'telephone' => $dizis['phone'],
                                                        'pro_id' => $dizis['pro_id'],
                                                        'city_id' => $dizis['city_id'],
                                                        'area_id' => $dizis['area_id'],
                                                        'province' => $dizis['pro_name'],
                                                        'city' => $dizis['city_name'],
                                                        'area' => $dizis['area_name'],
                                                        'address' => $dizis['address'],
                                                        'dz_id' => $dizis['id'],
                                                        'goods_price' => $goodinfos['goods_price'],
                                                        'freight' => $goodinfos['freight'],
                                                        'coupon_id' => $goodinfos['coupon_id'],
                                                        'coupon_price' => $goodinfos['coupon_price'],
                                                        'coupon_str' => $goodinfos['coupon_str'],
                                                        'youhui_price' => $goodinfos['youhui_price'],
                                                        'total_price' => $goodinfos['xiaoji_price'],
                                                        'state' => 0,
                                                        'zf_type' => 0,
                                                        'fh_status' => 0,
                                                        'order_status' => 0,
                                                        'user_id' => $user_id,
                                                        'zong_id' => $zong_id,
                                                        'order_type'=>1,
                                                        'shop_id' => $goodinfos['shop_id'],
                                                        'addtime' => time(),
                                                        'time_out' => $time_out,
                                                        'beizhu' => $beizhu
                                                    ));

                                                    if ($goodinfos['coupon_id']) {
                                                        Db::name('member_coupon')->where('user_id', $user_id)->where('coupon_id', $goodinfos['coupon_id'])->where('is_sy', 0)->where('shop_id', $goodinfos['shop_id'])->update(array('is_sy' => 1));
                                                        $goodyh_price = sprintf("%.2f", $goodinfos['goods_price'] - $goodinfos['coupon_price']);
                                                    }

                                                    $goodzs_price = $goodinfos['shop_price'];
                                                    $jian_price = 0;
                                                    $prom_id = 0;
                                                    $prom_str = '';

                                                    if ($goodinfos['coupon_id']) {
                                                        $dan_price = sprintf("%.2f", ($goodyh_price / $goodinfos['goods_price']) * $goodinfos['shop_price']);
                                                        $goodzs_price = $dan_price;
                                                        $jian_price = sprintf("%.2f", $goodinfos['shop_price'] - $dan_price);
                                                    }

                                                    if (!empty($cxgoods)) {
                                                        if ($goodinfos['id'] == $cxgoods['cxgds']) {
                                                            $zklv = $cxgoods['discount'] / 100;
                                                            $zkprice = sprintf("%.2f", $goodinfos['shop_price'] * $zklv);
                                                            $goodzs_price = sprintf("%.2f", $zkprice - $jian_price);
                                                            $prom_id = $cxgoods['promo_id'];
                                                            $zhenum = $cxgoods['discount'] / 10;
                                                            $prom_str = '满' . $cxgoods['man_num'] . '件' . $zhenum . '折';
                                                        }
                                                    }

                                                    $orgoods_id = Db::name('order_goods')->insertGetId(array(
                                                        'goods_id' => $goodinfos['id'],
                                                        'goods_name' => $goodinfos['goods_name'],
                                                        'thumb_url' => $goodinfos['thumb_url'],
                                                        'goods_attr_id' => $goodinfos['goods_attr_id'],
                                                        'goods_attr_str' => $goodinfos['goods_attr_str'],
                                                        'real_price' => $goodinfos['shop_price'],
                                                        'price' => $goodzs_price,
                                                        'goods_num' => $goodinfos['goods_num'],
                                                        'hd_type' => $goodinfos['hd_type'],
                                                        'hd_id' => $goodinfos['hd_id'],
                                                        'prom_id' => $prom_id,
                                                        'prom_str' => $prom_str,
                                                        'is_free' => $goodinfos['is_free'],
                                                        'shop_id' => $goodinfos['shop_id'],
                                                        'order_id' => $order_id
                                                    ));

                                                    if (in_array($goodinfos['hd_type'], array(0,2))) {
                                                        $prokcs = Db::name('product')->lock(true)->where('goods_id', $goodinfos['id'])->where('goods_attr', $goodinfos['goods_attr_id'])->find();
                                                        if ($prokcs) {
                                                            Db::name('product')->where('goods_id', $goodinfos['id'])->where('goods_attr', $goodinfos['goods_attr_id'])->setDec('goods_number', $goodinfos['goods_num']);
                                                        }
                                                    } elseif ($goodinfos['hd_type'] == 1) {
                                                        $hdactivitys = Db::name('rush_activity')->lock(true)->where('id', $goodinfos['hd_id'])->find();
                                                        if ($hdactivitys) {
                                                            Db::name('rush_activity')->where('id', $goodinfos['hd_id'])->setDec('kucun', $goodinfos['goods_num']);
                                                            Db::name('rush_activity')->where('id', $goodinfos['hd_id'])->setInc('sold', $goodinfos['goods_num']);
                                                        }
                                                    }

                                                    Db::name('order_zong')->update(array('id' => $zong_id, 'time_out' => $time_out));
                                                    Db::name('purch')->where('id', $pur_id)->where('user_id', $user_id)->delete();
                                                }

                                                // 提交事务
                                                Db::commit();
                                                $orderinfos = array('order_number' => $order_number, 'zf_type' => $zf_type);
                                                $value = array('status' => 200, 'mess' => '创建订单成功', 'data' => $orderinfos);
                                            } catch (\Exception $e) {
                                                // 回滚事务
                                                Db::rollback();
                                                $value = array('status' => 400, 'mess' => '创建订单失败', 'data' => array('status' => 400));
                                            }
                                        } else {
                                            $value = array('status' => 400, 'mess' => '创建订单失败', 'data' => array('status' => 400));
                                        }
                                    } else {
                                        $value = array('status' => 400, 'mess' => '商品信息参数错误', 'data' => array('status' => 400));
                                    }
                                } else {
                                    $value = array('status' => 400, 'mess' => '找不到相关商品信息', 'data' => array('status' => 400));
                                }
                            } else {
                                $value = array('status' => 400, 'mess' => '收货地址信息错误', 'data' => array('status' => 400));
                            }
                        } else {
                            $value = array('status' => 400, 'mess' => '支付方式参数错误', 'data' => array('status' => 400));
                        }
                    } else {
                        $value = array('status' => 400, 'mess' => '缺少地址信息', 'data' => array('status' => 400));
                    }
                } else {
                    $value = array('status' => 400, 'mess' => '缺少订单类型参数', 'data' => array('status' => 400));
                }
            } else {
                $value = array('status' => 400, 'mess' => '缺少立即购买商品参数', 'data' => array('status' => 400));
            }
        } else {
            $value = array('status' => 400, 'mess' => '请求方式不正确', 'data' => array('status' => 400));
        }
        return json($value);
    }
    
}