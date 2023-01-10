<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class YhOrder extends Common{

    public function lst(){
        if(input('user_id')){
            $user_id = input('user_id');
            
            $user_name = Db::name('member')->where('id',$user_id)->value('user_name');
            
            $filter = input('filter');
            if(!$filter || !in_array($filter, array(1,2,3,4,5,10))){
                $filter = 10;
            }
            
            switch ($filter){
                //待发货
                case 1:
                    $where = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>0,'a.order_status'=>0);
                    break;
                    //已发货
                case 2:
                    $where = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>0);
                    break;
                    //已完成
                case 3:
                    $where = array('a.user_id'=>$user_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1);
                    break;
                    //待支付
                case 4:
                    $where = array('a.user_id'=>$user_id,'a.state'=>0,'a.fh_status'=>0,'a.order_status'=>0);
                    break;
                    //已关闭
                case 5:
                    $where = array('a.user_id'=>$user_id,'a.order_status'=>2);
                    break;
                    //全部
                case 10:
                    $where = array('a.user_id'=>$user_id);
                    break;
            }
            
            
            $list = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.pro_name,d.city_name,u.area_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
            $page = $list->render();
            
            if(input('page')){
                $pnum = input('page');
            }else{
                $pnum = 1;
            }
            
            $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
            $this->assign('user_id',$user_id);
            $this->assign('user_name',$user_name);
            $this->assign('filter',$filter);
            $this->assign('prores',$prores);
            $this->assign('pnum',$pnum);
            $this->assign('page',$page);// 赋值分页输出
            $this->assign('list',$list);// 赋值数据集
            if(request()->isAjax()){
                return $this->fetch('ajaxpage');
            }else{
                return $this->fetch('lst');
            }
        }else{
            $this->error('缺少用户信息');
        }
    }
    
    public function getcitylist(){
        if(request()->isPost()){
            $pro_id = input('post.pro_id');
            if($pro_id){
                $cityres = Db::name('city')->where('pro_id',$pro_id)->field('id,city_name,zm')->order('sort asc')->select();
                if(empty($cityres)){
                    $cityres = 0;
                }
                return $cityres;
            }
        }
    }
    
    public function getarealist(){
        if(request()->isPost()){
            $city_id = input('post.city_id');
            if($city_id){
                $areares = Db::name('area')->where('city_id',$city_id)->field('id,area_name,zm')->order('sort asc')->select();
                if(empty($areares)){
                    $areares = 0;
                }
                return $areares;
            }
        }
    }
     
    //订单详情
    public function info(){
        if(input('order_id')){
            if(input('user_id')){
                $order_id = input('order_id');
                $user_id = input('user_id');
                $orders = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.pro_name,d.city_name,p.area_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area p','a.area_id = p.id','LEFT')->where('a.id',$order_id)->where('a.user_id',$user_id)->find();
                if($orders){
                    if($orders['state'] == 1 && $orders['fh_status'] == 0 && $orders['order_status'] == 0){
                        $orders['zhuangtai'] = 1;
                    }elseif($orders['state'] == 1 && $orders['fh_status'] == 1 && $orders['order_status'] == 0){
                        $orders['zhuangtai'] = 2;
                    }elseif($orders['state'] == 1 && $orders['fh_status'] == 1 && $orders['order_status'] == 1){
                        $orders['zhuangtai'] = 3;
                    }elseif($orders['state'] == 0 && $orders['fh_status'] == 0 && $orders['order_status'] == 0){
                        $orders['zhuangtai'] = 4;
                    }elseif($orders['order_status'] == 2){
                        $orders['zhuangtai'] = 5;
                    }
                
                    if($orders['order_type'] == 2){
                        $pintuans = Db::name('pintuan')->where('id',$orders['pin_id'])->field('id,pin_num,tuan_num,state,pin_status,timeout')->find();
                        $assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('order_id',$orders['id'])->find();
                    }else{
                        $pintuans = array();
                        $assembles = array();
                    }
                
                    $order_goodres = Db::name('order_goods')->where('order_id',$orders['id'])->select();
                    foreach ($order_goodres as $k => $v){
                        $order_goodres[$k]['dan_price'] = sprintf("%.2f", $v['real_price']*$v['goods_num']);
                    }
                
                    $wulius = Db::name('order_wuliu')->where('order_id',$order_id)->find();
                
                    $psres = Db::name('logistics')->where('is_show',1)->field('id,log_name')->order('sort asc')->select();
                
                    if($wulius){
                        $log_name = Db::name('logistics')->where('id',$wulius['ps_id'])->value('log_name');
                        $this->assign('log_name',$log_name);
                    }else{
                        $log_name = '';
                    }
                
                    $this->assign('orders',$orders);
                    $this->assign('pintuans',$pintuans);
                    $this->assign('assembles',$assembles);
                    $this->assign('order_goodres',$order_goodres);
                    $this->assign('wulius',$wulius);
                    $this->assign('psres',$psres);
                    $this->assign('log_name',$log_name);
                    return $this->fetch();
                }else{
                    $this->error('订单信息错误');
                }
            }else{
                $this->error('缺少用户信息');
            }
        }else{
            $this->error('缺少订单信息');
        }
    }
    
    
    public function delete(){
        if(input('id') && !is_array(input('id'))){
            $id = input('id');
            $orders = Db::name('order')->where('id',$id)->where('state',0)->where('order_status',2)->field('id')->find();
            if($orders){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('order')->where('id',$id)->update(array('is_show'=>0,'del_time'=>time()));
                    // 提交事务
                    Db::commit();
                    ys_admin_logs('删除订单','order',$id);
                    $value = array('status'=>1,'mess'=>'删除成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'未关闭订单不可删除');
            }
        }else{
            $value = array('status'=>0,'mess'=>'删除失败');
        }
        return json($value);
    }
    
    public function search(){
        if(input('post.user_id')){
            $user_id = input('post.user_id');
            
            $user_name = Db::name('member')->where('id',$user_id)->value('user_name');
            
            if(input('post.keyword') != ''){
                cookie('oruser_keyword',input('post.keyword'),7200);
            }else{
                cookie('oruser_keyword',null);
            }
            
            if(input('post.pro_id') != ''){
                cookie("oruser_pro_id", input('post.pro_id'), 7200);
            }
            
            if(input('post.city_id') != ''){
                cookie("oruser_city_id", input('post.city_id'), 7200);
            }
            
            if(input('post.area_id') != ''){
                cookie("oruser_area_id", input('post.area_id'), 7200);
            }
            
            if(input('post.order_type') != ''){
                cookie("oruser_order_type", input('post.order_type'), 7200);
            }
            
            if(input('post.order_zt') != ''){
                cookie("oruser_order_zt", input('post.order_zt'), 7200);
            }
            
            if(input('post.zf_type') != ''){
                cookie("oruser_zf_type", input('post.zf_type'), 7200);
            }
            
            if(input('post.starttime') != ''){
                $oruserstarttime = strtotime(input('post.starttime'));
                cookie('oruserstarttime',$oruserstarttime,7200);
            }
            
            if(input('post.endtime') != ''){
                $oruserendtime = strtotime(input('post.endtime'));
                cookie('oruserendtime',$oruserendtime,7200);
            }
            
            $where = array();
            $where['a.user_id'] = $user_id;
            
            if(cookie('oruser_keyword')){
                $where['a.ordernumber'] = cookie('oruser_keyword');
            }
            
            
            if(cookie('oruser_pro_id') != ''){
                $proid = (int)cookie('oruser_pro_id');
                if($proid != 0){
                    $where['a.pro_id'] = $proid;
                }
            }
            
            if(cookie('oruser_city_id') != ''){
                $cityid = (int)cookie('oruser_city_id');
                if($cityid != 0){
                    $where['a.city_id'] = $cityid;
                }
            }
            
            if(cookie('oruser_area_id') != ''){
                $areaid = (int)cookie('oruser_area_id');
                if($areaid != 0){
                    $where['a.area_id'] = $areaid;
                }
            }
            
            $nowtime = time();
            
            if(cookie('oruser_order_type') != ''){
                $order_type = (int)cookie('oruser_order_type');
                if($order_type != 0){
                    switch($order_type){
                        //普通订单
                        case 1:
                            $where['a.order_type'] = 1;
                            break;
                            //拼团订单
                        case 2:
                            $where['a.order_type'] = 2;
                            break;
                    }
                }
            }
            
            if(cookie('oruser_order_zt') != ''){
                $order_zt = (int)cookie('oruser_order_zt');
            
                if($order_zt != 0){
                    switch($order_zt){
                        //待发货
                        case 1:
                            $where['a.state'] = 1;
                            $where['a.fh_status'] = 0;
                            $where['a.order_status'] = 0;
                            break;
                            //已发货
                        case 2:
                            $where['a.state'] = 1;
                            $where['a.fh_status'] = 1;
                            $where['a.order_status'] = 0;
                            break;
                            //已完成
                        case 3:
                            $where['a.state'] = 1;
                            $where['a.fh_status'] = 1;
                            $where['a.order_status'] = 1;
                            break;
                            //待支付
                        case 4:
                            $where['a.state'] = 0;
                            $where['a.fh_status'] = 0;
                            $where['a.order_status'] = 0;
                            break;
                            //已关闭
                        case 5:
                            $where['a.order_status'] = 2;
                            break;
                    }
                }
            }
            
            if(cookie('oruser_zf_type') != ''){
                $zf_type = (int)cookie('oruser_zf_type');
                if($zf_type != 0){
                    switch($zf_type){
                        //支付宝支付
                        case 1:
                            $where['a.zf_type'] = 1;
                            break;
                        //微信支付
                        case 2:
                            $where['a.zf_type'] = 2;
                            break;
                        //余额支付
                        case 3:
                            $where['a.zf_type'] = 3;
                            break;                           
                    }
                }
            }
            
            if(cookie('oruserendtime') && cookie('oruserstarttime')){
                $where['a.addtime'] = array(array('egt',cookie('oruserstarttime')), array('lt',cookie('oruserendtime')));
            }
            
            if(cookie('oruserstarttime') && !cookie('oruserendtime')){
                $where['a.addtime'] = array('egt',cookie('oruserstarttime'));
            }
            
            if(cookie('oruserendtime') && !cookie('oruserstarttime')){
                $where['a.addtime'] = array('lt',cookie('oruserendtime'));
            }
            
            $list = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.pro_name,d.city_name,u.area_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
            
            $page = $list->render();
            
            $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
            
            if(cookie('oruser_pro_id')){
                $cityres = Db::name('city')->where('pro_id',cookie('oruser_pro_id'))->field('id,city_name,zm')->order('sort asc')->select();
            }
            
            if(cookie('oruser_pro_id') && cookie('oruser_city_id')){
                $areares = Db::name('area')->where('city_id',cookie('oruser_city_id'))->field('id,area_name,zm')->select();
            }
            
            if(input('page')){
                $pnum = input('page');
            }else{
                $pnum = 1;
            }
            
            $search = 1;
            
            if(cookie('oruser_pro_id') != ''){
                $this->assign('pro_id',cookie('oruser_pro_id'));
            }
            if(cookie('oruser_city_id') != ''){
                $this->assign('city_id',cookie('oruser_city_id'));
            }
            if(cookie('oruser_area_id') != ''){
                $this->assign('area_id',cookie('oruser_area_id'));
            }
            
            if(cookie('oruserstarttime')){
                $this->assign('starttime',cookie('oruserstarttime'));
            }
            
            if(cookie('oruserendtime')){
                $this->assign('endtime',cookie('oruserendtime'));
            }
            
            if(!empty($cityres)){
                $this->assign('cityres',$cityres);
            }
            
            if(!empty($areares)){
                $this->assign('areares',$areares);
            }
            
            if(cookie('oruser_keyword')){
                $this->assign('keyword',cookie('oruser_keyword'));
            }
            
            if(cookie('oruser_order_type') != ''){
                $this->assign('order_type',cookie('oruser_order_type'));
            }
            
            if(cookie('oruser_order_zt') != ''){
                $this->assign('order_zt',cookie('oruser_order_zt'));
            }
            
            if(cookie('oruser_zf_type') != ''){
                $this->assign('zf_type',cookie('oruser_zf_type'));
            }
            
            $this->assign('user_id',$user_id);
            $this->assign('user_name',$user_name);
            $this->assign('search',$search);
            $this->assign('pnum', $pnum);
            $this->assign('list', $list);// 赋值数据集
            $this->assign('page', $page);// 赋值分页输出
            $this->assign('prores',$prores);
            $this->assign('filter',10);
            if(request()->isAjax()){
                return $this->fetch('ajaxpage');
            }else{
                return $this->fetch('lst');
            }
        }else{
            $this->error('缺少用户信息');
        }
    }    
 
    
}
