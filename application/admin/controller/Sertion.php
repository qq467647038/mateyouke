<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Sertion as SertionMx;

class Sertion extends Common{
    
    public function lst(){
        $list = Db::name('sertion')->order('sort asc')->select();
        $this->assign('list',$list);// 赋值数据集
        return $this->fetch();
    }
    
    public function checkSername(){
        if(request()->isAjax()){
            $arr = Db::name('sertion')->where('ser_name',input('post.ser_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
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
        $count = Db::name('sertion')->where('id',$id)->update($data);
        if($count > 0){
            if($value == 1){
                ys_admin_logs('开启服务项','sertion',$id);
            }elseif($value == 0){
                ys_admin_logs('关闭服务项','sertion',$id);
            }
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'Sertion');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $sertion = new SertionMx();
                $sertion->data($data);
                $lastId = $sertion->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增服务项','sertion',$sertion->id);
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
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
            $result = $this->validate($data,'Sertion');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $sertion = new SertionMx();
                $count = $sertion->allowField(true)->save($data,array('id'=>$data['id']));
                if($count !== false){
                    ys_admin_logs('编辑服务项','sertion',$data['id']);
                    $value = array('status'=>1,'mess'=>'编辑成功');
                }else{
                    $value = array('status'=>0,'mess'=>'编辑失败');
                }
            }
            return json($value);
        }else{
            $sers = Db::name('sertion')->find(input('id'));
            $this->assign('sers', $sers);
            return $this->fetch();
        }
    }
    
    public function delete(){
        if(input('id') && !is_array(input('id'))){
            $id = input('id');
            $count = SertionMx::destroy($id);
            if($count > 0){
                ys_admin_logs('删除服务项','sertion',$id);
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'编辑失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }
    
    
    
    public function paixu(){
        if(request()->isAjax()){
            if(input('post.ids') && input('post.sort')){
                $ids = input('post.ids');
                $sort = input('post.sort');
                $ids = explode(',', $ids);
                $sort = explode(',', $sort);
                foreach ($ids as $k => $v){
                    Db::name('sertion')->where('id',$v)->update(array('sort'=>$sort[$k]));
                }
            }
            $value = array('status'=>1,'mess'=>'排序成功');
            return json($value);
        }
    } 
    
}
