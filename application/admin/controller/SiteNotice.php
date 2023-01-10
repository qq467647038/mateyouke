<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\SiteNotice as SiteNoticeModel;

class SiteNotice extends Common{
    //公告列表
    public function lst(){
        $list = Db::name('site_notice')->order('id desc')->paginate(25)->each(function($item){
            $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
            return $item;
        });
        
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
           'pnum'=>$pnum,
           'list'=>$list,
           'page'=>$page
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch();
        }
    }
    
    //编辑公告
    public function edit(){
        if(request()->isAjax()){
            $data = input('post.');
            $admin_id = session('admin_id');
            $result = $this->validate($data,'SiteNotice');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $ars = Db::name('site_notice')->where('id',$data['id'])->find();
                if($ars){
                    $news = new SiteNoticeModel();
                    $count = $news->allowField(true)->save($data,array('id'=>$data['id']));
                    if($count !== false){
                        $value = array('status'=>1,'mess'=>'编辑成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'编辑失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                }
            }
            return json($value);
        }else{
            $id = input('id');
            $admin_id = session('admin_id');
            $ars = Db::name('site_notice')->find($id);
            $this->assign('pnum', input('page'));
            if(input('s')){
                $this->assign('search', input('s'));
            }
            $this->assign('ars',$ars);
            return $this->fetch();
        }
    }
    
    //添加公告
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $admin_id = session('admin_id');
            $result = $this->validate($data,'SiteNotice');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $data['aid'] = session('admin_id');
                $data['addtime'] = time();
                $news = new SiteNoticeModel();
                $news->data($data);
                $lastId = $news->allowField(true)->save();
                if($lastId){
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }

    public function delete(){
        if(input('post.id')){
            $id = array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        if(!empty($id)){
            if(is_array($id)){
                $delId = implode(',', $id);
                $pic = Db::name('site_notice')->where('id','in',$delId)->select();
            }else{
                $pic =  Db::name('site_notice')->where('id',$id)->value('title');
            }
            $count = Db::name('site_notice')->delete($id);
            if($count > 0){
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'删除失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }
 
}
