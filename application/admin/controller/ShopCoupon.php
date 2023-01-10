<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class ShopCoupon extends Common{
    
    public function lst(){
        $shop_id = session('shop_id');
    
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3))){
            $filter = 3;
        }
    
        $where = array();
        $where['a.is_recycle'] = 0;
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
            //全部
            case 3:
    
                break;
        }
    
        $list = Db::name('coupon')->alias('a')->field('a.id,a.man_price,a.dec_price,a.start_time,a.end_time,a.addtime,a.onsale,a.checked,b.shop_name')->join('sp_shops b','a.shop_id = b.id','LEFT')->where($where)->order('a.sort asc')->paginate(25);
    
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $this->assign('pnum',$pnum);
        $this->assign('page',$page);
        $this->assign('filter',$filter);
        $this->assign('list',$list);// 赋值数据集
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
                    $coupons = Db::name('coupon')->where('id',$data['id'])->where('is_recycle',0)->where('shop_id','neq',$shop_id)->find();
                    if($coupons){
                        if($data['checked'] == 1){
                            $count = Db::name('coupon')->where('id',$data['id'])->update(array('checked'=>$data['checked']));
                        }elseif($data['checked'] == 2){
                            $count = Db::name('coupon')->where('id',$data['id'])->update(array('checked'=>$data['checked'],'onsale'=>0));
                        }
                        if($count !== false){
                            if($data['checked'] == 1){
                                ys_admin_logs('审核通过商家优惠券','coupon',$data['id']);
                            }elseif($data['checked'] == 2){
                                ys_admin_logs('审核不通过商家优惠券','coupon',$data['id']);
                            }
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
                $coupons = Db::name('coupon')->alias('a')->field('a.*,b.shop_name')->join('sp_shops b','a.shop_id = b.id','LEFT')->where('a.id',input('id'))->where('a.is_recycle',0)->where('a.shop_id','neq',$shop_id)->find();
                if($coupons){
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    $this->assign('pnum', input('page'));
                    $this->assign('filter',input('filter'));
                    $this->assign('coupons',$coupons);
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
            cookie('cou_keyword',input('post.keyword'),7200);
        }else{
            cookie('cou_keyword',null);
        }
    
        if(input('post.starttime') != ''){
            $coustarttime = strtotime(input('post.starttime'));
            cookie('coustarttime',$coustarttime,7200);
        }else{
            cookie('coustarttime',null);
        }
    
        if(input('post.endtime') != ''){
            $couendtime = strtotime(input('post.endtime'));
            cookie('couendtime',$couendtime,7200);
        }else{
            cookie('couendtime',null);
        }
    
        if(input('post.checked') != ''){
            cookie('couchecked',input('post.checked'),7200);
        }
    
        $where = array();
        $where['a.is_recycle'] = 0;
        $where['a.shop_id'] = array('neq',$shop_id);
    
        if(cookie('cou_keyword')){
            $shops = Db::name('shops')->where('shop_name',cookie('cou_keyword'))->where('shop_id','neq',$shop_id)->field('id')->find();
            if($shops){
                $where['a.shop_id'] = $shops['id'];
            }else{
                $where['a.shop_id'] = 'aa';
            }
        }
    
        if(cookie('couendtime') && cookie('coustarttime')){
            $where['a.addtime'] = array(array('egt',cookie('coustarttime')), array('lt',cookie('couendtime')));
        }
    
        if(cookie('coustarttime') && !cookie('couendtime')){
            $where['a.addtime'] = array('egt',cookie('coustarttime'));
        }
    
        if(cookie('couendtime') && !cookie('coustarttime')){
            $where['a.addtime'] = array('lt',cookie('couendtime'));
        }
    
        if(cookie('couchecked') != ''){
            $couchecked = (int)cookie('couchecked');
            if(!empty($couchecked)){
                switch ($couchecked){
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
    
        $list = Db::name('coupon')->alias('a')->field('a.id,a.man_price,a.dec_price,a.start_time,a.end_time,a.addtime,a.onsale,a.checked,b.shop_name')->join('sp_shops b','a.shop_id = b.id','LEFT')->where($where)->order('a.sort asc')->paginate(25);
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $search = 1;
    
        if(cookie('coustarttime')){
            $this->assign('starttime',cookie('coustarttime'));
        }
    
        if(cookie('couendtime')){
            $this->assign('endtime',cookie('couendtime'));
        }
    
        if(cookie('cou_keyword')){
            $this->assign('keyword',cookie('cou_keyword'));
        }
    
        if(cookie('couchecked')){
            $this->assign('checked',cookie('couchecked'));
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