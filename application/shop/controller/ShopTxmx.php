<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;

class ShopTxmx extends Common{
    
    public function lst(){
        $shop_id = session('shopsh_id');
        $list = Db::name('shop_txmx')->where('shop_id',$shop_id)->order('time desc')->paginate(25);
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    
    public function info(){
        if(input('tx_id')){
            $shop_id = session('shopsh_id');
            $tx_id = input('tx_id');
            $txs = Db::name('shop_txmx')->where('id',$tx_id)->where('shop_id',$shop_id)->find();
            if($txs){
                $wallets = Db::name('shop_wallet')->where('shop_id',$shop_id)->find();
                $txs['wallet_price'] = $wallets['price'];
                if(input('s')){
                    $this->assign('search',input('s'));
                }
                $this->assign('txs',$txs);
                return $this->fetch();
            }else{
                $this->error('参数错误');
            }
        }else{
            $this->error('缺少参数');
        }
    }
    
    public function search(){
        $shop_id = session('shopsh_id');
        
        if(input('post.keyword') != ''){
            cookie('shoptx_keyword',input('post.keyword'),7200);
        }else{
            cookie('shoptx_keyword',null);
        }
    
        if(input('post.tx_zt') != ''){
            cookie("shoptx_zt", input('post.tx_zt'), 7200);
        }
    
        if(input('post.starttime') != ''){
            $shoptxstarttime = strtotime(input('post.starttime'));
            cookie('shoptxstarttime',$shoptxstarttime,3600);
        }
    
        if(input('post.endtime') != ''){
            $shoptxendtime = strtotime(input('post.endtime'));
            cookie('shoptxendtime',$shoptxendtime,3600);
        }
    
        $where = array();
        
        $where['shop_id'] = $shop_id;
        
        if(cookie('shoptx_zt') != ''){
            $shoptx_zt = (int)cookie('shoptx_zt');
            if($shoptx_zt != 0){
                switch($shoptx_zt){
                    //待审核
                    case 1:
                        $where['checked'] = 0;
                        $where['complete'] = 0;
                        break;
                        //待打款
                    case 2:
                        $where['checked'] = 1;
                        $where['complete'] = 0;
                        break;
                        //已完成
                    case 3:
                        $where['checked'] = 1;
                        $where['complete'] = 1;
                        break;
                        //打款失败
                    case 4:
                        $where['checked'] = 1;
                        $where['complete'] = 2;
                        break;
                        //审核未通过
                    case 5:
                        $where['checked'] = 2;
                        $where['complete'] = 0;
                        break;
                }
            }
        }
    
        if(cookie('shoptx_keyword')){
            $where['tx_number'] = cookie('shoptx_keyword');
        }
    
        if(cookie('shoptxendtime') && cookie('shoptxstarttime')){
            $where['time'] = array(array('egt',cookie('shoptxstarttime')), array('lt',cookie('shoptxendtime')));
        }
    
        if(cookie('shoptxstarttime') && !cookie('shoptxendtime')){
            $where['time'] = array('egt',cookie('shoptxstarttime'));
        }
    
        if(cookie('shoptxendtime') && !cookie('shoptxstarttime')){
            $where['time'] = array('lt',cookie('shoptxendtime'));
        }
    
        $list =  Db::name('shop_txmx')->where($where)->order('time desc')->paginate(50);
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
    
        if(cookie('shoptxstarttime') != ''){
            $this->assign('starttime',cookie('shoptxstarttime'));
        }
    
        if(cookie('shoptxendtime') != ''){
            $this->assign('endtime',cookie('shoptxendtime'));
        }
    
        if(cookie('shoptx_keyword') != ''){
            $this->assign('keyword',cookie('shoptx_keyword'));
        }
    
        if(cookie('shoptx_zt') != ''){
            $this->assign('tx_zt',cookie('shoptx_zt'));
        }
    
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    
    public function index(){
        $shop_id = session('shopsh_id');
        $wallets = Db::name('shop_wallet')->where('shop_id',$shop_id)->field('price')->find();
        $cards = Db::name('shop_bankcard')->where('shop_id',$shop_id)->field('name,telephone,card_number,bank_name,branch_name')->find();
        if($wallets && $cards){
            $this->assign('price',$wallets['price']);
            $this->assign('cards',$cards);
            return $this->fetch();
        }elseif(!$wallets){
            $this->error('参数错误');
        }elseif(!$cards){
            $this->redirect('shop_bankcard/info');
        }
    }
    
    public function tixian(){
        if(request()->isPost()){
            $admin_id = session('shopadmin_id');
            $shop_id = session('shopsh_id');
            
            $paypwd = Db::name('shop_admin')->where('id',$admin_id)->value('paypwd');
            if($paypwd){
                if(input('post.pay_password') && preg_match("/^\\d{6}$/", input('post.pay_password'))){
                    $pay_password = input('post.pay_password');
                    if($paypwd == md5($pay_password)){
                        if(input('post.price')){
                            if(preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", input('post.price'))){
                                $price = input('post.price');
                                $webconfig = $this->webconfig;
                                if($price >= $webconfig['shopmintixian']){
                                    $wallets = Db::name('shop_wallet')->where('shop_id',$shop_id)->find();
                                    if($wallets && $wallets['price'] >= $price){
                                        $txmxnum = Db::name('shop_txmx')->where('shop_id',$shop_id)->whereTime('time', 'month')->count();
                                        if($txmxnum < $webconfig['shoptixiancishu']){
                                            $cards = Db::name('shop_bankcard')->where('shop_id',$shop_id)->find();
                                            if($cards){
                                                $tx_number = 'SHTX'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                                $txmxs = Db::name('shop_txmx')->where('tx_number',$tx_number)->find();
                                                if(!$txmxs){
                                                    $shengshiqu = $cards['province'].$cards['city'].$cards['area'];
                                                    // 启动事务
                                                    Db::startTrans();
                                                    try{
                                                        Db::name('shop_txmx')->insert(array(
                                                            'tx_number'=>$tx_number,
                                                            'price'=>$price,
                                                            'time'=>time(),
                                                            'shop_id'=>$shop_id,
                                                            'card_number'=>$cards['card_number'],
                                                            'zs_name'=>$cards['name'],
                                                            'bank_name'=>$cards['bank_name'],
                                                            'shengshiqu'=>$shengshiqu,
                                                            'branch_name'=>$cards['branch_name']
                                                        ));
                                                        Db::name('shop_wallet')->where('id',$wallets['id'])->setDec('price', $price);
                                                        // 提交事务
                                                        Db::commit();
                                                        $value = array('status'=>1,'mess'=>'提现申请成功，平台将尽快处理');
                                                    } catch (\Exception $e) {
                                                        // 回滚事务
                                                        Db::rollback();
                                                        $value = array('status'=>0,'mess'=>'申请提现失败');
                                                    }
                                                }else{
                                                    $value = array('status'=>0,'mess'=>'申请提现失败，请重试');
                                                }
                                            }else{
                                                $value = array('status'=>0,'mess'=>'请先绑定银行卡！');
                                            }
                                        }else{
                                            $value = array('status'=>0,'mess'=>'每月最多提现'.$webconfig['shoptixiancishu'].'次');
                                        }
                                    }else{
                                        $value = array('status'=>0,'mess'=>'您的钱包余额不足，提现失败');
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'每次最少提现'.$webconfig['shopmintixian'].'元');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'提现金额格式错误');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请填写提现金额');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'支付密码错误');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'支付密码错误');
                }
            }else{
                $value = array('status'=>5,'mess'=>'请先设置支付密码');
            }
            return json($value);
        }
    }
}
