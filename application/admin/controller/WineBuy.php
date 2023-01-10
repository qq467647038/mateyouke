<?php
/**
 * Created by PhpStorm.
 * User: 云
 * Date: 2022/1/17
 * Time: 4:23
 */

namespace app\admin\controller;

use think\Db;

class WineBuy extends Common
{
    public function splitUp(){
        if(request()->isAjax()){
            $input = input();
            
            if(!$input['id'] || !$input['num']){
                $value = array('status'=>400,'mess'=>'数据不能为空');
            }
            else{
                $info = Db::name('wine_order_buyer')->where('top_stop', 0)->where('delete', 0)->where('pay_status', 1)->where('status', 2)->where('day', '>', 0)->where('id', $input['id'])->find();
                if(is_null($info)){
                    $value = array('status'=>400,'mess'=>'数据不能为空');
                }
                else{
                    if($input['num']<=1 || (int)$input['num']!=$input['num']){
                        $value = array('status'=>400,'mess'=>'数据异常');
                    }
                    else{
                        $sale_amount = (int)$info['sale_amount']/$input['num'];
                        // $sale_frozen_fuel = (int)$info['sale_frozen_fuel']/$input['num'];
                        // $frozen_fuel = (int)$info['frozen_fuel']/$input['num'];
                        Db::startTrans();
                        try{
                            for($i=0; $i<$input['num']; $i++){
                                $insert = [];
                                $insert = $info;
                                unset($insert['id']);
                                $insert['sale_amount'] = $sale_amount;
                                $insert['separate_order'] = 1;
                                $insert['pid'] = $info['id'];
                                // $insert['sale_frozen_fuel'] = $sale_frozen_fuel;
                                // $insert['frozen_fuel'] = $frozen_fuel;
                                
                                $res = Db::name('wine_order_buyer')->insert($insert);
                                if(!$res){
                                    throw new Exception('拆分失败');
                                }
                            }
                            
                            $res = Db::name('wine_order_buyer')->where('id', $info['id'])->update([
                                'delete' => 1,
                                'status' => 7
                            ]);
                            if(!$res){
                                throw new Exception('拆分失败');
                            }
                            
                            Db::commit();
                            $value = array('status'=>200,'mess'=>'拆分成功');
                        }
                        catch(Exception $e){
                            Db::rollback();
                            $value = array('status'=>400,'mess'=>'拆分失败');
                        }
                    }
                }
            }
            
            return json($value);
        }
        else{
            $input = input();
            
            $info = Db::name('wine_order_buyer')->where('top_stop', 0)->where('delete', 0)->where('pay_status', 1)->where('status', 2)->where('day', '>', 0)->where('id', $input['id'])->find();
            
            $this->assign('info', $info);
            return $this->fetch();
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
                    ->where('wob.top_stop', 0)
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
    
    public function wineTypeLst($wine_goods_id){
        $post = input('post.'); $where = []; $where1 = [];
        if($post['keyword']){
            $where['wob.buy_id'] = Db::name('member')->where('phone', $post['keyword'])->value('id');
        }
        if($post['keyword']){
            $where1['wob.odd'] = $post['keyword'];
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

        
        $list = Db::name('wine_order_buyer')->alias('wob')->field('wob.*,m.user_name sale_user_name,mm.user_name buy_user_name,mm.phone buy_phone,m.phone sale_phone,wg.rate goods_rate,wg.day,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode, w.total_stock, w.brand, mm.checked')
                    ->where('wob.wine_goods_id', $wine_goods_id)
                    ->join('member mm', 'mm.id=wob.buy_id', 'left')
                    ->join('wine_goods wg', 'wg.id = wob.wine_goods_id', 'left')
                    ->join('bank_card bank', 'bank.user_id = wob.buy_id', 'left')
                    ->join('wx_card wx', 'wx.user_id = wob.buy_id', 'left')
                    ->join('zfb_card zfb', 'zfb.user_id = wob.buy_id', 'left')
                    // ->where($where)
                    ->where(function($query) use($where, $where1) {
                        $query->where($where)->whereOr($where1);
                    })
                    ->join('wallet w', 'w.user_id = wob.buy_id', 'left')
                    ->join('member m', 'm.id=wob.sale_id', 'left')
                    ->where('wob.addtime', 'between time', $whereTime)
                    ->where('wob.delete', 0)->order('wob.id desc')->paginate(25);
        $page = $list->render();
        $today_time = strtotime('today');  
        $total_num = Db::name('wine_order_buyer')->alias('wob')->field('wob.*,m.user_name sale_user_name,mm.user_name buy_user_name,mm.phone buy_phone,m.phone sale_phone,wg.rate goods_rate,wg.day,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode, w.total_stock, w.brand, mm.checked')
                    ->where('wob.wine_goods_id', $wine_goods_id)
                    ->join('member mm', 'mm.id=wob.buy_id', 'left')
                    ->join('wine_goods wg', 'wg.id = wob.wine_goods_id', 'left')
                    ->join('bank_card bank', 'bank.user_id = wob.buy_id', 'left')
                    ->join('wx_card wx', 'wx.user_id = wob.buy_id', 'left')
                    ->join('zfb_card zfb', 'zfb.user_id = wob.buy_id', 'left')
                    // ->where($where)
                    ->where(function($query) use($where, $where1) {
                        $query->where($where)->whereOr($where1);
                    })
                    ->join('wallet w', 'w.user_id = wob.buy_id', 'left')
                    ->join('member m', 'm.id=wob.sale_id', 'left')
                    ->where('wob.addtime', 'between time', $whereTime)
                    ->where('wob.delete', 0)->count();
        $today_num = Db::name('wine_order_buyer')->where('wine_goods_id', $wine_goods_id)->where('delete', 0)->where('addtime', '>=', $today_time)->count();

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
            return $this->fetch('wine_type_ajaxpage');
        } else {
            return $this->fetch('wine_type_lst');
        }
    }
}