<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Province as ProvinceMx;

class Province extends Common{
    //省区列表
    public function lst(){
        $list = Db::name('province')->order('sort asc')->paginate(3);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
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
                if(!isset($data['is_hot']) || !$data['is_hot']){
                    $data['is_hot'] = 0;
                }else{
                    $data['is_hot'] = 1;
                }
                $pro = new ProvinceMx();
                $pro->data($data);
                $lastId = $pro->allowField(true)->save();
                if($lastId){
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return $value;
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
                if(!isset($data['is_hot']) || !$data['is_hot']){
                    $data['is_hot'] = 0;
                }else{
                    $data['is_hot'] = 1;
                }
                $pro = new ProvinceMx();
                $count = $pro->allowField(true)->save($data,array('id'=>$data['id']));
                if($count !== false){
                    $value = array('status'=>1,'mess'=>'修改成功');
                }else{
                    $value = array('status'=>0,'mess'=>'修改失败');
                }
            }
            return $value;
        }else{
            $pros = Db::name('province')->where('id',input('id'))->find();
            if(input('s')){
                $this->assign('search', input('s'));
            }
            $this->assign('pnum', input('page'));
            $this->assign('pros',$pros);
            return $this->fetch();
        }
    }
    
    public function delete(){
        $id = input('id');
        if(!empty($id) && !is_array($id)){
            $citys = Db::name('city')->where('pro_id',$id)->field('id')->limit(1)->find();
            if($citys){
                $value = array('status'=>0,'mess'=>'该省下存在城市，删除失败');
            }else{
                 $companys = Db::name('company')->where('pro_id',$id)->field('id')->limit(1)->find();
                if($companys){
                    $value = array('status'=>0,'mess'=>'该省下存在省级合伙人，删除失败');
                }else{
                    $count = ProvinceMx::destroy($id);
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
            cookie('pro_name',input('post.keyword'),3600);
        }
        $where = array();
        if(cookie('pro_name')){
            $where['pro_name'] = array('like','%'.cookie('pro_name').'%');
        }
        $list = Db::name('province')->where($where)->order('sort asc')->paginate(3);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('pro_name')){
            $this->assign('pro_name',cookie('pro_name'));
        }
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
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
                Db::name('province')->update($data2);
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return $value;
    }    
}