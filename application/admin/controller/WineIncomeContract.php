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

class WineIncomeContract extends Common
{
    public function splitUp(){
        if(request()->isAjax()){
            $input = input();
            
            if(!$input['id'] || !$input['num']){
                $value = array('status'=>400,'mess'=>'数据不能为空');
            }
            else{
                $info = Db::name('wine_order_buyer_contract')->where('top_stop', 0)->where('delete', 0)->where('pay_status', 1)->where('status', 2)->where('day', '>', 0)->where('id', $input['id'])->find();
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
                                
                                $res = Db::name('wine_order_buyer_contract')->insert($insert);
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
            
            $info = Db::name('wine_order_buyer_contract')->where('top_stop', 0)->where('delete', 0)->where('pay_status', 1)->where('status', 2)->where('day', '>', 0)->where('id', $input['id'])->find();
            
            $this->assign('info', $info);
            return $this->fetch();
        }
    }
    
    public function lst(){
        $post = input(); $where = []; $where1 = []; $where2 = []; $whereTime=[];
        if($post['keyword']){
            $where['wob.buy_id'] = Db::name('member')->where('phone', $post['keyword'])->value('id');
            $where1['wob.odd'] = $post['keyword'];
            $where3['wob.sale_id'] = $where['wob.buy_id'];
        }
       
        if($post['day']){
            $where2['wob.wine_contract_day_id'] = $post['day'];
        }
        $where_delete = [];
        if(isset($post['delete']) && $post['delete'] > 0){
            $delete =  1;
        }else{
            $delete = $where_delete['wob.delete'] = 0;
        }
        
        if(isset($post['status']) && $post['status'] >= 0){
            $status = $where2['wob.status'] = $post['status'];
        }else{
            $status = -1;
        }
        
        if(isset($post['pay_status']) && $post['pay_status'] >= 0){
            $pay_status = $where2['wob.pay_status'] = $post['pay_status'];
        }else{
            $pay_status = -1;
        }
        
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }
        
        $day_arr = Db::name('wine_contract_day')->column('id, day');
        
        $list = Db::name('wine_order_buyer_contract')->alias('wob')->field('wob.*,m.user_name sale_user_name,m.true_name sale_true_name,mm.true_name buy_true_name,mm.user_name buy_user_name,mm.phone buy_phone,m.phone sale_phone,wg.rate goods_rate,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode, w.total_stock, w.brand, mm.checked, wcd.day_rate,wcd.day wcd_day')
                    ->join('member mm', 'mm.id=wob.buy_id', 'left')
                    ->join('wine_goods_contract wg', 'wg.id = wob.wine_goods_id', 'left')
                    ->join('bank_card bank', 'bank.user_id = wob.buy_id', 'left')
                    ->join('wx_card wx', 'wx.user_id = wob.buy_id', 'left')
                    ->join('wine_contract_day wcd', 'wcd.id = wob.wine_contract_day_id', 'left')
                    ->join('zfb_card zfb', 'zfb.user_id = wob.buy_id', 'left')
                    ->where($where2)
                    ->where(function($query) use($where, $where1,$where3) {
                        $query->where($where)->whereOr($where1)->whereOr($where3);
                    })
                    ->where('wob.top_stop', 0)
                    ->where('wob.addtime', 'between time', $whereTime)
                    ->join('wallet w', 'w.user_id = wob.buy_id', 'left')
                    ->join('member m', 'm.id=wob.sale_id', 'left')->where($where_delete)->order('wob.goods_name')->paginate(25, false, ['query'=>request()->param()]);
                    // echo Db::name('wine_order_buyer_contract')->getLastSql();
        $page = $list->render();
        $today_time = strtotime('today');  
        $total_num = Db::name('wine_order_buyer_contract')->alias('wob')->where($where2)->where($where_delete)->where(function($query) use($where, $where1,$where3) {
                        $query->where($where)->whereOr($where1)->whereOr($where3);
                    })->count();
        $today_num = Db::name('wine_order_buyer_contract')->where($where_delete)->alias('wob')->where($where2)->where(function($query) use($where, $where1,$where3) {
                        $query->where($where)->whereOr($where1)->whereOr($where3);
                    })->where('wob.addtime', '>=', $today_time)->count();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('keyword', $post['keyword']);
        $this->assign('where_time', $whereTime);
        $this->assign('status', $status);
        $this->assign('pay_status', $pay_status);
        $this->assign('delete', $delete);
        $this->assign('list', $list);
        $this->assign('day_arr', $day_arr);
        $this->assign('day', $post['day']);
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
    
    public function lst_export(){
        $post = input(); $where = []; $where1 = []; $where2 = []; $whereTime=[];
        if($post['keyword']){
            $where['wob.buy_id'] = Db::name('member')->where('phone', $post['keyword'])->value('id');
        }
        if($post['keyword']){
            $where1['wob.odd'] = $post['keyword'];
        }
        if($post['day']){
            $where2['wob.wine_contract_day_id'] = $post['day'];
        }
        
        $where_delete = [];
        if(isset($post['delete']) && $post['delete'] > 0){
            $delete =  1;
        }else{
            $delete = $where_delete['wob.delete'] = 0;
        }
        
        if(isset($post['status']) && $post['status'] >= 0){
            $where2['wob.status'] = $post['status'];
        }
        
        if(isset($post['pay_status']) && $post['pay_status'] >= 0){
            $where2['wob.pay_status'] = $post['pay_status'];
        }
        
        
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }
        
        $day_arr = Db::name('wine_contract_day')->column('id, day');
        
        $list = Db::name('wine_order_buyer_contract')->alias('wob')->field('wob.*,m.user_name sale_user_name,m.true_name sale_true_name,mm.true_name buy_true_name,mm.user_name buy_user_name,mm.phone buy_phone,m.phone sale_phone,wg.rate goods_rate,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode, w.total_stock, w.brand, mm.checked, wcd.day_rate,wcd.day wcd_day')
                    ->join('member mm', 'mm.id=wob.buy_id', 'left')
                    ->join('wine_goods_contract wg', 'wg.id = wob.wine_goods_id', 'left')
                    ->join('bank_card bank', 'bank.user_id = wob.buy_id', 'left')
                    ->join('wx_card wx', 'wx.user_id = wob.buy_id', 'left')
                    ->join('wine_contract_day wcd', 'wcd.id = wob.wine_contract_day_id', 'left')
                    ->join('zfb_card zfb', 'zfb.user_id = wob.buy_id', 'left')
                    ->where($where2)
                    ->where(function($query) use($where, $where1) {
                        $query->where($where)->whereOr($where1);
                    })
                    ->where('wob.top_stop', 0)
                    ->where('wob.addtime', 'between time', $whereTime)
                    ->join('wallet w', 'w.user_id = wob.buy_id', 'left')
                    ->join('member m', 'm.id=wob.sale_id', 'left')->where($where_delete)->order('wob.goods_name')->select();
                
        $arr = [];
        array_push($arr, ['A'=>'ID', 'B'=>'单号', 'C'=>'商品名称', 'D'=>'进货价', 'E'=>'比例', 'F'=>'销售价格', 'G'=>'销售者', 'H'=>'销售联系', 'I'=>'进货者',
                    'J'=>'进货联系','K'=>'支付时间','L'=>'支付状态','M'=>'商品状态','N'=>'出售时间','O'=>'转让时间','P'=>'进货开始','Q'=>'天数']);
                    
        foreach($list as $k=>$v){
            array_push($arr, ['A'=>$v['id'], 'B'=>$v['odd'], 'C'=>$v['goods_name'], 'D'=>$v['buy_amount'], 'E'=>$v['day_rate'], 'F'=>$v['sale_amount'], 'G'=>$v['sale_user_name'].'('.$v['sale_true_name'].')', 'H'=>$v['sale_phone'], 'I'=>$v['buy_user_name'].'('.$v['buy_true_name'].')', 'J'=>$v['buy_phone'], 'K'=>$v['paytime']?date('Y-m-d H:i:s', $v['paytime']):'', 'L'=>($v['pay_status']==0?'未支付':($v['pay_status']==1?'已支付':'')), 'M'=>($v['status']==1?'进货中':($v['status']==2?'已进货':'')), 'N'=>($v['sale_addtime']?date('Y-m-d H:i:s', $v['sale_addtime']):''), 'O'=>($v['confirm_exchange']?date('Y-m-d H:i:s', $v['confirm_exchange']):''), 'P'=>($v['addtime']?date('Y-m-d H:i:s', $v['addtime']):''),'Q'=>$v['wcd_day'].'天'.($v['day']>$v['wcd_day']?'*'.$v['day']/$v['wcd_day']:'')]);
        }

        vendor('PHPExcel.Classes.PHPExcel');
    
        $Excel = new \PHPExcel();
        // 设置
        $Excel
            ->getProperties()
            ->setCreator("dee")
            ->setLastModifiedBy("dee")
            ->setKeywords("excel")
            ->setCategory("result file");
    
        foreach($arr as $key => $val) { // 注意 key 是从 0 还是 1 开始，此处是 0
            $num = $key + 1;
    
            $Excel ->setActiveSheetIndex(0)
             //Excel的第A列，uid是你查出数组的键值，下面以此类推
              ->setCellValue('A'.$num, $val['A'])    
              ->setCellValue('B'.$num, $val['B'])
              ->setCellValue('C'.$num, $val['C'])
              ->setCellValue('D'.$num, $val['D'])
              ->setCellValue('E'.$num, $val['E'])
              ->setCellValue('F'.$num, $val['F'])
              ->setCellValue('G'.$num, $val['G'])
              ->setCellValue('H'.$num, $val['H'])
              ->setCellValue('I'.$num, $val['I'])
              ->setCellValue('J'.$num, $val['J'])
              ->setCellValue('K'.$num, $val['K'])
              ->setCellValue('L'.$num, $val['L'])
              ->setCellValue('M'.$num, $val['M'])
              ->setCellValue('N'.$num, $val['N'])
              ->setCellValue('O'.$num, $val['O'])
              ->setCellValue('P'.$num, $val['P'])
              ->setCellValue('Q'.$num, $val['Q']);
        }
    
        $Excel->getActiveSheet()->setTitle('export');
    
        $Excel->setActiveSheetIndex(0);
    
        $name = '进货列表'.date('Y-m-d').'.xlsx';
    
        header('Content-Type: application/vnd.ms-excel');
    
        header('Content-Disposition: attachment; filename='.$name);
    
        header('Cache-Control: max-age=0');
    
        $ExcelWriter = \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');
    
        $ExcelWriter->save('php://output');
    
        exit;
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

        
        $list = Db::name('wine_order_buyer_contract')->alias('wob')->field('wob.*,m.user_name sale_user_name,mm.user_name buy_user_name,mm.phone buy_phone,m.phone sale_phone,wg.rate goods_rate,wg.day,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode, w.total_stock, w.brand, mm.checked')
                    ->where('wob.wine_goods_id', $wine_goods_id)
                    ->join('member mm', 'mm.id=wob.buy_id', 'left')
                    ->join('wine_goods_contract wg', 'wg.id = wob.wine_goods_id', 'left')
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
        $total_num = Db::name('wine_order_buyer_contract')->alias('wob')->field('wob.*,m.user_name sale_user_name,mm.user_name buy_user_name,mm.phone buy_phone,m.phone sale_phone,wg.rate goods_rate,wg.day,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode, w.total_stock, w.brand, mm.checked')
                    ->where('wob.wine_goods_id', $wine_goods_id)
                    ->join('member mm', 'mm.id=wob.buy_id', 'left')
                    ->join('wine_goods_contract wg', 'wg.id = wob.wine_goods_id', 'left')
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
        $today_num = Db::name('wine_order_buyer_contract')->where('wine_goods_id', $wine_goods_id)->where('delete', 0)->where('addtime', '>=', $today_time)->count();

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
    
    public function cancelWineGoods(){
        $input = input();
        
        $info = Db::name('wine_order_buyer_contract')->where('id', $input['id'])->where('pay_status', 0)->where('delete', 0)->where('status', 1)->find();
        if(is_null($info)){
            return 0;
        }
        else{
            Db::startTrans();
            try{
                $res = Db::name('wine_order_buyer_contract')->where('id', $input['id'])->where('pay_status', 0)->where('delete', 0)->where('status', 1)->update([
                    'delete'=>1
                ]);
                if(!$res)throw new Exception('失败1');
                
                $res = Db::name('wine_order_saler_contract')->where('id', $info['wine_order_saler_id'])->where('status', 1)->where('delete', 0)->where('sale_id', $info['sale_id'])->update([
                    'status'=>0
                ]);
                if(!$res)throw new Exception('失败2');
                
                Db::commit();
                return 1;
            }
            catch(Exception $e){
                Db::rollback();
                return 0;
            }
        }
    }
    
    public function zhuanpai(){
        $input = input();
        
        $info = Db::name('wine_order_buyer_contract')->where('status', 2)->where('pay_status', 1)
        // ->where('day', 0)
        ->where('transfer', 0)
        ->where('id', $input['id'])->where('delete', 0)->where('top_stop', 0)->find();
        if(is_null($info)){
            $value = array('status'=>400,'mess'=>'参数异常');
            return 0;
        }
        else{
            $res = Db::name('wine_order_buyer_contract')->where('status', 2)->where('pay_status', 1)->where('transfer', 0)->where('id', $input['id'])->where('delete', 0)->where('top_stop', 0)->update([
                'transfer'=>1,
                'transfer_wine_contract_day_id'=>$info['wine_contract_day_id'],
                'sale_amount'=>$info['buy_amount'],
                'sale_addtime'=>time(),
                'zhuanpai'=>1
            ]);
            
            if($res){
                ys_admin_logs('转拍', 'wine_order_buyer_contract', $input['id']);
                $value = array('status'=>1,'mess'=>'转拍成功');
                return 1;
            }
            else{
                $value = array('status'=>400,'mess'=>'转拍失败');
                return 0;
            }
        }
        
        return json($value);
    }
    
    public function jinpaiEdit()
    {
        $id = input('id');
        $phone = input('phone');
        $rewards = Db::name('wine_order_buyer_contract')->where('id', $id)->find();
        if (!$rewards || $rewards['pay_status']<>0) {
            $value = array('status'=>400,'mess'=>'该订单不可修改');

            return json($value);
        }
        if(request()->isAjax()){
            if (empty($phone)) {
                $value = array('status'=>400,'mess'=>'手机号不能为空');
            }
            $info = Db::name('member')->where('phone', $phone)->find();
            if ($info) {
                $data['buy_id']  = $info['id'];
                $data['addtime'] = time();
                $res = Db::name('wine_order_buyer_contract')->where('id', $id)->update($data);
                if ($res) {
                    $value = array('status'=>1,'mess'=>'修改成功');
                } else {
                    $value = array('status'=>400,'mess'=>'修改失败');
                }
            } else {
                $value = array('status'=>400,'mess'=>'手机号没有注册');
            }
            return json($value);
        } else {
            $this->assign('info', $rewards);
            return $this->fetch();
        }

    }
}