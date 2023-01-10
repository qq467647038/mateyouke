<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class ShopPromotion extends Common{
    
    public function lst(){
        $shop_id = session('shop_id');
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,5))){
            $filter = 5;
        }
        
        $where = array();
        $where['a.is_show'] = 1;
        $where['a.shop_id'] = array('neq',$shop_id);
        switch($filter){
            //即将开始
            case 1:
                $where['a.start_time'] = array('gt',time());
                break;
            //活动中
            case 2:
                $where['a.start_time'] = array('elt',time());
                $where['a.end_time'] = array('gt',time());
                break;
            //已结束
            case 3:
                $where['a.end_time'] = array('elt',time());
                break;
        }
        
        $list = Db::name('promotion')->alias('a')->field('a.id,a.activity_name,a.type,a.start_time,a.end_time,a.pic_url,a.recommend,b.shop_name')->join('sp_shops b','a.shop_id = b.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();
        
        $listres = $list->toArray();
        $list = $listres['data'];
        
        if($list){
            foreach ($list as $k => $v){
                if($v['start_time'] > time()){
                    //即将开始
                    $list[$k]['zhuangtai'] = 1;
                }elseif($v['start_time'] <= time() && $v['end_time'] > time()){
                    //活动中
                    $list[$k]['zhuangtai'] = 2;
                }elseif($v['end_time'] <= time()){
                    //已结束
                    $list[$k]['zhuangtai'] = 3;
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
            return $this->fetch('lst');
        }
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $shop_id = session('shop_id');
        $name = input('post.name');
        $value = input('post.value');
        $rushs = Db::name('promotion')->where('id',$id)->where('is_show',1)->where('shop_id','neq',$shop_id)->find();
        if($rushs){
            $data[$name] = $value;
            $data['id'] = $id;
            $count = Db::name('promotion')->update($data);
            if($count > 0){
                ys_admin_logs('修改商家商品促销活动推荐状态','promotion',$id);
                $result = 1;
            }else{
                $result = 0;
            }
        }else{
            $result = 0;
        }
        return $result;
    }
    
    public function edit(){
        if(request()->isPost()){
            $shop_id = session('shop_id');
            $data = input('post.');
            if(!empty($data['id'])){
                if(isset($data['recommend']) && in_array($data['recommend'], array(0,1))){
                    $promos = Db::name('promotion')->where('id',$data['id'])->where('is_show',1)->where('shop_id','neq',$shop_id)->find();
                    if($promos){
                        $count = Db::name('promotion')->where('id',$data['id'])->update(array('recommend'=>$data['recommend']));
                        if($count !== false){
                            if($data['recommend'] == 1){
                                ys_admin_logs('商家商品促销活动设为推荐','promotion',$data['id']);
                            }elseif($data['recommend'] == 0){
                                ys_admin_logs('商家商品促销活动设为不推荐','promotion',$data['id']);
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
                $id = input('id');
                $promos = Db::name('promotion')->alias('a')->field('a.*,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$id)->where('a.shop_id','neq',$shop_id)->where('a.is_show',1)->find();
                if($promos){
                    if($promos['start_time'] > time()){
                        //即将开始
                        $promos['zhuangtai'] = 1;
                    }elseif($promos['start_time'] <= time() && $promos['end_time'] > time()){
                        //活动中
                        $promos['zhuangtai'] = 2;
                    }elseif($promos['end_time'] <= time()){
                        //已结束
                        $promos['zhuangtai'] = 3;
                    }
                    
                    $prom_typeres = Db::name('prom_type')->where('prom_id',$promos['id'])->select();
                
                    $cominfo = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.shop_price,b.cate_name')->join('sp_shop_cate b','a.shcate_id = b.id','LEFT')->where('a.id','in',$promos['info_id'])->where('a.shop_id','neq',$shop_id)->where('a.onsale',1)->order('a.addtime desc')->select();
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    $this->assign('pnum', input('page'));
                    $this->assign('filter',input('filter'));
                    $this->assign('cominfo',$cominfo);
                    $this->assign('prom_typeres',$prom_typeres);
                    $this->assign('promos', $promos);
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
            cookie('cppromo_keyword',input('post.keyword'),7200);
        }else{
            cookie('cppromo_keyword',null);
        }
        
        if(input('post.status') != ''){
            cookie('cppromo_status',input('post.status'),7200);
        }
        
        if(input('post.shop_name') != ''){
            cookie('cppromo_shop_name',input('post.shop_name'),7200);
        }else{
            cookie('cppromo_shop_name',null);
        }
        
        if(input('post.checked') != ''){
            cookie('cppromo_checked',input('post.checked'),7200);
        }
        
        if(input('post.starttime') != ''){
            $cppromostarttime = strtotime(input('post.starttime'));
            cookie('cppromostarttime',$cppromostarttime,7200);
        }
        
        if(input('post.endtime') != ''){
            $cppromoendtime = strtotime(input('post.endtime'));
            cookie('cppromoendtime',$cppromoendtime,7200);
        }
        
        if(input('post.recommend') != ''){
            cookie('cppromo_recommend',input('post.recommend'),7200);
        }
        
        $where = array();
        $where['a.is_show'] = 1;
        $where['a.shop_id'] = array('neq',$shop_id);
        
        if(cookie('cppromo_keyword')){
            $where['a.activity_name'] = cookie('cppromo_keyword');
        }
        
        if(cookie('cppromo_status') != ''){
            $cppromo_status = (int)cookie('cppromo_status');
            if(!empty($cppromo_status)){
                switch ($cppromo_status){
                    //即将开始
                    case 1:
                        $where['a.start_time'] = array('gt',time());
                        break;
                        //抢购中
                    case 2:
                        $where['a.start_time'] = array('elt',time());
                        $where['a.end_time'] = array('gt',time());
                        break;
                        //已结束
                    case 3:
                        $where['a.end_time'] = array('elt',time());
                        break;
                }
            }
        }
        
        if(cookie('cppromo_shop_name')){
            $shops = Db::name('shops')->where('shop_name',cookie('cppromo_shop_name'))->where('id','neq',$shop_id)->field('id')->find();
            if($shops){
                $where['a.shop_id'] = $shops['id'];
            }else{
                $where['a.shop_id'] = 'aa';
            }
        }
        
        if(cookie('cppromoendtime') && cookie('cppromostarttime')){
            $where['a.addtime'] = array(array('egt',cookie('cppromostarttime')), array('elt',cookie('cppromoendtime')));
        }
        
        if(cookie('cppromostarttime') && !cookie('cppromoendtime')){
            $where['a.addtime'] = array('egt',cookie('cppromostarttime'));
        }
        
        if(cookie('cppromoendtime') && !cookie('cppromostarttime')){
            $where['a.addtime'] = array('elt',cookie('cppromoendtime'));
        }
        
        if(cookie('cppromo_recommend') != ''){
            $cppromo_recommend = (int)cookie('cppromo_recommend');
            if(!empty($cppromo_recommend)){
                switch ($cppromo_recommend){
                    //推荐
                    case 1:
                        $where['a.recommend'] = 1;
                        break;
                        //未推荐
                    case 2:
                        $where['a.recommend'] = 0;
                        break;
                }
            }
        }

        $list = Db::name('promotion')->alias('a')->field('a.id,a.activity_name,a.type,a.start_time,a.end_time,a.pic_url,a.recommend,b.shop_name')->join('sp_shops b','a.shop_id = b.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();
        
        $listres = $list->toArray();
        $list = $listres['data'];
        
        if($list){
            foreach ($list as $k => $v){
                if($v['start_time'] > time()){
                    //即将开始
                    $list[$k]['zhuangtai'] = 1;
                }elseif($v['start_time'] <= time() && $v['end_time'] > time()){
                    //活动中
                    $list[$k]['zhuangtai'] = 2;
                }elseif($v['end_time'] <= time()){
                    //已结束
                    $list[$k]['zhuangtai'] = 3;
                }
            }
        }
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $search = 1;
        
        if(cookie('cppromostarttime')){
            $this->assign('starttime',cookie('cppromostarttime'));
        }
        
        if(cookie('cppromoendtime')){
            $this->assign('endtime',cookie('cppromoendtime'));
        }
        
        if(cookie('cppromo_recommend')){
            $this->assign('recommend',cookie('cppromo_recommend'));
        }
        
        if(cookie('cppromo_shop_name')){
            $this->assign('shop_name',cookie('cppromo_shop_name'));
        }
        
        if(cookie('cppromo_status')){
            $this->assign('status',cookie('cppromo_status'));
        }
        
        if(cookie('cppromo_keyword')){
            $this->assign('keyword',cookie('cppromo_keyword'));
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
    
    
}

