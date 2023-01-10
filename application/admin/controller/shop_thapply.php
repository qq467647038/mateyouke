<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class ThApply extends Common{

    public function lst(){
        $shop_id = session('shop_id');
        
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,4,5,10))){
            $filter = 10;
        }
    
        switch ($filter){
            //待平台审核
            case 1:
                $where = array('a.shop_id'=>array('neq',$shop_id),'a.apply_status'=>0);
                break;
            //平台已同意
            case 2:
                $where = array('a.shop_id'=>array('neq',$shop_id),'a.apply_status'=>1);
                break;
            //平台已拒绝
            case 3:
                $where = array('a.shop_id'=>array('neq',$shop_id),'a.apply_status'=>2);
                break;
            //已完成
            case 5:
                $where = array('a.shop_id'=>array('neq',$shop_id),'a.apply_status'=>3);
                break;
            //用户已撤销
            case 4:
                $where = array('a.shop_id'=>array('neq',$shop_id),'a.apply_status'=>4);
                break;
            //全部
            case 10:
                $where = array('a.shop_id'=>array('neq',$shop_id));
                break;
        }
    
    
        $list = Db::name('th_apply')->alias('a')->field('a.id,a.th_number,a.tui_price,a.apply_status,a.apply_time,b.user_name,b.phone,c.shop_name,d.cate_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_shop c','a.shop_id = c.id','LEFT')->join('sp_thcate d','a.thfw_id = d.id','LEFT')->where($where)->order('a.apply_time desc')->paginate(25);
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $this->assign('filter',$filter);
        $this->assign('pnum',$pnum);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('list',$list);// 赋值数据集
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
     
    //退换申请详情
    public function info(){
        if(input('th_id')){
            $shop_id = session('shop_id');
            $th_id = input('th_id');
            $applys = Db::name('th_apply')->alias('a')->field('a.*,b.user_name,b.phone,c.shop_name,d.cate_name,c.contacts,c.telephone,c.shengshiqu,c.address')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_shop c','a.shop_id = c.id','LEFT')->join('sp_thcate d','a.thfw_id = d.id','LEFT')->where('a.id',$th_id)->where('a.shop_id','neq',$shop_id)->find();
            if($applys){
                $tuiwulius = array();
                if(in_array($applys['thfw_id'], array(2,3)) && $applys['dcfh_status'] == 1){
                    $tuiwulius = Db::name('tui_wuliu')->where('th_id',$applys['id'])->find();
                }
                $wulius = array();
                if($applys['thfw_id'] == 3 && $applys['dcfh_status'] == 1 && $applys['sh_status'] == 1){
                    $wulius = Db::name('huan_wuliu')->alias('a')->field('a.*,b.log_name,b.telephone')->join('sp_logistics b','a.ps_id = b.id','LEFT')->where('a.th_id',$th_id)->find();
                }
                $order_goods = Db::name('order_goods')->where('id',$applys['orgoods_id'])->find();
                $thpicres = Db::name('thapply_pic')->where('th_id',$th_id)->select();
                $psres = Db::name('logistics')->where('is_show',1)->field('id,log_name')->order('sort asc')->select();
                
                $orders = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.pro_name,d.city_name,p.area_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area p','a.area_id = p.id','LEFT')->where('a.id',$applys['order_id'])->where('a.shop_id',$shop_id)->find();
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
                
                $this->assign('applys',$applys);
                $this->assign('tuiwulius',$tuiwulius);
                $this->assign('order_goods',$order_goods);
                $this->assign('thpicres',$thpicres);
                $this->assign('wulius',$wulius);
                $this->assign('psres',$psres);
                $this->assign('orders',$orders);
                return $this->fetch();
            }else{
                $this->error('订单信息错误');
            }
        }else{
            $this->error('缺少订单信息');
        }
    }

    //确认收货操作
    public function suresave(){
        $order_id = input('post.order_id');
        $count = Db::name('order')->update(array('o_state'=>9,'id'=>$order_id));
        $value = array('status'=>1,'mess'=>'收货成功');
        return json($value);
    }

    public function savewuliu(){
        $shop_id = session('shop_id');
        $ps_id = input('post.p_ps_id');
        $psnum = input('post.p_psnum');
        $order_id = input('post.order_id');
        $wuliu_infos = Db::name('order_wuliu')->where('pt_psnum',$psnum)->find();
        if(!$wuliu_infos){
            $logs = Db::name('logistics')->where('id',$ps_id)->find();
            $lastId = Db::name('order_wuliu')->insertGetId(array('pt_ps_id'=>$ps_id,'pt_psnum'=>$psnum,'order_id'=>$order_id));
            if($lastId){
                $count = Db::name('order')->update(array('o_state'=>3,'id'=>$order_id));
                //发货成功后更改订单状态为平台已发货， 商家待收货
                $value = array('status'=>1,'mess'=>'发货成功');
            }else{
                $value = array('status'=>0,'mess'=>'发货失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'发货失败,无此物流公司');
        }
        return json($value);
    }
    
    public function search(){
        $shop_id = session('shop_id');
        
        if(input('post.keyword') != ''){
            cookie('shopth_keyword',input('post.keyword'),7200);
        }else{
            cookie('shopth_keyword',null);
        }
        
        if(input('post.shop_name') != ''){
            cookie('shopth_shop_name',input('post.shop_name'),7200);
        }else{
            cookie('shopth_shop_name',null);
        }
        
        if(input('post.thfw_id') != ''){
            cookie("shopthfw_id", input('post.thfw_id'), 7200);
        }
    
        if(input('post.th_status') != ''){
            cookie("shopth_status", input('post.th_status'), 7200);
        }
    
        if(input('post.starttime') != ''){
            $shopthstarttime = strtotime(input('post.starttime'));
            cookie('shopthstarttime',$shopthstarttime,7200);
        }
    
        if(input('post.endtime') != ''){
            $shopthendtime = strtotime(input('post.endtime'));
            cookie('shopthendtime',$shopthendtime,7200);
        }
    
        $where = array();
        $where['a.shop_id'] = array('neq',$shop_id);
        
        if(cookie('shopthfw_id') != ''){
            $shopthfw_id = (int)cookie('shopthfw_id');
            if($shopthfw_id != 0){
                switch($shopthfw_id){
                    //仅退款
                    case 1:
                        $where['a.thfw_id'] = 1;
                        break;
                        //退货退款
                    case 2:
                        $where['a.thfw_id'] = 2;
                        break;
                        //换货
                    case 3:
                        $where['a.thfw_id'] = 3;
                        break;
                }
            }
        }
    
        if(cookie('shopth_status') != ''){
            $shopth_status = cookie('shopth_status');
            if($shopth_status != 0){
                switch($shopth_status){
                    //待平台审核
                    case 1:
                        $where['a.apply_status'] = 0;
                        break;
                    //平台已同意
                    case 2:
                        $where['a.apply_status'] = 1;
                        break;
                    //平台已拒绝
                    case 3:
                        $where['a.apply_status'] = 2;
                        break;
                    //已完成
                    case 5:
                        $where['a.apply_status'] = 3;
                        break;
                    //用户已撤销
                    case 4:
                        $where['a.apply_status'] = 4;
                        break;
                }
            }
        }
    
        if(cookie('shopthendtime') && cookie('shopthstarttime')){
            $where['a.apply_time'] = array(array('egt',cookie('shopthstarttime')), array('lt',cookie('shopthendtime')));
        }
    
        if(cookie('shopthstarttime') && !cookie('shopthendtime')){
            $where['a.apply_time'] = array('egt',cookie('shopthstarttime'));
        }
    
        if(cookie('shopthendtime') && !cookie('shopthstarttime')){
            $where['a.apply_time'] = array('lt',cookie('shopthendtime'));
        }
    
        if(cookie('shopth_keyword')){
            $where['a.ordernumber'] = cookie('shopth_keyword');
        }
        
        if(cookie('shopth_shop_name')){
            $shops = Db::name('shops')->where('shop_name',cookie('shopth_shop_name'))->where('id','neq',$shop_id)->field('id')->find();
            if($shops){
                $where['a.shop_id'] = $shops['id'];
            }else{
                $where['a.shop_id'] = 'aa';
            }
        }
        
        $list = Db::name('th_apply')->alias('a')->field('a.id,a.th_number,a.tui_price,a.apply_status,a.apply_time,b.user_name,b.phone,c.shop_name,d.cate_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_shop c','a.shop_id = c.id','LEFT')->join('sp_thcate d','a.thfw_id = d.id','LEFT')->where($where)->order('a.apply_time desc')->paginate(25);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $search = 1;
            
        if(cookie('shopthstarttime')){
            $this->assign('starttime',cookie('shopthstarttime'));
        }
    
        if(cookie('shopthendtime')){
            $this->assign('endtime',cookie('shopthendtime'));
        }
    
        if(cookie('shopth_keyword')){
            $this->assign('keyword',cookie('shopth_keyword'));
        }
    
        if(cookie('shopth_status') != ''){
            $this->assign('th_status',cookie('shopth_status'));
        }
    
        if(cookie('shopthfw_id') != ''){
            $this->assign('thfw_id',cookie('shopthfw_id'));
        }
    
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('filter',10);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }    
 
    
}
