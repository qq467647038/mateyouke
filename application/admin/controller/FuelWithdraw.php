<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class FuelWithdraw extends Common{
    //广告列表
    public function lst(){
        $list = Db::name('fuel_withdraw_way')->order('id desc')->paginate(25);
        $usdt_to_fuel = Db::name('config')->where('ename', 'usdt_to_fuel')->value('value');
        
        $data = $list->toArray()['data'];
        foreach ($data as &$v){
            if($v['type']==0)
            {
                $v['to_amount'] = sprintf('%.2f', $v['num']/$usdt_to_fuel);
            }
            else{
                $v['to_amount'] = $v['num'];
            }
        }
        
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign('pnum',$pnum);
        $this->assign('list',$list);
        $this->assign('data',$data);
        $this->assign('page',$page);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch();
        }
    }
    
    public function pass(){
        $info = Db::name('fuel_withdraw_way')->where('id', input('post.id'))->find();
        if($info['status']==1){
            return 0;
        }
        
        $res = Db::name('fuel_withdraw_way')->where('id', input('post.id'))->update([
            'status'=>1,
            'updatetime'=>time()
        ]);
        
        if($res){
            return 1;
        }
        else{
            return 0;
        }
    }
    
}