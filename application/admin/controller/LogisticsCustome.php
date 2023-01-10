<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\LogisticsCustome as LogisticsMx;

class LogisticsCustome extends Common{

    public function lst(){
        $list = Db::name('logistics_custome')->order('sort asc')->select();
        $this->assign('list',$list);// 赋值数据集
        return $this->fetch();
    }

    public function checkLogname(){
        if(request()->isAjax()){
            $arr = Db::name('logistics_custome')->where('log_name',input('post.log_name'))->find();
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
        $count = Db::name('logistics_custome')->where('id',$id)->update($data);
        if($count > 0){
            if($value == 1){
                ys_admin_logs('开启物流公司','logistics_custome',$id);
            }elseif($value == 0){
                ys_admin_logs('关闭物流公司','logistics_custome',$id);
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
            // $result = $this->validate($data,'Logistics');
            if(false){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $logistics = new LogisticsMx();
                $logistics->data($data);
                $lastId = $logistics->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增物流公司','logistics_custome',$logistics->id);
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
            // $result = $this->validate($data,'Logistics');
            if(false){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $logistics = new LogisticsMx();
                $count = $logistics->allowField(true)->save($data,array('id'=>$data['id']));
                if($count !== false){
                    ys_admin_logs('编辑物流公司','logistics_custome',$data['id']);
                    $value = array('status'=>1,'mess'=>'编辑成功');
                }else{
                    $value = array('status'=>0,'mess'=>'编辑失败');
                }
            }
            return json($value);
        }else{
            $logs = Db::name('logistics_custome')->find(input('id'));
            $this->assign('logs', $logs);
            return $this->fetch();
        }
    }

    public function delete(){
        if(input('id') && !is_array(input('id'))){
            $id = input('id');
                            $count = LogisticsMx::destroy($id);
                if($count > 0){
                    ys_admin_logs('删除物流公司','logistics_custome',$id);
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
        $logistics = new LogisticsMx();
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                $logistics->save($data2,array('id'=>$data2['id']));
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
            ys_admin_logs('更新物流公司排序','logistics_custome',1);
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }

}
