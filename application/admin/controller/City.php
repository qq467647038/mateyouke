<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\City as CityMx;

class City extends Common{
    //城市列表
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
        $list = Db::name('city')->alias('a')->field('a.*,b.pro_name')->join('sp_province b','a.pro_id = b.id','LEFT')->where($where)->order('a.id desc')->paginate(35);
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
            return $this->fetch();
        }
    }
    
    public function citylst(){
        if(input('pro_id')){
            $pro_id = input('pro_id');
            $filter = input('filter');
            if(!$filter || !in_array($filter, array(1,2,3))){
                $filter = 3;
            }
            $pro_name = Db::name('province')->where('id',$pro_id)->value('pro_name');
            if(!empty($pro_name)){
                $where = array();
                $where['a.pro_id'] = $pro_id;
                switch ($filter){
                    case 1:
                        $where['a.checked'] = 1;
                        break;
                    case 2:
                        $where['a.checked'] = 0;
                        break;
                }
                $list = Db::name('city')->alias('a')->field('a.*,b.pro_name')->join('sp_province b','a.pro_id = b.id','LEFT')->where($where)->order('a.sort asc,a.id desc')->select();
                $this->assign(array(
                    'list'=>$list,
                    'pro_name'=>$pro_name,
                    'filter'=>$filter,
                    'pro_id'=>$pro_id
                ));
                return $this->fetch('citylst');
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
            if($value == 1){
                $info = '设置热门城市';
            }elseif($value == 0){
                $info = '取消热门城市';
            }
            ys_admin_logs($info,'city',$id);
        }else{
            $result = 0;
        }
        return $result;
    }
    
    public function gaibianqy(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $pro_id = Db::name('city')->where('id',$data['id'])->value('pro_id');
        $pro_checked = Db::name('province')->where('id',$pro_id)->value('checked');
        if($pro_checked == 1){
            // 启动事务
            Db::startTrans();
            try{
                Db::name('city')->update($data);
                Db::name('area')->where('city_id',$data['id'])->update(array('checked'=>$value));
                // 提交事务
                Db::commit();
                $result = 1;
                if($value == 1){
                    $info = '开启城市';
                }elseif($value == 0){
                    $info = '关闭城市';
                }
                ys_admin_logs($info,'city',$id);
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $result = 0;
            }
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
                $pro_checked = Db::name('province')->where('id',$data['pro_id'])->value('checked');
                if($pro_checked && $pro_checked == 1){
                    if(strpos($data['city_name'], "市") === false){
                        $data['city_name'] = $data['city_name'].'市';
                    }
                    $data['zm'] = strtoupper($data['zm']);
                    if(!isset($data['is_hot']) || !$data['is_hot']){
                        $data['is_hot'] = 0;
                    }else{
                        $data['is_hot'] = 1;
                    }
                    
                    if(!isset($data['checked']) || !$data['checked']){
                        $data['checked'] = 0;
                    }else{
                        $data['checked'] = 1;
                    }
                    
                    $city_id = Db::name('city')->insertGetId($data);
                    if($city_id){
                        ys_admin_logs('新增城市','city',$city_id);
                        $value = array('status'=>1,'mess'=>'增加成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'增加失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'所属省份未开通增加失败');
                }
            }
            return json($value);
        }else{
            $prores = Db::name('province')->where('checked',1)->field('id,pro_name,zm')->order('sort asc')->select();
            if(input('pro_id')){
                $this->assign('pro_id',input('pro_id'));
            }
            $this->assign('prores',$prores);
            return $this->fetch();
        }
    }
    
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'City');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $pro_checked = Db::name('province')->where('id',$data['pro_id'])->value('checked');
                    if($pro_checked && $pro_checked == 1){
                        $cityinfos = Db::name('city')->where('id',$data['id'])->field('id,pro_id')->find();
                        if(!empty($cityinfos)){
                            if($data['pro_id'] != $cityinfos['pro_id']){
                                $orders = Db::name('order')->where('pro_id',$cityinfos['pro_id'])->where('city_id',$cityinfos['id'])->field('id')->limit(1)->find();
                                if($orders){
                                    $value = array('status'=>0,'mess'=>'该区域下存在商品订单，编辑失败');
                                    return json($value);
                                }
                            }
                
                            if(strpos($data['city_name'], "市") === false){
                                $data['city_name'] = $data['city_name'].'市';
                            }
                            $data['zm'] = strtoupper($data['zm']);
                
                            if(!isset($data['is_hot']) || !$data['is_hot']){
                                $data['is_hot'] = 0;
                            }else{
                                $data['is_hot'] = 1;
                            }
                
                            if(!isset($data['checked']) || !$data['checked']){
                                $data['checked'] = 0;
                            }else{
                                $data['checked'] = 1;
                            }
                
                            $city = new CityMx();
                            $count = $city->allowField(true)->save($data,array('id'=>$data['id']));
                            if($count !== false){
                                ys_admin_logs('编辑城市','city',$data['id']);
                                $value = array('status'=>1,'mess'=>'修改成功');
                            }else{
                                $value = array('status'=>0,'mess'=>'修改失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'修改失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'所属省份未开通修改失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数');
            }
            return json($value);
        }else{
            if(input('id')){
                $citys = Db::name('city')->where('id',input('id'))->find();
                if($citys){
                    $prores = Db::name('province')->where('checked',1)->field('id,pro_name,zm')->order('sort asc')->select();
                    if(input('s') && !input('pro_id')){
                        $this->assign('search', input('s'));
                        $this->assign('pnum', input('page'));
                    }elseif(input('pro_id') && !input('s')){
                        $this->assign('pro_id',input('pro_id'));
                    }elseif(!input('pro_id') && !input('s')){
                        $this->assign('pnum', input('page'));
                    }
                    if(input('filter')){
                        $filter = input('filter');
                    }else{
                        $filter = 0;
                    }
                    $this->assign('filter',$filter);
                    $this->assign('citys',$citys);
                    $this->assign('prores',$prores);
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
        $id = input('id');
        if(!empty($id) && !is_array($id)){
            $areas = Db::name('area')->where('city_id',$id)->field('id')->limit(1)->find();
            if($areas){
                $value = array('status'=>0,'mess'=>'该城市下存在区或县，删除失败');
            }else{
                $orders = Db::name('order')->where('city_id',$id)->field('id')->limit(1)->find();
                if($orders){
                    $value = array('status'=>0,'mess'=>'该城市下存在商品订单，删除失败');
                }else{
                    $address = Db::name('address')->where('city_id',$id)->field('id')->find();
                    if($address){
                        $value = array('status'=>0,'mess'=>'存在用户收货地址使用该城市，删除失败');
                    }else{
                        $count = CityMx::destroy($id);
                        if($count > 0){
                            ys_admin_logs('删除城市','city',$id);
                            $value = array('status'=>1,'mess'=>'删除成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'删除失败');
                        }
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
            cookie('city_name',input('post.keyword'),3600);
        }else{
            cookie('city_name',null);
        }
        
        if(input('post.checked') != ''){
            cookie("city_zt", input('post.checked'), 7200);
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
        
        if(cookie('city_zt') != ''){
            $city_zt = (int)cookie('city_zt');
            if($city_zt != 0){
                switch($city_zt){
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
        
        if(cookie('city_name') != ''){
            $where['a.city_name'] = array('like','%'.cookie('city_name').'%');
        }

        $list =  Db::name('city')->alias('a')->field('a.*,b.pro_name')->join('sp_province b','a.pro_id = b.id','LEFT')->where($where)->order('a.id desc')->paginate(25);
        $page = $list->render();
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('quyu_pro_id') != ''){
            $this->assign('pro_id',cookie('quyu_pro_id'));
        }
        if(cookie('city_zt') != ''){
            $this->assign('checked',cookie('city_zt'));
        }
        if(cookie('city_name') != ''){
            $this->assign('city_name',cookie('city_name'));
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
                Db::name('city')->update($data2);
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }
}