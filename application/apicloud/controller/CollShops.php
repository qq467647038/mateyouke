<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class CollShops extends Common{
    
    //收藏店铺
    public function coll(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.shop_id')){
                        $shop_id = input('post.shop_id');
                        $shops = Db::name('shops')->where('id',$shop_id)->where('open_status',1)->field('id')->find();
                        if($shops){
                            $coll_shops = Db::name('coll_shops')->where('user_id',$user_id)->where('shop_id',$shop_id)->find();
                            if(!$coll_shops){
                                // 启动事务
                                Db::startTrans();
                                try{
                                    Db::name('coll_shops')->insert(array('shop_id'=>$shop_id,'user_id'=>$user_id,'addtime'=>time()));
                                    Db::name('shops')->where('id',$shop_id)->setInc('coll_num',1);
									
									//关注直播间
									//7关注主播（仅限一次）
									
									Db::name('alive_fans')->where('user_id',$user_id)->update(array('isfollow'=>1));
									
									$alive = db('alive')->where(['shop_id'=>$shop_id])->find();
									$room = $alive['room'];
									$num = $this->getAliveIntegralRules(7);
									$this->addAliveIntegral($user_id,$shop_id,$room,$num,7);
									
                                    // 提交事务
                                    Db::commit();
                                    $value = array('status'=>200,'mess'=>'关注成功','data'=>array('status'=>200));
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'关注失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'已收藏该店铺，请勿重复收藏','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'店铺不存在','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少商家参数','data'=>array('status'=>400));
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
                    if(input('post.shop_id')){
                        $shop_id = input('post.shop_id');
                        $coll_shops = Db::name('coll_shops')->where('user_id',$user_id)->where('shop_id',$shop_id)->find();
                        if($coll_shops){
                            $coll_num = Db::name('shops')->where('id',$shop_id)->value('coll_num');
                            // 启动事务
                            Db::startTrans();
                            try{
                                Db::name('coll_shops')->delete($coll_shops['id']);
								Db::name('alive_fans')->where('user_id',$user_id)->update(array('isfollow'=>0));
                                if($coll_num > 0){
                                    Db::name('shops')->where('id',$shop_id)->setDec('coll_num',1);
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
                            $value = array('status'=>400,'mess'=>'该店铺暂未收藏，取消失败','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少商家参数','data'=>array('status'=>400));
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
