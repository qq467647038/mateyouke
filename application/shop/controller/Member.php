<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;

class Member extends Common{

    public function blacklst(){
        $shop_id = session('shopsh_id');
        $list = Db::name('alive_room_block')->alias('a')
        ->field('a.*,b.user_name,b.phone')
        ->join('sp_member b','a.user_id = b.id','LEFT')
        ->where('a.shop_id',$shop_id)->order('a.add_time desc')->select();
        //$page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign('pnum',$pnum);// 赋值分页输出
        $this->assign('list',$list);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('blacklst');
        }
    }

    public function delblack(){
        if(input('id') && !is_array(input('id'))){
            $shop_id = session('shopsh_id');
            $id = input('id');
            $block = Db::name('alive_room_block')->where('id',$id)->where('shop_id',$shop_id)->field('id')->find();
            if($block){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('alive_room_block')->where('id',$id)->delete();
                    // 提交事务
                    Db::commit();
                    $value = array('status'=>1,'mess'=>'删除成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'操作失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'删除失败');
        }
        return json($value);
    }
    
}
