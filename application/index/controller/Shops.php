<?php
namespace app\index\controller;
use app\index\controller\Common;
use app\index\model\Gongyong as GongyongMx;
use think\Db;

class Shops extends Common{
    
    //通过分类获取商家列表
    public function index() {
        $cate_id = !empty(input('cate_id')) ? input('cate_id') : 0;
        $pagenum = !empty(input('page')) ? input('page') : 1;
        if ($pagenum && preg_match("/^\\+?[1-9][0-9]*$/", $pagenum)) {
            $where = '';
            if ($cate_id) {
                $cates = Db::name('category')->where('id', $cate_id)->where('is_show', 1)->field('id,cate_name,type_id')->find();
            }

            $categoryres = Db::name('category')->where('is_show', 1)->field('id,pid')->order('sort asc')->select();
            $cateIds = array();
            $cateIds = get_all_child($categoryres, $cate_id);
            $cateIds[] = $cate_id;
            $cateIds = implode(',', $cateIds);

            $webconfig = $this->webconfig;
            $perpage = $webconfig['app_goodlst_num'];
            $offset = ($pagenum - 1) * $perpage;

            $shopidarr = Db::name('shop_management')->where('cate_id', 'in', $cateIds)->distinct(true)->field('shop_id')->limit($offset, $perpage)->select();
            if ($shopidarr) {
                $shopidres = array();
                foreach ($shopidarr as $v) {
                    $shopidres[] = $v['shop_id'];
                }
                $shopidres = implode(',', $shopidres);
                $where = "id in (" . $shopidres . ")";
            } else {
                if ($cates['cate_name']) {
                    $where = "find_in_set('" . $cates['cate_name'] . "',search_keywords)";
                }
            }

            if (input('sort')) {
                $sort = input('sort');
                switch ($sort) {
                    case 'zonghe':
                        $sortarr = array('shop_leixing' => 'desc', 'zonghe_fen' => 'desc', 'id' => 'desc');
                        break;
                    case 'deal_num':
                        $sortarr = array('deal_num ' => 'desc', 'id' => 'desc');
                        break;
                    case 'praise_lv':
                        $sortarr = array('praise_lv ' => 'desc', 'id' => 'desc');
                        break;
                    default:
                        $sortarr = array('shop_leixing' => 'desc', 'zonghe_fen' => 'desc', 'id' => 'desc');
                }
            } else {
                $sortarr = array('shop_leixing' => 'desc', 'zonghe_fen' => 'desc', 'id' => 'desc');
            }

            if (!empty($shopidarr)) {
                $shopres = Db::name('shops')->where($where)->where('open_status', 1)->field('id,shop_name,logo,praise_lv,deal_num')->order($sortarr)->select();
            } else {
                $shopres = Db::name('shops')->where($where)->where('open_status', 1)->field('id,shop_name,logo,praise_lv,deal_num')->order($sortarr)->limit($offset, $perpage)->select();
            }

            $webconfig = $this->webconfig;

            if ($shopres) {
                foreach ($shopres as $key => $val) {
                    $shopres[$key]['logo'] = $webconfig['weburl'] . '/' . $val['logo'];

                    if (!empty($shopidarr)) {
                        $shopres[$key]['goodres'] = Db::name('goods')->where('shop_id', $val['id'])->where('cate_id', 'in', $cateIds)->where('onsale', 1)->field('id,goods_name,thumb_url,min_price,zs_price,shop_id')->order('zonghe_lv desc,id asc')->limit(4)->select();
                    } else {
                        $shopres[$key]['goodres'] = Db::name('goods')->where('shop_id', $val['id'])->where('onsale', 1)->field('id,goods_name,thumb_url,min_price,zs_price,shop_id')->order('zonghe_lv desc,id asc')->limit(4)->select();
                    }

                    if ($shopres[$key]['goodres']) {
                        foreach ($shopres[$key]['goodres'] as $key2 => $val2) {
                            $ruinfo = array('id' => $val2['id'], 'shop_id' => $val2['shop_id']);
                            $gongyong = new GongyongMx();
                            $activitys = $gongyong->pdrugp($ruinfo);

                            if ($activitys) {
                                $shopres[$key]['goodres'][$key2]['zs_price'] = $activitys['price'];
                            } else {
                                $shopres[$key]['goodres'][$key2]['zs_price'] = $val2['min_price'];
                            }
                            $shopres[$key]['goodres'][$key2]['thumb_url'] = $webconfig['weburl'] . '/' . $val2['thumb_url'];
                        }
                    }
                }
            }
            $this->assign('shopres', $shopres);
            return $this->fetch();
        } else {
            $this->error('缺少页面参数', $this->gourl);
        }
    }
    
