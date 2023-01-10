<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Getgoods extends Common{
    //列表
    public function lst(){
        $where = array();
        if(input('goods_id')){
            $goods_id = input('goods_id');
            $where['a.id'] = array('not in',$goods_id);
        }else{
            $goods_id = '';
            if(cookie('ptget_goods_id')){
                cookie('ptget_goods_id',null);
            }
        }
        
        $where['a.onsale'] = 1;
        
        $list = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.shop_price,b.shop_name,c.cate_name')->join('sp_shops b','a.shop_id = b.id','LEFT')->join('sp_category c','a.cate_id = c.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
        
        $this->assign(array(
            'pnum'=>$pnum,
            'list'=>$list,
            'page'=>$page,
            'cateres'=>recursive($cateres),
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
            cookie('ptgoods_name',input('post.keyword'),3600);
        }else{
            cookie('ptgoods_name',null);
        }
        
        if(input('post.shop_name') != ''){
            cookie('ptgoods_shop_name',input('post.shop_name'),3600);
        }else{
            cookie('ptgoods_shop_name',null);
        }

        if(input('post.cate_id') != ''){
            cookie('ptgoods_cate_id',input('post.cate_id'),3600);
        }

        if(input('post.goods_id') != ''){
            cookie('ptget_goods_id',input('post.goods_id'),3600);
        }
        
        $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
        
        $where = array();
        
        $where['a.onsale'] = 1;
        
        if(cookie('ptget_goods_id')){
            $where['a.id'] = array('not in',cookie('ptget_goods_id'));
        }
        
        if(cookie('ptgoods_name')){
            $where['a.goods_name'] = cookie('ptgoods_name');
        }
        
        if(cookie('ptgoods_shop_name')){
            $shops = Db::name('shops')->where('shop_name',cookie('ptgoods_shop_name'))->field('id')->find();
            if($shops){
                $where['a.shop_id'] = $shops['id'];
            }
        }
        
        if(cookie('ptgoods_cate_id') != ''){
            //(int)将cookie字符串强制转换成整型
            $cid = (int)cookie('ptgoods_cate_id');
            if($cid != 0){
                $cateId = array();
                $cateId = get_all_child($cateres, $cid);
                $cateId[] = $cid;
                $cateId = implode(',', $cateId);
                $where['a.cate_id'] = array('in',$cateId);
            }
        }
        
        $list = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.shop_price,b.shop_name,c.cate_name')->join('sp_shops b','a.shop_id = b.id','LEFT')->join('sp_category c','a.cate_id = c.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();
         
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        if(cookie('ptget_goods_id')){
            $goods_id = cookie('ptget_goods_id');
        }else{
            $goods_id = '';
        }
        
        $search = 1;
        
        if(cookie('ptgoods_name')){
            $this->assign('keyword',cookie('ptgoods_name'));
        }
        
        if(cookie('ptgoods_shop_name')){
            $this->assign('shop_name',cookie('ptgoods_shop_name'));
        }
        
        $this->assign('goods_id',$goods_id);
        $this->assign('cate_id', $cid);
        $this->assign('cateres',recursive($cateres));
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