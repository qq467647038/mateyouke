<?php
/**
 * Created by PhpStorm.
 * User: 云
 * Date: 2022/1/17
 * Time: 4:23
 */

namespace app\admin\controller;

use think\Db;

class WineDistribution extends Common
{
    public function verify(){
        $post = input('post.');
        
        if(isset($post['token']) && !empty($post['token'])){
            if(md5($post['token']) == '89c6120e148a50f104f3494e76043354'){
                return 1;
            }
        }
        
        return 0;
    }
    
    public function verify1(){
        $post = input('post.');
        
        if(isset($post['token']) && !empty($post['token'])){
            if(md5($post['token']) == '1359d2f583888d77c3de2a7193ca471b'){
                return 1;
            }
        }
        
        return 0;
    }
    
    // public function lst(){
    //     $post = input('post.'); $where = []; $where1 = [];
    //     if($post['keyword']){
    //         $where['wobam.buy_id'] = Db::name('member')->where('phone', $post['keyword'])->value('id');
    //     }
    //     if($post['keyword']){
    //         $where1['wobam.odd'] = $post['keyword'];
    //     }

    //     $list = Db::name('wine_order_buyer_advance_match')->alias('wobam')
    //             ->join('bank_card bank', 'bank.user_id = wobam.buy_id', 'left')
    //             ->join('wx_card wx', 'wx.user_id = wobam.buy_id', 'left')
    //             ->join('zfb_card zfb', 'zfb.user_id = wobam.buy_id', 'left')
    //             ->where(function($query) use($where, $where1) {
    //                 $query->where($where)->whereOr($where1);
    //             })
    //             ->where('wobam.delete', 0)
    //             ->field('wobam.*,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode, w.total_stock, w.brand, w.credit_value')
    //             ->join('wallet w', 'w.user_id = wobam.buy_id', 'left')
    //             ->join('member m', 'm.id=wobam.buy_id', 'inner')->order('wobam.goods_name')->paginate(25)->each(function ($item, $index){
    //         $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
    //         return $item;
    //     });
    //     $page = $list->render();
    //     $today_time = strtotime('today');  
    //     $total_num = Db::name('wine_order_buyer_advance_match')->count();
    //     $today_num = Db::name('wine_order_buyer_advance_match')->where('addtime', '>=', $today_time)->count();

    //     if (input('page')) {
    //         $pnum = input('page');
    //     } else {
    //         $pnum = 1;
    //     }

