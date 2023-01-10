<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;

class Coupon extends Common{
    
    public function lst(){
        $shop_id = session('shopsh_id');
        
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,5))){
            $filter = 5;
        }
        
        $where = array();
        $where['shop_id'] = $shop_id;
        $where['is_recycle'] = 0;
        
        switch($filter){
            case 1:
                $where['onsale'] = 1;
                break;
            case 2:
                $where['onsale'] = 0;
                break;
            case 3:
                $where['end_time'] = array('elt',time()-3600*24);
                break;
        }
        
        $list = Db::name('coupon')->where($where)->field('id,man_price,dec_price,start_time,end_time,addtime,onsale,checked,sort')->order('sort asc')->paginate(25);
        $page = $list->render();
        
        $listres = $list->toArray();
        $list = $listres['data'];
        
        if($list){
            foreach ($list as $key => $val){
                if($val['end_time'] <= time()-3600*24){
                    $list[$key]['zhuangtai'] = 2;
                }else{
                    $list[$key]['zhuangtai'] = 1;
                }
            }
        }
       
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
            return $this->fetch();
        }
    }
    
    public function hslst(){
        $shop_id = session('shopsh_id');
        $where = array();
        $where['shop_id'] = $shop_id;
        $where['is_recycle'] = 1;
        $where['onsale'] = 0;
        $list = Db::name('coupon')->where($where)->field('id,man_price,dec_price,start_time,end_time,addtime,onsale,checked,sort')->order('sort asc')->paginate(25);
        $page = $list->render();
        
        $listres = $list->toArray();
        $list = $listres['data'];
        
        if($list){
            foreach ($list as $key => $val){
                if($val['end_time'] <= time()-3600*24){
                    $list[$key]['zhuangtai'] = 2;
                }else{
                    $list[$key]['zhuangtai'] = 1;
                }
            }
        }
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign('pnum',$pnum);
        $this->assign('page',$page);
        $this->assign('list',$list);
        if(request()->isAjax()){
            return $this->fetch('hsajaxpage');
        }else{
            return $this->fetch();
        }  
    }
    
    //修改状态
    public function gaibian(){
        $shop_id = session('shopsh_id');
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $coupons = Db::name('coupon')->where('id',$id)->where('shop_id',$shop_id)->where('is_recycle',0)->where('checked',1)->find();
        if($coupons){
            $data[$name] = $value;
            $data['id'] = $id;
            $data['shop_id'] = $shop_id;
            $count = Db::name('coupon')->where('id',$data['id'])->update($data);
            if($count > 0){
                $result = 1;
            }else{
                $result = 0;
            }
        }else{
            $result = 0;
        }
        return $result;
    }
    
    public function add(){
        if(request()->isPost()){
            $shop_id = session('shopsh_id');
            $data = input('post.');
            $data['shop_id'] = $shop_id;
            $data['addtime'] = time();
            
            $result = $this->validate($data,'Coupon');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if($data['man_price'] > $data['dec_price']){
                    $start_time = strtotime($data['start_time']);
                    $end_time = strtotime($data['end_time']);
                    if($start_time < $end_time){
                        /*if($start_time > time()){

                        }else{
                            $value = array('status'=>0,'mess'=>'开始日期必须大于当前日期');
                        }*/
                        $data['start_time'] = $start_time;
                        $data['end_time'] = $end_time;
                        $data['checked'] = 1;
                        $data['shop_id'] = $shop_id;
                        $lastId = Db::name('coupon')->insert($data);
                        if($lastId){
                            $value = array('status'=>1,'mess'=>'增加成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'增加失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'开始日期必须小于结束日期');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'满金额须大于减金额');
                }
            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }
    
    public function info(){
        if(input('id')){
            $shop_id = session('shopsh_id');
            $coupons = Db::name('coupon')->where('id',input('id'))->where('shop_id',$shop_id)->where('is_recycle',0)->find();
            if($coupons){
                $this->assign('coupons',$coupons);
                return $this->fetch();
            }else{
                $this->error('找不到相关信息');
            }
        }else{
            $this->error('缺少参数');
        }
    }
    
    /*public function edit(){
        if(request()->isPost()){
            $shop_id = session('shopsh_id');
            $data = input('post.');
            $data['shop_id'] = $shop_id;
            
            if(input('post.id')){
                $coupons = Db::name('coupon')->where('id',$data['id'])->where('shop_id',$shop_id)->where('is_recycle',0)->find();
                if($coupons){
                    $result = $this->validate($data,'Coupon');
                    if(true !== $result){
                        $value = array('status'=>0,'mess'=>$result);
                    }else{
                        if($coupons['checked'] == 2 && $data['onsale'] == 1){
                            $value = array('status'=>0,'mess'=>'违规优惠券不可上架');
                            return json($value);
                        }
                        
                        if($data['man_price'] > $data['dec_price']){
                            $start_time = strtotime($data['start_time']);
                            $end_time = strtotime($data['end_time']);
                            if($start_time < $end_time){
                                $data['start_time'] = $start_time;
                                $data['end_time'] = $end_time;
                                $count = Db::name('coupon')->where('id',$data['id'])->update($data);
                                if($count > 0){
                                    $value = array('status'=>1,'mess'=>'编辑成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'编辑失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'开始时间不能大于结束时间');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'满金额须大于减金额');
                        }
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'找不到相关信息');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数');
            }
            return $value;
        }else{
            if(input('id')){
                $shop_id = session('shopsh_id');
                $coupons = Db::name('coupon')->where('id',input('id'))->where('shop_id',$shop_id)->where('is_recycle',0)->find();
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
    }*/
    
    public function recycle(){
        $shop_id = session('shopsh_id');
        $id = input('id');
        if(!empty($id) && !is_array($id)){
            $coupons = Db::name('coupon')->where('id',$id)->where('shop_id',$shop_id)->where('is_recycle',0)->find();
            if($coupons){
                $count = Db::name('coupon')->where('id',$id)->where('shop_id',$shop_id)->update(array('is_recycle'=>1,'onsale'=>0));
                if($count > 0){
                    $value = array('status'=>1,'mess'=>'加入回收站成功');
                }else{
                    $value = array('status'=>0,'mess'=>'加入回收站失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'找不到相关信息');
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return $value;
    }
    
    public function recovery(){
        $shop_id = session('shopsh_id');
        $id = input('id');
        if(!empty($id) && !is_array($id)){
            $coupons = Db::name('coupon')->where('id',$id)->where('shop_id',$shop_id)->where('is_recycle',1)->where('onsale',0)->find();
            if($coupons){
                if($coupons['checked'] == 1){
                    $count = Db::name('coupon')->where('id',$id)->update(array('is_recycle'=>0));
                    if($count > 0){
                        $value = array('status'=>1,'mess'=>'恢复优惠券成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'恢复优惠券失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'违规优惠券不可恢复');
                }
            }else{
                $value = array('status'=>0,'mess'=>'找不到相关信息');
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return json($value);
    }
    
    public function search(){
        $shop_id = session('shopsh_id');
        
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
        
        if(input('post.onsale') != ''){
            cookie('couonsale',input('post.onsale'),7200);
        }
        
        $where = array();
        
        $where['shop_id'] = $shop_id;
        $where['is_recycle'] = 0;
        
        if(cookie('cou_keyword')){
            $where['man_price'] = cookie('cou_keyword');
        }
        
        if(cookie('couendtime') && cookie('coustarttime')){
            $where['addtime'] = array(array('egt',cookie('coustarttime')), array('lt',cookie('couendtime')));
        }
        
        if(cookie('coustarttime') && !cookie('couendtime')){
            $where['addtime'] = array('egt',cookie('coustarttime'));
        }
        
        if(cookie('couendtime') && !cookie('coustarttime')){
            $where['addtime'] = array('lt',cookie('couendtime'));
        }
        
        if(cookie('couonsale') != ''){
            $couonsale = (int)cookie('couonsale');
            if(!empty($couonsale)){
                switch ($couonsale){
                    //上架
                    case 1:
                        $where['onsale'] = 1;
                        break;
                    //下架
                    case 2:
                        $where['onsale'] = 0;
                        break;
                    //已过期
                    case 3:
                        $where['end_time'] = array('elt',time()-3600*24);
                        break;
                }
            }
        }

        $list = Db::name('coupon')->where($where)->field('id,man_price,dec_price,start_time,end_time,addtime,onsale,checked,sort')->order('sort asc')->paginate(25);
        $page = $list->render();
        
        $listres = $list->toArray();
        $list = $listres['data'];
        
        if($list){
            foreach ($list as $key => $val){
                if($val['end_time'] <= time()-3600*24){
                    $list[$key]['zhuangtai'] = 2;
                }else{
                    $list[$key]['zhuangtai'] = 1;
                }
            }
        }
        
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
        
        if(cookie('couonsale')){
            $this->assign('onsale',cookie('couonsale'));
        }
        
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('filter',5);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }        
    }
    
    public function paixu(){
        if(request()->isAjax()){
            $shop_id = session('shopsh_id');
            if(input('post.ids') && input('post.sort')){
                $ids = input('post.ids');
                $sort = input('post.sort');
                $ids = explode(',', $ids);
                $sort = explode(',', $sort);
                foreach ($ids as $k => $v){
                    $coupons = Db::name('coupon')->where('id',$v)->where('shop_id',$shop_id)->where('is_recycle',0)->find();
                    if($coupons){
                        Db::name('coupon')->where('id',$v)->update(array('sort'=>$sort[$k]));
                    }
                }
            }
            $value = array('status'=>1,'mess'=>'排序成功');
            return $value;
        }
    }
    
    
}