<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class CollGoods extends Common{
    
    //收藏商品
    public function coll(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.goods_id')){
                        $goods_id = input('post.goods_id');
                        $goods = Db::name('goods')->alias('a')->field('a.id,a.cate_id,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$goods_id)->where('a.onsale',1)->where('b.open_status',1)->find();
                        if($goods){
                            $coll_goods = Db::name('coll_goods')->where('user_id',$user_id)->where('goods_id',$goods_id)->find();
                            if(!$coll_goods){
                                $pid = Db::name('category')->where('id',$goods['cate_id'])->value('pid');
                                if($pid == 0){
                                    $cid = $goods['cate_id'];
                                }else{
                                    $categoryres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
                                    $cateIds = array();
                                    $cateIds = get_all_parent($categoryres, $goods['cate_id']);
                                    $cid = end($cateIds);
                                    
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        Db::name('coll_goods')->insert(array('goods_id'=>$goods_id,'user_id'=>$user_id,'cate_id'=>$cid,'addtime'=>time()));
                                        Db::name('goods')->where('id',$goods_id)->setInc('coll_num',1);
                                        // 提交事务
                                        Db::commit();
                                        $value = array('status'=>200,'mess'=>'收藏成功','data'=>array('status'=>200));
                                    } catch (\Exception $e) {
                                        // 回滚事务
                                        Db::rollback();
                                        $value = array('status'=>400,'mess'=>'收藏失败','data'=>array('status'=>400));
                                    }
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'已收藏该商品，请勿重复收藏','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'商品不存在或已下架','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少商品参数','data'=>array('status'=>400));
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
    
    //取消收藏
    public function cancelcoll(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.goods_id')){
                        $goods_id = input('post.goods_id');
                        $coll_goods = Db::name('coll_goods')->where('user_id',$user_id)->where('goods_id',$goods_id)->find();
                        if($coll_goods){
                            $coll_num = Db::name('goods')->where('id',$goods_id)->value('coll_num');
                            // 启动事务
                            Db::startTrans();
                            try{
                                Db::name('coll_goods')->delete($coll_goods['id']);
                                if($coll_num > 0){
                                    Db::name('goods')->where('id',$goods_id)->setDec('coll_num',1);
                                }
                                // 提交事务
                                Db::commit();
                                $value = array('status'=>200,'mess'=>'取消成功','data'=>array('status'=>200));
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status'=>400,'mess'=>'取消失败','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'该商品暂未收藏，取消失败','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少商品参数','data'=>array('status'=>400));
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