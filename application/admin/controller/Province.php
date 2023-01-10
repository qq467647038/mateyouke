<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Province as ProvinceMx;

class Province extends Common{
    //省份列表
    public function lst(){
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3))){
            $filter = 3;
        }
        $where = array();
        switch($filter){
            case 1:
                $where = array('checked'=>1);
                break;
            case 2:
                $where = array('checked'=>0);
                break;
        }
        $list = Db::name('province')->where($where)->order('sort asc')->select();
        $this->assign('filter',$filter);
        $this->assign('list',$list);
        return $this->fetch('lst');
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $count = Db::name('province')->update($data);
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    //修改状态
    public function gaibianqy(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        
        // 启动事务
        Db::startTrans();
        try{
            Db::name('province')->update($data);
            $cityres = Db::name('city')->where('pro_id',$data['id'])->field('id')->select();
            Db::name('city')->where('pro_id',$data['id'])->update(array('checked'=>$value));
            foreach ($cityres as $v){
                Db::name('area')->where('city_id',$v['id'])->update(array('checked'=>$value));
            }
            // 提交事务
            Db::commit();
            $result = 1;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $result = 0;
        }
        return $result;
    }
    
    //检索类型名称是否存在
    public function checkProname(){
        if(request()->isAjax()){
            $arr = Db::name('province')->where('pro_name',input('post.pro_name'))->find();
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
            $result = $this->validate($data,'Province');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $data['zm'] = strtoupper($data['zm']);
                // 启动事务
                Db::startTrans();
                try{
                    $municipalities = "北京,上海,天津,重庆,澳门,香港";
                    $municipalities2 = "内蒙古,西藏,新疆";
                    
                    if (strpos($data['pro_name'], "省") === false && strpos($data['pro_name'], "市") === false && strpos($data['pro_name'], "自治区") === false && strpos($data['pro_name'], "行政区") === false){
                        if(strpos(','.$municipalities.',',','.$data['pro_name'].',') === false && strpos(','.$municipalities2.',',','.$data['pro_name'].',') === false){
                            $data['pro_name'] = $data['pro_name'].'省';
                        }elseif(strpos(','.$municipalities.',',','.$data['pro_name'].',') !== false && $data['pro_name'] != '香港' && $data['pro_name'] != '澳门'){
                            $data['pro_name'] = $data['pro_name'].'市';
                        }elseif(strpos(','.$municipalities2.',',','.$data['pro_name'].',') !== false){
                            $data['pro_name'] = $data['pro_name'].'自治区';
                        }
                    }
                    
                    $pro_id = Db::name('province')->insertGetId($data);
                    $muni = "北京市,上海市,天津市,重庆市,香港,澳门";

                    // 提交事务
                    Db::commit();
                    ys_admin_logs('新增省份','province',$pro_id);
                    $value = array('status'=>1,'mess'=>'增加成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }
    
    
    public function edit(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'Province');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $data['zm'] = strtoupper($data['zm']);
                
                $municipalities = "北京,上海,天津,重庆,澳门,香港";
                $municipalities2 = "内蒙古,西藏,新疆";
                
                if (strpos($data['pro_name'], "省") === false && strpos($data['pro_name'], "市") === false && strpos($data['pro_name'], "自治区") === false && strpos($data['pro_name'], "行政区") === false){
                    if(strpos(','.$municipalities.',',','.$data['pro_name'].',') === false){
                        $data['pro_name'] = $data['pro_name'].'省';
                    }elseif(strpos(','.$municipalities.',',','.$data['pro_name'].',') !== false && $data['pro_name'] != '香港' && $data['pro_name'] != '澳门'){
                        $data['pro_name'] = $data['pro_name'].'市';
                    }elseif(strpos(','.$municipalities2.',',','.$data['pro_name'].',') !== false){
                        $data['pro_name'] = $data['pro_name'].'自治区';
                    }
                }
                
                $pro = new ProvinceMx();
                $count = $pro->allowField(true)->save($data,array('id'=>$data['id']));
                if($count !== false){
                    ys_admin_logs('编辑省份','province',$data['id']);
                    $value = array('status'=>1,'mess'=>'修改成功');
                }else{
                    $value = array('status'=>0,'mess'=>'修改失败');
                }
            }
            return json($value);
            
        }else{
            $pros = Db::name('province')->where('id',input('id'))->find();
            if($pros){
                if(input('s')){
                    $this->assign('search', input('s'));
                }
                $this->assign('filter',input('filter'));
                $this->assign('pros',$pros);
                return $this->fetch();
            }else{
                $this->error('找不到相关信息');
            }
        }
    }
    
    public function delete(){
        $id = input('id');
        if(!empty($id) && !is_array($id)){
            $citys = Db::name('city')->where('pro_id',$id)->field('id')->limit(1)->find();
            if($citys){
                $value = array('status'=>0,'mess'=>'该省份下存在城市，删除失败');
            }else{
                $orders = Db::name('order')->where('pro_id',$id)->field('id')->limit(1)->find();
                if($orders){
                    $value = array('status'=>0,'mess'=>'该省份下存在商品订单，删除失败');
                }else{
                    $address = Db::name('address')->where('pro_id',$id)->field('id')->find();
                    if($address){
                        $value = array('status'=>0,'mess'=>'存在用户收货地址使用该省份，删除失败');
                    }else{
                        $count = ProvinceMx::destroy($id);
                        if($count > 0){
                            ys_admin_logs('删除省份','province',$id);
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
            cookie('pro_name',input('post.keyword'),3600);
        }else{
            cookie('pro_name',null);
        }
        
        if(input('post.checked') != ''){
            cookie("pro_zt", input('post.checked'), 7200);
        }
        
        $where = array();
        
        if(cookie('pro_zt') != ''){
            $pro_zt = (int)cookie('pro_zt');
            if($pro_zt != 0){
                switch($pro_zt){
                    //开通
                    case 1:
                        $where['checked'] = 1;
                        break;
                    //关闭
                    case 2:
                        $where['checked'] = 0;
                        break;
                }
            }
        }
        
        if(cookie('pro_name')){
            $where['pro_name'] = array('like','%'.cookie('pro_name').'%');
        }
        
        
        $list = Db::name('province')->where($where)->order('sort asc')->select();
        $search = 1;
        if(cookie('pro_name')){
            $this->assign('pro_name',cookie('pro_name'));
        }
        if(cookie('pro_zt') != ''){
            $this->assign('checked',cookie('pro_zt'));
        }
        $this->assign('filter',3);
        $this->assign('search',$search);
        $this->assign('list', $list);// 赋值数据集
        return $this->fetch('lst');
    }
    
    //处理排序
    public function order(){
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                Db::name('province')->update($data2);
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }    
}