<?php

namespace app\index\controller;

use app\index\controller\Common;
use app\index\model\Gongyong as GongyongMx;
use think\Db;

class MemberOrder extends Common{
    
    //订单列表信息接口
    public function index(){
        $user_id = $this->user_id;
        $page = !empty(input('page')) ? input('page') : 1;
        $keyword = input('keyword');
        if($page && preg_match("/^\\+?[1-9][0-9]*$/", $page)){
            $webconfig = $this->webconfig;
            $perpage = 10;
            $offset = ($page - 1) * $perpage;
            $filter = input('filter');
            if(!$filter || !in_array($filter, array(1,2,3,4,5,6))){
                $filter = 6;
            }
            
            switch($filter){
                //待付款
                case 1:
                    $where = array('a.user_id'=>$user_id,'a.state'=>0,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
                    $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                    break;
                //待发货
                case 2:
                    $where = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
                    $sort = array('a.pay_time'=>'desc','a.id'=>'desc');
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
                //已取消
                case 5:
                    $where = array('a.user_id'=>$user_id,'a.state'=>0,'a.fh_status'=>0,'a.order_status'=>2,'a.ping'=>0,'a.is_show'=>1);
                    $sort = array('a.coll_time'=>'desc','a.id'=>'desc');
                    break;
                //全部
                case 6:
                    $where = array('a.user_id'=>$user_id,'a.is_show'=>1);
                    $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                    break;
            }
            if ($keyword) {
                $where['a.ordernumber'] = $keyword;
            }
            if(in_array($filter,array(1,2,3,4,5,6))){
                $orderes = Db::name('order')->alias('a')->field('a.id,a.zong_id,a.ordernumber,a.total_price,a.state,a.fh_status,a.order_status,a.is_show,a.ping,a.shop_id,b.shop_name,a.addtime')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where)->where('a.time_out', 'gt', time())->order($sort)->limit($offset,$perpage)->select();
//                $total = Db::name('order')->alias('a')->where($where)->count();
                if($orderes){
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
            }else{
                $orderes = Db::name('th_apply')->alias('a')->field('a.id,a.th_number,a.thfw_id,a.apply_status,a.tui_num,a.orgoods_id,a.order_id,a.shop_id,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.user_id',$user_id)->order('a.apply_time desc')->limit($offset,$perpage)->select();
                if($orderes){
                    foreach ($orderes as $k => $v){
                        switch($v['thfw_id']){
                            case 1:
                                if($v['apply_status'] == 0){
                                    $orderes[$k]['order_zt'] = '待平台处理';
                                }elseif($v['apply_status'] == 1){
                                    $orderes[$k]['order_zt'] = '待平台退款';
                                }elseif($v['apply_status'] == 2){
                                    $orderes[$k]['order_zt'] = '平台拒绝申请';
                                }elseif($v['apply_status'] == 3){
                                    $orderes[$k]['order_zt'] = '退款已完成';
                                }elseif($v['apply_status'] == 4){
                                    $orderes[$k]['order_zt'] = '已撤销';
                                }
                                break;
                            case 2:
                                if($v['apply_status'] == 0){
                                    $orderes[$k]['order_zt'] = '待平台处理';
                                }elseif($v['apply_status'] == 1){
                                    if($v['dcfh_status'] == 0){
                                        $orderes[$k]['order_zt'] = '待用户发货';
                                    }elseif($v['dcfh_status'] == 1 && $v['sh_status'] == 0){
                                        $orderes[$k]['order_zt'] = '待平台收货';
                                    }elseif($v['dcfh_status'] == 1 && $v['sh_status'] == 1){
                                        $orderes[$k]['order_zt'] = '待平台退款';
                                    }
                                }elseif($v['apply_status'] == 2){
                                    $orderes[$k]['order_zt'] = '平台拒绝申请';
                                }elseif($v['apply_status'] == 3){
                                    $orderes[$k]['order_zt'] = '退款已完成';
                                }elseif($v['apply_status'] == 4){
                                    $orderes[$k]['order_zt'] = '已撤销';
                                }
                                break;
                            case 3:
                                if($v['apply_status'] == 0){
                                    $orderes[$k]['order_zt'] = '待平台处理';
                                }elseif($v['apply_status'] == 1){
                                    if($v['dcfh_status'] == 0){
                                        $orderes[$k]['order_zt'] = '待用户发货';
                                    }elseif($v['dcfh_status'] == 1 && $v['sh_status'] == 0){
                                        $orderes[$k]['order_zt'] = '待平台收货';
                                    }elseif($v['sh_status'] == 1 && $v['fh_status'] == 0){
                                        $orderes[$k]['order_zt'] = '待平台发货';
                                    }elseif($v['fh_status'] == 1 && $v['shou_status'] == 0){
                                        $orderes[$k]['order_zt'] = '待用户收货';
                                    }
                                }elseif($v['apply_status'] == 2){
                                    $orderes[$k]['order_zt'] = '平台拒绝申请';
                                }elseif($v['apply_status'] == 3){
                                    $orderes[$k]['order_zt'] = '换货已完成';
                                }elseif($v['apply_status'] == 4){
                                    $orderes[$k]['order_zt'] = '已撤销';
                                }
                                break;
                        }
                        $orderes[$k]['orgoods'] = Db::name('order_goods')->where('id',$v['orgoods_id'])->where('order_id',$v['order_id'])->field('id,goods_id,goods_name,thumb_url,goods_attr_str,goods_num,th_status,order_id')->find();
                        $orderes[$k]['orgoods']['thumb_url'] = $webconfig['weburl'].'/'.$orderes[$k]['orgoods']['thumb_url'];
                    }
                }
            }
            $cartnum = Db::name('cart')->alias('a')->field('a.id,a.goods_id,a.goods_attr,a.num,a.shop_id,b.goods_name,b.shop_price,b.thumb_url,c.shop_name')->join('sp_goods b', 'a.goods_id = b.id', 'INNER')->join('sp_shops c', 'a.shop_id = c.id', 'INNER')->where('a.user_id', $user_id)->where('b.onsale', 1)->where('c.open_status', 1)->count();
            $member = new Member;
            $dfk_ordernum = $member->getOrderNumByState(1, $user_id);
            $dsh_ordernum = $member->getOrderNumByState(3, $user_id);
            $dpj_ordernum = $member->getOrderNumByState(4, $user_id);
            $this->assign('orderes', $orderes);
            $this->assign('filter', $filter);
            $this->assign('keyword', $keyword);
            $this->assign('cartnum', $cartnum);
            $this->assign('dfk_ordernum', $dfk_ordernum);
            $this->assign('dsh_ordernum', $dsh_ordernum);
            $this->assign('dpj_ordernum', $dpj_ordernum);
            return $this->fetch();
        }else{
            $this->error('缺少页数参数', $this->gourl);
        }
    }
    
    //取消订单
    public function quxiao() {
        if (request()->isPost()) {
            $user_id = $this->user_id;
            if (input('post.order_num')) {
                $order_num = input('post.order_num');
                $orders = Db::name('order')->where('ordernumber', $order_num)->where('user_id', $user_id)->where('state', 0)->where('fh_status', 0)->where('order_status', 0)->where('is_show', 1)->where('time_out', 'gt', time())->lock(true)->find();
                if ($orders) {
                    $orgoodres = Db::name('order_goods')->where('order_id', $orders['id'])->field('goods_id,goods_attr_id,goods_num,hd_type,hd_id')->select();
                    if ($orgoodres) {
                        // 启动事务
                        Db::startTrans();
                        try {
                            Db::name('order')->update(array('order_status' => 2, 'can_time' => time(), 'id' => $orders['id']));

                            if ($orders['coupon_id']) {
                                Db::name('member_coupon')->where('user_id', $user_id)->where('coupon_id', $orders['coupon_id'])->where('is_sy', 1)->where('shop_id', $orders['shop_id'])->update(array('is_sy' => 0));
                            }

                            foreach ($orgoodres as $v) {
                                if (in_array($v['hd_type'], array(0, 2, 3))) {
                                    $prokc = Db::name('product')->where('goods_id', $v['goods_id'])->where('goods_attr', $v['goods_attr_id'])->find();
                                    if ($prokc) {
                                        Db::name('product')->where('goods_id', $v['goods_id'])->where('goods_attr', $v['goods_attr_id'])->setInc('goods_number', $v['goods_num']);
                                    }
                                } elseif ($v['hd_type'] == 1) {
                                    Db::name('rush_activity')->where('id', $v['hd_id'])->setInc('kucun', $v['goods_num']);
                                    Db::name('rush_activity')->where('id', $v['hd_id'])->setDec('sold', $v['goods_num']);
                                }
                            }

                            // 提交事务
                            Db::commit();
                            $value = array('status' => 200, 'mess' => '取消订单成功', 'data' => array('status' => 200));
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                            $value = array('status' => 400, 'mess' => '取消订单失败', 'data' => array('status' => 400));
                        }
                    } else {
                        $value = array('status' => 400, 'mess' => '找不到相关类型订单商品', 'data' => array('status' => 400));
                    }
                } else {
                    $value = array('status' => 400, 'mess' => '找不到相关类型订单', 'data' => array('status' => 400));
                }
            } else {
                $value = array('status' => 400, 'mess' => '缺少订单号', 'data' => array('status' => 400));
            }
        } else {
            $value = array('status' => 400, 'mess' => '请求方式不正确', 'data' => array('status' => 400));
        }
        return json($value);
    }
    
    //删除订单
    public function delorder(){
        $user_id = $this->user_id;
        if(input('post.order_num')){
            $order_num = input('post.order_num');
            $orders = Db::name('order')->where('ordernumber',$order_num)->where('user_id',$user_id)->where('state',0)->where('fh_status',0)->where('order_status',2)->where('is_show',1)->find();
            if($orders){
                $count = Db::name('order')->update(array('is_show'=>0,'del_time'=>time(),'id'=>$orders['id']));

                if($count > 0){
                    $value = array('status'=>200,'mess'=>'删除订单成功','data'=>array('status'=>200));
                }else{
                    $value = array('status'=>400,'mess'=>'删除订单失败','data'=>array('status'=>400));
                }
            }else{
                $value = array('status'=>400,'mess'=>'找不到相关类型订单','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'缺少订单号','data'=>array('status'=>400));
        }
        return json($value);        
    }
    
    //订单详情
    public function orderinfo(){
        $user_id = $this->user_id;
        $order_num = input('order_num');
        $go_url = "member_order/index";
        if($order_num){
            $orders = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.contacts,a.telephone,a.province,a.city,a.area,a.address,a.goods_price,a.freight,a.youhui_price,a.total_price,a.beizhu,a.state,a.pay_time,a.fh_status,a.fh_time,a.order_status,a.is_show,a.coll_time,a.can_time,a.ping,a.zong_id,a.shop_id,a.addtime,a.zdsh_time,a.time_out,b.order_number,c.shop_name')->join('sp_order_zong b','a.zong_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->where('a.ordernumber',$order_num)->where('a.user_id',$user_id)->where('a.is_show',1)->find();
            if($orders){
                if($orders['pay_time']){
                    $orders['pay_time'] = date('Y-m-d H:i:s',$orders['pay_time']);
                }

                if($orders['fh_time']){
                    $orders['fh_time'] = date('Y-m-d H:i:s',$orders['fh_time']);
                }

                if($orders['coll_time']){
                    $orders['coll_time'] = date('Y-m-d H:i:s',$orders['coll_time']);
                }

                if($orders['can_time']){
                    $orders['can_time'] = date('Y-m-d H:i:s',$orders['can_time']);
                }

                if($orders['addtime']){
                    $orders['addtime'] = date('Y-m-d H:i:s',$orders['addtime']);
                }

                if($orders['state'] == 0 && $orders['fh_status'] == 0 && $orders['order_status'] == 0 && $orders['is_show'] == 1){
                    $orders['order_zt'] = "待付款";
                    $orders['filter'] = 1;
                    if($orders['time_out'] > time()){
                        $orders['sytime'] = time2string($orders['time_out']-time());
                    }else{
                        $orders['sytime'] = '';
                    }
                }elseif($orders['state'] == 1 && $orders['fh_status'] == 0 && $orders['order_status'] == 0 && $orders['is_show'] == 1){
                    $orders['order_zt'] = "待发货";
                    $orders['filter'] = 2;
                }elseif($orders['state'] == 1 && $orders['fh_status'] == 1 && $orders['order_status'] == 0 && $orders['is_show'] == 1){
                    $orders['order_zt'] = "待收货";
                    $orders['filter'] = 3;
                    $orders['sysh_time'] = time2string($orders['zdsh_time']-time());
                }elseif($orders['state'] == 1 && $orders['fh_status'] == 1 && $orders['order_status'] == 1 && $orders['is_show'] == 1){
                    $orders['order_zt'] = "已完成";
                    $orders['filter'] = 4;
                }elseif($orders['order_status'] == 2 && $orders['is_show'] == 1){
                    $orders['order_zt'] = "已取消";
                    $orders['filter'] = 5;
                }

                $orders['goodsinfo'] = Db::name('order_goods')->where('order_id',$orders['id'])->field('id,goods_id,goods_name,thumb_url,goods_attr_str,real_price,goods_num,th_status,order_id')->select();

                $webconfig = $this->webconfig;

                foreach ($orders['goodsinfo'] as $key => $val){
                    $orders['goodsinfo'][$key]['thumb_url'] = $webconfig['weburl'].'/'.$val['thumb_url'];
                }

                if($orders['fh_status'] == 1){
                    $order_wulius = Db::name('order_wuliu')->alias('a')->field('a.id,a.psnum,b.log_name,b.telephone')->join('sp_logistics b','a.ps_id = b.id','LEFT')->where('a.order_id',$orders['id'])->find();
                    $orders['wulius'] = $order_wulius;
                }
                $this->assign('orders', $orders);
                return $this->fetch();
            }else{
                $this->error('找不到相关订单', $go_url);
            }
        }else{
            $this->error('缺少订单号', $go_url);
        }
    }
    
    //获取退换货订单详情接口
    public function thorderinfo(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.th_number')){
                        $th_number = input('post.th_number');
                        
                        $applys = Db::name('th_apply')->where('th_number',$th_number)->where('user_id',$user_id)->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')->find();
                        if($applys){
                            $orders = Db::name('order')->where('id',$applys['order_id'])->where('state',1)->where('user_id',$user_id)->field('id,ordernumber,fh_status')->find();
                            if($orders){
                                $orgoods = Db::name('order_goods')->where('id',$applys['orgoods_id'])->where('order_id',$orders['id'])->where('th_status','neq',0)->field('id,goods_id,goods_name,thumb_url,goods_attr_str,price,goods_num,order_id')->find();
                                if($orgoods){
                                    $orgoods['ordernumber'] = $orders['ordernumber'];
                                    $orgoods['fh_status'] = $orders['fh_status'];
                            
                                    $webconfig = $this->webconfig;
                                    $orgoods['thumb_url'] = $webconfig['weburl'].'/'.$orgoods['thumb_url'];
                            
                                    $applys['apply_time'] = date('Y-m-d H:i:s',$applys['apply_time']);
                                    if(!empty($applys['agree_time'])){
                                        $applys['agree_time'] = date('Y-m-d H:i:s',$applys['agree_time']);
                                    }
                                    
                                    if(!empty($applys['refuse_time'])){
                                        $applys['refuse_time'] = date('Y-m-d H:i:s',$applys['refuse_time']);
                                    }
                                    
                                    if(!empty($applys['dcfh_time'])){
                                        $applys['dcfh_time'] = date('Y-m-d H:i:s',$applys['dcfh_time']);
                                    }
                                    
                                    if(!empty($applys['sh_time'])){
                                        $applys['sh_time'] = date('Y-m-d H:i:s',$applys['sh_time']);
                                    }
                                    
                                    if(!empty($applys['fh_time'])){
                                        $applys['fh_time'] = date('Y-m-d H:i:s',$applys['fh_time']);
                                    }
                                    
                                    if(!empty($applys['shou_time'])){
                                        $applys['shou_time'] = date('Y-m-d H:i:s',$applys['shou_time']);
                                    }
                                    
                                    if(!empty($applys['che_time'])){
                                        $applys['che_time'] = date('Y-m-d H:i:s',$applys['che_time']);
                                    }
                                    
                                    if(!empty($applys['com_time'])){
                                        $applys['com_time'] = date('Y-m-d H:i:s',$applys['com_time']);
                                    }
                                    
                                    $applys['thfw'] = Db::name('thcate')->where('id',$applys['thfw_id'])->value('cate_name');
                                    
                                    if($applys['apply_status'] == 0){
                                        $applys['zhuangtai'] = '待商家同意';
                                        $applys['filter'] = 1;
                                        $applys['sycheck_timeout'] = time2string($applys['check_timeout']-time());
                                    }elseif(in_array($applys['apply_status'], array(1,3))){
                                        switch ($applys['thfw_id']){
                                            case 1:
                                                if($applys['apply_status'] == 1){
                                                    $applys['zhuangtai'] = '商家已同意（退款中）';
                                                    $applys['filter'] = 2;
                                                    $applys['syshoptui_timeout'] = time2string($applys['shoptui_timeout']-time());
                                                }elseif($applys['apply_status'] == 3){
                                                    $applys['zhuangtai'] = '退款已完成';
                                                    $applys['filter'] = 3;
                                                }
                                                break;
                                            case 2:
                                                if($applys['apply_status'] == 1){
                                                    if($applys['dcfh_status'] == 0){
                                                        $applys['zhuangtai'] = '商家已同意（填写退货物流信息）';
                                                        $applys['filter'] = 4;
                                                        $applys['syyhfh_timeout'] = time2string($applys['yhfh_timeout']-time());
                                                    }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 0){
                                                        $applys['zhuangtai'] = '等待商家确认收货（退货退款中）';
                                                        $applys['filter'] = 5;
                                                    }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 1){
                                                        $applys['zhuangtai'] = '商家已收货（退货退款中）';
                                                        $applys['filter'] = 6;
                                                        $applys['syshoptui_timeout'] = time2string($applys['shoptui_timeout']-time());
                                                    }
                                                }elseif($applys['apply_status'] == 3){
                                                    $applys['zhuangtai'] = '退货退款已完成';
                                                    $applys['filter'] = 7;
                                                }
                                                break;
                                            case 3:
                                                if($applys['apply_status'] == 1){
                                                    if($applys['dcfh_status'] == 0){
                                                        $applys['zhuangtai'] = '商家已同意（填写退货物流信息）';
                                                        $applys['filter'] = 8;
                                                        $applys['syyhfh_timeout'] = time2string($applys['yhfh_timeout']-time());
                                                    }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 0){
                                                        $applys['zhuangtai'] = '等待商家确认收货（换货中）';
                                                        $applys['filter'] = 9;
                                                    }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['fh_status'] == 0){
                                                        $applys['zhuangtai'] = '商家已收货（换货中）';
                                                        $applys['filter'] = 10;
                                                    }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['fh_status'] == 1){
                                                        $applys['zhuangtai'] = '商家已发货（换货中）';
                                                        $applys['filter'] = 11;
                                                        $applys['syyhshou_timeout'] = time2string($applys['yhshou_timeout']-time());
                                                    }
                                                }elseif($applys['apply_status'] == 3){
                                                    $applys['zhuangtai'] = '换货已完成';
                                                    $applys['filter'] = 12;
                                                }
                                                break;
                                        }
                                    }elseif($applys['apply_status'] == 2){
                                        $applys['zhuangtai'] = '商家已拒绝';
                                        $applys['filter'] = 13;
                                    }elseif($applys['apply_status'] == 4){
                                        $applys['zhuangtai'] = '已撤销';
                                        $applys['filter'] = 14;
                                    }
                                    
                                    $thpicres = Db::name('thapply_pic')->where('th_id',$applys['id'])->select();
                                    
                                    if(in_array($applys['thfw_id'],array(2,3)) && $applys['apply_status'] == 1){
                                        $shopdzs = Db::name('shop_shdz')->where('shop_id',$applys['shop_id'])->find();
                                    }else{
                                        $shopdzs = array();
                                    }
                                    
                                    $tuiwulius = array();
                                    if(in_array($applys['thfw_id'], array(2,3)) && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 1){
                                        $tuiwulius = Db::name('tui_wuliu')->where('th_id',$applys['id'])->find();
                                    }
                                    
                                    $wulius = array();
                                    if($applys['thfw_id'] == 3 && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['fh_status'] == 1){
                                        $wulius = Db::name('huan_wuliu')->alias('a')->field('a.*,b.log_name,b.telephone')->join('sp_logistics b','a.ps_id = b.id','LEFT')->where('a.th_id',$applys['id'])->find();
                                    }
                                    
                                    $thapplyinfo = array('orgoods'=>$orgoods,'applys'=>$applys,'thpicres'=>$thpicres,'shopdzs'=>$shopdzs,'tuiwulius'=>$tuiwulius,'wulius'=>$wulius);
                                    $value = array('status'=>200,'mess'=>'获取退换货申请信息成功','data'=>$thapplyinfo);                            
                                }else{
                                    $value = array('status'=>400,'mess'=>'订单商品信息错误','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'订单信息错误','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关退换货信息','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少退换订单编号','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }

    //支付获取订单信息
    public function zhifuorder(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.order_nums/a') && is_array(input('post.order_nums/a'))){
                        if(input('post.zf_type') && in_array(input('post.zf_type'), array(1,2,3))){
                            $zf_type = input('post.zf_type');
                            $order_nums = input('post.order_nums/a');
                            $order_nums = array_unique($order_nums);
                            
                            $total_price = 0;
                            $orderids = array();
                            $outarr = array();
                            
                            foreach ($order_nums as $v){
                                $orders = Db::name('order')->where('ordernumber',$v)->where('user_id',$user_id)->where('state',0)->where('fh_status',0)->where('order_status',0)->where('is_show',1)->field('id,total_price,time_out')->find();
                                if($orders){
                                    $total_price+=$orders['total_price'];
                                    $orderids[] = $orders['id'];
                                    $outarr[] = $orders['time_out'];
                                }else{
                                    $value = array('status'=>400,'mess'=>'订单信息错误，操作失败','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }
                            
                            $order_number = 'D'.date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                            $dingdan = Db::name('order_zong')->where('order_number',$order_number)->find();
                            if(!$dingdan){
                                $datainfo = array();
                                $datainfo['order_number'] = $order_number;
                                $datainfo['total_price'] = $total_price;
                                $datainfo['state'] = 0;
                                $datainfo['zf_type'] = 0;
                                $datainfo['user_id'] = $user_id;
                                $datainfo['addtime'] = time();
                                $datainfo['time_out'] = min($outarr);
                            
                                // 启动事务
                                Db::startTrans();
                                try{
                                    $zong_id = Db::name('order_zong')->insertGetId($datainfo);
                                    if($zong_id){
                                        foreach ($orderids as $v2){
                                            Db::name('order')->update(array('zong_id'=>$zong_id,'id'=>$v2));
                                        }
                                    }
                                    // 提交事务
                                    Db::commit();
                                    $orderinfos = array('order_number'=>$order_number,'zf_type'=>$zf_type);
                                    $value = array('status'=>200,'mess'=>'获取订单信息成功','data'=>$orderinfos);
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'获取订单信息失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'支付方式参数错误','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少订单号','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //支付接口
    /*public function zhifu(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.order_num')){
                        if(input('post.zf_type') && input('post.zf_type') == 1){
                            $zf_type = input('post.zf_type');
                            $order_num = input('post.order_num');
                            $orderinfos = Db::name('order')->where('ordernumber',$order_num)->where('user_id',$user_id)->where('state',0)->where('fh_status',0)->where('order_status',0)->find();
                            if($orderinfos){
                                $order_number = 'D'.date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                $dingdan = Db::name('order_zong')->where('order_number',$order_number)->find();
                                if(!$dingdan){
                                    $datainfo = array();
                                    $datainfo['order_number'] = $order_number;
                                    $datainfo['contacts'] = $orderinfos['contacts'];
                                    $datainfo['telephone'] = $orderinfos['telephone'];
                                    $datainfo['pro_id'] = $orderinfos['pro_id'];
                                    $datainfo['city_id'] = $orderinfos['city_id'];
                                    $datainfo['area_id'] = $orderinfos['area_id'];
                                    $datainfo['province'] = $orderinfos['province'];
                                    $datainfo['city'] = $orderinfos['city'];
                                    $datainfo['area'] = $orderinfos['area'];
                                    $datainfo['address'] = $orderinfos['address'];
                                    $datainfo['dz_id'] = $orderinfos['dz_id'];
                                    $datainfo['freight'] = $orderinfos['freight'];
                                    $datainfo['total_price'] = $orderinfos['total_price'];
                                    $datainfo['state'] = 0;
                                    $datainfo['zf_type'] = 0;
                                    $datainfo['user_id'] = $user_id;
                                    $datainfo['addtime'] = time();
                                    
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        $zong_id = Db::name('order_zong')->insertGetId($datainfo);
                                        if($zong_id){
                                            Db::name('order')->update(array('zong_id'=>$zong_id,'id'=>$orderinfos['id']));
                                        }
                                        // 提交事务
                                        Db::commit();
                                        $webconfig = $this->webconfig;
                                        
                                        $orderzong_infos = Db::name('order_zong')->where('id',$zong_id)->where('state',0)->where('user_id',$user_id)->field('order_number,total_price')->find();
                                        //获取订单号
                                        $reoderSn = $orderzong_infos['order_number'];
                                        //获取支付金额
                                        $money = $orderzong_infos['total_price'];
                                        
                                        $wx = new Wxpay();
                                         
                                        $body = '一一孝笑好-商品支付';//支付说明
                                        
                                        $out_trade_no = $reoderSn;//订单号
                                        
                                        $total_fee = $money * 100;//支付金额(乘以100)
                                        
                                        $notify_url = $webconfig['weburl'].'/apicloud/Wxpaynotify/notify';//回调地址
                                        
                                        $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $notify_url);//调用微信支付的方法
                                        if($order['prepay_id']){
                                            //判断返回参数中是否有prepay_id
                                            $order1 = $wx->getOrder($order['prepay_id']);//执行二次签名返回参数
                                            $value = array('status'=>200,'mess'=>'成功','data'=>array('ordernumber'=>$orderzong_infos['order_number'],'wxpayinfos'=>$order1));
                                        }else{
                                            $value = array('status'=>400,'mess'=>$order['err_code_des'],'data'=>array('status'=>400));
                                        }
                                    } catch (\Exception $e) {
                                        // 回滚事务
                                        Db::rollback();
                                        $value = array('status'=>400,'mess'=>'支付失败','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关订单','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'支付方式参数错误','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少订单号','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);        
    }*/
    
    //确认收货
    public function qrshouhuo(){
        if(request()->isPost()){
            $user_id = $this->user_id;
            $order_num = input('post.order_num');
            if($order_num){
                $orders = Db::name('order')->where('ordernumber',$order_num)->where('user_id',$user_id)->where('state',1)->where('fh_status',1)->where('order_status',0)->where('is_show',1)->where('zdsh_time','gt',time())->lock(true)->field('id,total_price,shop_id,shouhou')->find();
                if($orders){
                    if($orders['shouhou'] == 0){
                        // 启动事务
                        Db::startTrans();
                        try{
                            Db::name('order')->where('id',$orders['id'])->update(array('order_status'=>1,'coll_time'=>time()));

                            if($orders['shop_id'] != 1){
                                $shops = Db::name('shops')->where('id',$orders['shop_id'])->field('id,indus_id')->find();
                                if($shops){
                                    $tui_price = 0;

                                    $applys = Db::name('th_apply')->where('order_id',$orders['id'])->where('thfw_id','in','1,2')->where('apply_status',3)->field('id,tui_price')->find();
                                    if($applys){
                                        $tui_price = Db::name('th_apply')->where('order_id',$orders['id'])->where('thfw_id','in','1,2')->where('apply_status',3)->sum('tui_price');
                                    }

                                    $total_price = $orders['total_price']-$tui_price;
                                    $remind = Db::name('industry')->where('id',$shops['indus_id'])->value('remind');
                                    if($remind){
                                        $remind_lv = $remind/1000;
                                        $remind_price = sprintf("%.2f",$total_price*$remind_lv);
                                        $total_price = sprintf("%.2f",$total_price-$remind_price);
                                    }
                                    $shop_wallets = Db::name('shop_wallet')->where('shop_id',$shops['id'])->find();
                                    if($shop_wallets){
                                        Db::name('shop_detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$total_price,'order_type'=>1,'order_id'=>$orders['id'],'shop_id'=>$shops['id'],'wat_id'=>$shop_wallets['id'],'time'=>time()));
                                        Db::name('shop_wallet')->where('id',$shop_wallets['id'])->setInc('price',$total_price);
                                    }
                                }
                            }

                            $goodinfos = Db::name('order_goods')->where('order_id',$orders['id'])->field('id,goods_id,goods_attr_id,goods_num,th_status,shop_id')->select();
                            if($goodinfos){
                                foreach ($goodinfos as $val2){
                                    if(in_array($val2['th_status'], array(0,8))){
                                        $gdinfos = Db::name('goods')->where('id',$val2['goods_id'])->field('id,sale_num,deal_num')->find();
                                        if($gdinfos){
                                            $deal_num = $gdinfos['deal_num']+$val2['goods_num'];
                                            $deal_lv = sprintf("%.2f",$deal_num/$gdinfos['sale_num'])*100;
                                            Db::name('goods')->update(array('id'=>$val2['goods_id'],'deal_num'=>$deal_num,'deal_lv'=>$deal_lv));
                                        }

                                        $spinfos = Db::name('shops')->where('id',$val2['shop_id'])->field('id,sale_num,deal_num')->find();
                                        if($spinfos){
                                            $shop_deal_num = $spinfos['deal_num']+$val2['goods_num'];
                                            $shop_deal_lv = sprintf("%.2f",$shop_deal_num/$spinfos['sale_num'])*100;
                                            Db::name('shops')->update(array('id'=>$val2['shop_id'],'deal_num'=>$shop_deal_num,'deal_lv'=>$shop_deal_lv));
                                        }

                                    }
                                }
                            }
                            // 提交事务
                            Db::commit();
                            $value = array('status'=>200,'mess'=>'确认收货成功','data'=>array('status'=>200));
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                            $value = array('status'=>400,'mess'=>'确认收货失败','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'订单存在未完成的售后商品，确认收货失败','data'=>array('status'=>400));
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'找不到相关类型订单信息','data'=>array('status'=>400));
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少订单号','data'=>array('status'=>400));
            }
            
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);        
    }
    
    
}