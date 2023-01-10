<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Position as PositionMx;

class Position extends Common{
    
    public function lst(){
        $list = Db::name('position')->order('sort asc')->select();
        $this->assign('list',$list);// 赋值数据集
        return $this->fetch();
    }
    
    public function checkPositionname(){
        if(request()->isAjax()){
            $arr = Db::name('position')->where('position_name',input('post.position_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
    
    public function checkSort(){
        if(request()->isAjax()){
            $arr = Db::name('position')->where('sort',input('post.sort'))->find();
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
            $result = $this->validate($data,'Position');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $position = new PositionMx();
                $position->data($data);
                $lastId = $position->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增职位','position',$position->id);
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
                $result = $this->validate($data,'Position');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $zhiweis = Db::name('position')->where('id',$data['id'])->find();
                    if($zhiweis){
                        if($data['quyu_level'] != $zhiweis['quyu_level']){
                            $sales = Db::name('member')->where('leixing',1)->where('wz_id',$data['id'])->find();
                            if($sales){
                                $value = array('status'=>0,'mess'=>'该职位存在工作人员，不能修改区域等级');
                                return json($value);
                            }
                        }
                
                        $position = new PositionMx();
                        $count = $position->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            ys_admin_logs('编辑职位','position',$data['id']);
                            $value = array('status'=>1,'mess'=>'编辑成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'编辑失败');
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
                $positions = Db::name('position')->find(input('id'));
                if($positions){
                    $this->assign('positions', $positions);
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
            $members = Db::name('member')->where('wz_id',$id)->where('leixing',1)->find();
            if(!empty($members)){
                $value = array('status'=>0,'mess'=>'该职位存在工作人员，删除失败');
            }else{
                $count = PositionMx::destroy($id);
                if($count > 0){
                    ys_admin_logs('删除职位','position',$id);
                    $value = array('status'=>1,'mess'=>'删除成功');
                }else{
                    $value = array('status'=>0,'mess'=>'编辑失败');
                }
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }
    
    //排序
    public function order(){
        $position = new PositionMx();
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                $position->save($data2,array('id'=>$data2['id']));
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
            ys_admin_logs('更新职位排序','position',1);
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }    
    
}
