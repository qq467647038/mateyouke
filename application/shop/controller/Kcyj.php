<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;

class Kcyj extends Common{
    public function lst(){
        $shop_id = session('shopsh_id');
        $webconfig = $this->webconfig;
        $product_zhi = $webconfig['productyjzhi']; 
        $list = Db::name('product')->alias('a')->field('a.id,a.goods_id,a.goods_number,a.goods_attr,b.goods_name')->join('sp_goods b','a.goods_id = b.id','LEFT')->where('a.shop_id',$shop_id)->where('a.goods_number','elt',$product_zhi)->paginate(25);
        $page = $list->render();
        
        $listres = $list->toArray();
        $list = $listres['data'];

        foreach ($list as $k =>$v){
            if($v['goods_attr']){
                $list[$k]['goods_attr_str'] = '';
                $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$v['goods_attr'])->where('a.goods_id',$v['goods_id'])->where('b.attr_type',1)->select();
                if($gares){
                    foreach ($gares as $key => $val){
                        if($key == 0){
                            $list[$k]['goods_attr_str'] = $val['attr_name'].':'.$val['attr_value'];
                        }else{
                            $list[$k]['goods_attr_str'] = $list[$k]['goods_attr_str'].' '.$val['attr_name'].':'.$val['attr_value'];
                        }
                    }
                }
            }else{
                $gares = array();
                $list[$k]['goods_attr_str'] = '';
            }
        }
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('pnum',$pnum);
        
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
}