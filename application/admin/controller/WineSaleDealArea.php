<?php
/**
 * Created by PhpStorm.
 * User: 云
 * Date: 2022/1/17
 * Time: 4:23
 */

namespace app\admin\controller;

use EasyWeChat\Kernel\Exceptions\Exception;
use think\Db;

class WineSaleDealArea extends Common{
    
    public function lst($deal_area_id = 0){
        $post = input(); $where = []; $where1 = []; $where2 = []; $whereTime=[];
        if($post['keyword']){
            $where['wos.sale_id'] = Db::name('member')->where('phone', $post['keyword'])->value('id');
        }
        if($post['keyword']){
            $where1['wos.odd'] = $post['keyword'];
        }
        $status = -1;
        if(isset($post['status'])){
            $status = $where2['wos.status'] = $post['status'];
        }
        
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }
        
        $list = Db::name('wine_order_saler')->alias('wos')
                ->where('wos.wine_deal_area_id', $deal_area_id)
                ->join('bank_card bank', 'bank.user_id = wos.sale_id', 'left')
                ->join('wx_card wx', 'wx.user_id = wos.sale_id', 'left')
                ->join('zfb_card zfb', 'zfb.user_id = wos.sale_id', 'left')
                ->join('wine_goods wg', 'wg.id = wos.wine_goods_id', 'left')
                ->field('wos.*,m.user_name,m.emergency_phone,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,wg.day,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode,m.checked, w.total_stock, w.brand, wg.adopt,wob.buy_id, bm.phone bm_phone, bm.emergency_phone bm_emergency_phone')
                ->join('wallet w', 'w.user_id = wos.sale_id', 'left')
                ->where(function($query) use($where, $where1) {
                    $query->where($where)->whereOr($where1);
                })
                ->where($where2)
                ->where('wos.addtime', 'between time', $whereTime)
                // ->where('wos.id', 1)
                ->join('member m', 'm.id=wos.sale_id', 'inner')
                ->join('wine_order_buyer wob', 'wob.wine_order_saler_id=wos.id', 'left')
                ->join('member bm', 'bm.id = wob.buy_id', 'left')
                // ->join('member bm', 'bm.id = wos')
                ->where('wos.delete', 0)->order('wos.goods_name, id desc')->paginate(25, false, ['query'=>request()->param()])->each(function ($item){
                    $adopt = explode('-', $item['adopt']);
                    $endtime = explode(':', $adopt[0]);
                    $item['endtime'] = date('Y-m-d', $item['addtime']+86400).' '.$endtime[0].':'.$endtime[1].':00';
                    return $item;
                });
                
                // echo Db::name('wine_order_saler')->getLastSql();exit;
        $today_time = strtotime('today');        
        $total_num = Db::name('wine_order_saler')->where('delete', 0)->count();
        $today_num = Db::name('wine_order_saler')->where('delete', 0)->where('addtime', '>=', $today_time)->count();
        $page = $list->render();
        
        $wine_goods = Db::name('wine_goods')->where('onsale', 1)->select();
        
        $deal_area = Db::name('wine_deal_area')->select();
        
        $manager_member_list = Db::name('member')->where('frozen', 0)->where('checked', 1)->where('add_way', 1)->where('admin_id', '>', 0)->select();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('keyword', $post['keyword']);
        $this->assign('status', $status);
        $this->assign('deal_area_id', $deal_area_id);
        $this->assign('where_time', $whereTime);
        $this->assign('wine_goods', $wine_goods);
        $this->assign('deal_area', $deal_area);
        $this->assign('manager_member_list', $manager_member_list);
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
