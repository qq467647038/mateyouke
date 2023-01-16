<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use app\apicloud\model\Member;
use app\apicloud\model\MemberBrowse;
use think\Db;
use app\apicloud\model\Category as CategoryModel;
use app\apicloud\model\Goods as GoodsModel;

class Goods extends Common{
    //根据分类获取商品列表
    public function getlsts(){
        $cate_id = input('post.cate_id');
        $type = input('post.type');

        $cate_id_arr = CategoryModel::where('pid', $cate_id)->column('id');

        $list = Db::name('goods')->where('type', $type)->whereIn('cate_id', $cate_id_arr)->where('onsale', 1)->limit(8)->select();
        foreach ($list as $k =>$v){
            $list[$k]['thumb_url'] = $this->webconfig['weburl'].'/'.$v['thumb_url'];
        }

        $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>$list);

        return json($value);
    }

//    public function getlsts(){
//        $cate_id = input('post.cate_id');
//
//        $cate_id_arr = CategoryModel::where('pid', $cate_id)->column('id');
//
//        $list = CategoryModel::with(['goods'=>function($query){
//            $query->limit(2);
//        }])->whereIn('id', $cate_id_arr)->paginate()->toArray()['data'];
//
//        $list = array_column($list, 'goods');
//        foreach ($list as $k =>$v){
//            foreach ($v as $k1 =>$v1) {
//                $list[$k][$k1]['thumb_url'] = $this->webconfig['weburl'] . '/' . $v1['thumb_url'];
//            }
//        }
//
//        $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>$list);
//
//        return json($value);
//    }


    public function uploadProof(){
        $input = input('post.');

        //图片文件夹判断
        $dirName = "public/uploads/proof/goods/" ;
        if(!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }
        if($input['isCover'] == 1){
            $file = request()->file('image');
            if($file){

                $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg,JPG,PNG,JPEG'])->move(ROOT_PATH . $dirName);

                if($info){
                    $original = 'uploads/proof/goods/' .$info->getSaveName();

                    $imgurl = $original;
                }else{
                }
            }
        }

        if($input['order_number']){
            $zong_id = Db::name('order_zong')->where('order_number', $input['order_number'])->value('id');

            $res = Db::name('order')->where('zong_id', $zong_id)->update([
                'proof_img' => $imgurl,
                'proof_state' => 1
            ]);

            $value = array('status'=>200,'mess'=>'上传凭证成功','data'=>'');
        }
        else
        {
            $value = array('status'=>400,'mess'=>'上传凭证失败','data'=>array('status'=>400));
        }

        return json($value);
    }

    // 查询出推荐的商品
    public function recommentGoods(){
        if(request()->isPost()) {
            $goods_id = input('goods_id');

            $cate_id = Db::name('goods')->where('id', $goods_id)->value('cate_id');


            $goods_id_arr = Db::name('goods')->where('is_recommend', 1)->where('onsale', 1)->where('is_recycle', 0)->where('checked', 1)->where('cate_id', $cate_id)->where('id', 'neq', $goods_id)->column('id');

            $length = count($goods_id_arr)-1;
            $sd = [];
            for ($i=0; $i<4; $i++){
                $rand_num = rand(0, $length);

                array_push($sd, $goods_id_arr[$rand_num]);
                unset($goods_id_arr[$rand_num]);
                --$length;
                sort($goods_id_arr);
            }

            $goods_list = Db::name('goods')->where('id', 'in', $sd)->select();
            // if ($goods_list){
            //     foreach($goods_list as $k1=>$v1){
            //         $goods_list[$k1]['thumb_url'] = $this->webconfig['weburl'].$v1['thumb_url'];
            //     }
            // }

            $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$goods_list);
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }

        return json($value);
    }

    //根据分类获取商品列表
    public function getlst($cate_id = 0, $page = 1){
//        echo 1;exit;
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                if(input('post.cate_id', $cate_id)){
                    if(input('post.page', $page) && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page', $page))){
                        $cate_id = input('post.cate_id', $cate_id);

                        $pagenum = input('post.page', $page);
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
//                                        $sortarr = array('a.leixing'=>'desc','a.zonghe_lv'=>'desc','a.id'=>'desc');
                                        $sortarr = array('a.id'=>'desc');
                                        break;
                                    case 'deal_num':
//                                        $sortarr = array('a.deal_num '=>'desc','a.id'=>'desc');
                                        $sortarr = array('a.sale_num '=>'desc','a.id'=>'desc');

                                        break;
                                    case 'low_height':
                                        $sortarr = array('a.zs_price'=>'asc','a.sort'=>'asc','a.id'=>'desc');
                                        break;
                                    case 'height_low':
                                        $sortarr = array('a.zs_price'=>'desc','a.sort'=>'asc','a.id'=>'desc');
                                        break;
                                    default:
//                                        $sortarr = array('a.leixing'=>'desc','a.zonghe_lv'=>'desc','a.id'=>'desc');
                                        $sortarr = array('a.id'=>'desc');

                                }
                            }else{
//                                $sortarr = array('a.leixing'=>'desc','a.zonghe_lv'=>'desc','a.id'=>'desc');
                                $sortarr = array('a.id'=>'desc');
                            }

                            $type = input('type', 0);
                            $goodres = Db::name('goods')->alias('a')->where('a.type', $type)->field('a.id,a.goods_name,a.sale_num,a.fictitious_sale_num,a.thumb_url,a.min_price,a.zs_price,a.leixing,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where1)->where($where2)->where($where3)->where($where4)->where($where5)->where($where6)->where("b.open_status = 1")->order($sortarr)->limit($offset,$perpage)->select();
                            // var_dump($goodres);exit;
                            // halt(Db::name('goods')->getLastSql());
                            if($goodres){
                                foreach ($goodres as $k =>$v){
                                    $goodres[$k]['thumb_url'] = $v['thumb_url'];
                                    $goodres[$k]['coupon'] = 0;
                                    $goodres[$k]['sale_num'] = $v['sale_num']+$v['fictitious_sale_num'];
                                
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

    // 获取标签（新品、热销）商品列表
    public function getTagGoodsList(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                if(input('post.tag')){
                    if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                        $tag = input('post.tag');
                        $pagenum = input('post.page');

                        if($tag == 'new') {
                            $where1 = "a.is_new = 1";
                        }
                        $webconfig = $this->webconfig;
                        $perpage = $webconfig['app_goodlst_num'];
                        $offset = ($pagenum - 1) * $perpage;
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
//                                    $sortarr = array('a.leixing'=>'desc','a.zonghe_lv'=>'desc','a.id'=>'desc');
                                    $sortarr = array('a.id'=>'desc');
                                    break;
                                case 'deal_num':
//                                    $sortarr = array('a.deal_num '=>'desc','a.id'=>'desc');
                                    $sortarr = array('a.sale_num '=>'desc','a.id'=>'desc');

                                    break;
                                case 'low_height':
                                    $sortarr = array('a.zs_price'=>'asc','a.id'=>'desc','a.sort'=>'asc');
                                    break;
                                case 'height_low':
                                    $sortarr = array('a.zs_price'=>'desc','a.id'=>'desc','a.sort'=>'asc');
                                    break;
                                default:
//                                    $sortarr = array('a.leixing'=>'desc','a.zonghe_lv'=>'desc','a.id'=>'desc');
                                    $sortarr = array('a.id'=>'desc');

                            }
                        }else{
//                            $sortarr = array('a.leixing'=>'desc','a.zonghe_lv'=>'desc','a.id'=>'desc');
                            $sortarr = array('a.id'=>'desc');
                        }

                        $goodres = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.min_price,a.zs_price,a.leixing,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where1)->where($where2)->where($where3)->where($where4)->where($where5)->where($where6)->where("b.open_status = 1")->order($sortarr)->limit($offset,$perpage)->select();

                        if($goodres){
                            foreach ($goodres as $k =>$v){
                                $goodres[$k]['thumb_url'] = $v['thumb_url'];
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

                        if($pagenum == 1){
                            $brandres = Db::name('brand')->where('is_show',1)->field('id,brand_name')->select();

//                            $shaixuan = Db::name('attr')->where('type_id',$cates['type_id'])->where('is_sear',1)->field('id,attr_name,attr_values')->select();
//                            if($shaixuan){
//                                foreach ($shaixuan as $key2 => $val2){
//                                    $shaixuan[$key2]['attr_values'] = explode(',',  $val2['attr_values']);
//                                }
//                            }

//                            $cateinfos = array('id'=>$cates['id'],'cate_name'=>$cates['cate_name']);

                            $goodlstinfo = array('goodres'=>$goodres);
                        }else{
                            $goodlstinfo = array('goodres'=>$goodres);
                        }

                            $value = array('status'=>200,'mess'=>'获取商品信息成功','data'=>$goodlstinfo);

                    }else{
                        $value = array('status'=>400,'mess'=>'缺少页面参数','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'缺少标签参数','data'=>array('status'=>400));
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
    public function goodsinfo(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            $is_vip = 0;
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                // 土特产商品 - 非旅游型
                // $category_id_arr = Db::name('category')->where('pid', 348)->whereOr('id', 348)->column('id');

                //vip商品
                // $vip_goods = Db::name('travel_distribute_profit')
                //     ->where('id',1)->value('goods_id');
                // if(!empty($result['user_id'])){
                //     $user_id = $result['user_id'];
                //     $is_vip = Db::name('member')->where('id',$user_id)->value('is_vip');
                //     bandPid($user_id,input('post.shareid'));
                // }else{
                //     $user_id = 0;
                // }

                $pin_id = '';
                $tuan_id = '';
                $memberpinres = array();
                
                if(input('post.goods_id')){
                    $goods_id = input('post.goods_id');
                    $goods = Db::name('goods')->alias('a')->field('a.sale_num,a.fictitious_sale_num,a.cate_id,a.id,a.vip_price,a.goods_name,a.thumb_url,a.shop_price,a.min_market_price,a.max_market_price,a.min_price,a.max_price,a.zs_price,a.goods_desc,a.fuwu,a.is_free,a.leixing,a.is_activity,a.shop_id,a.type')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$goods_id)->where('a.onsale',1)->where('b.open_status',1)->find();
                    if($goods){
                        // if(in_array($goods['cate_id'], $category_id_arr)){
                        //     // 土特产商品
                        //     $goods['goods_type'] = 'specialty';
                        // }else{
                        //     // 旅游商品
                        //     $goods['goods_type'] = 'travel';
                        // }

                        MemberBrowse::addBrowse($goods_id,$user_id);
                        $webconfig = $this->webconfig;
                        $goods['thumb_url'] = $goods['thumb_url'];
                        $goods['goods_desc'] = str_replace("/public/",$webconfig['weburl']."/public/",$goods['goods_desc']);
                        $goods['goods_desc'] = str_replace("<img","<img style='width:100%;'",$goods['goods_desc']);
                        
                        if($goods['min_market_price'] != $goods['max_market_price']){
                            $goods['zs_market_price'] = $goods['min_market_price'].'-'.$goods['max_market_price'];
                        }else{
                            $goods['zs_market_price'] = $goods['min_market_price'];
                        }
                        
                        if($user_id){
                            $colls = Db::name('coll_goods')->where('user_id',$user_id)->where('goods_id',$goods['id'])->find();
                            if($colls){
                                $goods['coll_goods'] = 1;
                            }else{
                                $goods['coll_goods'] = 0;
                            }
                        }else{
                            $goods['coll_goods'] = 0;
                        }
                        
                        $goods['shop_token'] = 'cxy365';//默认的自营token
                        $member_shops = Db::name('member')->where('shop_id',$goods['shop_id'])->field('id')->find();
                        if($member_shops){
                            $shoptoken_infos = Db::name('rxin')->where('user_id',$member_shops['id'])->field('token')->find();
                            if($shoptoken_infos){
                                $goods['shop_token'] = $shoptoken_infos['token'];
                            }
                        }
                        
                        $onetime = date('Y-m-d',time()-3600*24*30);
                        $oneriqi = strtotime($onetime);
                        
                        $goods['sale_number'] = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id = b.id','INNER')->where('a.goods_id',$goods['id'])->where('b.state',1)->where('b.addtime','egt',$oneriqi)->sum('a.goods_num');

                        $gpres = Db::name('goods_pic')->where('goods_id',$goods['id'])->field('id,img_url,sort')->order('sort asc')->select();
                        // foreach ($gpres as $kp => $vp){
                        //     $gpres[$kp]['img_url'] = $webconfig['weburl'].'/'.$vp['img_url'];
                        // }
                        if(count($gpres) <= 0){
                            $gpres[]['img_url'] = 'https://cxy365-file.obs.cn-south-1.myhuaweicloud.com/static/Tour-app/img/goods_pic.png';
                        }
                        
                        $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id,a.attr_value,a.attr_pic,b.attr_name,b.attr_type')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.goods_id',$goods['id'])->where('b.attr_type',1)->select();
                        
                        $guige = array();
                        $colores = array();
                        
                        $radioattr = array();
                        if($radiores){
                            foreach ($radiores as $kra => $vra){
                                if($vra['attr_pic']){
                                    $radiores[$kra]['attr_pic'] = $vra['attr_pic'];
                                }
                                
                                $radiores[$kra]['check'] = 'false';
                                
                                if($vra['attr_name'] == '颜色分类'){
                                    if($vra['attr_pic']){
                                        $colores[] = $vra['attr_pic'];
                                    }else{
                                        $colores[] = '';
                                    }
                                }
                            }
                            
                            foreach ($radiores as $v){
                                $radioattr[$v['attr_id']][] = $v;
                            }
                            
                            foreach ($radioattr as $kad => $vad){
                                $guige[] = $vad[0]['attr_name'];
                            }
                        }
                        
                        $radioattr = array_values($radioattr);
                        
                        $uniattr = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id,a.attr_value,b.attr_name,b.attr_type')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.goods_id',$goods['id'])->where('b.attr_type',0)->select();

                        $goods_attr = '';
                        $goods_attr_str = '';
                        $activitys = array();

                        if(input('post.rush_id') && !input('post.group_id') && !input('post.assem_id')){
                            //秒杀
                            $rush_id = input('post.rush_id');
                            $activitys = Db::name('rush_activity')->where('id',$rush_id)->where('goods_id',$goods['id'])->where('shop_id',$goods['shop_id'])->where('checked',1)->where('recommend',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,num,xznum,kucun,sold,start_time,end_time')->find();
                            if($activitys){
                                $activitys['ac_type'] = 1;
                            }
                        }elseif(input('post.group_id') && !input('post.rush_id') && !input('post.assem_id')){
                            //团购
                            $group_id = input('post.group_id');
                            $activitys = Db::name('group_buy')->where('id',$group_id)->where('goods_id',$goods['id'])->where('shop_id',$goods['shop_id'])->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,start_time,end_time')->find();
                            if($activitys){
                                $activitys['ac_type'] = 2;
                            }
                        }elseif(input('post.assem_id') && !input('post.rush_id') && !input('post.group_id')){
                            //拼团
                            $assem_id = input('post.assem_id');
                            $activitys = Db::name('assemble')->where('id',$assem_id)->where('goods_id',$goods['id'])->where('shop_id',$goods['shop_id'])->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,pin_num,start_time,end_time')->find();
                            if($activitys){
                                $activitys['ac_type'] = 3;
                            }
                        }
                        
                        if(empty($activitys)){
                            $ruinfo = array('id'=>$goods['id'],'shop_id'=>$goods['shop_id']);
                            $gongyong = new GongyongMx();
                            $activitys = $gongyong->pdrugp($ruinfo);
                        }

                        $activity_info = array();
//                        var_dump($activitys);exit;

                        if($activitys){
                            $goods['is_activity'] = $activitys['ac_type'];
                            
                            if(!empty($activitys['goods_attr'])){
                                $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$activitys['goods_attr'])->where('a.goods_id',$goods['id'])->where('b.attr_type',1)->select();
                                if($gares){
                                    foreach ($gares as $key => $val){
                                        if($key == 0){
                                            $goods_attr_str = $val['attr_name'].':'.$val['attr_value'];
                                        }else{
                                            $goods_attr_str = $goods_attr_str.' '.$val['attr_name'].':'.$val['attr_value'];
                                        }
                                    }
                                    $goods_attr = $activitys['goods_attr'];
                                    $goods['goods_name'] = $goods['goods_name'].' '.$goods_attr_str;
                                }
                            }
                            
                            $goods['zs_shop_price'] = $activitys['price'];
                            
                            if($activitys['ac_type'] == 1){
                                $pronum = $activitys['kucun'];
                                
                                $yslv = sprintf("%.2f",$activitys['sold']/$activitys['num'])*100;
                                $activity_info = array(
                                    'yslv'=>$yslv.'%',
                                    'xznum'=>$activitys['xznum'],
                                    'start_time'=>$activitys['start_time'],
                                    'end_time'=>$activitys['end_time'],
                                    'dqtime' => time()
                                );
                            }else{
                                if(!empty($activitys['goods_attr'])){
                                    $prores = Db::name('product')->where('goods_id',$goods['id'])->where('goods_attr',$activitys['goods_attr'])->field('goods_number')->find();
                                    if($prores){
                                        $pronum = $prores['goods_number'];
                                    }else{
                                        $pronum = 0;
                                    }
                                }else{
                                    $prores = Db::name('product')->where('goods_id',$goods['id'])->field('goods_number')->select();
                                    if($prores){
                                        $pronum = 0;
                                        foreach ($prores as $v3){
                                            $pronum+=$v3['goods_number'];
                                        }
                                    }else{
                                        $pronum = 0;
                                    }
                                }

                                if($activitys['ac_type'] == 2){
                                    $activity_info = array(
                                        'start_time'=>$activitys['start_time'],
                                        'end_time'=>$activitys['end_time'],
                                        'dqtime' => time()
                                    );
                                }elseif($activitys['ac_type'] == 3){
                                    $assem_type = 1;
                                    $zhuangtai = 0;
                                    $member_assem = array();
                                    
                                    if(input('post.pin_number')){
                                        if($user_id){
                                            $assem_number = input('post.pin_number');
                                            $pintuans = Db::name('pintuan')->where('assem_number',$assem_number)->where('state',1)->where('pin_status','in','0,1')->find();
                                            if($pintuans){
                                                $pthdinfos = Db::name('assemble')->where('id',$pintuans['hd_id'])->where('goods_id',$goods['id'])->where('shop_id',$goods['shop_id'])->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,pin_num,start_time,end_time')->find();
                                                if($pthdinfos){
                                                    $order_assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('user_id',$user_id)->where('state',1)->where('tui_status',0)->find();
                                                    if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                                        if($order_assembles){
                                                            $assem_type = 3;
                                                            $zhuangtai = 1;
                                                            $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                        }else{
                                                            $assem_type = 2;
                                                            $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                        }
                                                    }elseif($pintuans['pin_status'] == 1){
                                                        if($order_assembles){
                                                            $zhuangtai = 2;
                                                            $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                        }
                                                    }
                                                }
                                            }else{
                                                if(!empty($activitys['goods_attr'])){
                                                    $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$goods['id'])->where('goods_attr',$activitys['goods_attr'])->where('shop_id',$goods['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                                }else{
                                                    $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$goods['id'])->where('shop_id',$goods['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                                }
                                                if($order_assembles){
                                                    $pintuans = Db::name('pintuan')->where('id',$order_assembles['pin_id'])->where('state',1)->where('pin_status','in','0,1')->where('hd_id',$activitys['id'])->find();
                                                    if($pintuans){
                                                        if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                                            $assem_type = 3;
                                                            $zhuangtai = 1;
                                                            $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                        }elseif($pintuans['pin_status'] == 1){
                                                            $zhuangtai = 2;
                                                            $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }else{
                                        if($user_id){
                                            if(!empty($activitys['goods_attr'])){
                                                $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$goods['id'])->where('goods_attr',$activitys['goods_attr'])->where('shop_id',$goods['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                            }else{
                                                $order_assembles = Db::name('order_assemble')->where('user_id',$user_id)->where('goods_id',$goods['id'])->where('shop_id',$goods['shop_id'])->where('hd_id',$activitys['id'])->where('state',1)->where('tui_status',0)->order('addtime desc')->find();
                                            }
                                            if($order_assembles){
                                                $pintuans = Db::name('pintuan')->where('id',$order_assembles['pin_id'])->where('state',1)->where('pin_status','in','0,1')->where('hd_id',$activitys['id'])->find();
                                                if($pintuans){
                                                    if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                                        $assem_type = 3;
                                                        $zhuangtai = 1;
                                                        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                    }elseif($pintuans['pin_status'] == 1){
                                                        $zhuangtai = 2;
                                                        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    if($assem_type == 3){
                                        $pin_id = $pintuans['id'];
                                        $tuan_id = $order_assembles['id'];
                                    }
                                    
                                    if(!empty($pthdinfos) && $pthdinfos['id'] != $activitys['id']){
                                        $ptactivitys = $pthdinfos;
                                        
                                        if(!empty($ptactivitys['goods_attr'])){
                                            $ptgares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$ptactivitys['goods_attr'])->where('a.goods_id',$goods['id'])->where('b.attr_type',1)->select();
                                            if($ptgares){
                                                $ptgoods_attr_str = '';
                                        
                                                foreach ($ptgares as $kac => $vac){
                                                    if($kac == 0){
                                                        $ptgoods_attr_str = $vac['attr_name'].':'.$vac['attr_value'];
                                                    }else{
                                                        $ptgoods_attr_str = $ptgoods_attr_str.' '.$vac['attr_name'].':'.$vac['attr_value'];
                                                    }
                                                }
                                        
                                                $goods_attr = $ptactivitys['goods_attr'];
                                                $goods['goods_name'] = $goods['goods_name'].' '.$ptgoods_attr_str;
                                            }
                                        }
                                        
                                        $goods['zs_shop_price'] = $ptactivitys['price'];
                                        
                                        if(!empty($ptactivitys['goods_attr'])){
                                            $prores = Db::name('product')->where('goods_id',$goods['id'])->where('goods_attr',$ptactivitys['goods_attr'])->field('goods_number')->find();
                                            if($prores){
                                                $pronum = $prores['goods_number'];
                                            }else{
                                                $pronum = 0;
                                            }
                                        }else{
                                            $prores = Db::name('product')->where('goods_id',$goods['id'])->field('goods_number')->select();
                                            if($prores){
                                                $pronum = 0;
                                                foreach ($prores as $v3){
                                                    $pronum+=$v3['goods_number'];
                                                }
                                            }else{
                                                $pronum = 0;
                                            }
                                        }
                                    }else{
                                        $ptactivitys = $activitys;
                                    }
                                    
                                    if(!empty($ptactivitys['goods_attr'])){
                                        $gavdres = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_price')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$ptactivitys['goods_attr'])->where('a.goods_id',$goods['id'])->where('b.attr_type',1)->select();
                                        if($gavdres){
                                            $dandu_price = $goods['shop_price'];
                                            foreach ($gavdres as $vrp){
                                                $dandu_price+=$vrp['attr_price'];
                                            }
                                            $dandu_price=sprintf("%.2f", $dandu_price);
                                        }else{
                                            if($goods['min_price'] != $goods['max_price']){
                                                $dandu_price = $goods['min_price'].'-'.$goods['max_price'];
                                            }else{
                                                $dandu_price = $goods['min_price'];
                                            }
                                        }
                                    }else{
                                        if($goods['min_price'] != $goods['max_price']){
                                            $dandu_price = $goods['min_price'].'-'.$goods['max_price'];
                                        }else{
                                            $dandu_price = $goods['min_price'];
                                        }
                                    }
                                    
                                    if(in_array($assem_type,array(1,3))){
                                        if(!empty($ptactivitys['goods_attr'])){
                                            $userassem = Db::name('order_assemble')->alias('a')->field('a.pin_id')->join('sp_pintuan b','a.pin_id = b.id','INNER')->where('a.user_id',$user_id)->where('a.goods_id',$goods['id'])->where('a.goods_attr',$ptactivitys['goods_attr'])->where('a.shop_id',$goods['shop_id'])->where('a.hd_id',$ptactivitys['id'])->where('a.state',1)->where('a.tui_status',0)->where('b.state',1)->where('b.hd_id',$ptactivitys['id'])->where('b.pin_status',0)->where('b.timeout','gt',time())->group('a.pin_id')->select();
                                            if($userassem){
                                                $userpinid = array();
                                                foreach ($userassem as $vur){
                                                    $userpinid[] = $vur['pin_id'];
                                                }
                                                $userpinid = array_unique($userpinid);
                                                $userpinid = implode(',', $userpinid);
                                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b','a.tz_id = b.id','INNER')->where('a.id','not in',$userpinid)->where('a.hd_id',$ptactivitys['id'])->where('a.state',1)->where('a.pin_status',0)->where('a.timeout','gt',time())->order('a.tuan_num desc')->limit(3)->select();
                                            }else{
                                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b','a.tz_id = b.id','INNER')->where('a.hd_id',$ptactivitys['id'])->where('a.state',1)->where('a.pin_status',0)->where('a.timeout','gt',time())->order('a.tuan_num desc')->limit(3)->select();
                                            }
                                        }else{
                                            $userassem = Db::name('order_assemble')->alias('a')->field('a.pin_id')->join('sp_pintuan b','a.pin_id = b.id','INNER')->where('a.user_id',$user_id)->where('a.goods_id',$goods['id'])->where('a.shop_id',$goods['shop_id'])->where('a.hd_id',$ptactivitys['id'])->where('a.state',1)->where('a.tui_status',0)->where('b.state',1)->where('b.hd_id',$ptactivitys['id'])->where('b.pin_status',0)->where('b.timeout','gt',time())->group('a.pin_id')->select();
                                            if($userassem){
                                                $userpinid = array();
                                                foreach ($userassem as $vur){
                                                    $userpinid[] = $vur['pin_id'];
                                                }
                                                $userpinid = array_unique($userpinid);
                                                $userpinid = implode(',', $userpinid);
                                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b','a.tz_id = b.id','INNER')->where('a.id','not in',$userpinid)->where('a.hd_id',$ptactivitys['id'])->where('a.state',1)->where('a.pin_status',0)->where('a.timeout','gt',time())->order('a.tuan_num desc')->limit(3)->select();
                                            }else{
                                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b','a.tz_id = b.id','INNER')->where('a.hd_id',$ptactivitys['id'])->where('a.state',1)->where('a.pin_status',0)->where('a.timeout','gt',time())->order('a.tuan_num desc')->limit(3)->select();
                                            }
                                        }

                                        if($memberpinres){
                                            foreach ($memberpinres as $kpc => $vpc){
                                                $memberpinres[$kpc]['headimgurl'] = $vpc['headimgurl'];
                                                $memberpinres[$kpc]['pin_time_out'] = time2string($vpc['timeout']-time());
                                                $memberpinres[$kpc]['goods_id'] = $goods['id'];
                                            }
                                        }
                                    }

                                    if($assem_type == 1 && $zhuangtai == 0){
                                        if($user_id){
                                            $member_picinfos  = Db::name('member')->where('id',$user_id)->field('user_name,headimgurl')->find();
                                            $member_pic = $member_picinfos['headimgurl'];
                                            if($member_pic){
                                                $member_pic = $member_pic;
                                            }else{
                                                $member_pic = $webconfig['weburl'].'/static/admin/img/nopic.jpg';
                                            }
                                            $member_assem[] = array('pin_type'=>2,'user_name'=>$member_picinfos['user_name'],'headimgurl'=>$member_pic);
                                        }else{
                                            $member_pic = $webconfig['weburl'].'/static/admin/img/nopic.jpg';
                                            $member_assem[] = array('pin_type'=>2,'user_name'=>'','headimgurl'=>$member_pic);
                                        }
                                    }else{
                                        if(!empty($member_assem)){
                                            foreach ($member_assem as $kas => $vas){
                                                if($vas['headimgurl']){
                                                    $member_assem[$kas]['headimgurl'] = $vas['headimgurl'];
                                                }else{
                                                    $member_assem[$kas]['headimgurl'] = $webconfig['weburl'].'/static/admin/img/nopic.jpg';
                                                }
                                            }
                                        }
                                    }
                                    
                                    $activity_info = array(
                                        'assem_type'=>$assem_type,
                                        'zhuangtai'=>$zhuangtai,
                                        'pin_num'=>$ptactivitys['pin_num'],
                                        'dandu_price'=>$dandu_price,
                                        'member_assem'=>$member_assem,
                                        'start_time'=>$ptactivitys['start_time'],
                                        'end_time'=>$ptactivitys['end_time'],
                                        'dqtime' => time()
                                    );
                                }
                            }
                        }else{
                            $goods['is_activity'] = 0;

                            if($goods['min_price'] != $goods['max_price']){
                                $goods['zs_shop_price'] = $goods['min_price'].'-'.$goods['max_price'];
                            }else{
                                $goods['zs_shop_price'] = $goods['min_price'];
                            }
                            
                            $prores = Db::name('product')->where('goods_id',$goods['id'])->field('goods_number')->select();
                            if($prores){
                                $pronum = 0;
                                foreach ($prores as $v3){
                                    $pronum+=$v3['goods_number'];
                                }
                            }else{
                                $pronum = 0;
                            }
                        }

                        //邮费
                        if($goods['is_free'] == 0){
                            $shopinfos = Db::name('shops')->where('id',$goods['shop_id'])->field('freight,reduce')->find();
                            $freight = '运费'.$shopinfos['freight'].' 订单满'.$shopinfos['reduce'].'免运费';
                        }else{
                            $freight = '包邮';
                        }
                        
                        //优惠券
                        $couponinfos = array('is_show'=>0,'infos'=>'');
                        //商品活动信息
                        $huodong = array('is_show'=>0,'infos'=>'','prom_id'=>0);
                        
                        $couponres = Db::name('coupon')->where('shop_id',$goods['shop_id'])->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('man_price,dec_price')->order('man_price asc')->limit(3)->select();
                        if($couponres){
                            $couponinfos = array('is_show'=>1,'infos'=>$couponres);
                        }
                        
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

                        $goods['vip_price'] = empty($goods['vip_price'])? 100 : $goods['vip_price'];


                        //获取商品的佣金信息
                        $commission_arr = getCommissionPrice($goods['zs_shop_price']);
                        $goodsinfo = array(
                            'id'=>$goods['id'],
                            'is_vip_goods'  => ($goods['id'] == $vip_goods) ? 1 : 0,
                            'goods_name'=>$goods['goods_name'],
                            'thumb_url'=>$goods['thumb_url'],
                            'goods_desc'=>$goods['goods_desc'],
                            'freight'=>$freight,
                            'salenum'=>$goods['sale_num']+$goods['fictitious_sale_num'],
                            'leixing'=>$goods['leixing'],
                            'shop_id'=>$goods['shop_id'],
                            'zs_market_price'=>$goods['zs_market_price'],
                            'zs_shop_price'=>$goods['zs_shop_price'],
                            'vip_price' => sprintf("%.2f",$goods['zs_shop_price']*($goods['vip_price']/100)),
                            'is_activity'=>$goods['is_activity'],
                            'coll_goods'=>$goods['coll_goods'],
                            'sale_number'=>$goods['sale_number'],
                            'shop_token'=>$goods['shop_token'],
                            'goods_type'=>$goods['goods_type'],
                            'type'=>$goods['type'],
                            'commission_one' => $commission_arr['commission_one'],//一级佣金
                            'commission_two' => $commission_arr['commission_two'],//二级佣金
                        );
                        
                        $shopinfos = Db::name('shops')->where('id',$goods['shop_id'])->where('open_status',1)->field('id,shop_name,shop_desc,logo,goods_fen,fw_fen,wuliu_fen')->find();
                        $shopinfos['logo'] = $webconfig['weburl'].'/'.$shopinfos['logo'];
                        
                        $shop_customs = Db::name('shop_custom')->where('shop_id',$goods['shop_id'])->where('type',1)->field('info_id')->find();
                        
                        $remgoodres = array();
                        
                        if($shop_customs){
                            $remgoodres = Db::name('goods')->where('id','in',$shop_customs['info_id'])->where('shop_id',$goods['shop_id'])->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,leixing,shop_id')->order('zonghe_lv desc,sort asc')->select();
                        
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
                        $credit_info = [];
                        $credit_goods_id = input('post.credit_goods_id');
                        if (!empty($credit_goods_id)){
                            $credit_info  = Db::name('creditshop')
                                ->field('price,credit')
                                ->where(['id'=>$credit_goods_id,'status'=>1,'is_show'=>1])
                                ->find();
                            $credit_info['dandu_price'] = $goodsinfo['zs_shop_price'];
                        }
                        $goodinfores = array(
                            'goodsinfo'=>$goodsinfo,
                            'activity_info'=>$activity_info,
                            'goods_attr'=>$goods_attr,
                            'credit_info'   => $credit_info,
                            'goods_attr_str'=>$goods_attr_str,
                            'pronum'=>$pronum,
                            'gpres'=>$gpres,
                            'radioattr'=>$radioattr,
                            'uniattr'=>$uniattr,
                            'guige'=>$guige,
                            'colores'=>$colores,
                            'couponinfos'=>$couponinfos,
                            'huodong'=>$huodong,
                            'sertions'=>$sertions,
                            'shopinfos'=>$shopinfos,
                            'remgoodres'=>$remgoodres,
                            'pin_id'=>$pin_id,
                            'tuan_id'=>$tuan_id,
                            'memberpinres'=>$memberpinres,
                            'is_vip'    => $is_vip,
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


    public function get_goods_price(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                if(!empty($result['user_id'])){
                    $user_id = $result['user_id'];
                }else{
                    $user_id = 0;
                }
                
                $pin_id = '';
                $tuan_id = '';
                $memberpinres = array();
        
                $data = input('post.');
                if(!empty($data['goods_id']) && !empty($data['goods_attr'])){
                    if(input('post.fangshi') && input('post.fangshi') == 1){
                        if(!is_array($data['goods_attr'])){
                            $goods_id = $data['goods_id'];
                            $fangshi = $data['fangshi'];
                        
                            $data['goods_attr'] = trim($data['goods_attr']);
                            $data['goods_attr'] = str_replace('，', ',', $data['goods_attr']);
                            $data['goods_attr'] = rtrim($data['goods_attr'],',');
                        
                            if($data['goods_attr']){
                                $goods = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.shop_price,a.min_price,a.max_price,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$goods_id)->where('a.onsale',1)->where('b.open_status',1)->find();
                        
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
                                        $webconfig = $this->webconfig;
                        
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
                                                $prores = Db::name('product')->where('goods_id',$goods_id)->where('goods_attr',$goods_attr)->field('goods_number')->find();
                                                if($prores){
                                                    $goods_number = $prores['goods_number'];
                                                }else{
                                                    $goods_number = 0;
                                                }
                        
                                                if($activitys['ac_type'] == 2){
                                                    $activity_info = array(
                                                        'start_time'=>$activitys['start_time'],
                                                        'end_time'=>$activitys['end_time'],
                                                        'dqtime' => time()
                                                    );
                                                }elseif($activitys['ac_type'] == 3){
                                                    $assem_type = 1;
                                                    $zhuangtai = 0;
                                                    $member_assem = array();
                        
                                                    if(input('post.pin_number')){
                                                        if($user_id){
                                                            $assem_number = input('post.pin_number');
                                                            $pintuans = Db::name('pintuan')->where('assem_number',$assem_number)->where('state',1)->where('pin_status','in','0,1')->where('hd_id',$activitys['id'])->find();
                                                            if($pintuans){
                                                                $order_assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('user_id',$user_id)->where('state',1)->where('tui_status',0)->find();
                                                                if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                                                    if($order_assembles){
                                                                        $assem_type = 3;
                                                                        $zhuangtai = 1;
                                                                        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                                    }else{
                                                                        $assem_type = 2;
                                                                        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                                    }
                                                                }elseif($pintuans['pin_status'] == 1){
                                                                    if($order_assembles){
                                                                        $zhuangtai = 2;
                                                                        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
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
                                                                            $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                                        }elseif($pintuans['pin_status'] == 1){
                                                                            $zhuangtai = 2;
                                                                            $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }else{
                                                        if($user_id){
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
                                                                        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                                    }elseif($pintuans['pin_status'] == 1){
                                                                        $zhuangtai = 2;
                                                                        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                    
                                                    if($assem_type == 3){
                                                        $pin_id = $pintuans['id'];
                                                        $tuan_id = $order_assembles['id'];
                                                    }
                        
                                                    $gavdres = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_price')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$goods_attr)->where('a.goods_id',$goods_id)->where('b.attr_type',1)->select();
                                                    if($gavdres){
                                                        $dandu_price = $goods['shop_price'];
                                                        foreach ($gavdres as $vrp){
                                                            $dandu_price+=$vrp['attr_price'];
                                                        }
                                                        $dandu_price=sprintf("%.2f", $dandu_price);
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                        
                                                    if($assem_type == 1 && $zhuangtai == 0){
                                                        if($user_id){
                                                            $member_pic = Db::name('member')->where('id',$user_id)->value('headimgurl');
                                                            if($member_pic){
                                                                $member_pic = $webconfig['weburl'].'/'.$member_pic;
                                                            }else{
                                                                $member_pic = $webconfig['weburl'].'/static/admin/img/nopic.jpg';
                                                            }
                                                            $member_assem[] = array('pin_type'=>2,'headimgurl'=>$member_pic);
                                                        }else{
                                                            $member_pic = $webconfig['weburl'].'/static/admin/img/nopic.jpg';
                                                            $member_assem[] = array('pin_type'=>2,'user_name'=>'','headimgurl'=>$member_pic);
                                                        }
                                                    }else{
                                                        if(!empty($member_assem)){
                                                            foreach ($member_assem as $kas => $vas){
                                                                if($vas['headimgurl']){
                                                                    $member_assem[$kas]['headimgurl'] = $webconfig['weburl'].'/'.$vas['headimgurl'];
                                                                }else{
                                                                    $member_assem[$kas]['headimgurl'] = $webconfig['weburl'].'/static/admin/img/nopic.jpg';
                                                                }
                                                            }
                                                        }
                                                    }
                                                    
                                                    if(in_array($assem_type,array(1,3))){
                                                        if(!empty($activitys['goods_attr'])){
                                                            $userassem = Db::name('order_assemble')->alias('a')->field('a.pin_id')->join('sp_pintuan b','a.pin_id = b.id','INNER')->where('a.user_id',$user_id)->where('a.goods_id',$goods['id'])->where('a.goods_attr',$activitys['goods_attr'])->where('a.shop_id',$goods['shop_id'])->where('a.hd_id',$activitys['id'])->where('a.state',1)->where('a.tui_status',0)->where('b.state',1)->where('b.hd_id',$activitys['id'])->where('b.pin_status',0)->where('b.timeout','gt',time())->group('a.pin_id')->select();
                                                            if($userassem){
                                                                $userpinid = array();
                                                                foreach ($userassem as $vur){
                                                                    $userpinid[] = $vur['pin_id'];
                                                                }
                                                                $userpinid = array_unique($userpinid);
                                                                $userpinid = implode(',', $userpinid);
                                                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b','a.tz_id = b.id','INNER')->where('a.id','not in',$userpinid)->where('a.hd_id',$activitys['id'])->where('a.state',1)->where('a.pin_status',0)->where('a.timeout','gt',time())->order('a.tuan_num desc')->limit(3)->select();
                                                            }else{
                                                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b','a.tz_id = b.id','INNER')->where('a.hd_id',$activitys['id'])->where('a.state',1)->where('a.pin_status',0)->where('a.timeout','gt',time())->order('a.tuan_num desc')->limit(3)->select();
                                                            }
                                                        }else{
                                                            $userassem = Db::name('order_assemble')->alias('a')->field('a.pin_id')->join('sp_pintuan b','a.pin_id = b.id','INNER')->where('a.user_id',$user_id)->where('a.goods_id',$goods['id'])->where('a.shop_id',$goods['shop_id'])->where('a.hd_id',$activitys['id'])->where('a.state',1)->where('a.tui_status',0)->where('b.state',1)->where('b.hd_id',$activitys['id'])->where('b.pin_status',0)->where('b.timeout','gt',time())->group('a.pin_id')->select();
                                                            if($userassem){
                                                                $userpinid = array();
                                                                foreach ($userassem as $vur){
                                                                    $userpinid[] = $vur['pin_id'];
                                                                }
                                                                $userpinid = array_unique($userpinid);
                                                                $userpinid = implode(',', $userpinid);
                                                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b','a.tz_id = b.id','INNER')->where('a.id','not in',$userpinid)->where('a.hd_id',$activitys['id'])->where('a.state',1)->where('a.pin_status',0)->where('a.timeout','gt',time())->order('a.tuan_num desc')->limit(3)->select();
                                                            }else{
                                                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b','a.tz_id = b.id','INNER')->where('a.hd_id',$activitys['id'])->where('a.state',1)->where('a.pin_status',0)->where('a.timeout','gt',time())->order('a.tuan_num desc')->limit(3)->select();
                                                            }
                                                        }
                                                    
                                                        if($memberpinres){
                                                            foreach ($memberpinres as $kpc => $vpc){
                                                                $memberpinres[$kpc]['headimgurl'] = $webconfig['weburl'].'/'.$vpc['headimgurl'];
                                                                $memberpinres[$kpc]['pin_time_out'] = time2string($vpc['timeout']-time());
                                                                $memberpinres[$kpc]['goods_id'] = $goods['id'];
                                                            }
                                                        }
                                                    }
                        
                                                    $activity_info = array(
                                                        'assem_type'=>$assem_type,
                                                        'zhuangtai'=>$zhuangtai,
                                                        'pin_num'=>$activitys['pin_num'],
                                                        'dandu_price'=>$dandu_price,
                                                        'member_assem'=>$member_assem,
                                                        'start_time'=>$activitys['start_time'],
                                                        'end_time'=>$activitys['end_time'],
                                                        'dqtime' => time()
                                                    );
                                                }
                                            }
                                        }else{
                                            $is_activity = 0;

//                                            $gares = Db::name('goods_attr')->where('id','in',$goods_attr)->where('goods_id',$goods_id)->field('id,attr_price')->select();
                                            $gares = Db::name('goods_attr')->where('id','in',$goods_attr)->where('goods_id',$goods_id)->field('id,attr_price')->find();
                                            if($gares){
//                                                $zs_shop_price = $goods['shop_price'];
//                                                foreach ($gares as $v){
//                                                    $zs_shop_price+=$v['attr_price'];
//                                                }
                                                $zs_shop_price = $gares['attr_price'];
                                                $zs_shop_price=sprintf("%.2f", $zs_shop_price);
                                                $prores = Db::name('product')->where('goods_id',$goods_id)->where('goods_attr',$goods_attr)->field('goods_number')->find();
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
                                                $attr_pic = $webconfig['weburl'].'/'.$vd['attr_pic'];
                                            }
                                        }
                        
                                        $attrinfos = array('is_activity'=>$is_activity,'goods_name'=>$goods_name,'attr_pic'=>$attr_pic,'zs_shop_price'=>$zs_shop_price,'goods_number'=>$goods_number);
                        
                                        $goodsinfo = array('attrinfos'=>$attrinfos,'activity_info'=>$activity_info,'fangshi'=>$fangshi,'pin_id'=>$pin_id,'tuan_id'=>$tuan_id,'memberpinres'=>$memberpinres);
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
                        $value = array('status'=>400,'mess'=>'缺少购买方式参数','data'=>array('status'=>400));
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
    
    
    public function get_pingoods_price(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                if(!empty($result['user_id'])){
                    $user_id = $result['user_id'];
                }else{
                    $user_id = 0;
                }
                
                $pin_id = '';
                $tuan_id = '';
                $memberpinres = array();
                
                $data = input('post.');
                if(!empty($data['goods_id']) && !empty($data['goods_attr'])){
                    if(!empty($data['fangshi']) && in_array($data['fangshi'], array(1,2))){
                        if(!is_array($data['goods_attr'])){
                            $goods_id = $data['goods_id'];
                            $fangshi = $data['fangshi'];
                        
                            $data['goods_attr'] = trim($data['goods_attr']);
                            $data['goods_attr'] = str_replace('，', ',', $data['goods_attr']);
                            $data['goods_attr'] = rtrim($data['goods_attr'],',');
                        
                            if($data['goods_attr']){
                                $goods = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.shop_price,a.min_price,a.max_price,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$goods_id)->where('a.onsale',1)->where('b.open_status',1)->find();
                        
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
                                        $webconfig = $this->webconfig;
                        
                                        $ruinfo = array('id'=>$goods['id'],'shop_id'=>$goods['shop_id']);
                                        $ru_attr = $goods_attr;
                                        $gongyong = new GongyongMx();
                                        $activitys = $gongyong->pdrugp($ruinfo,$ru_attr);
                        
                                        $activity_info = array();
                        
                                        if((!$activitys) || ($activitys && $activitys['ac_type'] == 3 && $fangshi == 1)){
                                            $is_activity = 0;
                        
                                            $gares = Db::name('goods_attr')->where('id','in',$goods_attr)->where('goods_id',$goods_id)->field('id,attr_price')->select();
                                            if($gares){
                                                $zs_shop_price = $goods['shop_price'];
                                                foreach ($gares as $v){
                                                    $zs_shop_price+=$v['attr_price'];
                                                }
                                                $zs_shop_price=sprintf("%.2f", $zs_shop_price);
                                                $prores = Db::name('product')->where('goods_id',$goods_id)->where('goods_attr',$goods_attr)->field('goods_number')->find();
                                                if($prores){
                                                    $goods_number = $prores['goods_number'];
                                                }else{
                                                    $goods_number = 0;
                                                }
                                            }else{
                                                $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                        }else{
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
                                                $prores = Db::name('product')->where('goods_id',$goods_id)->where('goods_attr',$goods_attr)->field('goods_number')->find();
                                                if($prores){
                                                    $goods_number = $prores['goods_number'];
                                                }else{
                                                    $goods_number = 0;
                                                }
                        
                                                if($activitys['ac_type'] == 2){
                                                    $activity_info = array(
                                                        'start_time'=>$activitys['start_time'],
                                                        'end_time'=>$activitys['end_time'],
                                                        'dqtime' => time()
                                                    );
                                                }elseif($activitys['ac_type'] == 3){
                                                    $assem_type = 1;
                                                    $zhuangtai = 0;
                                                    $member_assem = array();
                        
                                                    if(input('post.pin_number')){
                                                        if($user_id){
                                                            $assem_number = input('post.pin_number');
                                                            $pintuans = Db::name('pintuan')->where('assem_number',$assem_number)->where('state',1)->where('pin_status','in','0,1')->where('hd_id',$activitys['id'])->find();
                                                            if($pintuans){
                                                                $order_assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('user_id',$user_id)->where('state',1)->where('tui_status',0)->find();
                                                                if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                                                    if($order_assembles){
                                                                        $assem_type = 3;
                                                                        $zhuangtai = 1;
                                                                        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                                    }else{
                                                                        $assem_type = 2;
                                                                        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                                    }
                                                                }elseif($pintuans['pin_status'] == 1){
                                                                    if($order_assembles){
                                                                        $zhuangtai = 2;
                                                                        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
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
                                                                            $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                                        }elseif($pintuans['pin_status'] == 1){
                                                                            $zhuangtai = 2;
                                                                            $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }else{
                                                        if($user_id){
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
                                                                        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                                    }elseif($pintuans['pin_status'] == 1){
                                                                        $zhuangtai = 2;
                                                                        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                    
                                                    if($assem_type == 3){
                                                        $pin_id = $pintuans['id'];
                                                        $tuan_id = $order_assembles['id'];
                                                    }
                        
                                                    $gavdres = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_price')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$goods_attr)->where('a.goods_id',$goods_id)->where('b.attr_type',1)->select();
                                                    if($gavdres){
                                                        $dandu_price = $goods['shop_price'];
                                                        foreach ($gavdres as $vrp){
                                                            $dandu_price+=$vrp['attr_price'];
                                                        }
                                                        $dandu_price=sprintf("%.2f", $dandu_price);
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                        
                                                    if($assem_type == 1 && $zhuangtai == 0){
                                                        if($user_id){
                                                            $member_pic = Db::name('member')->where('id',$user_id)->value('headimgurl');
                                                            if($member_pic){
                                                                $member_pic = $webconfig['weburl'].'/'.$member_pic;
                                                            }else{
                                                                $member_pic = $webconfig['weburl'].'/static/admin/img/nopic.jpg';
                                                            }
                                                            $member_assem[] = array('pin_type'=>2,'headimgurl'=>$member_pic);
                                                        }else{
                                                            $member_pic = $webconfig['weburl'].'/static/admin/img/nopic.jpg';
                                                            $member_assem[] = array('pin_type'=>2,'user_name'=>'','headimgurl'=>$member_pic);
                                                        }
                                                    }else{
                                                        if(!empty($member_assem)){
                                                            foreach ($member_assem as $kas => $vas){
                                                                if($vas['headimgurl']){
                                                                    $member_assem[$kas]['headimgurl'] = $webconfig['weburl'].'/'.$vas['headimgurl'];
                                                                }else{
                                                                    $member_assem[$kas]['headimgurl'] = $webconfig['weburl'].'/static/admin/img/nopic.jpg';
                                                                }
                                                            }
                                                        }
                                                    }
                                                    
                                                    if(in_array($assem_type,array(1,3))){
                                                        if(!empty($activitys['goods_attr'])){
                                                            $userassem = Db::name('order_assemble')->alias('a')->field('a.pin_id')->join('sp_pintuan b','a.pin_id = b.id','INNER')->where('a.user_id',$user_id)->where('a.goods_id',$goods['id'])->where('a.goods_attr',$activitys['goods_attr'])->where('a.shop_id',$goods['shop_id'])->where('a.hd_id',$activitys['id'])->where('a.state',1)->where('a.tui_status',0)->where('b.state',1)->where('b.hd_id',$activitys['id'])->where('b.pin_status',0)->where('b.timeout','gt',time())->group('a.pin_id')->select();
                                                            if($userassem){
                                                                $userpinid = array();
                                                                foreach ($userassem as $vur){
                                                                    $userpinid[] = $vur['pin_id'];
                                                                }
                                                                $userpinid = array_unique($userpinid);
                                                                $userpinid = implode(',', $userpinid);
                                                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b','a.tz_id = b.id','INNER')->where('a.id','not in',$userpinid)->where('a.hd_id',$activitys['id'])->where('a.state',1)->where('a.pin_status',0)->where('a.timeout','gt',time())->order('a.tuan_num desc')->limit(3)->select();
                                                            }else{
                                                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b','a.tz_id = b.id','INNER')->where('a.hd_id',$activitys['id'])->where('a.state',1)->where('a.pin_status',0)->where('a.timeout','gt',time())->order('a.tuan_num desc')->limit(3)->select();
                                                            }
                                                        }else{
                                                            $userassem = Db::name('order_assemble')->alias('a')->field('a.pin_id')->join('sp_pintuan b','a.pin_id = b.id','INNER')->where('a.user_id',$user_id)->where('a.goods_id',$goods['id'])->where('a.shop_id',$goods['shop_id'])->where('a.hd_id',$activitys['id'])->where('a.state',1)->where('a.tui_status',0)->where('b.state',1)->where('b.hd_id',$activitys['id'])->where('b.pin_status',0)->where('b.timeout','gt',time())->group('a.pin_id')->select();
                                                            if($userassem){
                                                                $userpinid = array();
                                                                foreach ($userassem as $vur){
                                                                    $userpinid[] = $vur['pin_id'];
                                                                }
                                                                $userpinid = array_unique($userpinid);
                                                                $userpinid = implode(',', $userpinid);
                                                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b','a.tz_id = b.id','INNER')->where('a.id','not in',$userpinid)->where('a.hd_id',$activitys['id'])->where('a.state',1)->where('a.pin_status',0)->where('a.timeout','gt',time())->order('a.tuan_num desc')->limit(3)->select();
                                                            }else{
                                                                $memberpinres =  Db::name('pintuan')->alias('a')->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')->join('sp_member b','a.tz_id = b.id','INNER')->where('a.hd_id',$activitys['id'])->where('a.state',1)->where('a.pin_status',0)->where('a.timeout','gt',time())->order('a.tuan_num desc')->limit(3)->select();
                                                            }
                                                        }
                                                    
                                                        if($memberpinres){
                                                            foreach ($memberpinres as $kpc => $vpc){
                                                                $memberpinres[$kpc]['headimgurl'] = $webconfig['weburl'].'/'.$vpc['headimgurl'];
                                                                $memberpinres[$kpc]['pin_time_out'] = time2string($vpc['timeout']-time());
                                                                $memberpinres[$kpc]['goods_id'] = $goods['id'];
                                                            }
                                                        }
                                                    }
                        
                                                    $activity_info = array(
                                                        'assem_type'=>$assem_type,
                                                        'zhuangtai'=>$zhuangtai,
                                                        'pin_num'=>$activitys['pin_num'],
                                                        'dandu_price'=>$dandu_price,
                                                        'member_assem'=>$member_assem,
                                                        'start_time'=>$activitys['start_time'],
                                                        'end_time'=>$activitys['end_time'],
                                                        'dqtime' => time()
                                                    );
                                                }
                                            }
                                        }
                        
                                        $goodsgares = Db::name('goods_attr')->alias('a')->field('a.attr_value,a.attr_pic,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$goods_attr)->where('a.goods_id',$goods_id)->where('b.attr_type',1)->select();
                                        foreach ($goodsgares as $vd){
                                            if(!empty($vd['attr_pic'])){
                                                $attr_pic = $webconfig['weburl'].'/'.$vd['attr_pic'];
                                            }
                                        }
                        
                                        $attrinfos = array('is_activity'=>$is_activity,'goods_name'=>$goods_name,'attr_pic'=>$attr_pic,'zs_shop_price'=>$zs_shop_price,'goods_number'=>$goods_number);
                        
                                        $goodsinfo = array('attrinfos'=>$attrinfos,'activity_info'=>$activity_info,'fangshi'=>$fangshi,'pin_id'=>$pin_id,'tuan_id'=>$tuan_id,'memberpinres'=>$memberpinres);
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
                        $value = array('status'=>400,'mess'=>'缺少购买方式参数','data'=>array('status'=>400));
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

    // 获取指定店铺的商品 
    public function getShopGoods(){
        if (request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if ($result['status'] == 200) {
                $user_id = $result['user_id']; 
                $shop_id = input('post.shop_id');
                if(empty($shop_id)){
                    datamsg(LOSE,'缺少店铺id参数');
                }else{
                    $goods = db('goods')->where(['onsale'=>1,'shop_id'=>$shop_id])->each(function($item,$key){
                        
                        return $goods;
                    });
                    datamsg(WIN, '获取成功',$goods);
                }
            } else {
                datamsg(LOSE, $result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式错误');
        }
    }

}