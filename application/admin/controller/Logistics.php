<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Logistics as LogisticsMx;

class Logistics extends Common{

    public function lst(){
        $list = Db::name('logistics')->order('sort asc')->select();
        $this->assign('list',$list);// 赋值数据集
        return $this->fetch();
    }

    public function checkLogname(){
        if(request()->isAjax()){
            $arr = Db::name('logistics')->where('log_name',input('post.log_name'))->find();
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
        $count = Db::name('logistics')->where('id',$id)->update($data);
        if($count > 0){
            if($value == 1){
                ys_admin_logs('开启物流公司','logistics',$id);
            }elseif($value == 0){
                ys_admin_logs('关闭物流公司','logistics',$id);
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
            $result = $this->validate($data,'Logistics');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $logistics = new LogisticsMx();
                $logistics->data($data);
                $lastId = $logistics->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增物流公司','logistics',$logistics->id);
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
            $result = $this->validate($data,'Logistics');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $logistics = new LogisticsMx();
                $count = $logistics->allowField(true)->save($data,array('id'=>$data['id']));
                if($count !== false){
                    ys_admin_logs('编辑物流公司','logistics',$data['id']);
                    $value = array('status'=>1,'mess'=>'编辑成功');
                }else{
                    $value = array('status'=>0,'mess'=>'编辑失败');
                }
            }
            return json($value);
        }else{
            $logs = Db::name('logistics')->find(input('id'));
            $this->assign('logs', $logs);
            return $this->fetch();
        }
    }

    public function delete(){
        if(input('id') && !is_array(input('id'))){
            $id = input('id');
            $orders = Db::name('order_wuliu')->where('ps_id',$id)->find();
            if(!empty($orders)){
                $value = array('status'=>0,'mess'=>'有订单使用该配送，删除失败');
            }else{
                $count = LogisticsMx::destroy($id);
                if($count > 0){
                    ys_admin_logs('删除物流公司','logistics',$id);
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
        $logistics = new LogisticsMx();
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                $logistics->save($data2,array('id'=>$data2['id']));
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
            ys_admin_logs('更新物流公司排序','logistics',1);
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }

}
