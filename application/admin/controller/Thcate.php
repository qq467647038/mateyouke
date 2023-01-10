<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Thcate as ThcateMx;

class Thcate extends Common{
    
    public function lst(){
        $list = Db::name('thcate')->order('sort asc')->select();
        $this->assign('list',$list);// 赋值数据集
        return $this->fetch();
    }
    
    public function checkCatename(){
        if(request()->isAjax()){
            $arr = Db::name('thcate')->where('cate_name',input('post.cate_name'))->find();
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
        $count = Db::name('thcate')->where('id',$id)->update($data);
        if($count > 0){
            if($value == 1){
                ys_admin_logs('显示退换方式','thcate',$id);
            }elseif($value == 0){
                ys_admin_logs('隐藏退换方式','thcate',$id);
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
            $result = $this->validate($data,'Thcate');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $thcate = new ThcateMx();
                $thcate->data($data);
                $lastId = $thcate->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增退换种类','thcate',$thcate->id);
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
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'Thcate');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $thcateinfos = Db::name('thcate')->where('id',$data['id'])->find();
                    if($thcateinfos){
                        $thcate = new ThcateMx();
                        $count = $thcate->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            ys_admin_logs('编辑退换种类','thcate',$data['id']);
                            $value = array('status'=>1,'mess'=>'编辑成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'编辑失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $thcates = Db::name('thcate')->find(input('id'));
                if($thcates){
                    $this->assign('thcates', $thcates);
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
        if(input('id') && !is_array(input('id'))){
            $id = input('id');
            $count = ThcateMx::destroy($id);
            if($count > 0){
                ys_admin_logs('删除退换种类','thcate',$id);
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'编辑失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }
    
    //排序
    public function order(){
        $thcate = new ThcateMx();
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                $thcate->save($data2,array('id'=>$data2['id']));
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
            ys_admin_logs('更新退换种类排序','thcate',1);
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }    
    
}
