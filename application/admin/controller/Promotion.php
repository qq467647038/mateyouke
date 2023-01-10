<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Promotion extends Common{
    
    public function lst(){
        $shop_id = session('shop_id');
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,5))){
            $filter = 5;
        }
        
        $where = array();
        $where['shop_id'] = $shop_id;
        $where['is_show'] = 1;
        switch($filter){
            //即将开始
            case 1:
                $where['start_time'] = array('gt',time());
                break;
            //活动中
            case 2:
                $where['start_time'] = array('elt',time());
                $where['end_time'] = array('gt',time());
                break;
            //已结束
            case 3:
                $where['end_time'] = array('elt',time());
                break;
        }
        
        $list = Db::name('promotion')->where($where)->field('id,activity_name,type,start_time,end_time,pic_url,recommend')->order('addtime desc')->paginate(25);
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
    
    //处理上传图片
    public function uploadify(){
        $admin_id = session('admin_id');
        $file = request()->file('filedata');
        if($file){
            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'promotion_pic');
            if($info){
                $zssjpics = Db::name('shopadmin_zspic')->where('admin_id',$admin_id)->find();
                if($zssjpics && $zssjpics['img_url']){
                    Db::name('shopadmin_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                        @unlink('./'.$zssjpics['img_url']);
                    }
                }
                $getSaveName = str_replace("\\","/",$info->getSaveName());
                $original = 'uploads/promotion_pic/'.$getSaveName;
                $image = \think\Image::open('./'.$original);
                $image->thumb(640, 400)->save('./'.$original,null,90);
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
            $admin_id = session('admin_id');
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
            $admin_id = session('admin_id');
            $shop_id = session('shop_id');
            $data = input('post.');
            $data['shop_id'] = $shop_id;
            $data['addtime'] = time();
            
            $result = $this->validate($data,'Promotion');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(!empty($data['man_num']) && is_array($data['man_num'])){
                    $man_num = $data['man_num'];
                
                    if(count($man_num) <= 3){
                        $man_num2 = array_unique($man_num);
                        if(count($man_num2) != count($man_num)){
                            $value = array('status'=>0,'mess'=>'存在相同的满数量，编辑失败');
                            return json($value);
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'最多允许添加三种满减方式');
                        return json($value);
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'请填写促销满数量');
                    return json($value);
                }
                
                if(!empty($data['discount']) && is_array($data['discount'])){
                    $discount = $data['discount'];
                
                    if(count($discount) <= 3){
                        $discount2 = array_unique($discount);
                        if(count($discount2) != count($discount)){
                            $value = array('status'=>0,'mess'=>'存在相同的折扣，编辑失败');
                            return json($value);
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'最多允许添加三种满减方式');
                        return json($value);
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'请填写折扣信息');
                    return json($value);
                }
                
                foreach ($man_num as $kc => $vc){
                    if(empty($vc) || !preg_match("/^\\+?[1-9][0-9]*$/", $vc)){
                        $value = array('status'=>0,'mess'=>'存在满数量格式错误');
                        return json($value);
                    }
                
                    if(!empty($discount[$kc]) && preg_match("/^\\+?[1-9][0-9]*$/", $discount[$kc])){
                        if($discount[$kc] < 10 || $discount[$kc] > 100){
                            $value = array('status'=>0,'mess'=>'折扣值在10到100区间内');
                            return json($value);
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'存在折扣信息格式错误');
                        return json($value);
                    }
                }
                
                if(!empty($data['pic_id'])){
                    $zssjpics = Db::name('shopadmin_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        $data['pic_url'] = $zssjpics['img_url'];
                        
                        
                        $start_time = strtotime($data['start_time']);
                        $end_time = strtotime($data['end_time']);
                        
                        if($start_time > time()){
                            if($start_time < $end_time){
                                if(!empty($data['goods_id']) && is_array($data['goods_id'])){
                                    $goodids = array_unique($data['goods_id']);
                                    $info_id = implode(',', $goodids);
                                
                                    foreach ($goodids as $v){
                                        $goods = Db::name('goods')->where('id',$v)->where('shop_id',$shop_id)->where('onsale',1)->field('id,shop_price')->find();
                                
                                        if($goods){
                                            $promhds = Db::name('promotion')->where(function ($query) use ($v,$start_time,$end_time,$shop_id){
                                                $query->where('find_in_set('.$v.',info_id)')->where('is_show',1)->where('start_time','elt',$start_time)->where('end_time','egt',$start_time)->where('shop_id',$shop_id);
                                            })->whereOr(function ($query) use ($v,$start_time,$end_time,$shop_id){
                                                $query->where('find_in_set('.$v.',info_id)')->where('is_show',1)->where('start_time','egt',$start_time)->where('start_time','elt',$end_time)->where('shop_id',$shop_id);
                                            })->field('id')->find();
                                            
                                            if($promhds){
                                                $value = array('status'=>0,'mess'=>'相同时间段内存在商品已参加促销活动');
                                                return json($value);
                                            }
                                        }else{
                                            $value = array('status'=>0,'mess'=>'商品信息有误，增加失败');
                                            return json($value);
                                        }
                                    }
                                    
                                    $datainfo = array(
                                        'activity_name'=>$data['activity_name'],
                                        'type'=>$data['type'],
                                        'start_time'=>$start_time,
                                        'end_time'=>$end_time,
                                        'pic_url'=>$data['pic_url'],
                                        'info_id'=>$info_id,
                                        'shop_id'=>$shop_id,
                                        'addtime'=>time()
                                    );
                                    
                                    $prom_id = Db::name('promotion')->insertGetId($datainfo);
                                    
                                    if($prom_id){
                                        foreach ($man_num as $kd => $vd){
                                            Db::name('prom_type')->insert(array('man_num'=>$vd,'discount'=>$discount[$kd],'prom_id'=>$prom_id));
                                        }
                                    
                                        if($zssjpics && $zssjpics['img_url']){
                                            Db::name('shopadmin_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                                        }
                                        $value = array('status'=>1,'mess'=>'增加成功');
                                    }else{
                                        $value = array('status'=>0,'mess'=>'增加失败');
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'请选择商品信息');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'开始时间需小于结束时间');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'开始时间需大于当前时间');
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
            $admin_id = session('admin_id');
            $shop_id = session('shop_id');
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
                $admin_id = session('admin_id');
                $shop_id = session('shop_id');
                $data = input('post.');
                $data['shop_id'] = $shop_id;
        
                $result = $this->validate($data,'Promotion');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $prohd_id = $data['id'];
                    
                    $promos = Db::name('promotion')->where('id',$data['id'])->where('shop_id',$shop_id)->where('is_show',1)->find();
                    if($promos){
                        if(!empty($data['man_num']) && is_array($data['man_num'])){
                            $man_num = $data['man_num'];
                            
                            if(count($man_num) <= 3){
                                $man_num2 = array_unique($man_num);
                                if(count($man_num2) != count($man_num)){
                                    $value = array('status'=>0,'mess'=>'存在相同的满数量，编辑失败');
                                    return json($value);
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'最多允许添加三种满减方式');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请填写促销满数量');
                            return json($value);
                        }
                        
                        if(!empty($data['discount']) && is_array($data['discount'])){
                            $discount = $data['discount'];
                            
                            if(count($discount) <= 3){
                                $discount2 = array_unique($discount);
                                if(count($discount2) != count($discount)){
                                    $value = array('status'=>0,'mess'=>'存在相同的折扣，编辑失败');
                                    return json($value);
                                } 
                            }else{
                                $value = array('status'=>0,'mess'=>'最多允许添加三种满减方式');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请填写折扣信息');
                            return json($value);
                        }
                        
                        if(!empty($data['hdpm_id']) && is_array($data['hdpm_id'])){
                            $hdpm_id = $data['hdpm_id'];
                        }else{
                            $value = array('status'=>0,'mess'=>'缺少促销方式参数');
                            return json($value);
                        }
                        
                        foreach ($man_num as $kc => $vc){
                            if(empty($vc) || !preg_match("/^\\+?[1-9][0-9]*$/", $vc)){
                                $value = array('status'=>0,'mess'=>'存在满数量格式错误');
                                return json($value);
                            }
                        
                            if(!empty($discount[$kc]) && preg_match("/^\\+?[1-9][0-9]*$/", $discount[$kc])){
                                if($discount[$kc] < 10 || $discount[$kc] > 100){
                                    $value = array('status'=>0,'mess'=>'折扣值在10到100区间内');
                                    return json($value);
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'存在折扣信息格式错误');
                                return json($value);
                            }
                        }
                        
                        
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
                        
                        $start_time = strtotime($data['start_time']);
                        $end_time = strtotime($data['end_time']);
                        if($start_time < $end_time){
                            if(!empty($data['goods_id']) && is_array($data['goods_id'])){
                                $goodids = array_unique($data['goods_id']);
                                $info_id = implode(',', $goodids);
                            
                                foreach ($goodids as $v){
                                    $goods = Db::name('goods')->where('id',$v)->where('shop_id',$shop_id)->where('onsale',1)->field('id,shop_price')->find();
                                    
                                    if($goods){
                                        $promhds = Db::name('promotion')->where(function ($query) use ($prohd_id,$v,$start_time,$end_time,$shop_id){
                                            $query->where('id','neq',$prohd_id)->where('find_in_set('.$v.',info_id)')->where('is_show',1)->where('start_time','elt',$start_time)->where('end_time','egt',$start_time)->where('shop_id',$shop_id);
                                        })->whereOr(function ($query) use ($prohd_id,$v,$start_time,$end_time,$shop_id){
                                            $query->where('id','neq',$prohd_id)->where('find_in_set('.$v.',info_id)')->where('is_show',1)->where('start_time','egt',$start_time)->where('start_time','elt',$end_time)->where('shop_id',$shop_id);
                                        })->field('id')->find();
                                    
                                        if($promhds){
                                            $value = array('status'=>0,'mess'=>'相同时间段内存在商品已参加促销活动');
                                            return json($value);
                                        }
                                    }else{
                                        $value = array('status'=>0,'mess'=>'商品信息有误，增加失败');
                                        return json($value);
                                    }
                                }
                                
                                $datainfo = array(
                                    'activity_name'=>$data['activity_name'],
                                    'type'=>$data['type'],
                                    'start_time'=>$start_time,
                                    'end_time'=>$end_time,
                                    'pic_url'=>$data['pic_url'],
                                    'info_id'=>$info_id,
                                    'id'=>$data['id']
                                );
                                
                                $count = Db::name('promotion')->update($datainfo);
                                
                                if($count !== false){
                                    foreach ($man_num as $kd => $vd){
                                        if(!empty($hdpm_id[$kd])){
                                            Db::name('prom_type')->where('id',$hdpm_id[$kd])->where('prom_id',$data['id'])->update(array('man_num'=>$vd,'discount'=>$discount[$kd]));
                                        }else{
                                            Db::name('prom_type')->insert(array('man_num'=>$vd,'discount'=>$discount[$kd],'prom_id'=>$data['id']));
                                        }
                                    }
                                
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
                                $value = array('status'=>0,'mess'=>'请选择商品信息');
                            }                        
                        }else{
                            $value = array('status'=>0,'mess'=>'开始时间需小于结束时间');
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
                $admin_id = session('admin_id');
                $shop_id = session('shop_id');
                $id = input('id');
                $promos = Db::name('promotion')->where('id',$id)->where('shop_id',$shop_id)->where('is_show',1)->find();
                if($promos){
                    $zssjpics = Db::name('shopadmin_zspic')->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        Db::name('shopadmin_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                            @unlink('./'.$zssjpics['img_url']);
                        }
                    }
                    
                    $prom_typeres = Db::name('prom_type')->where('prom_id',$promos['id'])->select();
                    
                    $cominfo = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.shop_price,b.cate_name')->join('sp_shop_cate b','a.shcate_id = b.id','LEFT')->where('a.id','in',$promos['info_id'])->where('a.shop_id',$shop_id)->where('a.onsale',1)->order('a.addtime desc')->select();
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
    
    public function deleteprom(){
        if(request()->isPost()){
            if(input('post.id') && input('post.prom_id')){
                $shop_id = session('shop_id');
                $id = input('post.id');
                $prom_id = input('post.prom_id');
                $proms = Db::name('promotion')->where('id',$prom_id)->where('shop_id',$shop_id)->where('is_show',1)->find();
                if($proms){
                    $prom_types = Db::name('prom_type')->where('id',$id)->where('prom_id',$prom_id)->find();
                    if($prom_types){
                        $count = Db::name('prom_type')->delete($id);
                        if($count > 0){
                            $value = array('status'=>1,'mess'=>'删除成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'删除失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关促销活动方式信息，删除失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'找不到相关促销活动信息，删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，删除失败');
            }
            return json($value);
        }        
    }
    
    public function delete(){
        $shop_id = session('shop_id');
        $id = input('id');
        if(!empty($id)){
            $promos = Db::name('promotion')->where('id',$id)->where('shop_id',$shop_id)->where('is_show',1)->field('id,pic_url')->find();
            if($promos){
                $count = Db::name('promotion')->update(array('id'=>$id,'is_show'=>0));
                if($count > 0){
                    $value = array('status'=>1,'mess'=>'删除成功');
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
        $shop_id = session('shop_id');
        
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
        $where['is_show'] = 1;
        
        
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
                        $where['end_time'] = array('gt',time());
                        break;
                        //已结束
                    case 3:
                        $where['end_time'] = array('elt',time());
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
            if(!empty($promo_recommend)){
                switch ($promo_recommend){
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

        $list = Db::name('promotion')->where($where)->field('id,activity_name,type,start_time,end_time,pic_url,recommend')->order('addtime desc')->paginate(25);
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
        $this->assign('filter',5);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }        
    }
    
    
}
