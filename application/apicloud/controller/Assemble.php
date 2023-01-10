<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class Assemble extends Common{
    
    //获取一级分类
    public function getcate(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                $cateres = Db::name('category')->where('pid',0)->where('is_show',1)->field('id,cate_name')->order('sort asc')->select();
                $value = array('status'=>200,'mess'=>'获取一级分类信息成功','data'=>$cateres);
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //根据分类获取拼团商品列表
    public function getgoodslst(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                    $pagenum = input('post.page');
                    $webconfig = $this->webconfig;
                    $perpage = $webconfig['app_goodlst_num'];
                    $offset = ($pagenum-1)*$perpage;
                    $where = array();
                    if(!input('post.cate_id')){
                        $where['a.recommend'] = 1;
                    }
                    $where['a.checked'] = 1;
                    $where['a.is_show'] = 1;
                    $where['a.start_time'] = array('elt',time());
                    $where['a.end_time'] = array('gt',time());
                    
                    if(input('post.cate_id')){
                        $cate_id = input('post.cate_id');
                        $cates = Db::name('category')->where('id',$cate_id)->where('pid',0)->where('is_show',1)->field('id,cate_name,type_id')->find();
                        if($cates){
                            $categoryres = Db::name('category')->where('is_show',1)->field('id,pid')->order('sort asc')->select();
                            $cateIds = array();
                            $cateIds = get_all_child($categoryres, $cate_id);
                            $cateIds[] = $cate_id;
                            $cateIds = implode(',', $cateIds);
                            $where['b.cate_id'] = array('in',$cateIds);
                        }else{
                            $value = array('status'=>400,'mess'=>'分类信息参数错误','data'=>array('status'=>400));
                            return json($value);
                        }
                    }
                    $where['b.onsale'] = 1;
                    $where['c.open_status'] = 1;
                    
                    $assembleres = Db::name('assemble')->alias('a')->field('a.id,a.goods_id,a.goods_attr,a.price,a.pin_num,b.goods_name,b.thumb_url,b.shop_price,b.min_price,b.max_price,b.zs_price,b.leixing,b.shop_id')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where($where)->group('a.goods_id')->order('a.apply_time asc')->limit($offset,$perpage)->select();

                    if($assembleres){
                        foreach ($assembleres as $kc => $vc){
                            $assembleres[$kc]['thumb_url'] = $webconfig['weburl'].'/'.$vc['thumb_url'];
                    
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
                                        $assembleres[$kc]['shop_price']+=$vr['attr_price'];
                                    }
                                    $assembleres[$kc]['goods_name']=$assembleres[$kc]['goods_name'].' '.$goods_attr_str;
                                    $assembleres[$kc]['shop_price']=sprintf("%.2f", $assembleres[$kc]['shop_price']);
                                }else{
                                    $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }else{
                                if($vc['min_price'] != $vc['max_price']){
                                    $assembleres[$kc]['shop_price'] = $vc['min_price'].'-'.$vc['max_price'];
                                }else{
                                    $assembleres[$kc]['shop_price'] = $vc['min_price'];
                                }
                            }
                            
                            $assembleres[$kc]['pin_number'] = Db::name('order_assemble')->where('hd_id',$vc['id'])->where('state',1)->count();
                        }
                    }
                    
                    $value = array('status'=>200,'mess'=>'获取拼团商品列表成功','data'=>$assembleres);
                }else{
                    $value = array('status'=>400,'mess'=>'缺少页面参数','data'=>array('status'=>400));
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
     * @function根据分类获取积分商品列表
     * @author Feifan.Chen <1057286925@qq.com>
     * @return \think\response\Json
     */
    public function getCreditGoodslst(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            $result['status'] = 200;
            if($result['status'] == 200){
                if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                    $pagenum = input('post.page');
                    $webconfig = $this->webconfig;
                    $perpage = $webconfig['app_goodlst_num'];
                    $offset = ($pagenum-1)*$perpage;
                    $where = array();
                    if(!input('post.cate_id')){
                        $where['a.recommend'] = 1;
                    }
                    $where['a.is_show'] = 1;
                    if(input('post.cate_id')){
                        $cate_id = input('post.cate_id');
                        $cates = Db::name('category')->where('id',$cate_id)->where('pid',0)->where('is_show',1)->field('id,cate_name,type_id')->find();
                        if($cates){
                            $categoryres = Db::name('category')->where('is_show',1)->field('id,pid')->order('sort asc')->select();
                            $cateIds = array();
                            $cateIds = get_all_child($categoryres, $cate_id);
                            $cateIds[] = $cate_id;
                            $cateIds = implode(',', $cateIds);
                            $where['b.cate_id'] = array('in',$cateIds);
                        }else{
                            $value = array('status'=>400,'mess'=>'分类信息参数错误','data'=>array('status'=>400));
                            return json($value);
                        }
                    }
                    $where['b.onsale'] = 1;
                    $where['c.open_status'] = 1;

                    $assembleres = Db::name('creditshop')->alias('a')->field('a.id,a.goods_id,a.goods_attr,a.price,a.credit,b.goods_name,b.thumb_url,b.shop_price,b.min_price,b.max_price,b.zs_price,b.leixing,b.shop_id')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where($where)->group('a.goods_id')->limit($offset,$perpage)->select();

                    if($assembleres){
                        foreach ($assembleres as $kc => $vc){
                            $assembleres[$kc]['thumb_url'] = $webconfig['weburl'].'/'.$vc['thumb_url'];

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
                                        $assembleres[$kc]['shop_price']+=$vr['attr_price'];
                                    }
                                    $assembleres[$kc]['goods_name']=$assembleres[$kc]['goods_name'].' '.$goods_attr_str;
                                    $assembleres[$kc]['shop_price']=sprintf("%.2f", $assembleres[$kc]['shop_price']);
                                }else{
                                    $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }else{
                                if($vc['min_price'] != $vc['max_price']){
                                    $assembleres[$kc]['shop_price'] = $vc['min_price'].'-'.$vc['max_price'];
                                }else{
                                    $assembleres[$kc]['shop_price'] = $vc['min_price'];
                                }
                            }

                            $assembleres[$kc]['pin_number'] = Db::name('order')
                                ->alias('a')
                                ->join('order_goods b','a.id=b.order_id','left')
                                ->where('goods_id',$vc['goods_id'])
                                ->where('state',1)
                                ->where('order_type',3)
                                ->count();
                        }
                    }

                    $value = array('status'=>200,'mess'=>'获取拼团商品列表成功','data'=>$assembleres);
                }else{
                    $value = array('status'=>400,'mess'=>'缺少页面参数','data'=>array('status'=>400));
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