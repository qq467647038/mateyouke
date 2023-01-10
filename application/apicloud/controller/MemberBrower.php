<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use app\apicloud\model\MemberBrowse;
use think\Db;

class MemberBrower extends Common{


    /**
     * @function添加商品足迹
     * @author Feifan.Chen <1057286925@qq.com>
     * @desc 此接口仅为外部测试接口内部调用MemberBrowse::addBrowse($goods_id,$user_id);
     * @return \think\response\Json
     */
    public function browerAdd(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                $goods_id = input('post.goods_id');
                if (empty($goods_id)) $value = array('status'=>400,'mess'=>'缺少商品参数','data'=>array('status'=>400));
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $data = MemberBrowse::addBrowse($goods_id,$user_id);
                    if ($data['status'] == 200){
                        $value = $data;
                    }else{
                        $value = array('status'=>400,'mess'=>'未知错误','data'=>[]);
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
     * @function我的足迹列表
     * @author Feifan.Chen <1057286925@qq.com>
     * @return \think\response\Json
     */
    public function browerList(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
//                    $user_id = 1;
                    $data = MemberBrowse::browerList($user_id);
                    foreach ($data as $a=>$v){
                        if (array_key_exists('goods_list',$v)){
                            foreach ($v['goods_list'] as $key=>&$value){
                                $value['thumb_url'] = $this->webconfig['weburl'].'/'.$value['thumb_url'];
                            }
                        };

                    }
                    $value = array('status'=>200,'mess'=>'获取足迹列表成功','data'=>$data);
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