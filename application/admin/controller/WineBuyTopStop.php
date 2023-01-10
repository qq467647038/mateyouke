<?php
/**
 * Created by PhpStorm.
 * User: 云
 * Date: 2022/1/17
 * Time: 4:23
 */

namespace app\admin\controller;

use think\Db;
use think\Exception;

class WineBuyTopStop extends Common
{
    public function send(){
        $post = input();
        
        $list = Db::name('wine_to_inkind')->order('id desc')->paginate(50, false, ['query'=>request()->param()]);
        $count = Db::name('wine_to_inkind')->count();
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum,
            'count'=>$count
        ));
        if(request()->isAjax()){
            return $this->fetch('send_ajaxpage');
        }else{
            return $this->fetch('send_lst');
        }
    }
    
    public function send_liji(){
        $input = input();
        
        $info = Db::name('wine_to_inkind')->where('id', $input['id'])->where('status', 0)->find();
        if(is_null($info)){
            return 0;
        }
        else{
            Db::startTrans();
            try{
                $res = Db::name('wine_order_buyer')->where('id', $info['wine_order_buyer_id'])->update([
                    'delete' => 1
                ]);
                if(!$res)throw new Exception('失败1');
                
                $res = Db::name('wine_to_inkind')->where('id', $info['id'])->update([
                    'status' => 1
                ]);
                if(!$res)throw new Exception('失败2');

                
                Db::commit();
                return 1;
            }
            catch(Exception $e){
                Db::rollback();
                // echo $e->getMessage();exit;
                return 0;
            }
        }
    }
    
    public function lst(){
        $post = input(); $where = []; $where1 = []; $whereTime=[];
        if($post['keyword']){
            $where['wob.buy_id'] = Db::name('member')->where('phone', $post['keyword'])->value('id');
        }
        if($post['keyword']){
            $where1['wob.odd'] = $post['keyword'];
        }
        
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }
        
        $list = Db::name('wine_order_buyer')->alias('wob')->field('wob.*,m.user_name sale_user_name,mm.user_name buy_user_name,mm.phone buy_phone,m.phone sale_phone,wg.rate goods_rate,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode, w.total_stock, w.brand, mm.checked')
                    ->join('member mm', 'mm.id=wob.buy_id', 'left')
                    ->join('wine_goods wg', 'wg.id = wob.wine_goods_id', 'left')
                    ->join('bank_card bank', 'bank.user_id = wob.buy_id', 'left')
                    ->join('wx_card wx', 'wx.user_id = wob.buy_id', 'left')
                    ->join('zfb_card zfb', 'zfb.user_id = wob.buy_id', 'left')
                    // ->where($where)
                    ->where(function($query) use($where, $where1) {
                        $query->where($where)->whereOr($where1);
                    })
                    ->where('wob.top_stop', 1)
                    ->where('wob.addtime', 'between time', $whereTime)
                    ->join('wallet w', 'w.user_id = wob.buy_id', 'left')
                    ->join('member m', 'm.id=wob.sale_id', 'left')->where('wob.delete', 0)->order('wob.goods_name')->paginate(25, false, ['query'=>request()->param()]);
        $page = $list->render();
        $today_time = strtotime('today');  
        $total_num = Db::name('wine_order_buyer')->where('delete', 0)->count();
        $today_num = Db::name('wine_order_buyer')->where('delete', 0)->where('addtime', '>=', $today_time)->count();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('keyword', $post['keyword']);
        $this->assign('where_time', $whereTime);
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