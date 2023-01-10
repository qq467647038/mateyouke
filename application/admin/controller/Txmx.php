<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Txmx extends Common{ 
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
        $user_id = session('user_id');
        if (!empty($user_id)){
            $where['user_id'] = $user_id;
        }
        $list = Db::name('withdraw')
            ->alias('a')
            ->field('a.*,b.user_name,b.phone')
            ->join('sp_member b','a.user_id = b.id','INNER')
            ->where($where)
            ->order('a.time desc')
            ->paginate(25);
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
                    $txs = Db::name('withdraw')->alias('a')->field('a.*')->join('sp_member b','a.user_id = b.id','INNER')->where('a.id',input('post.id'))->find();
                    if($txs && $txs['checked'] == 0 && $txs['complete'] == 0){
                        if($checked == 1){
                            $count = Db::name('withdraw')->update(array('checked'=>$checked,'id'=>input('post.id')));
                            if($count > 0){
                                ys_admin_logs('审核通过提现申请','withdraw',input('post.id'));
                                $value = array('status'=>1, 'mess'=>'设置成功');
                            }else{
                                $value = array('status'=>0, 'mess'=>'设置失败');
                            }
                        }elseif($checked == 2){
                            $wallets = Db::name('wallet')->where('user_id',$txs['user_id'])->find();
                            // 启动事务
                            Db::startTrans();
                            try{
                                Db::name('withdraw')->update(array('checked'=>$checked,'id'=>input('post.id')));

                                if($txs['type'] == 1){
                                    Db::name('wallet')->where('id',$wallets['id'])->setInc('price', $txs['price']);
                                    Db::name('detail')->insert(array('de_type'=>1,
                                                                        'sr_type'=>6,
                                                                        'zc_type'=>1,
                                                                        'price'=>$txs['price'],'tx_id'=>$txs['id'],
                                                                        'user_id'=>$txs['user_id'],'wat_id'=>$wallets['id'],'time'=>time(), 'remark'=>'用户余额提现被拒'));
                                }else{
                                    Db::name('wallet')->where('id',$wallets['id'])->setInc('agent_profit', $txs['price']);
                                    Db::name('detail')->insert(array('de_type'=>1,
                                        'sr_type'=>7,
                                        'zc_type'=>1,
                                        'price'=>$txs['price'],'tx_id'=>$txs['id'],
                                        'user_id'=>$txs['user_id'],'wat_id'=>$wallets['id'],'time'=>time(), 'remark'=>'用户代理余额提现被拒'));
                                }
                                // 提交事务
                                Db::commit();
                                ys_admin_logs('审核不通过提现申请','withdraw',input('post.id'));
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
                    $txs = Db::name('withdraw')->alias('a')->field('a.*,b.user_name,b.phone')->join('sp_member b','a.user_id = b.id','INNER')->where('a.id',$tx_id)->find();
                    if($txs && $txs['checked'] == 0 && $txs['complete'] == 0){
                        $wallets = Db::name('wallet')->where('user_id',$txs['user_id'])->find();
                        $txs['wallet_price'] = $wallets['price'];
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
                    $txs = Db::name('withdraw')->alias('a')->field('a.*')->join('sp_member b','a.user_id = b.id','INNER')->where('a.id',input('post.id'))->find();
                    if($txs && $txs['checked'] == 1 && $txs['complete'] == 0){
                        $wallets = Db::name('wallet')->where('user_id',$txs['user_id'])->find();
                        if($complete == 1){
                            // 启动事务
                            Db::startTrans();
                            try{
                                Db::name('withdraw')->update(array('complete'=>$complete,'wtime'=>time(),'id'=>input('post.id')));
                                Db::name('detail')->insert(array('de_type'=>2,'zc_type'=>1,'price'=>$txs['price'],'tx_id'=>$txs['id'],'user_id'=>$txs['user_id'],'wat_id'=>$wallets['id'],'time'=>time()));
                                $pt_wallets = Db::name('pt_wallet')->where('id',1)->find();
                                if($pt_wallets){
                                    Db::name('pt_wallet')->where('id',1)->setDec('price', $txs['price']);
                                    Db::name('pt_detail')->insert(array('de_type'=>2,'zc_type'=>1,'price'=>$txs['price'],'tx_type'=>1,'tx_id'=>$txs['id'],'wat_id'=>$pt_wallets['id'],'time'=>time()));
                                }
                                // 提交事务
                                Db::commit();
                                ys_admin_logs('打款成功提现申请','withdraw',input('post.id'));
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
                                    Db::name('withdraw')->update(array('complete'=>$complete,'remarks'=>$remarks,'id'=>input('post.id')));
                                    Db::name('wallet')->where('id',$wallets['id'])->setInc('price', $txs['price']);
                                    // 提交事务
                                    Db::commit();
                                    ys_admin_logs('打款失败提现申请','withdraw',input('post.id'));
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
                        $txs = Db::name('withdraw')->alias('a')->field('a.*,b.user_name,b.phone')->join('sp_member b','a.user_id = b.id','INNER')->where('a.id',$tx_id)->find();
                        if($txs && $txs['checked'] == 1 && $txs['complete'] == 0){
                            $wallets = Db::name('wallet')->where('user_id',$txs['user_id'])->find();
                            $txs['wallet_price'] = $wallets['price'];
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
            $txs = Db::name('withdraw')->alias('a')->field('a.*,b.user_name,b.phone')->join('sp_member b','a.user_id = b.id','INNER')->where('a.id',$tx_id)->find();
            if($txs){
                $wallets = Db::name('wallet')->where('user_id',$txs['user_id'])->find();
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
        if(input('post.keyword') != ''){
            cookie('tx_keyword',input('post.keyword'),7200);
        }else{
            cookie('tx_keyword',null);
        }
        
        if(input('post.tx_zt') != ''){
            cookie("tx_zt", input('post.tx_zt'), 7200);
        }
        
        if(input('post.starttime') != ''){
            $txstarttime = strtotime(input('post.starttime'));
            cookie('txstarttime',$txstarttime,3600);
        }
        
        if(input('post.endtime') != ''){
            $txendtime = strtotime(input('post.endtime'));
            cookie('txendtime',$txendtime,3600);
        }
        
        $where = array();

        if(cookie('tx_zt') != ''){
            $tx_zt = (int)cookie('tx_zt');
            if($tx_zt != 0){
                switch($tx_zt){
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
        
        if(cookie('tx_keyword')){
            $where['a.tx_number'] = cookie('tx_keyword');
        }
        
        if(cookie('txendtime') && cookie('txstarttime')){
            $where['a.time'] = array(array('egt',cookie('txstarttime')), array('lt',cookie('txendtime')));
        }
        
        if(cookie('txstarttime') && !cookie('txendtime')){
            $where['a.time'] = array('egt',cookie('txstarttime'));
        }
        
        if(cookie('txendtime') && !cookie('txstarttime')){
            $where['a.time'] = array('lt',cookie('txendtime'));
        }

        if(in_array(input('post.type'), [1, 2])){
            $where['a.type'] = array('eq',input('post.type'));
            $this->assign('type', input('post.type'));
        }
        
        $list =  Db::name('withdraw')->alias('a')->field('a.*,b.user_name,b.phone')->join('sp_member b','a.user_id = b.id','INNER')->where($where)->order('a.time desc')->paginate(50);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        
        if(cookie('txstarttime') != ''){
            $this->assign('starttime',cookie('txstarttime'));
        }
        
        if(cookie('txendtime') != ''){
            $this->assign('endtime',cookie('txendtime'));
        }
        
        if(cookie('tx_keyword') != ''){
            $this->assign('keyword',cookie('tx_keyword'));
        }
        
        if(cookie('tx_zt') != ''){
            $this->assign('tx_zt',cookie('tx_zt'));
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

}
?>
