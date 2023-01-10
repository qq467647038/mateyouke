<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use think\Db;

class GoodsCrowd extends Common
{
    public function lst()
    {
        $shop_id = session('shop_id');
        $list = Db::name('crowd_goods')->alias('a')->field('a.type,a.id,a.goods_name,a.thumb_url,a.market_price,a.shop_price,a.onsale,b.cate_name,vip_price, a.zkj,a.crowd_value,a.pre_sale,a.cur_qi,a.cur_crowd_num,a.addtime')->join('sp_category b', 'a.cate_id = b.id', 'LEFT')->order('a.id desc')->paginate(25);

        $page = $list->render();
        $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
        $brandres = Db::name('brand')->field('id,brand_name')->select();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('cateres', recursive($cateres));
        $this->assign('brandres', $brandres);
        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch('lst');
        }
    }

    public function orderlst(){
        $list = Db::name('crowd_order')->alias('co')->join('crowd_goods cg', 'cg.id = co.goods_id', 'inner')->field('co.*,cg.goods_name')->paginate(25);
        $page = $list->render();
        
        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        if (request()->isAjax()) {
            return $this->fetch('order_ajaxpage');
        } else {
            return $this->fetch('order_lst');
        }
    }























}

?>
