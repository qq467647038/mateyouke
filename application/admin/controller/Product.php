<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Product extends Common{
    public function lst(){
        if(input('goods_id')){
            $id = input('goods_id');
            $goodsinfo = Db::name('goods')->where('id',$id)->where('shop_id',1)->where('is_recycle',0)->field('id,goods_name')->find();
            if($goodsinfo){
                $_radioAttrRes = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id,a.attr_value,b.attr_name,b.attr_type')->join('sp_attr b','a.attr_id = b.id','LEFT')->where('a.goods_id',$id)->where('b.attr_type',1)->select();
                $radioAttrRes = array();
                if($_radioAttrRes){
                    foreach ($_radioAttrRes as $v){
                        $radioAttrRes[$v['attr_id']][] = $v;
                    }
                }
                $prores = Db::name('product')->where('goods_id',$id)->select();
                $this->assign('prores',$prores);
                $this->assign('goods_name',$goodsinfo['goods_name']);
                $this->assign('goods_id',$id);
                $this->assign('radioAttrRes',$radioAttrRes);
                return $this->fetch();
            }else{
                $this->error('商品参数错误');
            }
        }else{
            $this->error('缺少参数');
        }
    }
}