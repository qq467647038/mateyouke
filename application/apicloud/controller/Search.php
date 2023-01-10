<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class Search extends Common{

    //搜索分类和商品
    public function searchgoods(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
//            if($result['status'] == 200){

                if(input('post.keyword_name') || (!input('post.keyword_name') && input('post.filter') == 1)){
                    if(mb_strlen(input('post.keyword_name'),'UTF8') <= 50){
                        if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                            $keyword_name = input('post.keyword_name');
                            $pagenum = input('post.page');

                            $where1 = '';

                            $webconfig = $this->webconfig;
                            $perpage = $webconfig['app_goodlst_num'];
                            $offset = ($pagenum-1)*$perpage;


                            // 根据商品分类中的关键词关联搜索
                            $cates = Db::name('category')->where('is_show',1)->where("find_in_set('".$keyword_name."',search_keywords)")->field('id,type_id')->find();
                            if($cates){
                                $cate_id = $cates['id'];
                                $categoryres = Db::name('category')->where('is_show',1)->field('id,pid')->order('sort asc')->select();
                                $cateIds = array();
                                $cateIds = get_all_child($categoryres, $cate_id);
                                $cateIds[] = $cate_id;
                                $cateIds = implode(',', $cateIds);
                                
                                $where1 = "a.cate_id in (".$cateIds.")";
                            }else{
                                // 品牌名称关联
                                $brands = Db::name('brand')->where('is_show',1)->where('brand_name',$keyword_name)->field('id')->find();
                                if($brands){
                                    $where1 = "a.brand_id = ".$brands['id']."";
                                }else{
                                    $where1 = "find_in_set('".$keyword_name."',a.search_keywords)";
                                }
                            }
                            $where8 = '1=1';
                            if ((!input('post.keyword_name') && input('post.filter') == 1)){
                                $where1 = '1=1';
                            }else{
                                $where8 = "a.search_keywords like '%$keyword_name%' or keywords like '%$keyword_name%' or a.goods_name like '%$keyword_name%' ";
                            }
                            $where2 = "a.onsale = 1";
                            $where3 = '';
                            $where4 = '';
                            $where5 = '';
                            $where6 = '';
                            
                            if(input('post.goods_type') && input('post.goods_type') != 'all'){
                                $goods_type = input('post.goods_type');
                                switch($goods_type){
                                    case 1:
                                        $where3 = "a.leixing = 1"; // leixing:1自营，2商家
                                        break;
                                    case 2:
                                        $where3 = "a.is_activity = 1";
                                        break;
                                }
                            }

                            if(input('post.low_price') && input('post.height_price')){
                                $low_price = input('post.low_price');
                                $height_price = input('post.height_price');
                            
                                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $low_price)){
                                    $value = array('status'=>400,'mess'=>'最低价格格式错误','data'=>array('status'=>400));
                                    return json($value);
                                }
                            
                                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $height_price)){
                                    $value = array('status'=>400,'mess'=>'最高价格格式错误','data'=>array('status'=>400));
                                    return json($value);
                                }
                            
                                if($low_price >= $height_price){
                                    $value = array('status'=>400,'mess'=>'最低价格需小于最大价格','data'=>array('status'=>400));
                                    return json($value);
                                }
                            
                                $where4 = "a.zs_price >= '".$low_price."' AND a.zs_price <= '".$height_price."'";
                            }elseif(input('post.low_price') && !input('post.height_price')){
                                $low_price = input('post.low_price');
                            
                                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $low_price)){
                                    $value = array('status'=>400,'mess'=>'最低价格格式错误','data'=>array('status'=>400));
                                    return json($value);
                                }
                            
                                $where4 = "a.zs_price >= '".$low_price."'";
                            }elseif(!input('post.low_price') && input('post.height_price')){
                                $height_price = input('post.height_price');
                            
                                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $height_price)){
                                    $value = array('status'=>400,'mess'=>'最高价格格式错误','data'=>array('status'=>400));
                                    return json($value);
                                }
                            
                                $where4 = "a.zs_price <= '".$height_price."'";
                            }
                            
                            if(!empty($cates)){
                                if(input('post.brand_id') && input('post.brand_id') != 'all'){
                                    $brand_id = input('post.brand_id');
                                    $where5 = "a.brand_id = ".$brand_id."";
                                }
                                
                                if(input('post.goods_attr')){
                                    $goods_attr = input('post.goods_attr');
                                    $goods_attr = trim($goods_attr);
                                    $goods_attr = str_replace('，', ',', $goods_attr);
                                    $goods_attr = rtrim($goods_attr,',');
                                    $goods_attr = explode(',', $goods_attr);
                                
                                    if(!$goods_attr || !is_array($goods_attr)){
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
                                    case 'is_hot':
                                        $sortarr = array('a.sale_num'=>'desc','a.id'=>'desc');
                                        break;
                                    case 'is_new':
                                        $sortarr = array('a.addtime'=>'desc','a.id'=>'desc');
                                        break;
                                    case 'is_special':
                                        $sortarr = array('a.market_price'=>'asc','a.id'=>'desc');
                                        break;
                                    default:
                                        $sortarr = array('a.leixing'=>'desc','a.zonghe_lv'=>'desc','a.id'=>'desc');
                                }
                            }else{
                                $sortarr = array('a.leixing'=>'desc','a.zonghe_lv'=>'desc','a.id'=>'desc');
                            }

                            $where7 = '';

                            if (input('post.specialcondition')){
                                $where7 = input('post.specialcondition').'=1';
                            }

                            $goodres = Db::name('goods')
                                ->alias('a')
                                ->field('a.id,a.goods_name,a.thumb_url,a.min_price,a.zs_price,a.leixing,a.shop_id')
                                ->join('sp_shops b','a.shop_id = b.id','INNER')
                                ->where($where2)
                                ->whereOr($where1)
                                ->where($where3)
                                ->where($where4)
                                ->where($where5)
                                ->where($where6)
                                ->where($where7)
                                ->where($where8)
                                ->where("b.open_status = 1")
                                ->order($sortarr)
                                ->limit($offset,$perpage)
                                ->select();
                            $webconfig = $this->webconfig;

                            if($goodres){
                                foreach ($goodres as $k =>$v){
                                    // $goodres[$k]['thumb_url'] = $webconfig['weburl'].'/'.$v['thumb_url'];
                                    $goodres[$k]['coupon'] = 0;
                                    
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
                                    
                                    if(!$activitys || in_array($activitys['ac_type'], array(1,2))){
                                        //优惠券
                                        $coupons = Db::name('coupon')->where('shop_id',$v['shop_id'])->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->find();
                                        if($coupons){
                                            $goodres[$k]['coupon'] = 1;
                                        }
                                    }
                                }
                            }
                            
                            if(!empty($cates)){
                                if($pagenum == 1){
                                    $brandres = Db::name('brand')->where('find_in_set('.$cate_id.',cate_id_list)')->where('is_show',1)->field('id,brand_name')->select();
                            
                                    $shaixuan = Db::name('attr')->where('type_id',$cates['type_id'])->where('is_sear',1)->field('id,attr_name,attr_values')->select();
                                    if($shaixuan){
                                        foreach ($shaixuan as $k =>$v){
                                            $shaixuan[$k]['attr_values'] = explode(',',  $shaixuan[$k]['attr_values']);
                                        }
                                    }
                            
                                    $keyname = $keyword_name;
                                }
                            }elseif(!empty($brands)){
                                if($pagenum == 1){
                                    $brandres = array();
                                    $shaixuan = array();
                                    $keyname = $keyword_name;
                                }
                            }else{
                                if($pagenum == 1){
                                    $brandres = array();
                                    $shaixuan = array();
                                    $keyname = $keyword_name;
                                }
                            }
                            
                            if($pagenum == 1){
                                $goodlstinfo = array('keyword_name'=>$keyname,'goodres'=>$goodres,'brandres'=>$brandres,'shaixuan'=>$shaixuan);
                            }else{
                                $goodlstinfo = array('goodres'=>$goodres);
                            }
                            $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>$goodlstinfo);
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少页面参数','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'搜索内容最多50个字符','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'搜索内容不能为空','data'=>array('status'=>400));
                }
