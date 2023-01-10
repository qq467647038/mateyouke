<?php
namespace app\index\controller;

use app\index\controller\Common;
use app\index\model\Gongyong as GongyongMx;
use think\Db;

class Goods extends Common{
    //根据分类获取商品列表
    public function getlst(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                if(input('post.cate_id')){
                    if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                        $cate_id = input('post.cate_id');
                        $pagenum = input('post.page');
                        $cates = Db::name('category')->where('id',$cate_id)->where('is_show',1)->field('id,cate_name,type_id')->find();
                        if($cates){
                            $categoryres = Db::name('category')->where('is_show',1)->field('id,pid')->order('sort asc')->select();
                            $cateIds = array();
                            $cateIds = get_all_child($categoryres, $cate_id);
                            $cateIds[] = $cate_id;
                            $cateIds = implode(',', $cateIds);
                            
                            $webconfig = $this->webconfig;
                            $perpage = $webconfig['app_goodlst_num'];
                            $offset = ($pagenum-1)*$perpage;
                            
                            $where1 = "a.cate_id in (".$cateIds.")";
                            $where2 = "a.onsale = 1";
                            $where3 = '';
                            $where4 = '';
                            $where5 = '';
                            $where6 = '';
                            
                            if(input('post.goods_type') && input('post.goods_type') != 'all'){
                                $goods_type = input('post.goods_type');
                                switch($goods_type){
                                    case 1:
                                        $where3 = "a.leixing = 1";
                                        break;
                                    case 2:
                                        $where3 = "a.is_activity = 1";
                                        break;
                                }
                            }
                            
                            if(input('post.low_price') && input('post.max_price')){
                                $low_price = input('post.low_price');
                                $max_price = input('post.max_price');
                                
                                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $low_price)){
                                    $value = array('status'=>400,'mess'=>'最低价格格式错误','data'=>array('status'=>400));
                                    return json($value);
                                }
                                
                                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $max_price)){
                                    $value = array('status'=>400,'mess'=>'最高价格格式错误','data'=>array('status'=>400));
                                    return json($value);
                                }
                                
                                if($low_price >= $max_price){
                                    $value = array('status'=>400,'mess'=>'最低价格需小于最大价格','data'=>array('status'=>400));
                                    return json($value);
                                }
                                
