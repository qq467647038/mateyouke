<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class AgentProfit extends Common{

    // 旅游分销
    public function settings(){
        if(request()->isPost()){
            $data = input('post.');
            $data['update_time'] = time();

            // 验证数据是否正确
            $result = $this->validate($data,'AgentProfit');
            if(true !== $result){
                $value = array('status'=>400,'mess'=>$result);
                return json($value);
            }

            $res = Db::name('travel_agent_withdrawal')->where('id', 1)->update($data);
            if($res){
                $value = array('status'=>200,'mess'=>'更新成功');
                return json($value);
            }

            $value = array('status'=>400,'mess'=>'更新失败');
            return json($value);
        }

        $info = Db::name('travel_agent_withdrawal')->where('id', 1)->find();
        $this->assign('distributions', $info);
        return $this->fetch();
    }
}