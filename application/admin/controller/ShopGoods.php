<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class ShopGoods extends Common{
    
    public function lst(){
        $shop_id = session('shop_id');
    
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3))){
            $filter = 3;
        }
    
        $where = array();
        $where['a.shop_id'] = array('neq',$shop_id);
        switch($filter){
            //正常
            case 1:
                $where['a.checked'] = 1;
                break;
            //违规
            case 2:
                $where['a.checked'] = 2;
                break;
            case 3:
    
                break;
        }
    
        $list = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.market_price,a.shop_price,a.onsale,a.checked,b.shop_name,c.cate_name,d.brand_name')->join('sp_shops b','a.shop_id = b.id','LEFT')->join('sp_category c','a.cate_id = c.id','LEFT')->join('sp_brand d','a.brand_id = d.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('pnum',$pnum);
        $this->assign('filter',$filter);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function checked(){
        if(request()->isPost()){
            $shop_id = session('shop_id');
            $data = input('post.');
            if(!empty($data['id'])){
                if(!empty($data['checked']) && in_array($data['checked'], array(1,2))){
                    $goods = Db::name('goods')->where('id',$data['id'])->where('shop_id','neq',$shop_id)->find();
                    if($goods){
                        if($data['checked'] == 1){
                            $count = Db::name('goods')->where('id',$data['id'])->update(array('checked'=>$data['checked']));
                        }elseif($data['checked'] == 2){
                            $count = Db::name('goods')->where('id',$data['id'])->update(array('checked'=>$data['checked'],'onsale'=>0));
                        }
                        if($count !== false){
                            $value = array('status'=>1,'mess'=>'设置成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'设置失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'参数错误');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数');
            }
            return json($value);
        }else{
            if(input('id')){
                $shop_id = session('shop_id');
                $goodss = Db::name('goods')->alias('a')->field('a.*,b.shop_name,c.cate_name,d.brand_name,f.type_name')->join('sp_shops b','a.shop_id = b.id','LEFT')->join('sp_category c','a.cate_id = c.id','LEFT')->join('sp_brand d','a.brand_id = d.id','LEFT')->join('sp_type f','a.type_id = f.id','LEFT')->where('a.id',input('id'))->where('a.shop_id','neq',$shop_id)->find();
                if($goodss){
                    $levres = Db::name('member_level')->field('id,level_name')->order('id asc')->select();
                    $goodpicres = Db::name('goods_pic')->where('goods_id',input('id'))->order('sort asc')->select();
                    $mpres = Db::name('member_price')->where('goods_id',input('id'))->select();
                    $attres = Db::name('attr')->where('type_id',$goodss['type_id'])->order('sort asc')->select();
                
                    $arr = Db::name('goods_attr')->where('goods_id',input('id'))->select();
                    $gares = array();
                    if($arr){
                        foreach ($arr as $key => $val){
                            $gares[$val['attr_id']][] = $val;
                        }
                    }
                
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                
                    $this->assign('pnum', input('page'));
                    $this->assign('filter',input('filter'));
                    $this->assign('levres',$levres);
                    $this->assign('goodpicres',$goodpicres);
                    $this->assign('mpres',$mpres);
                    $this->assign('attres',$attres);
                    $this->assign('gares',$gares);
                    $this->assign('goodss',$goodss);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    public function search(){
        $shop_id = session('shop_id');
        if(input('post.keyword') != ''){
            cookie('shgoods_keyword',input('post.keyword'),7200);
        }else{
            cookie('shgoods_keyword',null);
        }
        
        if(input('post.shop_name') != ''){
            cookie('shgoods_shop_name',input('post.shop_name'),7200);
        }else{
            cookie('shgoods_shop_name',null);
        }
    
        if(input('post.checked') != ''){
            cookie("shgoods_checked", input('post.checked'), 7200);
        }
    
        if(input('post.starttime') != ''){
            $shgoodsstarttime = strtotime(input('post.starttime'));
            cookie('shgoodsstarttime',$shgoodsstarttime,3600);
        }
    
        if(input('post.endtime') != ''){
            $shgoodsendtime = strtotime(input('post.endtime'));
            cookie('shgoodsendtime',$shgoodsendtime,3600);
        }
    
        $where = array();
        
        $where['a.shop_id'] = array('neq',$shop_id);
        
        if(cookie('shgoods_checked') != ''){
            $shgoods_checked = (int)cookie('shgoods_checked');
            if($shgoods_checked != 0){
                switch($shgoods_checked){
                    //正常
                    case 1:
                        $where['a.checked'] = 1;
                        break;
                        //违规
                    case 2:
                        $where['a.checked'] = 2;
                        break;
                }
            }
        }
    
        if(cookie('shgoods_keyword')){
            $where['a.goods_name'] = cookie('shgoods_keyword');
        }
        
        if(cookie('shgoods_shop_name')){
            $shops = Db::name('shops')->where('shop_name',cookie('shgoods_shop_name'))->where('id','neq',$shop_id)->field('id')->find();
            if($shops){
                $where['a.shop_id'] = $shops['id'];
            }else{
                $where['a.shop_id'] = 'aa';
            }
        }
    
        if(cookie('shgoodsendtime') && cookie('shgoodsstarttime')){
            $where['a.addtime'] = array(array('egt',cookie('shgoodsstarttime')), array('lt',cookie('shgoodsendtime')));
        }
    
        if(cookie('shgoodsstarttime') && !cookie('shgoodsendtime')){
            $where['a.time'] = array('egt',cookie('shgoodsstarttime'));
        }
    
        if(cookie('shgoodsendtime') && !cookie('shgoodsstarttime')){
            $where['a.time'] = array('lt',cookie('shgoodsendtime'));
        }
        $list = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.market_price,a.shop_price,a.onsale,a.checked,b.shop_name,c.cate_name,d.brand_name')->join('sp_shops b','a.shop_id = b.id','LEFT')->join('sp_category c','a.cate_id = c.id','LEFT')->join('sp_brand d','a.brand_id = d.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
    
        if(cookie('shgoodsstarttime') != ''){
            $this->assign('starttime',cookie('shgoodsstarttime'));
        }
    
        if(cookie('shgoodsendtime') != ''){
            $this->assign('endtime',cookie('shgoodsendtime'));
        }
    
        if(cookie('shgoods_keyword') != ''){
            $this->assign('keyword',cookie('shgoods_keyword'));
        }
        
        if(cookie('shgoods_shop_name')){
            $this->assign('shop_name',cookie('shgoods_shop_name'));
        }
    
        if(cookie('shgoods_checked') != ''){
            $this->assign('checked',cookie('shgoods_checked'));
        }
    
        $filter = 3;
    
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('filter',$filter);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
}