    //获取店铺街列表
    public function shoplist(){
        $pagenum = !empty(input('post.page')) ? input('post.page') : 1;
        if($pagenum && preg_match("/^\\+?[1-9][0-9]*$/", $pagenum)){
            $where = '';
            $webconfig = $this->webconfig;
            $perpage = $webconfig['app_goodlst_num'];
            $offset = ($pagenum-1)*$perpage;

            if(input('post.sort')){
                $sort = input('post.sort');
                switch($sort){
                    case 'zonghe':
                        $sortarr = array('shop_leixing'=>'desc','zonghe_fen'=>'desc','id'=>'desc');
                        break;
                    case 'deal_num':
                        $sortarr = array('deal_num '=>'desc','id'=>'desc');
                        break;
                    case 'praise_lv':
                        $sortarr = array('praise_lv '=>'desc','id'=>'desc');
                        break;
                    default:
                        $sortarr = array('shop_leixing'=>'desc','zonghe_fen'=>'desc','id'=>'desc');
                }
            }else{
                $sortarr = array('shop_leixing'=>'desc','zonghe_fen'=>'desc','id'=>'desc');
            }

            $shopres = Db::name('shops')->where($where)->where('open_status',1)->field('id,shop_name,logo,praise_lv,deal_num')->order($sortarr)->limit($offset,$perpage)->select();

            if($shopres){
                foreach ($shopres as $key => $val){
                    $shopres[$key]['logo'] = $webconfig['weburl'].'/'.$val['logo'];

                    $shopres[$key]['goodres'] = Db::name('goods')->where('shop_id',$val['id'])->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,shop_id')->order('zonghe_lv desc,id asc')->limit(3)->select();

                    if($shopres[$key]['goodres']){
                        foreach ($shopres[$key]['goodres'] as $key2 => $val2){
                            $ruinfo = array('id'=>$val2['id'],'shop_id'=>$val2['shop_id']);
                            $gongyong = new GongyongMx();
                            $activitys = $gongyong->pdrugp($ruinfo);

                            if($activitys){
                                $shopres[$key]['goodres'][$key2]['zs_price'] = $activitys['price'];
                            }else{
                                $shopres[$key]['goodres'][$key2]['zs_price'] = $val2['min_price'];
                            }
                            $shopres[$key]['goodres'][$key2]['thumb_url'] = $webconfig['weburl'].'/'.$val2['thumb_url'];
                        }
                    }
                }
            }
            $this->assign('shopres', $shopres);
            return $this->fetch();
        }else{
            $this->error('缺少页面参数', $this->gourl);
        }
    }

