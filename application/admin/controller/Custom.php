<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Custom extends Common{
    
    public function lst(){
        $shop_id = session('shop_id');
        $list = Db::name('custom')->where('shop_id',$shop_id)->order('sort asc')->paginate(25);
        
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign(array(
            'list'=>$list,
            'pnum'=>$pnum,
            'page'=>$page
        ));
        return $this->fetch();
    }
    
    public function checkCustomname(){
        if(request()->isPost()){
            $shop_id = session('shop_id');
            $arr = Db::name('custom')->where('custom_name',input('post.custom_name'))->where('shop_id',$shop_id)->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
    
    public function add(){
        if(request()->isPost()){
            $shop_id = session('shop_id');
            $data = input('post.');
            $result = $this->validate($data,'Custom');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(!empty($data['goods_id']) && is_array($data['goods_id'])){
                    $goodids = array_unique($data['goods_id']);
                    $info_id = implode(',', $goodids);
                    
                    switch ($data['type']){
                        //商品信息
                        case 1:
                            foreach ($goodids as $v){
                                $goods = Db::name('goods')->where('id',$v)->where('onsale',1)->field('id')->find();
                                if(!$goods){
                                    $value = array('status'=>0,'mess'=>'推荐信息有误，增加失败');
                                    return json($value);
                                }
                            }
                            break;
                        //商家信息
                        case 2:
                            foreach ($goodids as $v){
                                $shops = Db::name('shops')->where('id',$v)->where('open_status',1)->field('id')->find();
                                if(!$shops){
                                    $value = array('status'=>0,'mess'=>'推荐信息有误，增加失败');
                                    return json($value);
                                }
                            }
                            break;
                    }

                    $data['addtime'] = time();
                    
                    $lastId = Db::name('custom')->insertGetId(array(
                        'custom_name'=>$data['custom_name'],
                        'type'=>$data['type'],
                        'sort'=>$data['sort'],
                        'info_id'=>$info_id,
                        'shop_id'=>$shop_id,
                        'addtime'=>time()
                    ));
                    if($lastId){
                        ys_admin_logs('新增推荐位','custom',$lastId);
                        $value = array('status'=>1,'mess'=>'增加成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'增加失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'请选择推荐信息');
                }
            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }
    
    public function getcusinfo(){
        if(request()->isPost()){
            if(input('post.id')){
                $shop_id = session('shop_id');
                $id = input('post.id');
                $coms = Db::name('custom')->where('id',$id)->where('shop_id',$shop_id)->find();
                if($coms){
                    switch ($coms['type']){
                        //商品信息
                        case 1:
                            $cominfo = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.shop_price,b.shop_name,c.cate_name')->join('sp_shops b','a.shop_id = b.id','LEFT')->join('sp_category c','a.cate_id = c.id','LEFT')->where('a.id','in',$coms['info_id'])->where('a.onsale',1)->order('a.addtime desc')->select();
                            break;
                        //商家信息
                        case 2:
                            $cominfo = Db::name('shops')->alias('a')->field('a.id,a.shop_name,a.contacts,a.telephone,b.industry_name')->join('sp_industry b','a.indus_id = b.id','LEFT')->where('a.id','in',$coms['info_id'])->where('a.open_status',1)->order('a.addtime desc')->select();
                            break;
                    }
                    $value = array('status'=>1,'type'=>$coms['type'],'info'=>$cominfo);
                }else{
                    $value = array('status'=>0,'mess'=>'参数错误');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数');
            }
            return json($value);
        }
    }
    
    
    public function edit(){
        if(request()->isAjax()){
            $data = input('post.');
            $shop_id = session('shop_id');
            $result = $this->validate($data,'Custom');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(input('post.id')){
                    if(!empty($data['goods_id']) && is_array($data['goods_id'])){
                        $goodids = array_unique($data['goods_id']);
                        $info_id = implode(',', $goodids);
                        
                        $tuijianwei = Db::name('custom')->where('id',$data['id'])->where('shop_id',$shop_id)->find();
                        if($tuijianwei){
                            switch ($data['type']){
                                //商品信息
                                case 1:
                                    foreach ($goodids as $v){
                                        $goods = Db::name('goods')->where('id',$v)->where('onsale',1)->field('id')->find();
                                        if(!$goods){
                                            $value = array('status'=>0,'mess'=>'推荐信息有误，编辑失败');
                                            return json($value);
                                        }
                                    }
                                    break;
                                //商家信息
                                case 2:
                                    foreach ($goodids as $v){
                                        $shops = Db::name('shops')->where('id',$v)->where('open_status',1)->field('id')->find();
                                        if(!$shops){
                                            $value = array('status'=>0,'mess'=>'推荐信息有误，增加失败');
                                            return json($value);
                                        }
                                    }
                                    break;
                            }
                            $count = Db::name('custom')->update(array(
                                'id'=>$data['id'],
                                'custom_name'=>$data['custom_name'],
                                'type'=>$data['type'],
                                'sort'=>$data['sort'],
                                'info_id'=>$info_id
                            ));
                            
                            if($count > 0){
                                ys_admin_logs('编辑推荐位','custom',$data['id']);
                                $value = array('status'=>1,'mess'=>'编辑成功');
                            }else{
                                $value = array('status'=>0,'mess'=>'编辑失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'信息错误，编辑失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'请选择推荐信息');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
                }
            }
            return $value;
        }else{
            if(input('id')){
                $shop_id = session('shop_id');
                $id = input('id');
                $coms = Db::name('custom')->where('id',$id)->where('shop_id',$shop_id)->find();
                if($coms){
                    switch ($coms['type']){
                        //商品信息
                        case 1:
                            $cominfo = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.shop_price,b.shop_name,c.cate_name')->join('sp_shops b','a.shop_id = b.id','LEFT')->join('sp_category c','a.cate_id = c.id','LEFT')->where('a.id','in',$coms['info_id'])->where('a.onsale',1)->order('a.addtime desc')->select();
                            break;
                        //商家信息
                        case 2:
                            $cominfo = Db::name('shops')->alias('a')->field('a.id,a.shop_name,a.contacts,a.telephone,b.industry_name')->join('sp_industry b','a.indus_id = b.id','LEFT')->where('a.id','in',$coms['info_id'])->where('a.open_status',1)->order('a.addtime desc')->select();
                            break;
                    }
                
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    $this->assign('pnum', input('page'));
                    $this->assign('coms',$coms);
                    $this->assign('cominfo',$cominfo);
                    return $this->fetch();
                }else{
                    $this->error('参数错误');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }

    
    //删除
    public function delete(){
       if(input('id') && !is_array(input('id'))){
           $shop_id = session('shop_id');
           $id = input('id');
           $coms = Db::name('custom')->where('id',$id)->where('shop_id',$shop_id)->find();
           if($coms){
               $count = Db::name('custom')->where('id',$id)->delete();
               if($count > 0){
                   ys_admin_logs('删除推荐位','custom',$id);
                   $value = array('status'=>1,'mess'=>'删除成功');
               }else{
                   $value = array('status'=>0,'mess'=>'删除失败');
               }
           }else{
               $value = array('status'=>0,'mess'=>'删除失败');
           }
           return $value;
       }
    }
    
    public function paixu(){
        if(request()->isAjax()){
            $shop_id = session('shop_id');
            if(input('post.ids') && input('post.sort')){
                $ids = input('post.ids');
                $sort = input('post.sort');
                $ids = explode(',', $ids);
                $sort = explode(',', $sort);
                foreach ($ids as $k => $v){
                    $coms = Db::name('custom')->where('id',$v)->where('shop_id',$shop_id)->find();
                    if($coms){
                        Db::name('custom')->where('id',$v)->update(array('sort'=>$sort[$k]));
                    }
                }
            }
            $value = array('status'=>1,'mess'=>'排序成功');
            return $value;
        }
    }
    
}