<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Getcates extends Common{
    //列表
    public function lst(){
        $where = array();
        if(input('goods_id')){
            $goods_id = input('goods_id');
            $where['id'] = array('not in',$goods_id);
        }else{
            $goods_id = '';
        }
        
        $where['pid'] = 0;
        $where['is_show'] = 1;
        
        $list = Db::name('category')->where($where)->field('id,cate_name')->order('sort asc')->select();
        
        $this->assign(array(
            'list'=>$list,
            'goods_id'=>$goods_id
        ));
        
        return $this->fetch();
    }
 
}