    //获取商家详情接口
    public function shopinfo(){
        $user_id = $this->user_id;
        $shop_id = input('shop_id');
        if($shop_id){
            $shops = Db::name('shops')->where('id',$shop_id)->where('open_status',1)->field('id,shop_name,logo,praise_lv,deal_num')->find();
            if($shops){
                $shops['shop_token'] = '';
                $member_shops = Db::name('member')->where('shop_id',$shop_id)->field('id')->find();
                if($member_shops){
                    $shoptoken_infos = Db::name('rxin')->where('user_id',$member_shops['id'])->field('token')->find();
                    if($shoptoken_infos){
                        $shops['shop_token'] = $shoptoken_infos['token'];
                    }
                }

                $webconfig = $this->webconfig;

                $shops['logo'] = $webconfig['weburl'].'/'.$shops['logo'];

                if($user_id){
                    $colls = Db::name('coll_shops')->where('user_id',$user_id)->where('shop_id',$shop_id)->find();
                    if($colls){
                        $shops['coll_shops'] = 1;
                    }else{
                        $shops['coll_shops'] = 0;
                    }
                }else{
                    $shops['coll_shops'] = 0;
                }

                //优惠券
                $couponres = Db::name('coupon')->where('shop_id',$shop_id)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id,man_price,dec_price,start_time,end_time,shop_id')->order('man_price asc')->select();
                foreach($couponres as $kpu => $vpu){
                    $couponres[$kpu]['start_time'] = date('m-d',$vpu['start_time']);
                    $couponres[$kpu]['end_time'] = date('m-d',$vpu['end_time']);
                    $member_coupons = Db::name('member_coupon')->where('user_id',$user_id)->where('coupon_id',$vpu['id'])->where('shop_id',$vpu['shop_id'])->find();
                    if($member_coupons){
                        $couponres[$kpu]['have'] = 1;
                    }else{
                        $couponres[$kpu]['have'] = 0;
                    }
                }

                //商品活动信息
                $promotionres = Db::name('promotion')->where('shop_id',$shop_id)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,pic_url,info_id,shop_id')->select();
                if($promotionres){
                    foreach ($promotionres as $k3 => $v3){
                        $promotionres[$k3]['pic_url'] = $webconfig['weburl'].'/'.$v3['pic_url'];
                        $info_id = explode(',', $v3['info_id']);
                        $promotionres[$k3]['goods_id'] = $info_id[0];
                    }
                }
                //今日促销
                $rushres = Db::name('rush_activity')->where('shop_id',$shop_id)->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->distinct(true)->field('goods_id')->order('apply_time desc')->select();
                $groupres = Db::name('group_buy')->where('shop_id',$shop_id)->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->distinct(true)->field('goods_id')->order('apply_time asc')->select();
                $assembleres = Db::name('assemble')->where('shop_id',$shop_id)->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->distinct(true)->field('goods_id')->order('apply_time asc')->select();

                $rusharr = array();
                if($rushres){
                    foreach ($rushres as $v){
                        $rusharr[] = $v['goods_id'];
                    }
                }
                if($rusharr){
                    $rusharr = array_unique($rusharr);
                }

                $grouparr = array();
                if($groupres){
                    foreach ($groupres as $v2){
                        $grouparr[] = $v2['goods_id'];
                    }
                }
                if($grouparr){
                    $grouparr = array_unique($grouparr);
                }

                $assemarr = array();
                if($assembleres){
                    foreach ($assembleres as $v2){
                        $assemarr[] = $v2['goods_id'];
                    }
                }
                if($assemarr){
                    $assemarr = array_unique($assemarr);
                }

                $cuxiaohd = array_merge($rusharr,$grouparr,$assemarr);

                $shopcomwz = array();

                $shop_customs = Db::name('shop_custom')->where('shop_id',$shop_id)->where('type',1)->field('info_id')->find();
                if($shop_customs){
                    $shopcomwz = explode(',', $shop_customs['info_id']);
                    $cuxiaoarr = array_merge($cuxiaohd,$shopcomwz);
                }else{
                    $cuxiaoarr = $cuxiaohd;
                }

                $cxgoodres = array();

                if($cuxiaoarr){
                    $cuxiaoarr = array_unique($cuxiaoarr);
                    $cuxiaoarr = implode(',', $cuxiaoarr);
                    $cxgoodres = Db::name('goods')->where('id','in',$cuxiaoarr)->where('shop_id',$shop_id)->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,leixing,shop_id,sale_num')->order('zonghe_lv desc,id asc')->select();
                    if($cxgoodres){
                        foreach ($cxgoodres as $k =>$v){
                            $cxgoodres[$k]['thumb_url'] = $webconfig['weburl'].'/'.$v['thumb_url'];
                            $cxgoodres[$k]['coupon'] = 0;

                            //优惠券
                            $coupons = Db::name('coupon')->where('shop_id',$v['shop_id'])->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->find();
                            if($coupons){
                                $cxgoodres[$k]['coupon'] = 1;
                            }

                            $ruinfo = array('id'=>$v['id'],'shop_id'=>$v['shop_id']);
                            $gongyong = new GongyongMx();
                            $activitys = $gongyong->pdrugp($ruinfo);

                            if($activitys){
                                $cxgoodres[$k]['is_activity'] = $activitys['ac_type'];

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
                                        $cxgoodres[$k]['goods_name'] = $v['goods_name'].' '.$goods_attr_str;
                                    }
                                }

                                $cxgoodres[$k]['zs_price'] = $activitys['price'];
                            }else{
                                $cxgoodres[$k]['is_activity'] = 0;
                                $cxgoodres[$k]['zs_price'] = $v['min_price'];
                            }
                        }
                    }
                }
                $shopcate = $this->getShopCategory($shop_id);
                $sale_top5 = $this->getGoodsSaleTop5($shop_id);
                $coll_top5 = $this->getGoodsCollTop5($shop_id);
                $shopinfores = array(
                    'shops'=>$shops,
                    'couponres'=>$couponres,
                    'promotionres'=>$promotionres,
                    'cxgoodres'=>$cxgoodres,
                    'shopcate' => $shopcate,
                    'sale_top5' => $sale_top5,
                    'coll_top5' => $coll_top5
                );
                $this->assign($shopinfores);
                return $this->fetch();
            }else{
                $this->error('找不到相关商家信息', $this->gourl);
            }
        }else{
            $this->error('缺少商家参数', $this->gourl);
        }
    }
    
    //获取商家全部商品
    public function allgoods(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                if(input('post.shop_id')){
                    if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                        $shop_id = input('post.shop_id');
                        $pagenum = input('post.page');
        
                        $shops = Db::name('shops')->where('id',$shop_id)->where('open_status',1)->field('id')->find();
                        if($shops){
                            $where = array();
                            $where['shop_id'] = $shop_id;
                            $where['onsale'] = 1;
        
                            $webconfig = $this->webconfig;
                            $perpage = $webconfig['app_goodlst_num'];
                            $offset = ($pagenum-1)*$perpage;
        
                            if(input('post.sort')){
                                $sort = input('post.sort');
                                switch($sort){
                                    case 'zonghe':
                                        $sortarr = array('zonghe_lv'=>'desc','id'=>'asc');
                                        break;
                                    case 'new':
                                        $sortarr = array('addtime'=>'desc','id'=>'asc');
                                        break;
                                    case 'deal_num':
                                        $sortarr = array('deal_num '=>'desc','id'=>'asc');
                                        break;
                                    case 'low_height':
                                        $sortarr = array('zs_price'=>'asc','id'=>'asc');
                                        break;
                                    case 'height_low':
                                        $sortarr = array('zs_price'=>'desc','id'=>'asc');
                                        break;
                                    default:
                                        $sortarr = array('zonghe_lv'=>'desc','id'=>'asc');
                                }
                            }else{
                                $sortarr = array('zonghe_lv'=>'desc','id'=>'asc');
                            }
        
                            $goodres = Db::name('goods')->where($where)->field('id,goods_name,thumb_url,min_price,zs_price,leixing,shop_id')->order($sortarr)->limit($offset,$perpage)->select();
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
                            $value = array('status'=>200,'mess'=>'获取商家商品信息成功','data'=>$goodres);
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关商家信息','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少页面参数','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'缺少商家参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //商家商品列表信息
    public function shopgoodres(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                if(input('post.shop_id')){
                    if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                        $shop_id = input('post.shop_id');
                        $pagenum = input('post.page');
    
                        $shops = Db::name('shops')->where('id',$shop_id)->where('open_status',1)->field('id')->find();
                        if($shops){
                            $where = array();
                            $where['shop_id'] = $shop_id;
                            $where['onsale'] = 1;
    
                            $webconfig = $this->webconfig;
                            $perpage = $webconfig['app_goodlst_num'];
                            $offset = ($pagenum-1)*$perpage;
    
                            if(input('post.shcate_id')){
                                $shcate_id = input('post.shcate_id');
                                $shcates = Db::name('shop_cate')->where('id',$shcate_id)->where('shop_id',$shop_id)->where('is_show',1)->field('id,cate_name')->find();
                                if($shcates){
                                    $shcateres = Db::name('shop_cate')->where('shop_id',$shop_id)->where('is_show',1)->field('id,pid')->order('sort asc')->select();
                                    $cateIds = array();
                                    $cateIds = get_all_child($shcateres, $shcate_id);
                                    $cateIds[] = $shcate_id;
                                    $cateIds = implode(',', $cateIds);
                                    $where['shcate_id'] = array('in',$cateIds);
                                }else{
                                    $value = array('status'=>400,'mess'=>'找不到相关分类信息','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }
    
                            if(input('post.sort')){
                                $sort = input('post.sort');
                                switch($sort){
                                    case 'zonghe':
                                        $sortarr = array('zonghe_lv'=>'desc','id'=>'asc');
                                        break;
                                    case 'new':
                                        $sortarr = array('addtime'=>'desc','id'=>'asc');
                                        break;
                                    case 'deal_num':
                                        $sortarr = array('deal_num '=>'desc','id'=>'asc');
                                        break;
                                    case 'low_height':
                                        $sortarr = array('zs_price'=>'asc','id'=>'asc');
                                        break;
                                    case 'height_low':
                                        $sortarr = array('zs_price'=>'desc','id'=>'asc');
                                        break;
                                    default:
                                        $sortarr = array('zonghe_lv'=>'desc','id'=>'asc');
                                }
                            }else{
                                $sortarr = array('zonghe_lv'=>'desc','id'=>'asc');
                            }
    
                            $goodres = Db::name('goods')->where($where)->field('id,goods_name,thumb_url,min_price,zs_price,leixing,shop_id')->order($sortarr)->limit($offset,$perpage)->select();
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
                            $value = array('status'=>200,'mess'=>'获取商家商品信息成功','data'=>$goodres);
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关商家信息','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少页面参数','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'缺少商家参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //获取店铺详细信息
    public function getshops(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                if(input('post.shop_id')){
                    $shop_id = input('post.shop_id');
                    $shops = Db::name('shops')->where('id',$shop_id)->where('open_status',1)->field('id,shop_name,logo,shop_desc,praise_lv,goods_fen,fw_fen,wuliu_fen')->find();
                    if($shops){
                        $webconfig = $this->webconfig;
                        $shops['logo'] = $webconfig['weburl'].'/'.$shops['logo'];
                        $value = array('status'=>200,'mess'=>'获取商家信息成功','data'=>$shops);
                    }else{
                        $value = array('status'=>400,'mess'=>'找不到相关商家信息','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'缺少商家参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //获取商家分类信息
    public function shopcates(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                if(input('post.shop_id')){
                    $shop_id = input('post.shop_id');
                    $shops = Db::name('shops')->where('id',$shop_id)->where('open_status',1)->field('id')->find();
                    if($shops){
                        $cateres = Db::name('shop_cate')->where('shop_id',$shop_id)->where('pid',0)->where('is_show',1)->field('id,cate_name,pid')->order('sort asc')->select();
                        foreach ($cateres as $k =>$v){
                            $cateres[$k]['twocate'] = Db::name('shop_cate')->where('shop_id',$shop_id)->where('pid',$v['id'])->where('is_show',1)->field('id,cate_name,pid')->order('sort asc')->select();
                        }
                        $cateinfos = array('shop_id'=>$shop_id,'cateres'=>$cateres);
                        $value = array('status'=>200,'mess'=>'获取商家分类成功','data'=>$cateinfos);
                    }else{
                        $value = array('status'=>400,'mess'=>'找不到相关商家信息','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'缺少商家参数','data'=>array('status'=>400));
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

    //获取商家促销活动信息
    public function getprominfo(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                if(input('post.shop_id')){
                    if(input('post.goods_id')){
                        if(input('post.prom_id')){
                            $shop_id = input('post.shop_id');
                            $goods_id = input('post.goods_id');
                            $prom_id = input('post.prom_id');
                            
                            $promotions = Db::name('promotion')->alias('a')->field('a.id,a.start_time,a.end_time,a.info_id,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$prom_id)->where('a.shop_id',$shop_id)->where("find_in_set('".$goods_id."',a.info_id)")->where('a.is_show',1)->where('a.start_time','elt',time())->where('a.end_time','gt',time())->where('b.open_status',1)->find();
                            
                            if($promotions){
                                $promoinfos = array();
                                $start_time = date('Y年m月d日 H时',$promotions['start_time']);
                                $end_time = date('Y年m月d日 H时',$promotions['end_time']);
                                $prom_typeres = Db::name('prom_type')->where('prom_id',$promotions['id'])->select();
                                if($prom_typeres){
                                    $promotion_infos = '';
                                    
                                    foreach ($prom_typeres as $kcp => $vcp){
                                        $zhekou = $vcp['discount']/10;
                                        if($kcp == 0){
                                            $promotion_infos = '商品满 '.$vcp['man_num'].'件 享'.$zhekou.'折';
                                        }else{
                                            $promotion_infos = $promotion_infos.'  满 '.$vcp['man_num'].'件 享'.$zhekou.'折';
                                        }
                                    }
                                    
                                    $promoinfos['id'] = $promotions['id'];
                                    $promoinfos['hd_name'] = $promotion_infos;
                                    $promoinfos['time'] = '有效期：'.$start_time.'至'.$end_time.'截止';
                                    $promoinfos['start_time'] = $promotions['start_time'];
                                    $promoinfos['end_time'] = $promotions['end_time'];
                                    $promoinfos['dqtime'] = time();
                                    
                                    $value = array('status'=>200,'mess'=>'获取促销活动信息成功','data'=>$promoinfos);
                                }else{
                                    $value = array('status'=>400,'mess'=>'找不到相关活动信息或活动已过期','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关活动信息','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少活动参数','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少商品参数','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'缺少商家参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);        
    }
    
    
    public function getprolst(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                if(input('post.shop_id')){
                    if(input('post.goods_id')){
                        if(input('post.prom_id')){
                            if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                                $shop_id = input('post.shop_id');
                                $goods_id = input('post.goods_id');
                                $prom_id = input('post.prom_id');
                                $pagenum = input('post.page');
                                
                                $webconfig = $this->webconfig;
                                $perpage = $webconfig['app_goodlst_num'];
                                $offset = ($pagenum-1)*$perpage;
                        
                                $promotions = Db::name('promotion')->alias('a')->field('a.id,a.start_time,a.end_time,a.info_id,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$prom_id)->where('a.shop_id',$shop_id)->where("find_in_set('".$goods_id."',a.info_id)")->where('a.is_show',1)->where('a.start_time','elt',time())->where('a.end_time','gt',time())->where('b.open_status',1)->find();
                                
                                if($promotions){
                                    $goodres = Db::name('goods')->where('id','in',$promotions['info_id'])->where('shop_id',$shop_id)->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,leixing,shop_id')->order('zonghe_lv desc,id asc')->limit($offset,$perpage)->select();
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
                                    $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>$goodres);
                                }else{
                                    $value = array('status'=>400,'mess'=>'找不到相关活动信息','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'缺少页面参数','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少活动参数','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少商品参数','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'缺少商家参数','data'=>array('status'=>400));
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