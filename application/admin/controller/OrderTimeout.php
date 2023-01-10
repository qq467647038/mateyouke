<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class OrderTimeout extends Common{
    
    public function info(){
        if(request()->isPost()){
            $data = input('post.');
            //$result = $this->validate($data,'OrderTimeout');
            $result = true;
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $order_timeouts = Db::name('order_timeout')->where('id',1)->find();
                if($order_timeouts){
                    $count = Db::name('order_timeout')->update(array(
                        'normal_out_order' => $data['normal_out_order'],
                        // 'rushactivity_out_order'=>$data['rushactivity_out_order'],
                        // 'group_out_order'=>$data['group_out_order'],
                        // 'assemorder_timeout' => $data['assemorder_timeout'],
                        // 'assem_timeout'=>$data['assem_timeout'],
                        'zdqr_sh_time'=>$data['zdqr_sh_time'],
                        'check_timeout' => $data['check_timeout'],
                        'shoptui_timeout'=>$data['shoptui_timeout'],
                        'yhfh_timeout'=>$data['yhfh_timeout'],
                        'yhshou_timeout'=>$data['yhshou_timeout'],
                        'id'=>$order_timeouts['id']
                    ));
                    if($count !== false){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('编辑订单超时配置','order_timeout',$order_timeouts['id']);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }else{
                    $lastId = Db::name('order_timeout')->insertGetId(array(
                        'normal_out_order' => $data['normal_out_order'],
                        // 'rushactivity_out_order'=>$data['rushactivity_out_order'],
                        // 'group_out_order'=>$data['group_out_order'],
                        // 'assemorder_timeout' => $data['assemorder_timeout'],
                        // 'assem_timeout'=>$data['assem_timeout'],
                        'zdqr_sh_time'=>$data['zdqr_sh_time'],
                        'check_timeout' => $data['check_timeout'],
                        'shoptui_timeout'=>$data['shoptui_timeout'],
                        'yhfh_timeout'=>$data['yhfh_timeout'],
                        'yhshou_timeout'=>$data['yhshou_timeout'],
                    ));
                    if($lastId){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('新增订单超时配置','order_timeout',$lastId);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }
            }
            return json($value);
        }else{
            $order_timeouts = Db::name('order_timeout')->where('id',1)->find();
            $this->assign('order_timeouts',$order_timeouts);
            return $this->fetch();
        }
    }
}
