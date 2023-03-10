<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use think\Db;

class GoodsCrowd extends Common
{
    public function desclst(){
        $list = Db::name('crowd_goods')->order('id desc')->paginate(25);

        $page = $list->render();

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
            return $this->fetch('desc_ajaxpage');
        } else {
            return $this->fetch('desclst');
        }
    }
    
    public function lst()
    {
        $shop_id = session('shop_id');

        $list = Db::name('crowd_goods')->alias('a')->where('id', function($query){
            $query->table('sp_crowd_goods')->field('max(id)')->where('crowd_mark', Db::raw('a.crowd_mark'));
        })->order('a.id desc')->field('a.id,a.goods_name,a.thumb_url,a.sy,a.status,a.cur_crowd_num,a.limit_buy')->paginate(25);

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

    public function recycle()
    {
        $id = input('id');
        $shop_id = session('shop_id');
        if (!empty($id) && !is_array($id)) {
            $goods = Db::name('crowd_goods')->where('id', $id)->find();
            if ($goods) {
                // ????????????
                Db::startTrans();
                try {
                    if($goods['cur_crowd_num'] > 0){
                        throw new \Exception('????????????????????????');
                    }
                    
                    $res = Db::name('crowd_goods')->where('id', $id)->where('cur_crowd_num', 0)->delete();
                    if(!$res){
                        throw new \Exception('????????????');
                    }
                    
                    // ????????????
                    Db::commit();
                    // ys_admin_logs('?????????????????????', 'goods', $id);
                    $value = array('status' => 1, 'mess' => '????????????');
                } catch (\Exception $e) {
                    // ????????????
                    Db::rollback();
                    $value = array('status' => 0, 'mess' => '????????????');
                }
            } else {
                $value = array('status' => 0, 'mess' => '?????????????????????');
            }
        } else {
            $value = array('status' => 0, 'mess' => '?????????????????????');
        }
        return json($value);
    }





















}

?>
