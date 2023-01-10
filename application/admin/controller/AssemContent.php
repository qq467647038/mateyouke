<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class AssemContent extends Common{
    
    public function info(){
        if(request()->isPost()){
            $shop_id = session('shop_id');
            if(input('post.content')){
                $infos = Db::name('assem_content')->where('id',1)->find();
                if($infos){
                    $count = Db::name('assem_content')->where('id',$infos['id'])->update(array('content'=>input('post.content')));
                    if($count !== false){
                        $value = array('status'=>1,'mess'=>'保存成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'找不到相关信息');
                }
            }else{
                $value = array('status'=>0,'mess'=>'拼团规则介绍不能为空');
            }
            return json($value);
        }else{
            $shop_id = session('shop_id');
            $infos = Db::name('assem_content')->where('id',1)->find();
            $this->assign('infos',$infos);
            return $this->fetch();
        }
    }
}