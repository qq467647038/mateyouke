<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\City as CityMx;

class City extends Common{
    //城市列表
    public function lst(){
        $list = Db::name('city')->alias('a')->field('a.*,b.pro_name')->join('sp_province b','a.pro_id = b.id','LEFT')->order('a.sort asc')->paginate(3);
        $page = $list->render();
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum,
            'prores'=>$prores
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function citylst(){
        if(input('pro_id')){
            $pro_id = input('pro_id');
            $pro_name = Db::name('province')->where('id',$pro_id)->value('pro_name');
            if(!empty($pro_name)){
                $list = Db::name('city')->alias('a')->field('a.*,b.pro_name')->join('sp_province b','a.pro_id = b.id','LEFT')->where('a.pro_id',$pro_id)->order('a.sort asc')->paginate(3);
                $page = $list->render();
                $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
                if(input('page')){
                    $pnum = input('page');
                }else{
                    $pnum = 1;
                }
                $this->assign(array(
                    'list'=>$list,
                    'page'=>$page,
                    'pnum'=>$pnum,
                    'pro_name'=>$pro_name,
                    'pro_id'=>$pro_id,
                    'prores'=>$prores
                ));
                if(request()->isAjax()){
                    return $this->fetch('ajaxpage');
                }else{
                    return $this->fetch('lst');
                }
            }else{
                $this->error('参数错误');
            }
        }else{
            $this->error('参数错误');
        }
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $count = Db::name('city')->update($data);
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    //检索类型名称是否存在
    public function checkCityname(){
        if(request()->isAjax()){
            $arr = Db::name('city')->where('city_name',input('post.city_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'City');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $data['zm'] = strtoupper($data['zm']);
                $data['price'] = trim($data['price']);
                $data['fee_price'] = trim($data['fee_price']);
                if(!isset($data['is_hot']) || !$data['is_hot']){
                    $data['is_hot'] = 0;
                }else{
                    $data['is_hot'] = 1;
                }
                $city = new CityMx(); 
                $city->data($data);
                $lastId = $city->allowField(true)->save();
                if($lastId){
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return $value;
        }else{
            $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
            if(input('pro_id')){
                $this->assign('pro_id',input('pro_id'));
            }
            $this->assign('prores',$prores);
            return $this->fetch();
        }
    }
    
    public function edit(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'City');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $data['zm'] = strtoupper($data['zm']);
                $data['price'] = trim($data['price']);
                $data['fee_price'] = trim($data['fee_price']);
                if(!isset($data['is_hot']) || !$data['is_hot']){
                    $data['is_hot'] = 0;
                }else{
                    $data['is_hot'] = 1;
                }
                $city = new CityMx();
                $count = $city->allowField(true)->save($data,array('id'=>$data['id']));
                if($count !== false){
                    $value = array('status'=>1,'mess'=>'修改成功');
                }else{
                    $value = array('status'=>0,'mess'=>'修改失败');
                }
            }
            return $value;
        }else{
            $citys = Db::name('city')->where('id',input('id'))->find();
            $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
            if(input('s')){
                $this->assign('search', input('s'));
            }
            if(input('pro_id')){
                $this->assign('pro_id',input('pro_id'));
            }
            $this->assign('pnum', input('page'));
            $this->assign('citys',$citys);
            $this->assign('prores',$prores);
            return $this->fetch();
        }
    }
    
    public function delete(){
        $id = input('id');
        if(!empty($id) && !is_array($id)){
            $citys = Db::name('area')->where('city_id',$id)->field('id')->limit(1)->find();
            if($citys){
                $value = array('status'=>0,'mess'=>'该省下存在区或县，删除失败');
            }else{
                $companys = Db::name('company')->where('city_id',$id)->field('id')->limit(1)->find();
                if($companys){
                    $value = array('status'=>0,'mess'=>'该省下存在省级合伙人，删除失败');
                }else{
                    $count = CityMx::destroy($id);
                    if($count > 0){
                        $value = array('status'=>1,'mess'=>'删除成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'删除失败');
                    }
                }
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return $value;
    }
    
    public function search(){
        if(input('post.keyword') != ''){
            cookie('city_name',input('post.keyword'),3600);
        }
        if(input('post.pro_id') != ''){
            cookie("quyu_pro_id", input('post.pro_id'), 3600);
        }
        
        $where = array();
        
        if(cookie('quyu_pro_id') != ''){
            $proid = (int)cookie('quyu_pro_id');
            if($proid != 0){
                $where['a.pro_id'] = $proid;
            }
        }
        
        if(cookie('city_name') != ''){
            $where['a.city_name'] = array('like','%'.cookie('city_name').'%');
        }

        $list =  Db::name('city')->alias('a')->field('a.*,b.pro_name')->join('sp_province b','a.pro_id = b.id','LEFT')->where($where)->order('a.sort asc')->paginate(3);
        $page = $list->render();
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('quyu_pro_id') != ''){
            $this->assign('sheng_id',cookie('quyu_pro_id'));
        }
        if(cookie('city_name') != ''){
            $this->assign('city_name',cookie('city_name'));
        }
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('prores',$prores);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    //处理排序
    public function order(){
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                Db::name('city')->update($data2);
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return $value;
    }
}