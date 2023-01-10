<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Tousu extends Common{
    
    public function lst(){
        $list = Db::name('tousu')->field('id,zhuti,contacts,telephone,email,wx_num,qq_num,addtime')->order('addtime desc')->paginate(50);
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
        $tousus = Db::name('tousu')->where('id',$id)->find();
        $this->assign('tousus',$tousus);
        return $this->fetch();
    }   

    public function delete(){
        if(input('post.id')){
            $id= array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        if(!empty($id)){
            $count = Db::name('toushu')->delete($id);
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
            cookie('tousu_keyword',input('post.keyword'),7200);
        }
        
        if(input('post.starttime') != ''){
            $orstarttime = strtotime(input('post.starttime'));
            cookie('tousustarttime',$orstarttime,7200);
        }
        
        if(input('post.endtime') != ''){
            $orendtime = strtotime(input('post.endtime'));
            cookie('tousuendtime',$orendtime,7200);
        }
        
        $where = array();
        
        if(cookie('tousu_keyword')){
            $where['a.zhuti'] = array('like','%'.cookie('tousu_keyword').'%');
        }
        
        if(cookie('tousuendtime') && cookie('tousustarttime')){
            $where['a.addtime'] = array(array('egt',cookie('tousustarttime')), array('lt',cookie('tousuendtime')));
        }
        
        if(cookie('tousustarttime') && !cookie('tousuendtime')){
            $where['a.addtime'] = array('egt',cookie('tousustarttime'));
        }
        
        if(cookie('tousuendtime') && !cookie('tousustarttime')){
            $where['a.addtime'] = array('lt',cookie('tousuendtime'));
        }
        
        $list = Db::name('tousu')->field('id,zhuti,contacts,telephone,email,wx_num,qq_num,addtime')->order('addtime desc')->paginate(50);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('tousu_keyword')){
            $this->assign('keyword',cookie('tousu_keyword'));
        }
        
        if(cookie('tousustarttime')){
            $this->assign('starttime',cookie('tousustarttime'));
        }
        
        if(cookie('tousuendtime')){
            $this->assign('endtime',cookie('tousuendtime'));
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