<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Getshops extends Common{
    //列表
    public function lst(){
        $where = array();
        if(input('goods_id')){
            $goods_id = '1,'.input('goods_id');
            $where['a.id'] = array('not in',$goods_id);
        }else{
            $where['a.id'] = array('neq',1);
            $goods_id = '';
            if(cookie('ptget_shops_id')){
                cookie('ptget_shops_id',null);
            }
        }
        
        $where['a.open_status'] = 1;
        
        $list = Db::name('shops')->alias('a')->field('a.id,a.shop_name,a.contacts,a.telephone,b.industry_name')->join('sp_industry b','a.indus_id = b.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $industryres = Db::name('industry')->where('is_show',1)->field('id,industry_name')->order('sort asc')->select();
        
        $this->assign(array(
            'pnum'=>$pnum,
            'list'=>$list,
            'page'=>$page,
            'industryres'=>$industryres,
            'goods_id'=>$goods_id
        ));
        
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch();
        }
    }
    
    //搜索
    public function search(){
        if(input('post.keyword') != ''){
            cookie('ptshops_keyword',input('post.keyword'),3600);
        }else{
            cookie('ptshops_keyword',null);
        }
        
        if(input('post.search_type') != ''){
            cookie('ptshops_type',input('post.search_type'),7200);
        }
        
        if(input('post.indus_id') != ''){
            cookie('ptshops_indus_id',input('post.indus_id'),3600);
        }

        if(input('post.goods_id') != ''){
            cookie('ptget_shops_id','1,'.input('post.goods_id'),3600);
        }
        
        $industryres = Db::name('industry')->where('is_show',1)->field('id,industry_name')->order('sort asc')->select();
        
        $where = array();
        
        if(cookie('ptget_shops_id')){
            $where['a.id'] = array('not in',cookie('ptget_shops_id'));
        }else{
            $where['a.id'] = array('neq',1);
        }
        
        $where['a.open_status'] = 1;

        if(cookie('ptshops_indus_id') != ''){
            //(int)将cookie字符串强制转换成整型
            $indus_id = (int)cookie('ptshops_indus_id');
            if($indus_id != 0){
                $where['a.indus_id'] = $indus_id;
            }
        }
        
        if(cookie('ptshops_type')){
            if(cookie('ptshops_type') == 1 && cookie('ptshops_keyword')){
                $where['a.shop_name'] = cookie('ptshops_keyword');
            }elseif(cookie('ptshops_type') == 2 && cookie('ptshops_keyword')){
                $where['a.telephone'] = cookie('ptshops_keyword');
            }
        }

        $list = Db::name('shops')->alias('a')->field('a.id,a.shop_name,a.contacts,a.telephone,b.industry_name')->join('sp_industry b','a.indus_id = b.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();
         
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        if(cookie('ptget_shops_id')){
            $goods_id = cookie('ptget_shops_id');
        }else{
            $goods_id = '';
        }
        
        $search = 1;
        
        if(cookie('ptshops_keyword')){
            $this->assign('keyword',cookie('ptshops_keyword'));
        }
        
        if(cookie('ptshops_type') != ''){
            $this->assign('search_type',cookie('ptshops_type'));
        }
        
        
        $this->assign('goods_id',$goods_id);
        $this->assign('indus_id', $indus_id);
        $this->assign('industryres',$industryres);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('search',$search);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
 
}