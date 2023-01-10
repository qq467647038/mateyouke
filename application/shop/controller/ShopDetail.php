<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;

class ShopDetail extends Common{
    public function lst(){
        $shop_id = session('shopsh_id');
        
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3))){
            $filter = 3;
        }

        $where = array();
        
        switch ($filter){
            case 1:
                //收入
                $where = array('shop_id'=>$shop_id,'de_type'=>1);
                break;
            case 2:
                //支出
                $where = array('shop_id'=>$shop_id,'de_type'=>2);
                break;
            case 3:
                //全部
                $where = array('shop_id'=>$shop_id);
                break;
        }
        
        $list = Db::name('shop_detail')->where($where)->order('time desc')->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $wallet = Db::name('shop_wallet')->where('shop_id',$shop_id)->find();
        $totalprice = $wallet['price'];
        
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum,
            'filter'=>$filter,
            'totalprice'=>$totalprice,
            'shop_id'=>$shop_id
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function info(){
        if(input('de_id')){
            $shop_id = session('shopsh_id');
            $de_id = input('de_id');
            
            $details = Db::name('shop_detail')->where('id',$de_id)->where('shop_id',$shop_id)->find();
            if($details){
                if($details['de_type'] == 1){
                    //收入
                    switch ($details['sr_type']){
                        //订单完成
                        case 1:
                            $details['ordernumber'] = Db::name('order')->where('id',$details['order_id'])->where('shop_id',$shop_id)->value('ordernumber');
                            if(!$details['ordernumber']){
                                $this->error('获取失败');
                            }
                            break;
                    }
                }elseif($details['de_type'] == 2){
                    //支出
                    switch ($details['zc_type']){
                        //提现
                        case 1:
                            $details['tx_number'] = Db::name('shop_txmx')->where('id',$details['tx_id'])->where('shop_id',$shop_id)->value('tx_number');
                            if(!$details['tx_number']){
                                $this->error('获取失败');
                            }
                            break;
                    }
                }
                $this->assign('details',$details);
                return $this->fetch();
            }else{
                $this->error('明细信息错误');
            }
        }else{
            $this->error('明细信息错误');
        }
    }
    
    public function search(){
        $shop_id = session('shopsh_id');
        $where = array();
        $wallet = Db::name('shop_wallet')->where('shop_id',$shop_id)->find();
        $totalprice = $wallet['price'];
        
        $where['shop_id'] = $shop_id;
        
        if(input('post.de_zt') != ''){
            cookie("shopde_zt", input('post.de_zt'), 7200);
        }
        
        if(input('post.starttime') != ''){
            $shopdestarttime = strtotime(input('post.starttime'));
            cookie('shopdestarttime',$shopdestarttime,3600);
        }
        
        if(input('post.endtime') != ''){
            $shopdeendtime = strtotime(input('post.endtime'));
            cookie('shopdeendtime',$shopdeendtime,3600);
        }
        
        if(cookie('shopde_zt') != ''){
            $de_zt = (int)cookie('shopde_zt');
            if($de_zt != 0){
                switch($de_zt){
                    //收入
                    case 1:
                        $where['de_type'] = 1;
                        break;
                        //支出
                    case 2:
                        $where['de_type'] = 2;
                        break;
                }
            }
        }
         
        
        if(cookie('shopdeendtime') && cookie('shopdestarttime')){
            $where['time'] = array(array('egt',cookie('shopdestarttime')), array('lt',cookie('shopdeendtime')));
        }
        
        if(cookie('shopdestarttime') && !cookie('shopdeendtime')){
            $where['time'] = array('egt',cookie('shopdestarttime'));
        }
        
        if(cookie('shopdeendtime') && !cookie('shopdestarttime')){
            $where['time'] = array('lt',cookie('shopdeendtime'));
        }
        
        $list = Db::name('shop_detail')->where($where)->order('time desc')->paginate(50);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        
        if(cookie('shopdestarttime')){
            $this->assign('starttime',cookie('shopdestarttime'));
        }
        
        if(cookie('shopdeendtime')){
            $this->assign('endtime',cookie('shopdeendtime'));
        }
        
        if(cookie('shopde_zt') != ''){
            $this->assign('de_zt',cookie('shopde_zt'));
        }
        
        if(input('post.filter')){
            $filter = input('post.filter');
        }else{
            $filter = 3;
        }
        
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('filter',$filter);
        $this->assign('totalprice',$totalprice);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }

    
}
