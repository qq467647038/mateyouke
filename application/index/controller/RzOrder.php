<?php
namespace app\index\controller;
use app\index\controller\Common;
use think\Db;

class RzOrder extends Common{

    public function index(){
        if(session('user_id')){
            $user_id = session('user_id');
            $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->order('apply_time desc')->find();
            if($applyinfos){
                if($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                    $rzorders = Db::name('rz_order')->where('user_id',$user_id)->where('apply_id',$applyinfos['id'])->field('id,state')->find();
                    if($rzorders && $rzorders['state'] == 0){
                        $industrys = Db::name('industry')->where('id',$applyinfos['indus_id'])->where('is_show',1)->field('id,industry_name')->find();
                        if($industrys){
                            // 启动事务
                            Db::startTrans();
                            try{
                                Db::name('rz_order')->update(array('id'=>$rzorders['id'],'state'=>1,'pay_time'=>time()));
                                Db::name('apply_info')->update(array('state'=>1,'pay_time'=>time(),'id'=>$applyinfos['id']));
            
                                // 提交事务
                                Db::commit();
                                return $this->fetch();
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $this->redirect('index/index');
                            }
                        }else{
                            $this->redirect('index/index');;
                        }
                    }else{
                        $this->redirect('index/index');;
                    }
                }else{
                    $this->redirect('index/index');;
                }
            }else{
                $this->redirect('index/index');;
            }
        }else{
            $this->redirect('login/index');
        }
    }
    
   
}