    //     $this->assign('keyword', $post['keyword']);
    //     $this->assign('list', $list);
    //     $this->assign('page', $page);
    //     $this->assign('pnum', $pnum);
    //     $this->assign('today_num', $today_num);
    //     $this->assign('total_num', $total_num);
    //     if (request()->isAjax()) {
    //         return $this->fetch('ajaxpage');
    //     } else {
    //         return $this->fetch('lst');
    //     }
    // }
    public function lst(){
        $post = input('post.'); $where = []; $where1 = [];
        if($post['keyword']){
            $where['wobam.buy_id'] = Db::name('member')->where('phone', $post['keyword'])->value('id');
        }
        if($post['keyword']){
            $where1['wobam.odd'] = $post['keyword'];
        }
        
        $list = Db::name('wine_order_buyer_advance_match')->alias('wobam')->field('wobam.*,m.user_name sale_user_name,mm.user_name buy_user_name,mm.phone buy_phone,m.phone sale_phone,wg.rate goods_rate,wg.day,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode, w.total_stock, w.brand, mm.checked')
                    ->join('member mm', 'mm.id=wobam.buy_id', 'left')
                    ->join('wine_goods wg', 'wg.id = wobam.wine_goods_id', 'left')
                    ->join('bank_card bank', 'bank.user_id = wobam.buy_id', 'left')
                    ->join('wx_card wx', 'wx.user_id = wobam.buy_id', 'left')
                    ->join('zfb_card zfb', 'zfb.user_id = wobam.buy_id', 'left')
                    // ->where($where)
                    ->where(function($query) use($where, $where1) {
                        $query->where($where)->whereOr($where1);
                    })
                    ->join('wallet w', 'w.user_id = wobam.buy_id', 'left')
                    ->join('member m', 'm.id=wobam.sale_id', 'left')->where('wobam.delete', 0)->order('wobam.goods_name')->paginate(25);
        $page = $list->render();
        $today_time = strtotime('today');  
        $total_num = Db::name('wine_order_buyer_advance_match')->where('delete', 0)->count();
        $today_num = Db::name('wine_order_buyer_advance_match')->where('delete', 0)->where('addtime', '>=', $today_time)->count();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('keyword', $post['keyword']);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('today_num', $today_num);
        $this->assign('total_num', $total_num);
        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch('lst');
        }
    }
    
    // public function wineTypeLst($wine_goods_id){
    //     $post = input('post.'); $where = []; $where1 = [];
    //     if($post['keyword']){
    //         $where['wobam.buy_id'] = Db::name('member')->where('phone', $post['keyword'])->value('id');
    //     }
    //     if($post['keyword']){
    //         $where1['wobam.odd'] = $post['keyword'];
    //     }
        
    //     $list = Db::name('wine_order_buyer_advance_match')->alias('wobam')
    //             ->where('wobam.wine_goods_id', $wine_goods_id)
    //             // ->join('wine_goods wg', 'wg.id = wobam.wine_goods_id', 'left')
    //             ->join('bank_card bank', 'bank.user_id = wobam.buy_id', 'left')
    //             ->join('wx_card wx', 'wx.user_id = wobam.buy_id', 'left')
    //             ->join('zfb_card zfb', 'zfb.user_id = wobam.buy_id', 'left')
                
    //             // ->join('wine_order_buyer wob', 'wob.wine_order_record_id = wor.id', 'left')
    //             ->field('wobam.*,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode,w.total_stock, w.brand, w.credit_value')
    //             ->join('wallet w', 'w.user_id = wobam.buy_id', 'left')
    //             ->where(function($query) use($where, $where1) {
    //                 $query->where($where)->whereOr($where1);
    //             })
    //             ->where('wobam.delete', 0)
    //             ->join('member m', 'm.id=wobam.buy_id', 'inner')->order('wobam.id desc')->paginate(25)->each(function ($item, $index){
    //         $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
    //         return $item;
    //     });

    //     $page = $list->render();
    //     $today_time = strtotime('today');  
    //     $total_num = Db::name('wine_order_buyer_advance_match')->where('wine_goods_id', $wine_goods_id)->count();
    //     $today_num = Db::name('wine_order_buyer_advance_match')->where('wine_goods_id', $wine_goods_id)->where('addtime', '>=', $today_time)->count();

    //     if (input('page')) {
    //         $pnum = input('page');
    //     } else {
    //         $pnum = 1;
    //     }

    //     $this->assign('keyword', $post['keyword']);
    //     $this->assign('list', $list);
    //     $this->assign('page', $page);
    //     $this->assign('pnum', $pnum);
    //     $this->assign('today_num', $today_num);
    //     $this->assign('total_num', $total_num);
    //     if (request()->isAjax()) {
    //         return $this->fetch('ajaxpage');
    //     } else {
    //         return $this->fetch('lst');
    //     }
    // }
    
    
    
    public function wineTypeLst($wine_goods_id){
        $post = input('post.'); $where = []; $where1 = [];
        if($post['keyword']){
            $where['wobam.buy_id'] = Db::name('member')->where('phone', $post['keyword'])->value('id');
        }
        if($post['keyword']){
            $where1['wobam.odd'] = $post['keyword'];
        }
        
        $list = Db::name('wine_order_buyer_advance_match')->alias('wobam')->field('wobam.*,m.user_name sale_user_name,mm.user_name buy_user_name,mm.phone buy_phone,m.phone sale_phone,wg.rate goods_rate,wg.day,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode, w.total_stock, w.brand, mm.checked')
                    ->where('wobam.wine_goods_id', $wine_goods_id)
                    ->join('member mm', 'mm.id=wobam.buy_id', 'left')
                    ->join('wine_goods wg', 'wg.id = wobam.wine_goods_id', 'left')
                    ->join('bank_card bank', 'bank.user_id = wobam.buy_id', 'left')
                    ->join('wx_card wx', 'wx.user_id = wobam.buy_id', 'left')
                    ->join('zfb_card zfb', 'zfb.user_id = wobam.buy_id', 'left')
                    // ->where($where)
                    ->where(function($query) use($where, $where1) {
                        $query->where($where)->whereOr($where1);
                    })
                    ->join('wallet w', 'w.user_id = wobam.buy_id', 'left')
                    ->join('member m', 'm.id=wobam.sale_id', 'left')->where('wobam.delete', 0)->order('wobam.id desc')->paginate(25);
        $page = $list->render();
        $today_time = strtotime('today');  
        $total_num = Db::name('wine_order_buyer_advance_match')->where('wine_goods_id', $wine_goods_id)->where('delete', 0)->count();
        $today_num = Db::name('wine_order_buyer_advance_match')->where('wine_goods_id', $wine_goods_id)->where('delete', 0)->where('addtime', '>=', $today_time)->count();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('keyword', $post['keyword']);
        $this->assign('wine_goods_id', $wine_goods_id);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('today_num', $today_num);
        $this->assign('total_num', $total_num);
        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch('lst');
        }
    }
}