//            }else{
//                $value = $result;
//            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);        
    }
    
    
    //搜索商家
    public function searchshops(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                if(input('post.keyword_name')){
                    if(mb_strlen(input('post.keyword_name'),'UTF8') <= 50){
                        if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                            $keyword_name = input('post.keyword_name');
                            $pagenum = input('post.page');
                            
                            $where1 = '';
                            
                            $webconfig = $this->webconfig;
                            $perpage = $webconfig['app_goodlst_num'];
                            $offset = ($pagenum-1)*$perpage;
                            
                            $cates = Db::name('category')->where('is_show',1)->where("find_in_set('".$keyword_name."',search_keywords)")->field('id,type_id')->find();
                            if($cates){
                                $cate_id = $cates['id'];
                                $categoryres = Db::name('category')->where('is_show',1)->field('id,pid')->order('sort asc')->select();
                                $cateIds = array();
                                $cateIds = get_all_child($categoryres, $cate_id);
                                $cateIds[] = $cate_id;
                                $cateIds = implode(',', $cateIds);
                                
                                $shopidarr = Db::name('shop_management')->where('cate_id','in',$cateIds)->distinct(true)->field('shop_id')->limit($offset,$perpage)->select();
                                if($shopidarr){
                                    $shopidres = array();
                                    foreach($shopidarr as $v){
                                        $shopidres[] = $v['shop_id'];
                                    }
                                    $shopidres = implode(',',$shopidres);
                                    $where1 = "id in (".$shopidres.")";
                                }else{
                                    $where1 = "find_in_set('".$keyword_name."',search_keywords)";
                                }
                            }else{
                                $brands = Db::name('brand')->where('is_show',1)->where('brand_name',$keyword_name)->field('id')->find();
                                if($brands){
                                    $shopidarr = Db::name('shop_managebrand')->where('brand_id',$brands['id'])->distinct(true)->field('shop_id')->limit($offset,$perpage)->select();
                                    if($shopidarr){
                                        $shopidres = array();
                                        foreach($shopidarr as $v){
                                            $shopidres[] = $v['shop_id'];
                                        }
                                        $shopidres = implode(',',$shopidres);
                                        $where1 = "id in (".$shopidres.")";
                                    }else{
                                        $where1 = "find_in_set('".$keyword_name."',search_keywords)";
                                    }
                                }else{
                                    $shopidarr = array();
                                    $where1 = "find_in_set('".$keyword_name."',search_keywords)";
                                }
                            }
                            
                            if(input('post.sort')){
                                $sort = input('post.sort');
                                switch($sort){
                                    case 'zonghe':
                                        $sortarr = array('shop_leixing'=>'asc','zonghe_fen'=>'desc','id'=>'asc');
                                        break;
                                    case 'deal_num':
                                        $sortarr = array('deal_num '=>'desc','id'=>'asc');
                                        break;
                                    case 'praise_lv':
                                        $sortarr = array('praise_lv '=>'desc','id'=>'asc');
                                        break;
                                    default:
                                        $sortarr = array('shop_leixing'=>'asc','zonghe_fen'=>'desc','id'=>'asc');
                                }
                            }else{
                                $sortarr = array('shop_leixing'=>'asc','zonghe_fen'=>'desc','id'=>'asc');
                            }
                            
                            if(!empty($shopidarr)){
                                $shopres = Db::name('shops')->where($where1)->where('open_status',1)->field('id,shop_name,logo,praise_lv,deal_num')->order($sortarr)->select();
                            }else{
                                $shopres = Db::name('shops')->where($where1)->where('open_status',1)->field('id,shop_name,logo,praise_lv,deal_num')->order($sortarr)->limit($offset,$perpage)->select();
                            }
                            
                            
                            $webconfig = $this->webconfig;
                            
                            if($shopres){
                                foreach ($shopres as $key => $val){
                                    // $shopres[$key]['logo'] = $webconfig['weburl'].'/'.$val['logo'];
                                    
                                    if(!empty($cates) && !empty($shopidarr)){
                                        $shopres[$key]['goodres'] = Db::name('goods')->where('shop_id',$val['id'])->where('cate_id','in',$cateIds)->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,shop_id')->order('zonghe_lv desc,id asc')->limit(3)->select();
                                    }elseif(!empty($cates) && empty($shopidarr)){
                                        $shopres[$key]['goodres'] = Db::name('goods')->where('shop_id',$val['id'])->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,shop_id')->order('zonghe_lv desc,id asc')->limit(3)->select();
                                    }elseif(!empty($brands) && !empty($shopidarr)){
                                        $shopres[$key]['goodres'] = Db::name('goods')->where('shop_id',$val['id'])->where('brand_id',$brands['id'])->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,shop_id')->order('zonghe_lv desc,id asc')->limit(3)->select();
                                    }elseif(!empty($brands) && empty($shopidarr)){
                                        $shopres[$key]['goodres'] = Db::name('goods')->where('shop_id',$val['id'])->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,shop_id')->order('zonghe_lv desc,id asc')->limit(3)->select();
                                    }else{
                                        $shopres[$key]['goodres'] = Db::name('goods')->where('shop_id',$val['id'])->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,shop_id')->order('zonghe_lv desc,id asc')->limit(3)->select();
                                    }
                                    
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
                                            $shopres[$key]['goodres'][$key2]['thumb_url'] = $val2['thumb_url'];
                                        }
                                    }
                                }
                            }
                            $value = array('status'=>200,'mess'=>'获取商家信息成功','data'=>$shopres);
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少页面参数','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'搜索内容最多50个字符','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'搜索内容不能为空','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    /**
     * @function发送宝贝页面商品列表
     * @author Feifan.Chen <1057286925@qq.com>
     * @return \think\response\Json
     */
    public function sendGoods()
    {
        if (request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if ($result['status'] == 200) {
                $webconfig = $this->webconfig;
                $keyword = trim(input('keyword'));
                $shop_id = input('shop_id');
                $shop_id = empty($shop_id) ? 1 : $shop_id;

                $where = '1=1';
                if (!empty($keyword)) {
                    $where = " a.search_keywords = '$keyword' or goods_name like '%$keyword%'";
                }
                $goodres = Db::name('goods')
                    ->alias('a')
                    ->field('a.id,a.goods_name,a.thumb_url,a.min_price,a.zs_price,a.leixing,a.shop_id')
                    ->join('sp_shops b', 'a.shop_id = b.id', 'INNER')
                    ->where("b.open_status = 1")
                    ->where('a.shop_id', $shop_id)
                    ->where('a.onsale',1)
                    ->where($where)
                    ->select();
                // foreach ($goodres as $k=>&$v){
                //     $v['thumb_url'] = $webconfig['weburl'].'/'.$v['thumb_url'];
                // }

                $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>$goodres);
            } else {
                $value = $result;
            }

        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
}
