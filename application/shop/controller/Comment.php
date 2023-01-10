<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;

class Comment extends Common{
    public function lst(){
        $shop_id = session('shopsh_id');
        $filter = input('filter');
        
        if(!$filter || !in_array($filter, array(1,2,3))){
            $filter = 1;
        }
    
        $where = array();
        
        switch ($filter){
            case 1:
                $where = array('a.shop_id'=>$shop_id,'a.checked'=>1);
                break;
            case 2:
                $where = array('a.shop_id'=>$shop_id,'a.checked'=>2);
                break;
            case 3:
                $where = array('a.shop_id'=>$shop_id);
                break;
        }
        
        $list = Db::name('comment')->alias('a')->field('a.*,b.goods_name,b.goods_attr_str,c.user_name,c.phone')->join('sp_order_goods b','a.orgoods_id = b.id','LEFT')->join('sp_member c','a.user_id = c.id','LEFT')->where($where)->order('a.time desc')->paginate(25)->each(function($item,$k){
            $images = Db::name('comment_pic')->where('com_id',$item['id'])->select();
            $item['images'] = array();
            if($images){
                foreach ($images as $ik=>$iv){
                    $item['images'][] = $this->webconfig['weburl'].$iv['img_url'];
                }
            }
            return $item;
        });
    
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum,
            'filter'=>$filter
        ));
    
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function info(){
        if(input('com_id')){
            $shop_id = session('shopsh_id');
            $com_id = input('com_id');
            $coms = Db::name('comment')->alias('a')->field('a.*,b.goods_name,b.goods_attr_str,c.user_name,c.phone,d.shop_name')->join('sp_order_goods b','a.orgoods_id = b.id','LEFT')->join('sp_member c','a.user_id = c.id','LEFT')->join('sp_shops d','a.shop_id = d.id','LEFT')->where('a.id',$com_id)->where('a.shop_id',$shop_id)->find();
            if($coms){
                $coms['images'] = Db::name('comment_pic')->where('com_id',$com_id)->select();
                if($coms['images']){
                    foreach ($coms['images'] as $ik=>$iv){
                        $coms['images'][$ik]['img_url'] = $this->webconfig['weburl'].$iv['img_url'];
                    }
                }
                if(input('s')){
                    $this->assign('search',input('s'));
                }
                $this->assign('pnum',input('page'));
                $this->assign('filter',input('filter'));
                $this->assign('coms',$coms);
                return $this->fetch();
            }else{
                $this->error('找不到相关信息');
            }
        }else{
            $this->error('缺少参数');
        }
    }

    public function search(){
        $shop_id = session('shopsh_id');
        
        if(input('post.keyword') != ''){
            cookie('shoppj_keyword',input('post.keyword'),7200);
        }else{
            cookie('shoppj_keyword',null);
        }
    
        if(input('post.pj_zt') != ''){
            cookie("shoppj_zt", input('post.pj_zt'), 7200);
        }
    
        if(input('post.starttime') != ''){
            $shoppjstarttime = strtotime(input('post.starttime'));
            cookie('shoppjstarttime',$shoppjstarttime,3600);
        }
    
        if(input('post.endtime') != ''){
            $shoppjendtime = strtotime(input('post.endtime'));
            cookie('shoppjendtime',$shoppjendtime,3600);
        }
    
        $where = array();
        $where['a.shop_id'] = $shop_id;
        
        if(cookie('shoppj_zt') != ''){
            $pj_zt = (int)cookie('shoppj_zt');
            if($pj_zt != 0){
                switch($pj_zt){
                    //正常
                    case 1:
                        $where['a.checked'] = 1;
                        break;
                    //撤销
                    case 2:
                        $where['a.checked'] = 2;
                        break;
                }
            }
        }
    
        if(cookie('shoppj_keyword')){
            $where['a.content'] = array('like','%'.cookie('shoppj_keyword').'%');
        }
    
        if(cookie('shoppjendtime') && cookie('shoppjstarttime')){
            $where['a.time'] = array(array('egt',cookie('shoppjstarttime')), array('lt',cookie('shoppjendtime')));
        }
    
        if(cookie('shoppjstarttime') && !cookie('shoppjendtime')){
            $where['a.time'] = array(array('egt',cookie('shoppjstarttime')));
        }
    
        if(cookie('shoppjendtime') && !cookie('shoppjstarttime')){
            $where['a.time'] = array(array('lt',cookie('shoppjendtime')));
        }
    
        $list = Db::name('comment')->alias('a')->field('a.*,b.goods_name,b.goods_attr_str,c.user_name,c.phone')->join('sp_order_goods b','a.orgoods_id = b.id','LEFT')->join('sp_member c','a.user_id = c.id','LEFT')->where($where)->order('a.time desc')->paginate(25);
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
    
        if(cookie('shoppjstarttime')){
            $this->assign('starttime',cookie('shoppjstarttime'));
        }
    
        if(cookie('shoppjendtime')){
            $this->assign('endtime',cookie('shoppjendtime'));
        }
    
        if(cookie('shoppj_keyword')){
            $this->assign('keyword',cookie('shoppj_keyword'));
        }
    
        if(cookie('shoppj_zt') != ''){
            $this->assign('pj_zt',cookie('shoppj_zt'));
        }
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('filter',3);
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