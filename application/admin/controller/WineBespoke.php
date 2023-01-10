<?php
/**
 * Created by PhpStorm.
 * User: 云
 * Date: 2022/1/17
 * Time: 4:23
 */

namespace app\admin\controller;

use think\Db;
use app\admin\model\WineBespoke as WineBespokeModel;

class WineBespoke extends Common
{
    // public function lst(){
    //     $post = input(); $where = []; $where1 = []; $whereTime=[];
    //     if($post['keyword']){
    //         $where['wor.buy_id'] = Db::name('member')->where('phone', $post['keyword'])->value('id');
    //     }
    //     if($post['keyword']){
    //         $where1['wor.odd'] = $post['keyword'];
    //     }
    //     if($post['startDate']){
    //         $whereTime = [$post['startDate'], $post['endDate']];
    //     }
    //     else{
    //         $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
    //     }

    //     $list = WineBespokeModel::with(['wineBespoke'=>function ($query){
    //                 $query->where('status', 0)->field('buy_id, goods_name');
    //             }, 'wineDistribution'=>function($query){
    //                 $query->where('delete', 0)->where('status', 1)->field('buy_id, goods_name');
    //             }])->alias('wor')
    //             ->join('wine_goods wg', 'wg.id = wor.wine_goods_id', 'left')
    //             ->join('bank_card bank', 'bank.user_id = wor.buy_id', 'left')
    //             ->join('wx_card wx', 'wx.user_id = wor.buy_id', 'left')
    //             ->join('zfb_card zfb', 'zfb.user_id = wor.buy_id', 'left')
    //             ->join('wine_order_buyer wob', 'wob.wine_order_record_id = wor.id', 'left')
    //             ->where(function($query) use($where, $where1) {
    //                 $query->where($where)->whereOr($where1);
    //             })
    //             ->where('wor.addtime', 'between time', $whereTime)
    //             ->field('wor.*,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode,`wob`.`addtime` match_time, w.total_stock, w.brand,m.user_name,wg.adopt, w.credit_value')
    //             ->join('wallet w', 'w.user_id = wor.buy_id', 'left')
    //             ->join('member m', 'm.id=wor.buy_id', 'inner')->order('wor.goods_name')->paginate(25, false, ['query'=>request()->param()])->each(function ($item, $index){
    //         $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
    //         if($item['match_time'] > 0){
    //             $item['match_time'] = date('Y-m-d H:i:s', $item['match_time']);
    //         }
    //         return $item;
    //     });
        
    //     $page = $list->render();
    //     $today_time = strtotime('today');  
    //     $total_num = Db::name('wine_order_record')->count();
    //     $today_num = Db::name('wine_order_record')->where('addtime', '>=', $today_time)->count();

    //     if (input('page')) {
    //         $pnum = input('page');
    //     } else {
    //         $pnum = 1;
    //     }

    //     $this->assign('keyword', $post['keyword']);
    //     $this->assign('where_time', $whereTime);
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
        $list = Db::name('wine_deal_area')->order('id asc')->select();
        
        for($i=0; $i<count($list); $i++){
            $list[$i]['count'] = Db::name('wine_order_record')->where('wine_deal_area_id', $list[$i]['id'])->where('addtime', '>=', strtotime('today'))->count();
        }
        
        $this->assign('list', $list);
        return $this->fetch('lst');
    }
    
    public function wineTypeLst($wine_goods_id){
        $post = input('post.'); $where = []; $where1 = [];
        if($post['keyword']){
            $where['wor.buy_id'] = Db::name('member')->where('phone', $post['keyword'])->value('id');
        }
        if($post['keyword']){
            $where1['wor.odd'] = $post['keyword'];
        }
        
        if($post['zfb']){
            $where1['zfb.name'] = $post['zfb'];
        }
        if($post['wx']){
            $where1['wx.name'] = $post['wx'];
        }
        if($post['card_name']){
            $where1['bank.name'] = $post['card_name'];
        }
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }
        
//        $list = Db::name('wine_order_record')->order('id desc')->paginate(25);
        $list = WineBespokeModel::with(['wineBespoke'=>function ($query){
                    $query->where('status', 0);
                }, 'wineDistribution'=>function($query){
                    $query->where('delete', 0)->where('status', 1)->field('buy_id, goods_name');
                }])->alias('wor')
                // ->field('wor.*,m.user_name,wg.adopt')
                ->where('wor.wine_goods_id', $wine_goods_id)
                ->join('wine_goods wg', 'wg.id = wor.wine_goods_id', 'left')
                ->join('bank_card bank', 'bank.user_id = wor.buy_id', 'left')
                ->join('wx_card wx', 'wx.user_id = wor.buy_id', 'left')
                ->join('zfb_card zfb', 'zfb.user_id = wor.buy_id', 'left')
                ->join('wine_order_buyer wob', 'wob.wine_order_record_id = wor.id', 'left')
                ->field('wor.*,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode,`wob`.`addtime` match_time, w.total_stock, w.brand, w.credit_value,m.user_name,wg.adopt')
                ->join('wallet w', 'w.user_id = wor.buy_id', 'left')
                ->where(function($query) use($where, $where1) {
                    $query->where($where)->whereOr($where1);
                })->where('wor.addtime', 'between time', $whereTime)
                ->join('member m', 'm.id=wor.buy_id', 'inner')->order('wor.id desc')->paginate(25)->each(function ($item, $index){
            $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
            if($item['match_time'] > 0){
                $item['match_time'] = date('Y-m-d H:i:s', $item['match_time']);
            }
            
            return $item;
        });

        $page = $list->render();
        $today_time = strtotime('today');  
        $total_num = WineBespokeModel::with(['wineBespoke'=>function ($query){
                    $query->where('status', 0);
                }, 'wineDistribution'=>function($query){
                    $query->where('delete', 0)->where('status', 1)->field('buy_id, goods_name');
                }])->alias('wor')
                // ->field('wor.*,m.user_name,wg.adopt')
                ->where('wor.wine_goods_id', $wine_goods_id)
                ->join('wine_goods wg', 'wg.id = wor.wine_goods_id', 'left')
                ->join('bank_card bank', 'bank.user_id = wor.buy_id', 'left')
                ->join('wx_card wx', 'wx.user_id = wor.buy_id', 'left')
                ->join('zfb_card zfb', 'zfb.user_id = wor.buy_id', 'left')
                ->join('wine_order_buyer wob', 'wob.wine_order_record_id = wor.id', 'left')
                ->field('wor.*,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode,`wob`.`addtime` match_time, w.total_stock, w.brand, w.credit_value,m.user_name,wg.adopt')
                ->join('wallet w', 'w.user_id = wor.buy_id', 'left')->where('wor.addtime', 'between time', $whereTime)
                ->where(function($query) use($where, $where1) {
                    $query->where($where)->whereOr($where1);
                })
                ->join('member m', 'm.id=wor.buy_id', 'inner')->count();
        $today_num = Db::name('wine_order_record')->where('wine_goods_id', $wine_goods_id)->where('addtime', '>=', $today_time)->count();

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
}