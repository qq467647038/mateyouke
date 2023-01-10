<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;

class PromActivity extends Common{
    
    public function lst(){
        $shop_id = session('shopsh_id');
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,5))){
            $filter = 5;
        }
        
        $where = array();
        $where['shop_id'] = $shop_id;
        switch($filter){
            //即将开始
            case 1:
                $where['start_time'] = array('gt',time());
                break;
            //活动中
            case 2:
                $where['start_time'] = array('elt',time());
                $where['end_time'] = array('egt',time());
                break;
            //已结束
            case 3:
                $where['end_time'] = array('lt',time());
                break;
        }

        $list = Db::name('prom_activity')->where($where)->field('id,activity_name,type,discount,reduction,start_time,end_time,pic_url')->order('addtime desc')->paginate(25);
        $page = $list->render();

        $listres = $list->toArray();
        $list = $listres['data'];
        
        if($list){
            foreach ($list as $k => $v){
                if($v['start_time'] > time()){
                    //即将开始
                    $list[$k]['zhuangtai'] = 1;
                }elseif($v['start_time'] <= time() && $v['end_time'] >= time()){
                    //活动中
                    $list[$k]['zhuangtai'] = 2;
                }elseif($v['end_time'] < time()){
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
    
    //处理上传图片
    public function uploadify(){

        $admin_id = session('shopadmin_id');
        $file = request()->file('filedata');

        if($file){
            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'prom_activity_pic');

            if($info){
                $zssjpics = Db::name('shopadmin_zspic')->where('admin_id',$admin_id)->find();
                if($zssjpics && $zssjpics['img_url']){
                    Db::name('shopadmin_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                        @unlink('./'.$zssjpics['img_url']);
                    }
                }
                $getSaveName = str_replace("\\","/",$info->getSaveName());

                $original = 'uploads/prom_activity_pic/'.$getSaveName;
                $image = \think\Image::open('./'.$original);
                $image->thumb(350, 350)->save('./'.$original);
                if($zssjpics){
                    Db::name('shopadmin_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>$original));
                    $zspic_id = $zssjpics['id'];
                }else{
                    $zspic_id = Db::name('shopadmin_zspic')->insertGetId(array('admin_id'=>$admin_id,'img_url'=>$original));
                }
                $picarr = array('img_url'=>$original,'pic_id'=>$zspic_id);
                $value = array('status'=>1,'path'=>$picarr);
            }else{
                $value = array('status'=>0,'msg'=>$file->getError());
            }
        }else{
            $value = array('status'=>0,'msg'=>'文件不存在');
        }
        return json($value);
    }
    
    //手动删除未保存的上传图片手机
    public function delfile(){
        if(input('post.zspic_id')){
            $admin_id = session('shopadmin_id');
            $zspic_id = input('post.zspic_id');
            $pics = Db::name('shopadmin_zspic')->where('id',$zspic_id)->where('admin_id',$admin_id)->find();
            if($pics && $pics['img_url']){
                $count = Db::name('shopadmin_zspic')->where('id',$pics['id'])->update(array('img_url'=>''));
                if($count > 0){
                    if($pics['img_url'] && file_exists('./'.$pics['img_url'])){
                        @unlink('./'.$pics['img_url']);
                    }
                    $value = 1;
                }else{
                    $value = 0;
                }
            }else{
                $value = 0;
            }
        }else{
            $value = 0;
        }
        return json($value);
    }
    
    public function add(){
        if(request()->isPost()){
            $admin_id = session('shopadmin_id');
            $shop_id = session('shopsh_id');
            $data = input('post.');
            $data['shop_id'] = $shop_id;
            $data['addtime'] = time();
            
            $result = $this->validate($data,'Promotion');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(!empty($data['pic_id'])){
                    $zssjpics = Db::name('shopadmin_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        $data['pic_url'] = $zssjpics['img_url'];
                        
                        if($data['type'] == 1){
                            if(empty($data['discount'])){
                                $value = array('status'=>0,'mess'=>'请填写活动折扣');
                                return json($value);
                            }
                            $data['reduction'] = 0;
                        }
                        
                        if($data['type'] == 2){
                            if(empty($data['reduction'])){
                                $value = array('status'=>0,'mess'=>'请填写活动立减价格');
                                return json($value);
                            }
                            $data['discount'] = 100;
                        }
                        
                        if(!empty($data['goods_id']) && is_array($data['goods_id'])){
                            $goodids = array_unique($data['goods_id']);
                            $info_id = implode(',', $goodids);
                        
                            foreach ($goodids as $v){
                                $goods = Db::name('goods')->where('id',$v)->where('shop_id',$shop_id)->where('onsale',1)->field('id,shop_price')->find();
                        
                                if(!$goods){
                                    $value = array('status'=>0,'mess'=>'商品信息有误，增加失败');
                                    return json($value);
                                }else{
                                    if($data['type'] == 2){
                                        if($goods['shop_price']-$data['reduction'] < 10){
                                            $value = array('status'=>0,'mess'=>'存在商品价格与立减价格过于接近，增加失败');
                                            return json($value);
                                        }
                                    }
                                }
                            }
                        
                            $start_time = strtotime($data['start_time']);
                            $end_time = strtotime($data['end_time']);
                        
                            if($start_time < $end_time){
                                $lastId = Db::name('prom_activity')->insertGetId(array(
                                    'activity_name'=>$data['activity_name'],
                                    'type'=>$data['type'],
                                    'discount'=>$data['discount'],
                                    'reduction'=>$data['reduction'],
                                    'num'=>$data['num'],
                                    'start_time'=>$start_time,
                                    'end_time'=>$end_time,
                                    'pic_url'=>$data['pic_url'],
                                    'info_id'=>$info_id,
                                    'shop_id'=>$shop_id,
                                    'addtime'=>time()
                                ));
                        
                                if($lastId){
                                    if($zssjpics && $zssjpics['img_url']){
                                        Db::name('shopadmin_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                                    }
                                    $value = array('status'=>1,'mess'=>'增加成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'增加失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'开始时间需小于结束时间');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请选择商品信息');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'请上传活动宣传图');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'请上传活动宣传图');
                }
            }
            return json($value);
        }else{
            $admin_id = session('shopadmin_id');
            $shop_id = session('shopsh_id');
            $zssjpics = Db::name('shopadmin_zspic')->where('admin_id',$admin_id)->find();
            if($zssjpics && $zssjpics['img_url']){
                Db::name('shopadmin_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                    @unlink('./'.$zssjpics['img_url']);
                }
            }
            
            return $this->fetch();
        }
    }
    
    public function edit(){
        if(request()->isPost()){
            if(input('post.id')){
                $admin_id = session('shopadmin_id');
                $shop_id = session('shopsh_id');
                $data = input('post.');
                $data['shop_id'] = $shop_id;
        
                $result = $this->validate($data,'Promotion');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $promos = Db::name('prom_activity')->where('id',$data['id'])->where('shop_id',$shop_id)->find();
                    if($promos){
                        if(!empty($data['pic_id'])){
                            $zssjpics = Db::name('shopadmin_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                            if($zssjpics && $zssjpics['img_url']){
                                $data['pic_url'] = $zssjpics['img_url'];
                            }else{
                                if(!empty($promos['pic_url'])){
                                    $data['pic_url'] = $promos['pic_url'];
                                }
                            }
                        }else{
                            if(!empty($promos['pic_url'])){
                                $data['pic_url'] = $promos['pic_url'];
                            }
                        }
                        
                        if($data['type'] == 1){
                            if(empty($data['discount'])){
                                $value = array('status'=>0,'mess'=>'请填写活动折扣');
                                return json($value);
                            }
                            $data['reduction'] = 0;
                        }
                        
                        if($data['type'] == 2){
                            if(empty($data['reduction'])){
                                $value = array('status'=>0,'mess'=>'请填写活动立减价格');
                                return json($value);
                            }else{
                                if($data['reduction'] < 1){
                                    $value = array('status'=>0,'mess'=>'活动立减价格不得小于1元');
                                    return json($value);
                                }
                            }
                            $data['discount'] == 100;
                        }
                        
                        if(!empty($data['goods_id']) && is_array($data['goods_id'])){
                            $goodids = array_unique($data['goods_id']);
                            $info_id = implode(',', $goodids);
                        
                            foreach ($goodids as $v){
                                $goods = Db::name('goods')->where('id',$v)->where('shop_id',$shop_id)->where('onsale',1)->field('id,shop_price')->find();
                        
                                if(!$goods){
                                    $value = array('status'=>0,'mess'=>'商品信息有误，增加失败');
                                    return json($value);
                                }else{
                                    if($data['type'] == 2){
                                        if($goods['shop_price']-$data['reduction'] < 10){
                                            $value = array('status'=>0,'mess'=>'存在商品价格与立减价格过于接近，增加失败');
                                            return json($value);
                                        }
                                    }
                                }
                            }
                        
                            $start_time = strtotime($data['start_time']);
                            $end_time = strtotime($data['end_time']);
                        
                            if($start_time < $end_time){
                                $count = Db::name('prom_activity')->update(array(
                                    'activity_name'=>$data['activity_name'],
                                    'type'=>$data['type'],
                                    'discount'=>$data['discount'],
                                    'reduction'=>$data['reduction'],
                                    'num'=>$data['num'],
                                    'start_time'=>$start_time,
                                    'end_time'=>$end_time,
                                    'pic_url'=>$data['pic_url'],
                                    'info_id'=>$info_id,
                                    'id'=>$data['id']
                                ));
                        
                                if($count !== false){
                                    if(!empty($zssjpics) && $zssjpics['img_url']){
                                        Db::name('shopadmin_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                                        if($promos['pic_url'] && file_exists('./'.$promos['pic_url'])){
                                            @unlink('./'.$promos['pic_url']);
                                        }
                                    }
                                    $value = array('status'=>1,'mess'=>'编辑成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'编辑失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'开始时间需小于结束时间');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请选择商品信息');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $admin_id = session('shopadmin_id');
                $shop_id = session('shopsh_id');
                $id = input('id');
                $promos = Db::name('prom_activity')->where('id',$id)->where('shop_id',$shop_id)->find();
                if($promos){
                    $zssjpics = Db::name('shopadmin_zspic')->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        Db::name('shopadmin_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                            @unlink('./'.$zssjpics['img_url']);
                        }
                    }
                    
                    $cominfo = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.shop_price,b.cate_name')->join('sp_category b','a.cate_id = b.id','LEFT')->where('a.id','in',$promos['info_id'])->where('a.shop_id',$shop_id)->where('a.onsale',1)->order('a.addtime desc')->select();
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    $this->assign('pnum', input('page'));
                    $this->assign('filter',input('filter'));
                    $this->assign('cominfo',$cominfo);
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
    
    public function delete(){
        $shop_id = session('shopsh_id');
        $id = input('id');
        if(!empty($id)){
            $promos = Db::name('prom_activity')->where('id',$id)->where('shop_id',$shop_id)->field('id,pic_url')->find();
            if($promos){
                $count = Db::name('prom_activity')->delete($id);
                if($count > 0){
                    if(!empty($promos['pic_url']) && file_exists('./'.$promos['pic_url'])){
                        @unlink('./'.$promos['pic_url']);
                    }
                    $value = array('status'=>0,'mess'=>'删除成功');
                }else{
                    $value = array('status'=>0,'mess'=>'删除失败');
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
            cookie('promo_keyword',input('post.keyword'),7200);
        }else{
            cookie('promo_keyword',null);
        }
        
        if(input('post.status') != ''){
            cookie('promo_status',input('post.status'),7200);
        }
        
        if(input('post.starttime') != ''){
            $promostarttime = strtotime(input('post.starttime'));
            cookie('promostarttime',$promostarttime,7200);
        }
        
        if(input('post.endtime') != ''){
            $promoendtime = strtotime(input('post.endtime'));
            cookie('promoendtime',$promoendtime,7200);
        }
        
        if(input('post.recommend') != ''){
            cookie('promo_recommend',input('post.recommend'),7200);
        }
        
        $where = array();
        
        $where['shop_id'] = $shop_id;
        
        if(cookie('promo_keyword')){
            $where['activity_name'] = cookie('promo_keyword');
        }
        
        if(cookie('promo_status') != ''){
            $promo_status = (int)cookie('promo_status');
            if(!empty($promo_status)){
                switch ($promo_status){
                        //即将开始
                    case 1:
                        $where['start_time'] = array('gt',time());
                        break;
                        //抢购中
                    case 2:
                        $where['start_time'] = array('elt',time());
                        $where['end_time'] = array('egt',time());
                        break;
                        //已结束
                    case 3:
                        $where['end_time'] = array('lt',time());
                        break;
                }
            }
        }
        
        if(cookie('promoendtime') && cookie('promostarttime')){
            $where['addtime'] = array(array('egt',cookie('promostarttime')), array('elt',cookie('promoendtime')));
        }
        
        if(cookie('promostarttime') && !cookie('promoendtime')){
            $where['addtime'] = array('egt',cookie('promostarttime'));
        }
        
        if(cookie('promoendtime') && !cookie('promostarttime')){
            $where['addtime'] = array('elt',cookie('promoendtime'));
        }
        
        if(cookie('promo_recommend') != ''){
            $promo_recommend = (int)cookie('promo_recommend');
            if(!empty(promo_recommend)){
                switch (promo_recommend){
                    //推荐
                    case 1:
                        $where['recommend'] = 1;
                        break;
                        //未推荐
                    case 2:
                        $where['recommend'] = 0;
                        break;
                }
            }
        }

        $list = Db::name('prom_activity')->where($where)->field('id,activity_name,type,discount,reduction,num,start_time,end_time,pic_url,recommend')->order('addtime desc')->paginate(25);
        $page = $list->render();
        
        $listres = $list->toArray();
        $list = $listres['data'];
        
        if($list){
            foreach ($list as $k => $v){
                if($v['start_time'] > time()){
                    //即将开始
                    $list[$k]['zhuangtai'] = 1;
                }elseif($v['start_time'] <= time() && $v['end_time'] >= time()){
                    //活动中
                    $list[$k]['zhuangtai'] = 2;
                }elseif($v['end_time'] < time()){
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
        
        if(cookie('promostarttime')){
            $this->assign('starttime',cookie('promostarttime'));
        }
        
        if(cookie('promoendtime')){
            $this->assign('endtime',cookie('promoendtime'));
        }
        
        if(cookie('promo_recommend')){
            $this->assign('recommend',cookie('promo_recommend'));
        }
        
        if(cookie('promo_status')){
            $this->assign('status',cookie('promo_status'));
        }
        
        if(cookie('promo_keyword')){
            $this->assign('keyword',cookie('promo_keyword'));
        }
        
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('filter',10);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }        
    }
    
    
}
