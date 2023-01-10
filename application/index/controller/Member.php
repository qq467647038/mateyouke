<?php
namespace app\index\controller;

use app\index\controller\Common;
use think\Db;

class Member extends Common {

    public function index() {
        $user_id = $this->user_id;
        $filter = input('post.filter');
        if(!$filter || !in_array($filter, array(1,3,4))){
            $filter = 1;
        }
        switch($filter){
            //待付款
            case 1:
                $where = array('a.user_id'=>$user_id,'a.state'=>0,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;
            //待收货
            case 3:
                $where = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>0,'a.is_show'=>1);
                $sort = array('a.fh_time'=>'desc','a.id'=>'desc');
                break;
            //待评价
            case 4:
                $where = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1,'a.ping'=>0,'a.is_show'=>1);
                $sort = array('a.coll_time'=>'desc','a.id'=>'desc');
                break;
        }
        $orderes = Db::name('order')->alias('a')->field('a.id,a.zong_id,a.ordernumber,a.total_price,a.state,a.fh_status,a.order_status,a.is_show,a.ping,a.shop_id,b.shop_name,a.addtime')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where)->where('a.time_out', 'gt', time())->order($sort)->limit(3)->select();
        if ($orderes) {
            $webconfig = $this->webconfig;
            foreach ($orderes as $k => $v){
                if($v['state'] == 0 && $v['fh_status'] == 0 && $v['order_status'] == 0 && $v['is_show'] == 1){
                    $orderes[$k]['order_zt'] = "待付款";
                    $orderes[$k]['filter'] = 1;
                }elseif($v['state'] == 1 && $v['fh_status'] == 0 && $v['order_status'] == 0 && $v['is_show'] == 1){
                    $orderes[$k]['order_zt'] = "待发货";
                    $orderes[$k]['filter'] = 2;
                }elseif($v['state'] == 1 && $v['fh_status'] == 1 && $v['order_status'] == 0 && $v['is_show'] == 1){
                    $orderes[$k]['order_zt'] = "待收货";
                    $orderes[$k]['filter'] = 3;
                }elseif($v['state'] == 1 && $v['fh_status'] == 1 && $v['order_status'] == 1 && $v['is_show'] == 1){
                    $orderes[$k]['order_zt'] = "已完成";
                    $orderes[$k]['filter'] = 4;
                }elseif($v['order_status'] == 2 && $v['is_show'] == 1){
                    $orderes[$k]['order_zt'] = "已取消";
                    $orderes[$k]['filter'] = 5;
                }
                $orderes[$k]['goodsinfo'] = Db::name('order_goods')->where('order_id',$v['id'])->field('id,goods_id,goods_name,thumb_url,goods_attr_str,price,goods_num,th_status,order_id')->select();
                foreach ($orderes[$k]['goodsinfo'] as $key => $val){
                    $orderes[$k]['goodsinfo'][$key]['thumb_url'] = $webconfig['weburl'].'/'.$val['thumb_url'];
                }
                $orderes[$k]['spnum'] = Db::name('order_goods')->where('order_id',$v['id'])->sum('goods_num');

                $orderes[$k]['zong_number'] = Db::name('order_zong')->where('id', $v['zong_id'])->field('order_number')->find()['order_number'];
            }
        }
        
        $dfk_ordernum = $this->getOrderNumByState(1, $user_id);
        $dsh_ordernum = $this->getOrderNumByState(3, $user_id);
        $dpj_ordernum = $this->getOrderNumByState(4, $user_id);
        $this->assign('orderes', $orderes);
        $this->assign('dfk_ordernum', $dfk_ordernum);
        $this->assign('dsh_ordernum', $dsh_ordernum);
        $this->assign('dpj_ordernum', $dpj_ordernum);
        return $this->fetch();
    }
    
    //获得每一个状态总共订单数
    public function getOrderNumByState($state, $user_id) {
        switch($state){
            //待付款
            case 1:
                $where = array('a.user_id'=>$user_id,'a.state'=>0,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;
            //待收货
            case 3:
                $where = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>0,'a.is_show'=>1);
                $sort = array('a.fh_time'=>'desc','a.id'=>'desc');
                break;
            //待评价
            case 4:
                $where = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1,'a.ping'=>0,'a.is_show'=>1);
                $sort = array('a.coll_time'=>'desc','a.id'=>'desc');
                break;
        }
        return Db::name('order')->alias('a')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where)->where('a.time_out', 'gt', time())->order($sort)->count();
    }


}
