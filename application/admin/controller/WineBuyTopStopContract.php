<?php
/**
 * Created by PhpStorm.
 * User: 云
 * Date: 2022/1/17
 * Time: 4:23
 */

namespace app\admin\controller;

use think\Db;
use think\Exception;

class WineBuyTopStopContract extends Common
{
    public function send(){
        $post = input();
        
        $list = Db::name('wine_to_inkind_contract')->order('id desc')->paginate(50, false, ['query'=>request()->param()]);
        $count = Db::name('wine_to_inkind_contract')->count();
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
            'count'=>$count
        ));
        if(request()->isAjax()){
            return $this->fetch('send_ajaxpage');
        }else{
            return $this->fetch('send_lst');
        }
    }
    
    public function send_liji(){
        $input = input();
        
        $info = Db::name('wine_to_inkind_contract')->where('id', $input['id'])->where('status', 0)->find();
        if(is_null($info)){
            return 0;
        }
        else{
            Db::startTrans();
            try{
                $res = Db::name('wine_order_buyer_contract')->where('id', $info['wine_order_buyer_id'])->update([
                    'delete' => 1
                ]);
                if(!$res)throw new Exception('失败1');
                
                $res = Db::name('wine_to_inkind_contract')->where('id', $info['id'])->update([
                    'status' => 1
                ]);
                if(!$res)throw new Exception('失败2');

                
                Db::commit();
                return 1;
            }
            catch(Exception $e){
                Db::rollback();
                // echo $e->getMessage();exit;
                return 0;
            }
        }
    }
}