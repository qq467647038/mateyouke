<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\SaleTime as SaleTimeMx;

class SaleTime extends Common{
    
    public function lst(){
        $list = Db::name('sale_time')->order('time asc')->select();
        $this->assign('list',$list);// 赋值数据集
        return $this->fetch();
    }

    public function checkTime(){
        if(request()->isPost()){
            $arr = Db::name('sale_time')->where('time',input('post.time'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
    
    public function add(){
        if(request()->isPost()){
            $data = input('post.');
            $result = $this->validate($data,'SaleTime');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $sale = new SaleTimeMx();
                $sale->data($data);
                $lastId = $sale->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增秒杀活动时间段','flash_sale',$sale->id);
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
        if(request()->isPost()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'SaleTime');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $infos = Db::name('sale_time')->where('id',$data['id'])->field('id')->find();
                    if($infos){
                        $sale = new SaleTimeMx();
                        $count = $sale->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            ys_admin_logs('编辑秒杀活动时间段','sale_time',$data['id']);
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
                $id = input('id');
                $sales = Db::name('sale_time')->find($id);
                if($sales){
                    $this->assign('sales', $sales);
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
            $count = SaleTimeMx::destroy($id);
            if($count > 0){
                ys_admin_logs('删除秒杀活动时间段','sale_time',$id);
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'编辑失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return $value;
    }
    
}
