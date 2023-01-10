<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class MemberColl extends Common{
    
    //读取用户收藏列表
    public function shoucanglst(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];

                    if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                        $pagenum = input('post.page');
                    
                        $webconfig = $this->webconfig;
                        $perpage = $webconfig['app_goodlst_num'];
                        $offset = ($pagenum-1)*$perpage;
                        
                        if(input('post.filter') && in_array(input('post.filter'), array(1,2,3))){
                            $filter = input('post.filter');
                        }else{
                            $filter = 1;
                        }
                        
                        switch($filter){
                            case 1:
                                $goodidres = Db::name('coll_goods')->where('user_id',$user_id)->field('goods_id')->order('addtime desc')->limit($offset,$perpage)->select();
                                if($goodidres){
                                    $goodidarr = array();
                                    foreach ($goodidres as $v2){
                                        $goodidarr[] = $v2['goods_id'];
                                    }
                                    if($goodidarr){
                                        $goodidarr = array_unique($goodidarr);
                                        $goodidarr = implode(',', $goodidarr);
                                        $goodres = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.min_price,a.zs_price,a.leixing,a.onsale,a.shop_id,b.open_status')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id','in',$goodidarr)->select();
                                        
                                        if($goodres){
                                            foreach ($goodres as $k =>$v){
                                                $goodres[$k]['thumb_url'] = $webconfig['weburl'].'/'.$v['thumb_url'];
                                                
                                                $ruinfo = array('id'=>$v['id'],'shop_id'=>$v['shop_id']);
                                                $gongyong = new GongyongMx();
                                                $activitys = $gongyong->pdrugp($ruinfo);
                                        
                                                if($activitys){
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
                                                    $goodres[$k]['zs_price'] = $v['min_price'];
                                                }
                                                
                                                if($v['onsale'] == 1 && $v['open_status'] == 1){
                                                    $goodres[$k]['youxiao'] = 1;
                                                }else{
                                                    $goodres[$k]['youxiao'] = 2;
                                                }
                                                $goodres[$k]['filter'] = 1;
                                            }
                                        }
                                    }else{
                                        $goodres = array();
                                    }
                                }else{
                                    $goodres = array();
                                }
                                $infores = $goodres;
                                break;
                            case 2:
                                $shopidres = Db::name('coll_shops')->where('user_id',$user_id)->field('shop_id')->order('addtime desc')->limit($offset,$perpage)->select();
                                if($shopidres){
                                    $shopidarr = array();
                                    foreach ($shopidres as $v){
                                        $shopidarr[] = $v['shop_id'];
                                    }
                                    
                                    if($shopidarr){
                                        $shopidarr = array_unique($shopidarr);
                                        $shopidarr = implode(',', $shopidarr);
                                        
                                        $shopres = Db::name('shops')->where('id','in',$shopidarr)->field('id,shop_name,logo,open_status')->select();
                                        
                                        if($shopres){
                                            foreach ($shopres as $kr => $vr){
                                                $shopres[$kr]['logo'] = $webconfig['weburl'].'/'.$vr['logo'];
                                                
                                                if($vr['open_status'] == 1){
                                                    $shopres[$kr]['youxiao'] = 1;
                                                }else{
                                                    $shopres[$kr]['youxiao'] = 2;
                                                }
                                                $shopres[$kr]['filter'] = 2;
                                            }
                                        }
                                    }else{
                                        $shopres = array();
                                    }
                                }else{
                                    $shopres = array();
                                }
                                $infores = $shopres;
                                break;
                            case 3:
                                $infores = array();
                                break;
                        }
                        $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$infores);
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少页面参数','data'=>array('status'=>400));
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
    
    //删除收藏信息
    public function cancelcoll(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    
                    if(input('post.filter') && in_array(input('post.filter'), array(1,2,3))){
                        if(input('post.coll_id') && !is_array(input('post.coll_id'))){
                            $filter = input('post.filter');
                            
                            $coll_id = input('post.coll_id');
                            $coll_id = trim($coll_id);
                            $coll_id = str_replace('，', ',', $coll_id);
                            $coll_id = rtrim($coll_id,',');
                            
                            if($coll_id){
                                if(strpos($coll_id, ',') !== false){
                                    $collres = explode(',', $coll_id);
                                    $collres = array_unique($collres);
                                    
                                    if($collres && is_array($collres)){
                                        switch($filter){
                                            case 1:
                                                foreach ($collres as $val){
                                                    if(!empty($val)){
                                                        $colls = Db::name('coll_goods')->where('user_id',$user_id)->where('goods_id',$val)->find();
                                                        if(!$colls){
                                                            $value = array('status'=>400,'mess'=>'收藏信息参数错误','data'=>array('status'=>400));
                                                            return json($value);
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'收藏信息参数错误','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }

                                                $colltstr = implode(',', $collres);
                                                $count = Db::name('coll_goods')->where('user_id',$user_id)->where('goods_id','in',$colltstr)->delete();
                                                break;
                                            case 2:
                                                foreach ($collres as $val){
                                                    if(!empty($val)){
                                                        $colls = Db::name('coll_shops')->where('user_id',$user_id)->where('shop_id',$val)->find();
                                                        if(!$colls){
                                                            $value = array('status'=>400,'mess'=>'收藏信息参数错误','data'=>array('status'=>400));
                                                            return json($value);
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'收藏信息参数错误','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                                                
                                                $colltstr = implode(',', $collres);
                                                $count = Db::name('coll_shops')->where('user_id',$user_id)->where('shop_id','in',$colltstr)->delete();
                                                break;
                                            case 3:
                                                
                                                break;
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'收藏信息参数错误','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }else{
                                    switch($filter){
                                        case 1:
                                            $colls = Db::name('coll_goods')->where('user_id',$user_id)->where('goods_id',$coll_id)->find();
                                            if($colls){
                                                $count = Db::name('coll_goods')->where('user_id',$user_id)->where('goods_id',$coll_id)->delete();
                                            }else{
                                                $value = array('status'=>400,'mess'=>'收藏信息参数错误','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                            break;
                                        case 2:
                                            $colls = Db::name('coll_shops')->where('user_id',$user_id)->where('shop_id',$coll_id)->find();
                                            if($colls){
                                                $count = Db::name('coll_shops')->where('user_id',$user_id)->where('shop_id',$coll_id)->delete();
                                            }else{
                                                $value = array('status'=>400,'mess'=>'收藏信息参数错误','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                            break;
                                        case 3:
                                            
                                            break;
                                    }
                                }
                        
                                if($count > 0){
                                    $value = array('status'=>200,'mess'=>'删除成功','data'=>array('status'=>200));
                                }else{
                                    $value = array('status'=>400,'mess'=>'删除失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'收藏信息参数错误','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少收藏信息参数','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
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
     * @function取消单个商品
     * @author Feifan.Chen <1057286925@qq.com>
     * @return \think\response\Json
     */
    public function cancelGoods(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                        if(input('post.coll_id')){
                            $coll_id = input('post.coll_id');
                            $colls = Db::name('coll_goods')->where('user_id',$user_id)->where('goods_id',$coll_id)->find();
                            if(!$colls){
                                $value = array('status'=>400,'mess'=>'收藏信息参数错误','data'=>array('status'=>400));
                                return json($value);
                            }
                            $count = Db::name('coll_goods')->where('user_id',$user_id)->where('goods_id',$coll_id)->delete();
                            if ($count){
                                $value = array('status'=>200,'mess'=>'取消收藏成功','data'=>[]);
                            }

                        }else{
                            $value = array('status'=>400,'mess'=>'缺少收藏信息参数','data'=>array('status'=>400));
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