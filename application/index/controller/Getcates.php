<?php
namespace app\index\controller;
use app\index\controller\Common;
use think\Db;

class Getcates extends Common{
    //列表
    public function lst(){
        $where = array();
        if(input('indus_id')){
            $indus_id = input('indus_id');
            $industrys = Db::name('industry')->where('id',$indus_id)->where('is_show',1)->field('id,cate_id_list')->find();
            if($industrys){
                if(input('goods_id')){
                    $goods_id = input('goods_id');
                    $goodsids = explode(',', $industrys['cate_id_list']);
                    foreach ($goodsids as $k => $v){
                         if(strpos($goods_id, $v) !== false){
                             unset($goodsids[$k]);
                         }
                    }
                }else{
                    $goods_id = '';
                    $goodsids = explode(',', $industrys['cate_id_list']);
                }

                $where['id'] = array('in',$goodsids);
                $where['pid'] = 0;
                $where['is_show'] = 1;
                
                $list = Db::name('category')->where($where)->field('id,cate_name')->order('sort asc')->select();

                $this->assign(array(
                    'list'=>$list,
                    'goods_id'=>$goods_id
                ));
                
                return $this->fetch();
            }else{
                $this->error('找不到相关主营行业');
            }
        }else{
            $this->error('请选择主营行业');
        }
    }
 
}