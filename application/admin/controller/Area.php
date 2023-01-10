<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Area as AreaMx;

class Area extends Common{
    //区县列表
    public function lst(){
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3))){
            $filter = 3;
        }
        $where = array();
        switch ($filter){
            case 1:
                $where['a.checked'] = 1;
                break;
            case 2:
                $where['a.checked'] = 0;
        }
        $list = Db::name('area')->alias('a')->field('a.*,b.city_name')->join('sp_city b','a.city_id = b.id','LEFT')->where($where)->order('a.id desc')->paginate(60);
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
            'filter'=>$filter,
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
                $list = Db::name('area')->alias('a')->field('a.*,b.city_name')->join('sp_city b','a.city_id = b.id','LEFT')->where('a.city_id',$city_id)->order('a.sort asc,a.id desc')->select();
                $this->assign(array(
                    'list'=>$list,
                    'city_name'=>$city_name,
                    'pro_id'=>$pro_id,
                    'city_id'=>$city_id,
                ));
                return $this->fetch('arealst');
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
                $cityres = Db::name('city')->where('pro_id',$pro_id)->where('checked',1)->field('id,city_name,zm')->order('sort asc')->select();
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
        $city_id = Db::name('area')->where('id',$data['id'])->value('city_id');
        $pro_id = Db::name('city')->where('id',$city_id)->value('pro_id');
        $city_checked = Db::name('city')->where('id',$city_id)->value('checked');
        $pro_checked = Db::name('province')->where('id',$pro_id)->value('checked');
        if($pro_checked == 1 && $city_checked == 1){
            $count = Db::name('area')->update($data);
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
                $city_checked = Db::name('city')->where('id',$data['city_id'])->value('checked');
                if($city_checked && $city_checked == 1){
                    $data['zm'] = strtoupper($data['zm']);
                    unset($data['pro_id']);
                    
                    // 启动事务
                    Db::startTrans();
                    try{
                        $area_id = Db::name('area')->insertGetId($data);
                        $citys = Db::name('city')->where('id',$data['city_id'])->field('city_name,pro_id')->find();
                        $area_name = $data['area_name'];
                        $pro_id = $citys['pro_id'];
                        $city_id = $data['city_id'];
                        
                        $city_zs = Db::name('city')->where('id',$city_id)->value('city_zs');
                        $pro_zs = Db::name('province')->where('id',$pro_id)->value('pro_zs');
                        if($city_zs == 0){
                            Db::name('city')->update(array('city_zs'=>1,'id'=>$city_id));
                        }
                        if($pro_zs == 0){
                            Db::name('province')->update(array('pro_zs'=>1,'id'=>$pro_id));
                        }
                        // 提交事务
                        Db::commit();
                        ys_admin_logs('新增区县','area',$area_id);
                        $value = array('status'=>1,'mess'=>'增加成功');
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $value = array('status'=>0,'mess'=>'增加失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'所属城市未开通，新增失败');
                }
            }
            return json($value);
        }else{
            $prores = Db::name('province')->where('checked',1)->field('id,pro_name,zm')->order('sort asc')->select();
            if(input('city_id')){
                $pro_id = Db::name('city')->where('id',input('city_id'))->where('checked',1)->value('pro_id');
                if($pro_id){
                    $cityres = Db::name('city')->where('pro_id',$pro_id)->where('checked',1)->field('id,city_name,zm')->order('sort asc')->select();
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
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'Area');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $city_checked = Db::name('city')->where('id',$data['city_id'])->value('checked');
                    if($city_checked && $city_checked == 1){
                        $areainfos = Db::name('area')->where('id',$data['id'])->field('id,city_id')->find();
                        if(!empty($areainfos)){
                            if($data['city_id'] != $areainfos['city_id']){
                                $orders = Db::name('order')->where('city_id',$areainfos['city_id'])->where('area_id',$areainfos['id'])->field('id')->limit(1)->find();
                                if($orders){
                                    $value = array('status'=>0,'mess'=>'该区域下存在商品订单，编辑失败');
                                    return json($value);
                                }
                            }
                
                            $data['zm'] = strtoupper($data['zm']);
                
                            $area = new AreaMx();
                            $count = $area->allowField(true)->save($data,array('id'=>$data['id']));
                            if($count !== false){
                                ys_admin_logs('修改区县','area',$data['id']);
                                $value = array('status'=>1,'mess'=>'修改成功');
                            }else{
                                $value = array('status'=>0,'mess'=>'修改失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'修改失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'所属城市未开通，编辑失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $id = input('id');
                $areas = Db::name('area')->where('id',$id)->find();
                if($areas){
                    $prores = Db::name('province')->field('id,pro_name,zm')->where('checked',1)->order('sort asc')->select();
                    $pro_id = Db::name('city')->where('id',$areas['city_id'])->where('checked',1)->value('pro_id');
                    $cityres = Db::name('city')->where('pro_id',$pro_id)->where('checked',1)->field('id,city_name,zm')->order('sort asc')->select();
                    if(input('s') && !input('city_id')){
                        $this->assign('search', input('s'));
                        $this->assign('pnum', input('page'));
                    }elseif(input('city_id') && !input('s')){
                        $this->assign('city_id',input('city_id'));
                    }elseif(!input('pro_id') && !input('s')){
                        $this->assign('pnum', input('page'));
                    }
                    $this->assign('areas',$areas);
                    $this->assign('prores',$prores);
                    $this->assign('pro_id',$pro_id);
                    $this->assign('cityres',$cityres);
                    $this->assign('filter',input('filter'));
                    return $this->fetch();
                }else{
                    $this->error('参数错误');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    public function delete(){
        $id = input('id');
        if(!empty($id) && !is_array($id)){
            $orders = Db::name('order')->where('area_id',$id)->field('id')->limit(1)->find();
            if($orders){
                $value = array('status'=>0,'mess'=>'该区县下存在商品订单，删除失败');
            }else{
                $address = Db::name('address')->where('area_id',$id)->field('id')->find();
                if($address){
                    $value = array('status'=>0,'mess'=>'存在用户收货地址使用该区县，删除失败');
                }else{
                    $count = AreaMx::destroy($id);
                    if($count > 0){
                        ys_admin_logs('删除区县','area',$id);
                        $value = array('status'=>1,'mess'=>'删除成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'删除失败');
                    }
                }
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return json($value);
    }
    
    public function search(){
        if(input('post.keyword') != ''){
            cookie('area_name',input('post.keyword'), 7200);
        }else{
            cookie('area_name',null);
        }
        
        if(input('post.checked') != ''){
            cookie("area_zt", input('post.checked'), 7200);
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
        
        if(cookie('area_zt') != ''){
            $area_zt = (int)cookie('area_zt');
            if($area_zt != 0){
                switch($area_zt){
                    //开通
                    case 1:
                        $where['a.checked'] = 1;
                        break;
                    //关闭
                    case 2:
                        $where['a.checked'] = 0;
                        break;
                }
            }
        }
        
        if(cookie('area_name') != ''){
            $where['a.area_name'] = array('like','%'.cookie('area_name').'%');
        }
        $list = Db::name('area')->alias('a')->field('a.*,b.city_name')->join('sp_city b','a.city_id = b.id','LEFT')->where($where)->order('a.id desc')->paginate(160);
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
        if(cookie('area_zt') != ''){
            $this->assign('checked',cookie('area_zt'));
        }
        if(cookie('area_name') != ''){
            $this->assign('area_name',cookie('area_name'));
        }
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('prores',$prores);
        $this->assign('filter',3);
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
        return json($value);
    }
    
}