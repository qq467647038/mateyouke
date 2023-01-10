<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Area as AreaMx;

class Area extends Common{
    //区县列表
    public function lst(){
        $list = Db::name('area')->alias('a')->field('a.*,b.city_name')->join('sp_city b','a.city_id = b.id','LEFT')->order('a.sort asc')->paginate(3);
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
    
    public function arealst(){
        if(input('city_id')){
            $city_id = input('city_id');
            $cityinfo = Db::name('city')->where('id',$city_id)->field('id,city_name,pro_id')->find();
            if(!empty($cityinfo)){
                $city_name = $cityinfo['city_name'];
                $pro_id = $cityinfo['pro_id'];
                $list = Db::name('area')->alias('a')->field('a.*,b.city_name')->join('sp_city b','a.city_id = b.id','LEFT')->where('a.city_id',$city_id)->order('a.sort asc')->paginate(3);
                $page = $list->render();
                $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
                $cityres = Db::name('city')->where('pro_id',$pro_id)->field('id,city_name,zm')->order('sort asc')->select();
                if(input('page')){
                    $pnum = input('page');
                }else{
                    $pnum = 1;
                }
                $this->assign(array(
                    'list'=>$list,
                    'page'=>$page,
                    'pnum'=>$pnum,
                    'city_name'=>$city_name,
                    'pro_id'=>$pro_id,
                    'city_id'=>$city_id,
                    'prores'=>$prores,
                    'cityres'=>$cityres
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
    
    public function getcitylist(){
        if(request()->isPost()){
            $pro_id = input('post.pro_id');
            if($pro_id != 0){
                $cityres = Db::name('city')->where('pro_id',$pro_id)->field('id,city_name,zm')->order('sort asc')->select();
                if(empty($cityres)){
                    $cityres = 0;
                }
                return $cityres;
            }
        }
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $count = Db::name('area')->update($data);
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    //检索类型名称是否存在
    public function checkAreaname(){
        if(request()->isAjax()){
            $arr = Db::name('area')->where('area_name',input('post.area_name'))->find();
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
            $result = $this->validate($data,'Area');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $data['zm'] = strtoupper($data['zm']);
                $area = new AreaMx(); 
                $area->data($data);
                $lastId = $area->allowField(true)->save();
                if($lastId){
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return $value;
        }else{
            $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
            if(input('city_id')){
                $pro_id = Db::name('city')->where('id',input('city_id'))->value('pro_id');
                if($pro_id){
                    $cityres = Db::name('city')->where('pro_id',$pro_id)->field('id,city_name,zm')->order('sort asc')->select();
                    $this->assign('pro_id',$pro_id);
                    $this->assign('cityres',$cityres);
                    $this->assign('city_id',input('city_id'));
                }
            }
            $this->assign('prores',$prores);
            return $this->fetch();
        }
    }
    
    public function edit(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'Area');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $data['zm'] = strtoupper($data['zm']);
                $area = new AreaMx();
                $count = $area->allowField(true)->save($data,array('id'=>$data['id']));
                if($count !== false){
                    $value = array('status'=>1,'mess'=>'修改成功');
                }else{
                    $value = array('status'=>0,'mess'=>'修改失败');
                }
            }
            return $value;
        }else{
            $id = input('id');
            $areas = Db::name('area')->where('id',$id)->find();
            $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
            $pro_id = Db::name('city')->where('id',$areas['city_id'])->value('pro_id');
            $cityres = Db::name('city')->where('pro_id',$pro_id)->field('id,city_name,zm')->order('sort asc')->select();
            if(input('s')){
                $this->assign('search', input('s'));
            }
            if(input('city_id')){
                $this->assign('city_id',input('city_id'));
            }
            $this->assign('pnum', input('page'));
            $this->assign('areas',$areas);
            $this->assign('prores',$prores);
            $this->assign('pro_id',$pro_id);
            $this->assign('cityres',$cityres);
            return $this->fetch();
        }
    }
    
    public function delete(){
        $id = input('id');
        if(!empty($id) && !is_array($id)){
            $companys = Db::name('company')->where('area_id',$id)->field('id')->limit(1)->find();
            if($companys){
                $value = array('status'=>0,'mess'=>'该区域下存在区域合伙人，删除失败');
            }else{
                $shops = Db::name('shops')->where('area_id',$id)->field('id')->limit(1)->find();
                if($shops){
                    $value = array('status'=>0,'mess'=>'该区域下存在加盟商铺，删除失败');
                }else{
                    $count = AreaMx::destroy($id);
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
            cookie('area_name',input('post.keyword'), 7200);
        }
        
        if(input('post.pro_id') != ''){
            cookie("qy_pro_id", input('post.pro_id'), 7200);
        }
        
        if(input('post.city_id') != ''){
            cookie("quyu_city_id", input('post.city_id'), 7200);
        }
        
        $where = array();
        
        if(cookie('quyu_city_id') != ''){
            $cityid = (int)cookie('quyu_city_id');
            if($cityid != 0){
                $where['a.city_id'] = $cityid;
            }
        }
        
        if(cookie('area_name') != ''){
            $where['a.area_name'] = array('like','%'.cookie('area_name').'%');
        }
        $list = Db::name('area')->alias('a')->field('a.*,b.city_name')->join('sp_city b','a.city_id = b.id','LEFT')->where($where)->order('a.sort asc')->paginate(3);
        $page = $list->render();
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('qy_pro_id')){
            $cityres = Db::name('city')->where('pro_id',cookie('qy_pro_id'))->field('id,city_name,zm')->select();
            if(!empty($cityres)){
                $this->assign('cityres',$cityres);
            }
        }
        if(cookie('quyu_city_id') != ''){
            $this->assign('city_id',cookie('quyu_city_id'));
        }
        if(cookie('qy_pro_id') != ''){
            $this->assign('pro_id',cookie('qy_pro_id'));
        }
        if(cookie('area_name') != ''){
            $this->assign('area_name',cookie('area_name'));
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
                Db::name('area')->update($data2);
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return $value;
    }
    
}