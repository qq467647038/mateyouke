<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;

class ShopContent extends Common{
    
    public function info(){
        if(request()->isPost()){
            $shop_id = session('shopsh_id');
            if(input('post.content')){
                $infos = Db::name('shop_content')->where('shop_id',$shop_id)->find();
                if($infos){
                    $count = Db::name('shop_content')->where('id',$infos['id'])->update(array('content'=>input('post.content')));
                    if($count !== false){
                        $value = array('status'=>1,'mess'=>'保存成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }else{
                    $lastId = Db::name('shop_content')->insert(array('content'=>input('post.content'),'shop_id'=>$shop_id));
                    if($lastId){
                        $value = array('status'=>1,'mess'=>'保存成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'商家详情介绍不能为空');
            }
            return $value;
        }else{
            $shop_id = session('shopsh_id');
            $infos = Db::name('shop_content')->where('shop_id',$shop_id)->find();
            $this->assign('infos',$infos);
            return $this->fetch();
        }
    }
}