                                $where4 = "a.zs_price >= '".$low_price."' AND a.zs_price <= '".$max_price."'";
                            }elseif(input('post.low_price') && !input('post.max_price')){
                                $low_price = input('post.low_price');
                                
                                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $low_price)){
                                    $value = array('status'=>400,'mess'=>'最低价格格式错误','data'=>array('status'=>400));
                                    return json($value);
                                }

                                $where4 = "a.zs_price >= '".$low_price."'";
                            }elseif(!input('post.low_price') && input('post.max_price')){
                                $max_price = input('post.max_price');
                                
                                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $max_price)){
                                    $value = array('status'=>400,'mess'=>'最高价格格式错误','data'=>array('status'=>400));
                                    return json($value);
                                }
                                
                                $where4 = "a.zs_price <= '".$max_price."'";
                            }
                            
                            if(input('post.brand_id') && input('post.brand_id') != 'all'){
                                $brand_id = input('post.brand_id');
                                $where5 = "a.brand_id = ".$brand_id."";
                            }
                            
                            if(input('post.goods_attr') && !is_array(input('post.goods_attr'))){
                                $goods_attr = input('post.goods_attr');
                                $goods_attr = trim($goods_attr);
                                $goods_attr = str_replace('，', ',', $goods_attr);
                                $goods_attr = rtrim($goods_attr,',');
                                
                                if($goods_attr){
                                    $goods_attr = explode(',', $goods_attr);
                                    $goods_attr = array_unique($goods_attr);
                                    
                                    if(!$goods_attr || !is_array($goods_attr)){
                                        $value = array('status'=>400,'mess'=>'商品属性筛选条件参数错误','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'商品属性筛选条件参数错误','data'=>array('status'=>400));
                                    return json($value);
                                }

                                
                                foreach ($goods_attr as $kca => $va){
                                    if(!empty($va)){
                                        if($kca == 0){
                                            $where6 = "find_in_set('".$va."',a.shuxings)";
                                        }else{
                                            $where6 = $where6." AND find_in_set('".$va."',a.shuxings)";
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'商品属性筛选条件参数错误','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                            }
                            
                            if(input('post.sort')){
                                $sort = input('post.sort');
                                switch($sort){
                                    case 'zonghe':
                                        $sortarr = array('a.leixing'=>'desc','a.zonghe_lv'=>'desc','a.id'=>'desc');
                                        break;
                                    case 'deal_num':
                                        $sortarr = array('a.deal_num '=>'desc','a.id'=>'desc');
                                        break;
                                    case 'low_height':
                                        $sortarr = array('a.zs_price'=>'asc','a.id'=>'desc');
                                        break;
                                    case 'height_low':
                                        $sortarr = array('a.zs_price'=>'desc','a.id'=>'desc');
                                        break;
                                    default:
                                        $sortarr = array('a.leixing'=>'desc','a.zonghe_lv'=>'desc','a.id'=>'desc');
                                }
                            }else{
                                $sortarr = array('a.leixing'=>'desc','a.zonghe_lv'=>'desc','a.id'=>'desc');
                            }
                            
                            $goodres = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.min_price,a.zs_price,a.leixing,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where1)->where($where2)->where($where3)->where($where4)->where($where5)->where($where6)->where("b.open_status = 1")->order($sortarr)->limit($offset,$perpage)->select();
                            
                            if($goodres){
                                foreach ($goodres as $k =>$v){
                                    $goodres[$k]['thumb_url'] = $webconfig['weburl'].'/'.$v['thumb_url'];
                                    $goodres[$k]['coupon'] = 0;
                                
                                    //优惠券
                                    $coupons = Db::name('coupon')->where('shop_id',$v['shop_id'])->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->find();
                                    if($coupons){
                                        $goodres[$k]['coupon'] = 1;
                                    }
                                
                                    $ruinfo = array('id'=>$v['id'],'shop_id'=>$v['shop_id']);
                                    $gongyong = new GongyongMx();
                                    $activitys = $gongyong->pdrugp($ruinfo);

                                    if($activitys){
                                        $goodres[$k]['is_activity'] = $activitys['ac_type'];
                                        
                                        if(!empty($activitys['goods_attr'])){
                                            $goods_attr_str = '';
                                            $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$activitys['goods_attr'])->where('a.goods_id',$v['id'])->where('b.attr_type',1)->select();
                                            if($gares){
                                                foreach ($gares as $key => $val){
                                                    if($key == 0){
                                                        $goods_attr_str = $val['attr_name'].':'.$val['attr_value'];
                                                    }else{
                                                        $goods_attr_str = $goods_attr_str.' '.$val['attr_name'].':'.$val['attr_value'];
                                                    }
                                                }
                                                $goodres[$k]['goods_name'] = $v['goods_name'].' '.$goods_attr_str;
                                            }
                                        }
                                        
                                        $goodres[$k]['zs_price'] = $activitys['price'];
                                    }else{
                                        $goodres[$k]['is_activity'] = 0;
                                        $goodres[$k]['zs_price'] = $v['min_price'];
                                    }
                                }
                            }
                            
                            if($pagenum == 1){
                                $brandres = Db::name('brand')->where('find_in_set('.$cate_id.',cate_id_list)')->where('is_show',1)->field('id,brand_name')->select();
                            
                                $shaixuan = Db::name('attr')->where('type_id',$cates['type_id'])->where('is_sear',1)->field('id,attr_name,attr_values')->select();
                                if($shaixuan){
                                    foreach ($shaixuan as $key2 => $val2){
                                        $shaixuan[$key2]['attr_values'] = explode(',',  $val2['attr_values']);
                                    }
                                }
                            
                                $cateinfos = array('id'=>$cates['id'],'cate_name'=>$cates['cate_name']);
                            
                                $goodlstinfo = array('cates'=>$cateinfos,'goodres'=>$goodres,'brandres'=>$brandres,'shaixuan'=>$shaixuan);
                            }else{
                                $goodlstinfo = array('goodres'=>$goodres);
                            }
                            
                            $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>$goodlstinfo);
                        }else{
                            $value = array('status'=>400,'mess'=>'分类信息参数错误','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少页面参数','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'缺少分类参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //商品详情
    public function goodsinfo() {
        $user_id = $this->user_id;
        $goods_id = input('goods_id');
        
        if (!$goods_id) {
            $this->error('参数错误', $this->gourl);
        }
        
        $goods = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.shop_price,a.min_market_price,a.max_market_price,a.min_price,a.max_price,a.zs_price,a.goods_desc,a.fuwu,a.is_free,a.leixing,a.is_activity,a.shop_id,a.sale_num,a.comment_num,a.shopzh_lv')->join('sp_shops b', 'a.shop_id = b.id', 'INNER')->where('a.id', $goods_id)->where('a.onsale', 1)->where('b.open_status', 1)->find();
        if (empty($goods)) {
            $this->error('商品已下架或不存在', $this->gourl);
        }
        $webconfig = $this->webconfig;
        $goods['thumb_url'] = $webconfig['weburl'] . '/' . $goods['thumb_url'];
        $goods['goods_desc'] = str_replace("/public/", $webconfig['weburl'] . "/public/", $goods['goods_desc']);
        $goods['goods_desc'] = str_replace("<img", "<img style='width:100%;'", $goods['goods_desc']);
        if ($goods['min_market_price'] != $goods['max_market_price']) {
            $goods['zs_market_price'] = $goods['min_market_price'] . '-' . $goods['max_market_price'];
        } else {
            $goods['zs_market_price'] = $goods['min_market_price'];
        }

        if ($user_id) {
            $colls = Db::name('coll_goods')->where('user_id', $user_id)->where('goods_id', $goods_id)->find();
            if ($colls) {
                $goods['coll_goods'] = 1;
            } else {
                $goods['coll_goods'] = 0;
            }
        } else {
            $goods['coll_goods'] = 0;
        }

        $goods['shop_token'] = '';
        $member_shops = Db::name('member')->where('shop_id', $goods['shop_id'])->field('id')->find();
        if ($member_shops) {
            $shoptoken_infos = Db::name('rxin')->where('user_id', $member_shops['id'])->field('token')->find();
            if ($shoptoken_infos) {
                $goods['shop_token'] = $shoptoken_infos['token'];
            }
        }

        $gpres = Db::name('goods_pic')->where('goods_id', $goods_id)->field('id,img_url,sort')->order('sort asc')->select();
        foreach ($gpres as $kp => $vp) {
            $gpres[$kp]['img_url'] = $webconfig['weburl'] . '/' . $vp['img_url'];
        }

        $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id,a.attr_value,a.attr_pic,b.attr_name,b.attr_type')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.goods_id', $goods_id)->where('b.attr_type', 1)->select();

        $guige = array();
        $colores = array();

        $radioattr = array();
        $color_arr = array();
        $size_arr = array();
        if ($radiores) {
            foreach ($radiores as $kra => $vra) {
                if ($vra['attr_pic']) {
                    $radiores[$kra]['attr_pic'] = $webconfig['weburl'] . '/' . $vra['attr_pic'];
                }

                $radiores[$kra]['check'] = 'false';

                if ($vra['attr_name'] == '颜色分类') {
                    if ($vra['attr_pic']) {
                        $colores[] = $webconfig['weburl'] . '/' . $vra['attr_pic'];
                    } else {
                        $colores[] = '';
                    }
                    $color_arr[] = $radiores[$kra];
                }
                
                if ($vra['attr_name'] == '尺寸' || $vra['attr_name'] == '尺码') {
                    $size_arr[] = $vra;
                }
            }

            foreach ($radiores as $v) {
                $radioattr[$v['attr_id']][] = $v;
            }

            foreach ($radioattr as $kad => $vad) {
                $guige[] = $vad[0]['attr_name'];
            }
        }

        $radioattr = array_values($radioattr);

        $uniattr = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id,a.attr_value,b.attr_name,b.attr_type')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.goods_id', $goods_id)->where('b.attr_type', 0)->select();

        $goods_attr = '';
        $goods_attr_str = '';

        $ruinfo = array('id' => $goods['id'], 'shop_id' => $goods['shop_id']);
        $gongyong = new GongyongMx();
        $activitys = $gongyong->pdrugp($ruinfo);

        $activity_info = array();

        if ($activitys) {
            $goods['is_activity'] = $activitys['ac_type'];

            if (!empty($activitys['goods_attr'])) {
                $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', 'in', $activitys['goods_attr'])->where('a.goods_id', $goods_id)->where('b.attr_type', 1)->select();
                if ($gares) {
                    foreach ($gares as $key => $val) {
                        if ($key == 0) {
                            $goods_attr_str = $val['attr_name'] . ':' . $val['attr_value'];
                        } else {
                            $goods_attr_str = $goods_attr_str . ' ' . $val['attr_name'] . ':' . $val['attr_value'];
                        }
                    }
                    $goods_attr = $activitys['goods_attr'];
                    $goods['goods_name'] = $goods['goods_name'] . ' ' . $goods_attr_str;
                }
            }

            $goods['zs_shop_price'] = $activitys['price'];

            if ($activitys['ac_type'] == 1) {
                $pronum = $activitys['kucun'];

                $yslv = sprintf("%.2f", $activitys['sold'] / $activitys['num']) * 100;
                $activity_info = array(
                    'yslv' => $yslv . '%',
                    'xznum' => $activitys['xznum'],
                    'start_time' => $activitys['start_time'],
                    'end_time' => $activitys['end_time'],
                    'dqtime' => time()
                );
            } else {
                if (!empty($activitys['goods_attr'])) {
                    $prores = Db::name('product')->where('goods_attr', $activitys['goods_attr'])->where('goods_id', $goods_id)->field('goods_number')->find();
                    if ($prores) {
                        $pronum = $prores['goods_number'];
                    } else {
                        $pronum = 0;
                    }
                } else {
                    $prores = Db::name('product')->where('goods_id', $goods_id)->field('goods_number')->select();
                    if ($prores) {
                        $pronum = 0;
                        foreach ($prores as $v3) {
                            $pronum += $v3['goods_number'];
                        }
                    } else {
                        $pronum = 0;
                    }
                }
                
                $activity_info = array(
                    'start_time' => $activitys['start_time'],
                    'end_time' => $activitys['end_time'],
                    'dqtime' => time()
                );
            }
        } else {
            $goods['is_activity'] = 0;

            if ($goods['min_price'] != $goods['max_price']) {
                $goods['zs_shop_price'] = $goods['min_price'] . '-' . $goods['max_price'];
            } else {
                $goods['zs_shop_price'] = $goods['min_price'];
            }

            $prores = Db::name('product')->where('goods_id', $goods_id)->field('goods_number')->select();
            if ($prores) {
                $pronum = 0;
                foreach ($prores as $v3) {
                    $pronum += $v3['goods_number'];
                }
            } else {
                $pronum = 0;
            }
        }

        //邮费
        if ($goods['is_free'] == 0) {
            $shopinfos = Db::name('shops')->where('id', $goods['shop_id'])->field('freight,reduce')->find();
            $freight = '运费' . $shopinfos['freight'] . ' 订单满' . $shopinfos['reduce'] . '免运费';
        } else {
            $freight = '包邮';
        }

        //优惠券
        $couponinfos = array('is_show' => 0, 'infos' => '');
        $couponres = Db::name('coupon')->where('shop_id', $goods['shop_id'])->where('start_time', 'elt', time())->where('end_time', 'gt', time() - 3600 * 24)->where('onsale', 1)->field('man_price,dec_price')->order('man_price asc')->limit(3)->select();
        if ($couponres) {
            $couponinfos = array('is_show' => 1, 'infos' => $couponres);
        }

        //商品活动信息
        $huodong = array('is_show' => 0, 'infos' => '', 'prom_id' => 0);
        $promotions = Db::name('promotion')->where("find_in_set('" . $goods_id . "',info_id)")->where('shop_id', $goods['shop_id'])->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,start_time,end_time')->find();
        if ($promotions) {
            $prom_typeres = Db::name('prom_type')->where('prom_id', $promotions['id'])->select();
        } else {
            $prom_typeres = array();
        }

        $goods_promotion = '';

        if (!empty($promotions) && !empty($prom_typeres)) {
            $start_time = date('Y年m月d日 H时', $promotions['start_time']);
            $end_time = date('Y年m月d日 H时', $promotions['end_time']);
            foreach ($prom_typeres as $kcp => $vcp) {
                $zhekou = $vcp['discount'] / 10;
                if ($kcp == 0) {
                    $goods_promotion = '商品满 ' . $vcp['man_num'] . '件 享' . $zhekou . '折';
                } else {
                    $goods_promotion = $goods_promotion . '  满 ' . $vcp['man_num'] . '件 享' . $zhekou . '折';
                }
            }
            $huodong = array('is_show' => 1, 'infos' => $goods_promotion, 'prom_id' => $promotions['id']);
        }

        //服务项
        $sertions = array('is_show' => 0, 'infos' => '');

        if (!empty($goods['fuwu'])) {
            $sertionres = Db::name('sertion')->where('id', 'in', $goods['fuwu'])->where('is_show', 1)->field('ser_name')->order('sort asc')->limit(2)->select();
            if ($sertionres) {
                $sertions = array('is_show' => 1, 'infos' => $sertionres);
            }
        }

        $goodsinfo = array(
            'id' => $goods['id'],
            'goods_name' => $goods['goods_name'],
            'thumb_url' => $goods['thumb_url'],
            'goods_desc' => $goods['goods_desc'],
            'freight' => $freight,
            'salenum' => 0,
            'leixing' => $goods['leixing'],
            'shop_id' => $goods['shop_id'],
            'zs_market_price' => $goods['zs_market_price'],
            'zs_shop_price' => $goods['zs_shop_price'],
            'is_activity' => $goods['is_activity'],
            'coll_goods' => $goods['coll_goods'],
            'shop_token' => $goods['shop_token'],
            'sale_num' => $goods['sale_num'],
            'comment_num' => $goods['comment_num'],
            'shopzh_lv' => $goods['shopzh_lv']
        );

        $shopinfos = Db::name('shops')->where('id', $goods['shop_id'])->where('open_status', 1)->field('id,shop_name,shop_desc,logo,goods_fen,fw_fen,wuliu_fen')->find();
        $shopinfos['logo'] = $webconfig['weburl'] . '/' . $shopinfos['logo'];

        $shop_customs = Db::name('shop_custom')->where('shop_id', $goods['shop_id'])->where('type', 1)->field('info_id')->find();

        $remgoodres = array();

        if ($shop_customs) {
            $remgoodres = Db::name('goods')->where('id', 'in', $shop_customs['info_id'])->where('shop_id', $goods['shop_id'])->where('onsale', 1)->field('id,goods_name,thumb_url,min_price,zs_price,leixing,shop_id')->order('zonghe_lv desc,id asc')->select();

            if ($remgoodres) {
                foreach ($remgoodres as $k2 => $v2) {
                    $remgoodres[$k2]['thumb_url'] = $webconfig['weburl'] . '/' . $v2['thumb_url'];

                    $reruinfo = array('id' => $v2['id'], 'shop_id' => $v2['shop_id']);
                    $regongyong = new GongyongMx();
                    $reactivitys = $regongyong->pdrugp($reruinfo);

                    if ($reactivitys) {
                        if (!empty($reactivitys['goods_attr'])) {
                            $regoods_attr_str = '';
                            $regares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', 'in', $reactivitys['goods_attr'])->where('a.goods_id', $v2['id'])->where('b.attr_type', 1)->select();
                            if ($regares) {
                                foreach ($regares as $key2 => $val2) {
                                    if ($key2 == 0) {
                                        $regoods_attr_str = $val2['attr_name'] . ':' . $val2['attr_value'];
                                    } else {
                                        $regoods_attr_str = $regoods_attr_str . ' ' . $val2['attr_name'] . ':' . $val2['attr_value'];
                                    }
                                }
                                $remgoodres[$k2]['goods_name'] = $v2['goods_name'] . ' ' . $regoods_attr_str;
                            }
                        }

                        $remgoodres[$k2]['zs_price'] = $reactivitys['price'];
                    } else {
                        $remgoodres[$k2]['zs_price'] = $v2['min_price'];
                    }
                }
            }
        }
        $shopcate = $this->getShopCategory($goods['shop_id']);
        $gpformat = $this->gpresFormat($gpres);
        $sale_top5 = $this->getGoodsSaleTop5($goods['shop_id']);
        $coll_top5 = $this->getGoodsCollTop5($goods['shop_id']);
        $this->assign('goodsinfo', $goodsinfo);
        $this->assign('activity_info', $activity_info);
        $this->assign('goods_attr', $goods_attr);
        $this->assign('goods_attr_str', $goods_attr_str);
        $this->assign('pronum', $pronum);
        $this->assign('gpres', $gpres);
        $this->assign('gpformat', $gpformat);
        $this->assign('radioattr', $radioattr);
        $this->assign('uniattr', $uniattr);
        $this->assign('guige', $guige);
        $this->assign('colores', $colores);
        $this->assign('couponinfos', $couponinfos);
        $this->assign('huodong', $huodong);
        $this->assign('sertions', $sertions);
        $this->assign('shopinfos', $shopinfos);
        $this->assign('remgoodres', $remgoodres);
        $this->assign('shopcate', $shopcate);
        $this->assign('color_arr', $color_arr);
        $this->assign('size_arr', $size_arr);
        $this->assign('sale_top5', $sale_top5);
        $this->assign('coll_top5', $coll_top5);
        return $this->fetch();
    }

    public function get_goods_price(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                $data = input('post.');
                if(!empty($data['goods_id']) && !empty($data['goods_attr'])){
                    if(!is_array($data['goods_attr'])){
                        $goods_id = $data['goods_id'];
                        
                        $data['goods_attr'] = trim($data['goods_attr']);
                        $data['goods_attr'] = str_replace('，', ',', $data['goods_attr']);
                        $data['goods_attr'] = rtrim($data['goods_attr'],',');
                        
                        if($data['goods_attr']){
                            $goods = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.shop_price,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$goods_id)->where('a.onsale',1)->where('b.open_status',1)->find();
                            
                            if($goods){
                                $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.goods_id',$goods_id)->where('b.attr_type',1)->select();
                                if($radiores){
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
                                                if(is_numeric($ga) && strpos($ga,".") === false){
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
                                    }else{
                                        $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                                        return json($value);
                                    }
                            
                                    $goods_attr = implode(',', $gattr);
                                    $goods_name = $goods['goods_name'];
                                    $attr_pic = '';
                            
                                    $ruinfo = array('id'=>$goods['id'],'shop_id'=>$goods['shop_id']);
                                    $ru_attr = $goods_attr;
                                    $gongyong = new GongyongMx();
                                    $activitys = $gongyong->pdrugp($ruinfo,$ru_attr);
                            
                                    $activity_info = array();
                            
                                    if($activitys){
                                        $is_activity = $activitys['ac_type'];
                            
                                        if(!empty($activitys['goods_attr'])){
                                            $goods_attr_str = '';
                                            $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$activitys['goods_attr'])->where('a.goods_id',$goods_id)->where('b.attr_type',1)->select();
                                            if($gares){
                                                foreach ($gares as $key => $val){
                                                    if($key == 0){
                                                        $goods_attr_str = $val['attr_name'].':'.$val['attr_value'];
                                                    }else{
                                                        $goods_attr_str = $goods_attr_str.' '.$val['attr_name'].':'.$val['attr_value'];
                                                    }
                                                }
                                                $goods_name = $goods['goods_name'].' '.$goods_attr_str;
                                            }
                                        }
                            
                                        $zs_shop_price = $activitys['price'];
                            
                                        if($activitys['ac_type'] == 1){
                                            $goods_number = $activitys['kucun'];
                            
                                            $yslv = sprintf("%.2f",$activitys['sold']/$activitys['num'])*100;
                                            $activity_info = array(
                                                'yslv'=>$yslv.'%',
                                                'xznum'=>$activitys['xznum'],
                                                'start_time'=>$activitys['start_time'],
                                                'end_time'=>$activitys['end_time'],
                                                'dqtime' => time()
                                            );
                                        }else{
                                            $prores = Db::name('product')->where('goods_attr',$goods_attr)->where('goods_id',$goods_id)->field('goods_number')->find();
                                            if($prores){
                                                $goods_number = $prores['goods_number'];
                                            }else{
                                                $goods_number = 0;
                                            }
                                            
                                            $activity_info = array(
                                                'start_time'=>$activitys['start_time'],
                                                'end_time'=>$activitys['end_time'],
                                                'dqtime' => time()
                                            ); 
                                        }
                                    }else{
                                        $is_activity = 0;
                            
                                        $gares = Db::name('goods_attr')->where('id','in',$goods_attr)->where('goods_id',$goods_id)->field('id,attr_price')->select();
                                        if($gares){
                                            $zs_shop_price = $goods['shop_price'];
                                            foreach ($gares as $v){
                                                $zs_shop_price+=$v['attr_price'];
                                            }
                                            $zs_shop_price=sprintf("%.2f", $zs_shop_price);
                                            $prores = Db::name('product')->where('goods_attr',$goods_attr)->where('goods_id',$goods_id)->field('goods_number')->find();
                                            if($prores){
                                                $goods_number = $prores['goods_number'];
                                            }else{
                                                $goods_number = 0;
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }
                            
                                    $goodsgares = Db::name('goods_attr')->alias('a')->field('a.attr_value,a.attr_pic,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$goods_attr)->where('a.goods_id',$goods_id)->where('b.attr_type',1)->select();
                                    foreach ($goodsgares as $vd){
                                        if(!empty($vd['attr_pic'])){
                                            $webconfig = $this->webconfig;
                                            $attr_pic = $webconfig['weburl'].'/'.$vd['attr_pic'];
                                        }
                                    }
                            
                                    $attrinfos = array('is_activity'=>$is_activity,'goods_name'=>$goods_name,'attr_pic'=>$attr_pic,'zs_shop_price'=>$zs_shop_price,'goods_number'=>$goods_number);
                            
                                    $goodsinfo = array('attrinfos'=>$attrinfos,'activity_info'=>$activity_info);
                                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$goodsinfo);
                                }else{
                                    $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'商品已下架或不存在','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'商品属性参数错误','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少商品单选属性参数','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'缺少参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //店铺分类
    protected function getShopCategory($shop_id) {
        $shops = Db::name('shops')->where('id',$shop_id)->where('open_status',1)->field('id')->find();
        $cateinfos = array();
        if($shops){
            $cateres = Db::name('shop_cate')->where('shop_id',$shop_id)->where('pid',0)->where('is_show',1)->field('id,cate_name,pid')->order('sort asc')->select();
            foreach ($cateres as $k =>$v){
                $cateres[$k]['twocate'] = Db::name('shop_cate')->where('shop_id',$shop_id)->where('pid',$v['id'])->where('is_show',1)->field('id,cate_name,pid')->order('sort asc')->select();
            }
            $cateinfos = array('shop_id'=>$shop_id,'cateres'=>$cateres);
        }
        return $cateinfos;
    }
    
    //商品相册处理
    protected function gpresFormat($gpres) {
        $gp_format = array();
        $gp_arr = array();
        foreach ($gpres as $v) {
            $gp_format['_small'] = $v['img_url'];
            $gp_format['_mid'] = $v['img_url'];
            $gp_format['_big'] = $v['img_url'];
            array_push($gp_arr, $gp_format);
        }
        return json_encode($gp_arr);
    }
    
    //销售量前5
    protected function getGoodsSaleTop5($shop_id) {
        $where = array('shop_id' => $shop_id, 'onsale' => 1);
        $top5_list = Db::name('goods')->where($where)->field('id, goods_name, zs_price, thumb_url, sale_num')->order('sale_num desc')->limit(5)->select();
        foreach ($top5_list as $key => $v) {
            $top5_list[$key]['thumb_url'] = $this->webconfig['weburl'] . '/' .$v['thumb_url'];
        }
        return $top5_list;
    }
    
    //收藏量前5
    protected function getGoodsCollTop5($shop_id) {
        $where = array('shop_id' => $shop_id, 'onsale' => 1);
        $top5_list = Db::name('goods')->where($where)->field('id, goods_name, zs_price, thumb_url, coll_num')->order('coll_num desc')->limit(5)->select();
        foreach ($top5_list as $key => $v) {
            $top5_list[$key]['thumb_url'] = $this->webconfig['weburl'] . '/' .$v['thumb_url'];
        }
        return $top5_list;
    }
    
}