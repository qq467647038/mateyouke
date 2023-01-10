<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class WineAppeal extends Common{
    public function lst(){
        $post = input('post.'); $where = [];
        if($post['keyword']){
            $where['wob.odd'] = $post['keyword'];
        }
        
        $list = Db::name('wine_order_buyer')->alias('wob')->field('wob.*,m.user_name sale_user_name,mm.user_name buy_user_name,mm.phone buy_phone,m.phone sale_phone,wg.rate goods_rate,wg.day,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode, mmw.total_stock mmw_total_stock, mmw.brand mmw_brand, mw.total_stock mw_total_stock, mw.brand mw_brand')
            ->join('member mm', 'mm.id=wob.buy_id', 'left')
            ->join('wallet mmw', 'mmw.user_id = wob.buy_id', 'left')
            ->join('wine_goods wg', 'wg.id = wob.wine_goods_id', 'left')
            ->join('bank_card bank', 'bank.user_id = wob.buy_id', 'left')
            ->join('wx_card wx', 'wx.user_id = wob.buy_id', 'left')
            ->join('zfb_card zfb', 'zfb.user_id = wob.buy_id', 'left')
            ->order('id desc')
            ->join('wallet mw', 'mw.user_id = wob.sale_id', 'left')
            ->where($where)
            ->join('member m', 'm.id=wob.sale_id', 'left')->where('wob.delete', 0)->order('wob.goods_name')
            ->where('wob.status', 3)->paginate(25);
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
    
    // 强制成交
    public function confirmAuto(){
        $post = input('post.');
        
        // $exchange_success_brand = Db::name('config')->where('ename', 'exchange_success_brand')->value('value');
        
        $info = Db::name('wine_order_buyer')->where('delete', 0)->where('status', 3)->where('id', $post['id'])->find();
        Db::startTrans();
        $time = time();
        
        try{
            $res = Db::name('wine_order_buyer')->where('id', $info['id'])->update([
                'status'=>2,
                'pay_status'=>1,
                'paytime'=>$time,
                'confirm_exchange' => $time
            ]);
            if (!$res)throw new \Exception($res);
            
            $wine_order_saler_info = Db::name('wine_order_saler')
                ->where('sale_id', $info['sale_id'])
                ->where('delete', 0)
                ->where('status', 1)
                ->where('id', $info['wine_order_saler_id'])->find();
                
            if ($wine_order_saler_info['pipei_amount'] == $wine_order_saler_info['sale_amount']){
                 $income_total_price = Db::name('wine_order_buyer')->where('delete', 0)->where('sale_id', $info['sale_id'])->where('pay_status', 1)
                        ->where('wine_order_saler_id', $wine_order_saler_info['id'])->where('status', 2)->sum('buy_amount');

                 if($income_total_price == $wine_order_saler_info['sale_amount']){
                     $res = Db::name('wine_order_saler')->where('id', $wine_order_saler_info['id'])->update([
                        'status'=>2,
                        'confirm_exchange'=>$time
                     ]);
                     if (!$res)throw new \Exception('转让失败');
                                         
                     $res = Db::name('member')->where('id', $info['buy_id'])->setInc('agent_num');
                     if (!$res)throw new \Exception('转让失败2');
                 }
            }


            Db::commit();
            $value = array('status' => 200, 'mess' => '成交成功', 'data' => array('status' => 200));

        }
        catch (\Exception $e){
            Db::rollback();
            $value = array('status' => 400, 'mess' => '成交失败', 'data' => array('status' => 400));
        }
        
        return json($value);
    }
    
    // 强制取消
    public function cancelAuto(){
        $v = Db::name('wine_order_buyer')->where('status', 3)->where('delete', 0)->find();
        $buy_amount = Db::name('wine_goods')->where('id', $v['wine_goods_id'])->value('goods_desc');

        $addtime = $v['addtime'];
        $time = time();
        // var_dump($v);exit;
        Db::startTrans();
        try{
            $res = Db::name('wine_order_buyer')->where('id', $v['id'])->update([
                'status' => 5
            ]);
            if(!$res)throw new \Exception('取消失败1');
            // var_dump(Db::name('wine_order_saler')->where('id', $v['wine_order_saler_id'])->find());exit;
            $res = Db::name('wine_order_saler')->where('id', $v['wine_order_saler_id'])->where('status', 'in', [1, 3])->update([
                'status' => 0
            ]);
            if(!$res)throw new \Exception('取消失败2');
            
            // 订单超时未付款 冻结时间已过  -  冻结惩罚
            $res = Db::name('member')->where('id', $v['buy_id'])->update([
                'checked' => 0
            ]);
            if(!$res)throw new \Exception('取消失败3');

            
            Db::commit();
            $value = array('status' => 200, 'mess' => '取消成功', 'data' => array('status' => 200));
        }
        catch(\Exception $e){
            Db::rollback();
            $value = array('status' => 400, 'mess' => '取消失败'.$e->getMessage(), 'data' => array('status' => 400));
        }
        
        return json($value);
    }
}
