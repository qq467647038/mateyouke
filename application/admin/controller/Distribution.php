<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Distribution extends Common{

    // 旅游分销
    public function rate_info(){
        if(request()->isPost()){
            $data = input('post.');

            // 验证数据是否正确
            $result = $this->validate($data,'Distribution.travel');
            if(true !== $result){
                $value = array('status'=>400,'mess'=>$result);
                return json($value);
            }

            $res = Db::name('travel_distribute_profit')->where('id', 1)->update($data);
            if($res){
                $value = array('status'=>200,'mess'=>'更新成功');
                return json($value);
            }

            $value = array('status'=>400,'mess'=>'更新失败');
            return json($value);
        }

        $info = Db::name('travel_distribute_profit')->where('id', 1)->find();
        $goods_list = Db::name('goods')
            ->field('id,goods_name')
            ->where(['onsale'=>1,'is_recycle'=>0])
            ->select();
        $this->assign('goods_list',$goods_list);
        $this->assign('distributions', $info);
        return $this->fetch();
    }
    
    public function info(){
        if(request()->isPost()){
            $data = input('post.');
            foreach ($data as $k=>&$v){
                settype($v,'int');
            }

            $result = $this->validate($data,'Distribution');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $distributions = Db::name('distribution')->where('id',1)->find();
                if($distributions){
                    $count = Db::name('distribution')->update(array(
                        'is_open' => $data['is_open'],
                        'one_profit'=>$data['one_profit'],
                        'two_profit'=>$data['two_profit'],
                        'commission_rate' => $data['commission_rate'],
                        'id'=>$distributions['id'],
                        'goods_id'  => $data['goods_id'],
                        'one_vip'   => $data['one_vip'],
                        'two_vip'   => $data['two_vip'],
                        'ten_one_vip'   => $data['ten_one_vip'],
                        'ten_two_vip'   => $data['ten_two_vip'],
                    ));
                    if($count !== false){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('编辑分销信息配置','distribution',$distributions['id']);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }else{
                    $lastId = Db::name('distribution')->insertGetId(array(
                        'is_open' => $data['is_open'],
                        'one_profit'=>$data['one_profit'],
                        'two_profit'=>$data['two_profit'],
                        'commission_rate' => $data['commission_rate'],
                        'goods_id'  => $data['goods_id'],
                        'one_vip'   => $data['one_vip'],
                        'two_vip'   => $data['two_vip'],
                        'ten_one_vip'   => $data['ten_one_vip'],
                        'ten_two_vip'   => $data['ten_two_vip'],
                    ));
                    if($lastId){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('新增分销信息配置','distribution',$lastId);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }
            }
            return json($value);
        }else{
            $distributions = Db::name('distribution')->where('id',1)->find();
            $goods_list = Db::name('goods')
                ->field('id,goods_name')
                ->where(['onsale'=>1,'is_recycle'=>0])
                ->select();
            $this->assign('goods_list',$goods_list);
            $this->assign('distributions',$distributions);
            return $this->fetch();
        }
    }
}