<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class RzOrder extends Common{ 
    
    public function lst(){
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3))){
            $filter = 3;
        }
        
        $where = array();
        
        switch ($filter){
            case 1:
                //已支付
                $where = array('a.state'=>1);
                break;
            case 2:
                //未支付
                $where = array('a.state'=>0);
                break;
            case 3:
                //全部
                break;
        }
        
        $list = Db::name('rz_order')->alias('a')->field('a.id,a.ordernumber,a.contacts,a.telephone,a.total_price,a.state,a.addtime,b.pro_name,c.city_name,d.area_name')->join('sp_province b','a.pro_id = b.id','LEFT')->join('sp_city c','a.city_id = c.id','LEFT')->join('sp_area d','a.area_id = d.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
        
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum,
            'prores'=>$prores,
            'filter'=>$filter
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }        

    }
    
    public function info(){
        if(input('order_id')){
            $id = input('order_id');
            $orders = Db::name('rz_order')->alias('a')->field('a.*,b.industry_name,c.pro_name,d.city_name,u.area_name')->join('sp_industry b','a.indus_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where('a.id',$id)->find();
            if($orders){
                if(input('s')){
                    $this->assign('search',input('s'));
                }
                $this->assign('orders',$orders);
                return $this->fetch();
            }else{
                $this->error('参数错误');
            }
        }else{
            $this->error('缺少参数');
        }
    }
    
    
    public function search(){
        if(input('post.keyword') != ''){
            cookie('rzorder_keyword',input('post.keyword'),7200);
        }else{
            cookie('rzorder_keyword',null);
        }
    
        if(input('post.search_type') != ''){
            cookie('rzorder_type',input('post.search_type'),7200);
        }
    
        if(input('post.zhifu_type') != ''){
            cookie("rzorder_zhifu_type", input('post.zhifu_type'), 7200);
        }
    
        if(input('post.starttime') != ''){
            $rzorderstarttime = strtotime(input('post.starttime'));
            cookie('rzorderstarttime',$rzorderstarttime,3600);
        }
    
        if(input('post.endtime') != ''){
            $rzorderendtime = strtotime(input('post.endtime'));
            cookie('rzorderendtime',$rzorderendtime,3600);
        }
    
        if(input('post.pro_id') != ''){
            cookie("rzorder_pro_id", input('post.pro_id'), 7200);
        }
    
        if(input('post.city_id') != ''){
            cookie("rzorder_city_id", input('post.city_id'), 7200);
        }
    
        if(input('post.area_id') != ''){
            cookie("rzorder_area_id", input('post.area_id'), 7200);
        }
    
        $where = array();
    
        if(cookie('rzorder_pro_id') != ''){
            $proid = (int)cookie('rzorder_pro_id');
            if($proid != 0){
                $where['a.pro_id'] = $proid;
            }
        }
    
        if(cookie('rzorder_city_id') != ''){
            $cityid = (int)cookie('rzorder_city_id');
            if($cityid != 0){
                $where['a.city_id'] = $cityid;
            }
        }
    
        if(cookie('rzorder_area_id') != ''){
            $areaid = (int)cookie('rzorder_area_id');
            if($areaid != 0){
                $where['a.area_id'] = $areaid;
            }
        }
    
        if(cookie('rzorder_zhifu_type') != ''){
            $rzorder_zhifu_type = (int)cookie('rzorder_zhifu_type');
            if($rzorder_zhifu_type != 0){
                switch($rzorder_zhifu_type){
                    //已支付
                    case 1:
                        $where['a.state'] = 1;
                        break;
                        //待支付
                    case 2:
                        $where['a.state'] = 0;
                        break;
                }
            }
        }
    
        if(cookie('rzorder_type')){
            if(cookie('rzorder_type') == 1 && cookie('rzorder_keyword')){
                $where['a.ordernumber'] = cookie('rzorder_keyword');
            }elseif(cookie('rzorder_type') == 2 && cookie('rzorder_keyword')){
                $where['a.telephone'] = cookie('rzorder_keyword');
            }
        }
    
        if(cookie('rzorderendtime') && cookie('rzorderstarttime')){
            $where['a.addtime'] = array(array('egt',cookie('rzorderstarttime')), array('lt',cookie('rzorderendtime')));
        }
    
        if(cookie('rzorderstarttime') && !cookie('rzorderendtime')){
            $where['a.addtime'] = array('egt',cookie('rzorderstarttime'));
        }
    
        if(cookie('rzorderendtime') && !cookie('rzorderstarttime')){
            $where['a.addtime'] = array('lt',cookie('rzorderendtime'));
        }
    
    
        $list = Db::name('rz_order')->alias('a')->field('a.id,a.ordernumber,a.contacts,a.telephone,a.total_price,a.state,a.addtime,b.pro_name,c.city_name,d.area_name')->join('sp_province b','a.pro_id = b.id','LEFT')->join('sp_city c','a.city_id = c.id','LEFT')->join('sp_area d','a.area_id = d.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();
    
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
    
        if(cookie('rzorder_pro_id')){
            $cityres = Db::name('city')->where('pro_id',cookie('rzorder_pro_id'))->field('id,city_name,zm')->order('sort asc')->select();
        }
    
        if(cookie('rzorder_pro_id') && cookie('rzorder_city_id')){
            $areares = Db::name('area')->where('city_id',cookie('rzorder_city_id'))->field('id,area_name,zm')->select();
        }
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
    
        if(cookie('rzorder_keyword') != ''){
            $this->assign('keyword',cookie('rzorder_keyword'));
        }
    
        if(cookie('rzorder_type') != ''){
            $this->assign('search_type',cookie('rzorder_type'));
        }
    
        if(cookie('rzorder_zhifu_type') != ''){
            $this->assign('zhifu_type',cookie('rzorder_zhifu_type'));
        }
    
        if(cookie('rzorderstarttime') != ''){
            $this->assign('starttime',cookie('rzorderstarttime'));
        }
    
        if(cookie('rzorderendtime') != ''){
            $this->assign('endtime',cookie('rzorderendtime'));
        }
    
        if(cookie('rzorder_pro_id') != ''){
            $this->assign('pro_id',cookie('rzorder_pro_id'));
        }
        if(cookie('rzorder_city_id') != ''){
            $this->assign('city_id',cookie('rzorder_city_id'));
        }
        if(cookie('rzorder_area_id') != ''){
            $this->assign('area_id',cookie('rzorder_area_id'));
        }
    
        if(!empty($cityres)){
            $this->assign('cityres',$cityres);
        }
    
        if(!empty($areares)){
            $this->assign('areares',$areares);
        }
    
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('filter',3);
        $this->assign('prores',$prores);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
}