<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class OrderZong extends Common{

    public function lst(){
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3))){
            $filter = 1;
        }
        
        $where = array();
    
        switch ($filter){
            //已支付
            case 1:
                $where = array('oz.state'=>1);
                break;
            //待支付
            case 2:
                $where = array('oz.state'=>0);
                break;
            case 3:

                break;
        }
    
    
        $list = Db::name('order_zong')->alias('oz')->where($where)->join('member m', 'oz.user_id = m.id', 'inner')->field('oz.*, m.user_name, m.headimgurl')->order('oz.addtime desc')->paginate(25);
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $this->assign('filter',$filter);
        $this->assign('pnum',$pnum);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('list',$list);// 赋值数据集
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function delete(){
        if(input('id') && !is_array(input('id'))){
            $id = input('id');
            $orders = Db::name('order_zong')->where('id',$id)->where('state',0)->field('id')->find();
            if($orders){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('order_zong')->where('id',$id)->delete();
                    // 提交事务
                    Db::commit();
                    ys_admin_logs('删除总订单','order_zong',$id);
                    $value = array('status'=>1,'mess'=>'删除成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'已支付订单不可删除');
            }
        }else{
            $value = array('status'=>0,'mess'=>'删除失败');
        }
        return json($value);
    }
    
    public function search(){
        if(input('post.keyword') != ''){
            cookie('zong_keyword',input('post.keyword'),7200);
        }else{
            cookie('zong_keyword',null);
        }
    
        if(input('post.order_zt') != ''){
            cookie("zong_order_zt", input('post.order_zt'), 7200);
        }
    
        if(input('post.zf_type') != ''){
            cookie("zong_zf_type", input('post.zf_type'), 7200);
        }
    
        if(input('post.starttime') != ''){
            $zongstarttime = strtotime(input('post.starttime'));
            cookie('zongstarttime',$zongstarttime,7200);
        }
    
        if(input('post.endtime') != ''){
            $zongendtime = strtotime(input('post.endtime'));
            cookie('zongendtime',$zongendtime,7200);
        }
    
        $where = array();
        
        if(cookie('zong_keyword')){
            $where['order_number'] = cookie('zong_keyword');
        }
    
        $nowtime = time();
    
        if(cookie('zong_order_zt') != ''){
            $order_zt = (int)cookie('zong_order_zt');
    
            if($order_zt != 0){
                switch($order_zt){
                    //已支付
                    case 1:
                        $where['state'] = 1;
                        break;
                    //待支付
                    case 2:
                        $where['state'] = 0;
                        break;
                }
            }
        }
    
        if(cookie('zong_zf_type') != ''){
            $zf_type = (int)cookie('zong_zf_type');
            if($zf_type != 0){
                switch($zf_type){
                    //微信支付
                    case 1:
                        $where['zf_type'] = 1;
                        break;
                    //支付宝支付
                    case 2:
                        $where['zf_type'] = 2;
                        break;  
                    //余额支付
                    case 2:
                        $where['zf_type'] = 3;
                        break;
                }
            }
        }
    
        if(cookie('zongendtime') && cookie('zongstarttime')){
            $where['addtime'] = array(array('egt',cookie('zongstarttime')), array('lt',cookie('zongendtime')));
        }
    
        if(cookie('zongstarttime') && !cookie('zongendtime')){
            $where['addtime'] = array('egt',cookie('zongstarttime'));
        }
    
        if(cookie('zongendtime') && !cookie('zongstarttime')){
            $where['addtime'] = array('lt',cookie('zongendtime'));
        }
    
        $list = Db::name('order_zong')->where($where)->order('addtime desc')->paginate(25)->each(function($item){
            $item['headimgurl'] = Db::name('member')->where('id', $item['user_id'])->value('headimgurl');
            return $item;
        });
    
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $search = 1;
    
        if(cookie('zongstarttime')){
            $this->assign('starttime',cookie('zongstarttime'));
        }
    
        if(cookie('zongendtime')){
            $this->assign('endtime',cookie('zongendtime'));
        }
    
        if(cookie('zong_keyword')){
            $this->assign('keyword',cookie('zong_keyword'));
        }
    
        if(cookie('zong_order_zt') != ''){
            $this->assign('order_zt',cookie('zong_order_zt'));
        }
    
        if(cookie('zong_zf_type') != ''){
            $this->assign('zf_type',cookie('zong_zf_type'));
        }
    
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('filter',3);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }    
 
    
}
