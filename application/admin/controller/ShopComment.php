<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class ShopComment extends Common{
    public function lst(){
        $shop_id = session('shop_id');
        
        $filter = input('filter');
        
        if(!$filter || !in_array($filter, array(1,2,3))){
            $filter = 1;
        }
    
        $where = array();
        
        switch ($filter){
            case 1:
                $where = array('a.shop_id'=>array('neq',$shop_id),'a.checked'=>1);
                break;
            case 2:
                $where = array('a.shop_id'=>array('neq',$shop_id),'a.checked'=>2);
                break;
            case 3:
                $where = array('a.shop_id'=>array('neq',$shop_id));
                break;
        }

        $list = Db::name('comment')->alias('a')->field('a.*,b.goods_name,b.goods_attr_str,c.user_name,c.phone,d.shop_name')->join('sp_order_goods b','a.orgoods_id = b.id','LEFT')->join('sp_member c','a.user_id = c.id','LEFT')->join('sp_shops d','a.shop_id = d.id','LEFT')->where($where)->order('a.time desc')->paginate(25)->each(function($item,$k){
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
    
    public function checked(){
        if(request()->isPost()){
            if(input('post.id')){
                $shop_id = session('shop_id');
                $com_id = input('post.id');
                $comments = Db::name('comment')->where('id',$com_id)->where('shop_id','neq',$shop_id)->field('id')->find();
                if($comments){
                    if(input('post.checked') && in_array(input('post.checked'), array(1,2))){
                        $count = Db::name('comment')->update(array('checked'=>input('post.checked'),'id'=>$com_id));
                        if($count !== false){
                            $value = array('status'=>1, 'mess'=>'设置成功');
                        }else{
                            $value = array('status'=>0, 'mess'=>'设置失败');
                        }
                    }else{
                        $value = array('status'=>0, 'mess'=>'参数错误');
                    }
                }else{
                    $value = array('status'=>0, 'mess'=>'找不到相关信息');
                }
            }else{
                $value = array('status'=>0, 'mess'=>'缺少参数');
            }
            return $value;
        }else{
            if(input('com_id')){
                $shop_id = session('shop_id');
                $com_id = input('com_id');
                $coms = Db::name('comment')->alias('a')->field('a.*,b.goods_name,b.goods_attr_str,c.user_name,c.phone,d.shop_name')->join('sp_order_goods b','a.orgoods_id = b.id','LEFT')->join('sp_member c','a.user_id = c.id','LEFT')->join('sp_shops d','a.shop_id = d.id','LEFT')->where('a.id',$com_id)->where('a.shop_id','neq',$shop_id)->find();
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
    }
    
    public function deletecp(){
        $shop_id = session('shop_id');
        $id = input('post.id');
        if(!empty($id) && !is_array($id)){
            if(input('post.com_id')){
                $com_id = input('post.com_id');
                $coms = Db::name('comment')->where('id',$com_id)->where('shop_id','neq',$shop_id)->field('id')->find();
                if($coms){
                    $com_pics = Db::name('comment_pic')->where('id',$id)->where('com_id',$com_id)->find();
                    if($com_pics){
                        $count  = Db::name('comment_pic')->delete($id);
                        if($count > 0){
                            if($com_pics['img_url'] && file_exists('./'.$com_pics['img_url'])){
                                @unlink('./'.$com_pics['img_url']);
                            }
                            $value = array('status'=>1,'mess'=>'删除成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'删除失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'找不到相关信息');
                }
            }else{
                $value = array('status'=>0,'mess'=>'请选择删除项');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return $value;
    }
    
    public function delete(){
        $shop_id = session('shop_id');
        
        $id = input('id');
        if(!empty($id) && !is_array($id)){
            $coms = Db::name('comment')->where('id',$id)->where('shop_id','neq',$shop_id)->field('id')->find();
            if($coms){
                $picres = Db::name('comment_pic')->where('com_id',$id)->select();
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('comment')->where('id',$id)->delete();
                    Db::name('comment_pic')->where('com_id',$id)->delete();
                    // 提交事务
                    Db::commit();
                    if($picres){
                        foreach ($picres as $v){
                            if($v['img_url'] && file_exists('./'.$v['img_url'])){
                                @unlink('./'.$v['img_url']);
                            }
                        }
                    }
                    $value = array('status'=>1,'mess'=>'删除成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'找不到相关信息');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return $value;
    }
    
    public function search(){
        $shop_id = session('shop_id');
        
        if(input('post.keyword') != ''){
            cookie('shpj_keyword',input('post.keyword'),7200);
        }else{
            cookie('shpj_keyword',null);
        }
    
        if(input('post.pj_zt') != ''){
            cookie("shpj_zt", input('post.pj_zt'), 7200);
        }
    
        if(input('post.starttime') != ''){
            $shpjstarttime = strtotime(input('post.starttime'));
            cookie('shpjstarttime',$shpjstarttime,3600);
        }
    
        if(input('post.endtime') != ''){
            $shpjendtime = strtotime(input('post.endtime'));
            cookie('shpjendtime',$shpjendtime,3600);
        }
    
        $where = array();
        $where['a.shop_id'] = array('neq',$shop_id);
        
        if(cookie('shpj_zt') != ''){
            $pj_zt = (int)cookie('shpj_zt');
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
    
        if(cookie('shpj_keyword')){
            $where['a.content'] = array('like','%'.cookie('shpj_keyword').'%');
        }
    
        if(cookie('shpjendtime') && cookie('shpjstarttime')){
            $where['a.time'] = array(array('egt',cookie('shpjstarttime')), array('lt',cookie('shpjendtime')));
        }
    
        if(cookie('shpjstarttime') && !cookie('shpjendtime')){
            $where['a.time'] = array(array('egt',cookie('shpjstarttime')));
        }
    
        if(cookie('shpjendtime') && !cookie('shpjstarttime')){
            $where['a.time'] = array(array('lt',cookie('shpjendtime')));
        }
    
        $list = Db::name('comment')->alias('a')->field('a.*,b.goods_name,b.goods_attr_str,c.user_name,c.phone,d.shop_name')->join('sp_order_goods b','a.orgoods_id = b.id','LEFT')->join('sp_member c','a.user_id = c.id','LEFT')->join('sp_shops d','a.shop_id = d.id','LEFT')->where($where)->order('a.time desc')->paginate(25);
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
    
        if(cookie('shpjstarttime')){
            $this->assign('starttime',cookie('shpjstarttime'));
        }
    
        if(cookie('shpjendtime')){
            $this->assign('endtime',cookie('shpjendtime'));
        }
    
        if(cookie('shpj_keyword')){
            $this->assign('keyword',cookie('shpj_keyword'));
        }
    
        if(cookie('shpj_zt') != ''){
            $this->assign('pj_zt',cookie('shpj_zt'));
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