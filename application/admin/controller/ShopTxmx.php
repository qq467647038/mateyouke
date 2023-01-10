<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class ShopTxmx extends Common{ 
    public function lst(){
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,4,5,10))){
            $filter = 10;
        }
        
        $where = array();
        
        switch ($filter){
            case 1:
                //待审核
                $where = array('a.checked'=>0,'a.complete'=>0);
                break;
            case 2:
                //待打款
                $where = array('a.checked'=>1,'a.complete'=>0);
                break;
            case 3:
                //已完成
                $where = array('a.checked'=>1,'a.complete'=>1);
                break;
            case 4:
                //打款失败
                $where = array('a.checked'=>1,'a.complete'=>2);
                break;
            case 5:
                //审核未通过
                $where = array('a.checked'=>2,'a.complete'=>0);
                break;
        }
        
        $list = Db::name('shop_txmx')->alias('a')->field('a.*,b.shop_name,b.telephone')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where)->order('a.time desc')->paginate(25);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum,
            'filter'=>$filter
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function checked(){
        if(request()->isPost()){
            if(input('post.id')){
                if(input('post.checked') && in_array(input('post.checked'), array(1,2))){
                    $checked = input('post.checked');
                    $id = input('post.id');
                    $txs = Db::name('shop_txmx')->alias('a')->field('a.*')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$id)->find();
                    if($txs && $txs['checked'] == 0 && $txs['complete'] == 0){
                        if($checked == 1){
                            $count = Db::name('shop_txmx')->update(array('checked'=>$checked,'id'=>$id));
                            if($count > 0){
                                ys_admin_logs('审核通过商家提现申请','shop_txmx',$id);
                                $value = array('status'=>1, 'mess'=>'设置成功');
                            }else{
                                $value = array('status'=>0, 'mess'=>'设置失败');
                            }
                        }elseif($checked == 2){
                            $wallets = Db::name('shop_wallet')->where('shop_id',$txs['shop_id'])->find();
                            // 启动事务
                            Db::startTrans();
                            try{
                                Db::name('shop_txmx')->update(array('checked'=>$checked,'id'=>$id));
                                Db::name('shop_wallet')->where('id',$wallets['id'])->setInc('price', $txs['price']);
                                // 提交事务
                                Db::commit();
                                ys_admin_logs('审核不通过商家提现申请','shop_txmx',$id);
                                $value = array('status'=>1, 'mess'=>'设置成功');
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status'=>0, 'mess'=>'设置失败');
                            }
                        }
                    }else{
                        $value = array('status'=>0, 'mess'=>'参数错误，设置失败');
                    }
                }else{
                    $value = array('status'=>0, 'mess'=>'参数错误，设置失败');
                }
            }else{
                $value = array('status'=>0, 'mess'=>'参数错误，设置失败');
            }
            return json($value);
        }else{
            if(input('tx_id') && input('filter')){
                if(in_array(input('filter'), array(1,2,3,4,5,10))){
                    $tx_id = input('tx_id');
                    $txs = Db::name('shop_txmx')->alias('a')->field('a.*,b.shop_name,b.telephone')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$tx_id)->find();
                    if($txs && $txs['checked'] == 0 && $txs['complete'] == 0){
                        $wallets = Db::name('shop_wallet')->where('shop_id',$txs['shop_id'])->find();
                        $shop_admins = Db::name('shop_admin')->where('shop_id',$txs['shop_id'])->field('phone')->find();
                        $txs['wallet_price'] = $wallets['price'];
                        $txs['phone'] = $shop_admins['phone'];
                        if(input('s')){
                            $this->assign('search',input('s'));
                        }
                        $this->assign('pnum',input('page'));
                        $this->assign('filter',input('filter'));
                        $this->assign('txs',$txs);
                        return $this->fetch();
                    }else{
                        $this->error('参数错误');
                    }
                }else{
                    $this->error('参数错误');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    public function dakuan(){
        if(request()->isPost()){
            if(input('post.id')){
                if(input('post.complete') && in_array(input('post.complete'), array(1,2))){
                    $complete = input('post.complete');
                    $id = input('post.id');
                    
                    $txs = Db::name('shop_txmx')->alias('a')->field('a.*')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$id)->find();
                    if($txs && $txs['checked'] == 1 && $txs['complete'] == 0){
                        $wallets = Db::name('shop_wallet')->where('shop_id',$txs['shop_id'])->find();
                        if($complete == 1){
                            // 启动事务
                            Db::startTrans();
                            try{
                                Db::name('shop_txmx')->update(array('complete'=>$complete,'wtime'=>time(),'id'=>$id));
                                Db::name('shop_detail')->insert(array('de_type'=>2,'zc_type'=>1,'price'=>$txs['price'],'tx_id'=>$txs['id'],'shop_id'=>$txs['shop_id'],'wat_id'=>$wallets['id'],'time'=>time()));
                                $pt_wallets = Db::name('pt_wallet')->where('id',1)->find();
                                if($pt_wallets){
                                    Db::name('pt_wallet')->where('id',1)->setDec('price', $txs['price']);
                                    Db::name('pt_detail')->insert(array('de_type'=>2,'zc_type'=>1,'price'=>$txs['price'],'tx_type'=>2,'tx_id'=>$txs['id'],'wat_id'=>$pt_wallets['id'],'time'=>time()));
                                }
                                // 提交事务
                                Db::commit();
                                ys_admin_logs('打款成功商家提现申请','shop_txmx',$id);
                                $value = array('status'=>1, 'mess'=>'设置成功');
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status'=>0, 'mess'=>'设置失败');
                            }
                        }elseif($complete == 2){
                            if(input('post.remarks')){
                                $remarks = input('post.remarks');
                                // 启动事务
                                Db::startTrans();
                                try{
                                    Db::name('shop_txmx')->update(array('complete'=>$complete,'remarks'=>$remarks,'id'=>$id));
                                    Db::name('shop_wallet')->where('id',$wallets['id'])->setInc('price', $txs['price']);
                                    // 提交事务
                                    Db::commit();
                                    ys_admin_logs('打款失败商家提现申请','shop_txmx',$id);
                                    $value = array('status'=>1, 'mess'=>'设置成功');
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>0, 'mess'=>'设置失败');
                                }
                            }else{
                                $value = array('status'=>0, 'mess'=>'请填写失败原因');
                            }
                        }
                    }else{
                        $value = array('status'=>0, 'mess'=>'参数错误，设置失败');
                    }
                }else{
                    $value = array('status'=>0, 'mess'=>'参数错误，设置失败');
                }
            }else{
                $value = array('status'=>0, 'mess'=>'参数错误，设置失败');
            }
            return json($value);
        }else{
            if(input('tx_id') && input('filter')){
                if(in_array(input('filter'), array(1,2,3,4,5,10))){
                    if(input('tx_id')){
                        $tx_id = input('tx_id');
                        $txs = Db::name('shop_txmx')->alias('a')->field('a.*,b.shop_name,b.telephone')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$tx_id)->find();
                        if($txs && $txs['checked'] == 1 && $txs['complete'] == 0){
                            $wallets = Db::name('shop_wallet')->where('shop_id',$txs['shop_id'])->find();
                            $shop_admins = Db::name('shop_admin')->where('shop_id',$txs['shop_id'])->field('phone')->find();
                            $txs['wallet_price'] = $wallets['price'];
                            $txs['phone'] = $shop_admins['phone'];
                            if(input('s')){
                                $this->assign('search',input('s'));
                            }
                            $this->assign('pnum',input('page'));
                            $this->assign('filter',input('filter'));
                            $this->assign('txs',$txs);
                            return $this->fetch();
                        }else{
                            $this->error('参数错误');
                        }
                    }else{
                        $this->error('缺少参数');
                    }
                }else{
                    $this->error('参数错误');
                }
            }else{
                $this->error('缺少参数');
            }    
        }
    }
    
    public function info(){
        if(input('tx_id')){
            $tx_id = input('tx_id');
            $txs = Db::name('shop_txmx')->alias('a')->field('a.*,b.shop_name,b.telephone')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$tx_id)->find();
            if($txs){
                $wallets = Db::name('shop_wallet')->where('shop_id',$txs['shop_id'])->find();
                $shop_admins = Db::name('shop_admin')->where('shop_id',$txs['shop_id'])->field('phone')->find();
                $txs['wallet_price'] = $wallets['price'];
                $txs['phone'] = $shop_admins['phone'];
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
        if(input('post.keyword') != ''){
            cookie('shtx_keyword',input('post.keyword'),7200);
        }else{
            cookie('shtx_keyword',null);
        }
        
        if(input('post.tx_zt') != ''){
            cookie("shtx_zt", input('post.tx_zt'), 7200);
        }
        
        if(input('post.starttime') != ''){
            $shtxstarttime = strtotime(input('post.starttime'));
            cookie('shtxstarttime',$shtxstarttime,3600);
        }
        
        if(input('post.endtime') != ''){
            $shtxendtime = strtotime(input('post.endtime'));
            cookie('shtxendtime',$shtxendtime,3600);
        }
        
        $where = array();

        if(cookie('shtx_zt') != ''){
            $shtx_zt = (int)cookie('shtx_zt');
            if($shtx_zt != 0){
                switch($shtx_zt){
                    //待审核
                    case 1:
                        $where['a.checked'] = 0;
                        $where['a.complete'] = 0;
                        break;
                        //待打款
                    case 2:
                        $where['a.checked'] = 1;
                        $where['a.complete'] = 0;
                        break;
                        //已完成
                    case 3:
                        $where['a.checked'] = 1;
                        $where['a.complete'] = 1;
                        break;
                        //打款失败
                    case 4:
                        $where['a.checked'] = 1;
                        $where['a.complete'] = 2;
                        break;
                        //审核未通过
                    case 5:
                        $where['a.checked'] = 2;
                        $where['a.complete'] = 0;
                        break;
                }
            }
        }
        
        if(cookie('shtx_keyword')){
            $where['a.tx_number'] = cookie('shtx_keyword');
        }
        
        if(cookie('shtxendtime') && cookie('shtxstarttime')){
            $where['a.time'] = array(array('egt',cookie('shtxstarttime')), array('lt',cookie('shtxendtime')));
        }
        
        if(cookie('shtxstarttime') && !cookie('shtxendtime')){
            $where['a.time'] = array('egt',cookie('shtxstarttime'));
        }
        
        if(cookie('shtxendtime') && !cookie('shtxstarttime')){
            $where['a.time'] = array('lt',cookie('shtxendtime'));
        }
        
        $list =  Db::name('shop_txmx')->alias('a')->field('a.*,b.shop_name,b.telephone')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where)->order('a.time desc')->paginate(50);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        
        if(cookie('shtxstarttime') != ''){
            $this->assign('starttime',cookie('shtxstarttime'));
        }
        
        if(cookie('shtxendtime') != ''){
            $this->assign('endtime',cookie('shtxendtime'));
        }
        
        if(cookie('shtx_keyword') != ''){
            $this->assign('keyword',cookie('shtx_keyword'));
        }
        
        if(cookie('shtx_zt') != ''){
            $this->assign('tx_zt',cookie('shtx_zt'));
        }
        
        $filter = 10;
        
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('filter',$filter);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }      
    }

    /***
     * 获取商家待提现余额信息
     */
    public function shoplist(){
        $today = date('d',time());
        $wallet = Db::name('shop_wallet')->alias('a')->field('a.*,b.shop_name,b.settlement_date,b.service_rate')->join('sp_shops b','a.shop_id = b.id','INNER')->select();
        if($wallet){
            foreach($wallet as $key=>$value){
                //获取结算日期，如果当前日子大于结算日期，结算日期加30
                if($today > $value['settlement_date']){
                    $wallet[$key]['sort'] = $value['settlement_date'] + 30;
                    $wallet[$key]['year'] = date('Y-m',strtotime('+1 month'));
                }else{
                    $wallet[$key]['sort'] = $value['settlement_date'];
                    $wallet[$key]['year'] = date('Y-m',time());
                }
            }
        }
        $sort_arr = [];
        foreach ($wallet as $key => $value) {
            $sort_arr[] = $value['sort'];
        }

        array_multisort($sort_arr, SORT_ASC, $wallet);
        $this->assign(array(
            'list'=>$wallet,
        ));
        return $this->fetch();
    }

}
?>

