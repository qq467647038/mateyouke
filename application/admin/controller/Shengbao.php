<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Shengbao extends Common{
    
    public function lst(){
        $list = Db::name('shengbao')->field('id,guzhang,contacts,telephone,shengshiqu,address,addtime')->order('addtime desc')->paginate(50);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }

    public function info(){
        $id = input('id');
        $gzs = Db::name('shengbao')->where('id',$id)->find();
        $this->assign('gzs',$gzs);
        return $this->fetch();
    }   

    public function delete(){
        if(input('post.id')){
            $id= array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        if(!empty($id)){
            $count = Db::name('shengbao')->delete($id);
            if($count > 0){
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'编辑失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }

    public function search(){
        if(input('post.keyword') != ''){
            cookie('gz_keyword',input('post.keyword'),7200);
        }
        
        if(input('post.starttime') != ''){
            $gzstarttime = strtotime(input('post.starttime'));
            cookie('gzstarttime',$gzstarttime,7200);
        }
        
        if(input('post.endtime') != ''){
            $gzendtime = strtotime(input('post.endtime'));
            cookie('gzendtime',$gzendtime,7200);
        }
        
        $where = array();
        
        if(cookie('gz_keyword')){
            $where['a.zhuti'] = array('like','%'.cookie('gz_keyword').'%');
        }
        
        if(cookie('gzendtime') && cookie('gzstarttime')){
            $where['a.addtime'] = array(array('egt',cookie('gzstarttime')), array('lt',cookie('gzendtime')));
        }
        
        if(cookie('gzstarttime') && !cookie('gzendtime')){
            $where['a.addtime'] = array('egt',cookie('gzstarttime'));
        }
        
        if(cookie('gzendtime') && !cookie('gzstarttime')){
            $where['a.addtime'] = array('lt',cookie('gzendtime'));
        }
        
        $list = Db::name('shengbao')->field('id,guzhang,contacts,telephone,shengshiqu,address,addtime')->order('addtime desc')->paginate(50);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('gz_keyword')){
            $this->assign('keyword',cookie('gz_keyword'));
        }
        
        if(cookie('gzstarttime')){
            $this->assign('starttime',cookie('gzstarttime'));
        }
        
        if(cookie('gzendtime')){
            $this->assign('endtime',cookie('gzendtime'));
        }
        
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
}

?>