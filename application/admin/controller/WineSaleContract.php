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
use app\admin\model\WineBespoke as WineBespokeModel;

class WineSaleContract extends Common
{
    public function onkey_xiajia(){
        $deal_area = Db::name('wine_contract_day')->column('id, deal_area');
        
        foreach ($deal_area as $k=>$v){
            $timearea = explode('-', $v);
            
            $starttime = strtotime(date('Y-m-d'). ' ' .trim($timearea[0]));
            $endtime = strtotime(date('Y-m-d'). ' ' .trim($timearea[1]));
            
            $time = time();
            if($time>=$starttime && $time<=$endtime){
                Db::name('wine_order_saler_contract')->where('wine_contract_day_id', $k)->where('status', 0)->where('delete', 0)->update([
                    'onsale'=>0
                ]);
            }
        }
        
        return 1;
    }
    
    public function enquiryaccountinfo(){
        if(request()->isAjax()){
            $post = input('post.');
            
            $user_id = Db::name('member')->where('phone', $post['phone'])->value('id');
            
            $wine_order_record = Db::name('wine_order_record')->where('buy_id', $user_id)->order('id desc')->select();
            foreach ($wine_order_record as &$v){
                $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
            }
            
            $wine_order_buyer = Db::name('wine_order_buyer')->where('buy_id', $user_id)->order('id desc')->select();
            foreach ($wine_order_buyer as &$v){
                $v['paytime'] = $v['paytime'] ? date('Y-m-d H:i:s', $v['paytime']) : '';
                $v['confirm_exchange'] = $v['confirm_exchange'] ? date('Y-m-d H:i:s', $v['confirm_exchange']) : '';
                $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
            }
            
            
            $wine_order_saler = Db::name('wine_order_saler')->where('sale_id', $user_id)->order('id desc')->select();
            foreach ($wine_order_saler as &$v){
                $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
            }
            
            
            return json([
                'wine_order_record'=>$wine_order_record,
                'wine_order_buyer'=>$wine_order_buyer,
                'wine_order_saler'=>$wine_order_saler
            ]);
        }
        else{
            return $this->fetch('enquiry_account_info');
        }
    }
    
    public function verify(){
        $post = input('post.');
        
        if(isset($post['token']) && !empty($post['token'])){
            if(md5($post['token']) == '56cb563577750c7899c0c8afaae5f08c'){
                return 1;
            }
        }
        
        return 0;
    }
    
    
    public function del(){
        $input = input();
        $idArr = [];
        if(is_array($input['id'])){
            $idArr = $input['id'];
        }
        else{
            $idArr = [$input['id']];
        }
        
        $res = Db::name('wine_order_saler_contract')->where('id', 'in', $idArr)->update([
            'delete'=>1
        ]);
        if($res){
            ys_admin_logs('删除销售表的订单', 'wine_order_saler_contract', $idArr);
            echo 1;exit;
        }
        echo 0;exit;
    }
    
    public function shangjia(){
        $input = input();
        $idArr = [];
        if(is_array($input['id'])){
            $idArr = $input['id'];
        }
        else{
            $idArr = [$input['id']];
        }
        
        $res = Db::name('wine_order_saler_contract')->where('id', 'in', $idArr)->update([
            'onsale'=>1
        ]);
        if($res){
            ys_admin_logs('批量上架', 'wine_order_saler_contract', $idArr);
            echo 1;exit;
        }
        echo 0;exit;
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $count = Db::name('wine_order_saler_contract')->update($data);
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    public function xiajia(){
        $input = input();
        $idArr = [];
        if(is_array($input['id'])){
            $idArr = $input['id'];
        }
        else{
            $idArr = [$input['id']];
        }
        
        $res = Db::name('wine_order_saler_contract')->where('id', 'in', $idArr)->update([
            'onsale'=>0
        ]);
        if($res){
            ys_admin_logs('批量下架', 'wine_order_saler_contract', $idArr);
            echo 1;exit;
        }
        echo 0;exit;
    }
    
    public function lst(){
        $post = input(); $where = []; $where1 = []; $where2 = []; $whereTime=[];
        $keyword = $post['keyword'];
        
        $wine_deal_area_id = $post['wine_deal_area_id'];
        if($wine_deal_area_id!=-1 && $wine_deal_area_id){
            $where['wos.wine_deal_area_id'] = $wine_deal_area_id;
        }
        
        $status = -1;
        if(isset($post['status']) && $post['status'] > 0){
            $status = $where2['wos.status'] = $post['status'];
        }
        
        if($post['day']){
            $where2['wos.wine_contract_day_id'] = $post['day'];
        }
        
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01 00:00:00', date('Y-m-d', strtotime('tomorrow')).' 00:00:00'];
        }
        
        $day_arr = Db::name('wine_contract_day')->column('id, day');
        
        $list = Db::name('wine_order_saler_contract')->alias('wos')
                ->join('bank_card bank', 'bank.user_id = wos.sale_id', 'left')
                ->join('wx_card wx', 'wx.user_id = wos.sale_id', 'left')
                ->join('zfb_card zfb', 'zfb.user_id = wos.sale_id', 'left')
                ->join('wine_goods_contract wg', 'wg.id = wos.wine_goods_id', 'left')
                ->join('wine_contract_day wcd', 'wcd.id = wos.wine_contract_day_id', 'left')
                ->join('member m', 'm.id=wos.sale_id', 'inner')
//                 ->join('wine_deal_area_contract wda', 'wda.id = wos.wine_deal_area_id', 'left')
// <<<<<<< HEAD
//                 ->field('wos.*,m.user_name,m.phone,m.emergency_phone,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,wg.day,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode,m.checked, w.total_stock, w.brand, wg.adopt,wob.buy_id, bm.phone bm_phone, bm.emergency_phone bm_emergency_phone,wda.desc')
// =======
                ->field('wos.*,m.phone,m.true_name,m.user_name,m.emergency_phone,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,wg.day,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode,m.checked, w.total_stock, w.brand, wg.adopt, bm.phone bm_phone, bm.emergency_phone bm_emergency_phone, wcd.day')
                ->where(function ($query)use($keyword){
                    if(trim($keyword)!='')$query->where('m.id', $keyword)->whereOr('wos.odd', $keyword)->whereOr('m.phone', $keyword)->whereOr('m.true_name', $keyword);
                })
                ->join('wallet w', 'w.user_id = wos.sale_id', 'left')
                ->where(function($query) use($where, $where1) {
                    $query->where($where)->whereOr($where1);
                })
                ->where($where2)
                // ->where('wob.delete', 0)
                ->where('wos.addtime', 'between time', $whereTime)
                // ->where('wos.id', 1)
                
                // ->join('wine_order_buyer_contract wob', 'wob.wine_order_saler_id=wos.id', 'left')
                // ->join('member bm', 'bm.id = wob.buy_id', 'left')
                ->join('member bm', 'bm.id=wos.assign_buyer_id', 'left')
                // ->join('member bm', 'bm.id = wos')
                ->where('wos.delete', 0)->order('wos.goods_name, id desc')->paginate(25, false, ['query'=>request()->param()])->each(function ($item){
                    $item['buy_id'] = Db::name('wine_order_buyer_contract')->where('wine_order_saler_id', $item['id'])->where('delete', 0)->value('buy_id');
                    
                    
                    $adopt = explode('-', $item['adopt']);
                    $endtime = explode(':', $adopt[0]);
                    $item['endtime'] = date('Y-m-d', $item['addtime']+86400).' '.$endtime[0].':'.$endtime[1].':00';
                    return $item;
                });
        // echo Db::name('wine_order_saler_contract')->getLastSql();
        $count = Db::name('wine_order_saler_contract')->alias('wos')
                ->join('bank_card bank', 'bank.user_id = wos.sale_id', 'left')
                ->join('wx_card wx', 'wx.user_id = wos.sale_id', 'left')
                ->join('zfb_card zfb', 'zfb.user_id = wos.sale_id', 'left')
                ->join('wine_goods_contract wg', 'wg.id = wos.wine_goods_id', 'left')
                ->join('wine_deal_area_contract wda', 'wda.id = wos.wine_deal_area_id', 'left')
                ->field('wos.*,m.user_name,m.emergency_phone,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,wg.day,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode,m.checked, w.total_stock, w.brand, wg.adopt,wob.buy_id, bm.phone bm_phone, bm.emergency_phone bm_emergency_phone,wda.desc')
                ->where(function ($query)use($keyword){
                    if(trim($keyword)!='')$query->where('wos.id', $keyword)->whereOr('wos.odd', $keyword)->whereOr('wos.goods_name', $keyword);
                })
                ->join('wallet w', 'w.user_id = wos.sale_id', 'left')
                ->where(function($query) use($where, $where1) {
                    $query->where($where)->whereOr($where1);
                })
                ->where($where2)
                ->where('wos.addtime', 'between time', $whereTime)
                // ->where('wos.id', 1)
                ->join('member m', 'm.id=wos.sale_id', 'inner')
                ->join('wine_order_buyer_contract wob', 'wob.wine_order_saler_id=wos.id', 'left')
                ->join('member bm', 'bm.id = wob.buy_id', 'left')
                // ->join('member bm', 'bm.id = wos')
                ->where('wos.delete', 0)->count();
                
                // echo Db::name('wine_order_saler')->getLastSql();exit;
        $today_time = strtotime('today');        
        $total_num = Db::name('wine_order_saler_contract')->where('delete', 0)->count();
        $today_num = Db::name('wine_order_saler_contract')->where('delete', 0)->where('addtime', '>=', $today_time)->count();
        $page = $list->render();
        
        $wine_goods = Db::name('wine_goods_contract')->where('onsale', 1)->select();
        
        // $deal_area = Db::name('wine_deal_area')->select();
        $deal_area = Db::name('wine_deal_area_contract')->column('id, desc');
        $deal_area_day = Db::name('wine_contract_day')->column('id, day');
        
        $manager_member_list = Db::name('member')->where('frozen', 0)->where('checked', 1)->where('add_way', 1)->where('admin_id', '>', 0)->select();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('keyword', $post['keyword']);
        $this->assign('wine_deal_area_id', $wine_deal_area_id);
        $this->assign('deal_area_day', $deal_area_day);
        $this->assign('status', $status);
        $this->assign('where_time', $whereTime);
        $this->assign('wine_goods', $wine_goods);
        $this->assign('deal_area', $deal_area);
        $this->assign('manager_member_list', $manager_member_list);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('day_arr', $day_arr);
        $this->assign('day', $post['day']);
        $this->assign('count', $count);
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
        $keyword = $post['keyword'];
        
        $status = -1;
        if(isset($post['status']) && $post['status'] > 0){
            $status = $where2['wos.status'] = $post['status'];
        }
        
        if($post['day']){
            $where2['wos.wine_contract_day_id'] = $post['day'];
        }
        
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01 00:00:00', date('Y-m-d', strtotime('tomorrow')).' 00:00:00'];
        }
        
        $day_arr = Db::name('wine_contract_day')->column('id, day');
        
        $list = Db::name('wine_order_saler_contract')->alias('wos')
                ->join('bank_card bank', 'bank.user_id = wos.sale_id', 'left')
                ->join('wx_card wx', 'wx.user_id = wos.sale_id', 'left')
                ->join('zfb_card zfb', 'zfb.user_id = wos.sale_id', 'left')
                ->join('wine_goods_contract wg', 'wg.id = wos.wine_goods_id', 'left')
                ->join('wine_contract_day wcd', 'wcd.id = wos.wine_contract_day_id', 'left')
                ->join('member m', 'm.id=wos.sale_id', 'inner')
                ->join('wine_deal_area_contract wda', 'wda.id = wos.wine_deal_area_id', 'left')
                ->field('wos.*,m.phone,m.true_name,m.user_name,m.emergency_phone,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,wg.day,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode,m.checked, w.total_stock, w.brand, wg.adopt,wob.buy_id, bm.phone bm_phone, bm.emergency_phone bm_emergency_phone,wda.desc, wcd.day')
                ->where(function ($query)use($keyword){
                    if(trim($keyword)!='')$query->where('m.id', $keyword)->whereOr('wos.odd', $keyword)->whereOr('m.phone', $keyword)->whereOr('m.true_name', $keyword);
                })
                ->join('wallet w', 'w.user_id = wos.sale_id', 'left')
                ->where(function($query) use($where, $where1) {
                    $query->where($where)->whereOr($where1);
                })
                ->where($where2)
                ->where('wos.addtime', 'between time', $whereTime)
                // ->where('wos.id', 1)
                
                ->join('wine_order_buyer_contract wob', 'wob.wine_order_saler_id=wos.id', 'left')
                // ->join('member bm', 'bm.id = wob.buy_id', 'left')
                ->join('member bm', 'bm.id=wos.assign_buyer_id', 'left')
                // ->join('member bm', 'bm.id = wos')
                ->where('wos.delete', 0)->order('wos.goods_name, id desc')->select();
                
        $arr = [];
        array_push($arr, ['A'=>'ID', 'B'=>'编号', 'C'=>'商品名称', 'D'=>'所有者名称', 'E'=>'所有者手机', 'F'=>'销售价格', 'G'=>'天数', 'H'=>'上架/下架', 'I'=>'拍卖状态',
                    'J'=>'指定买家','K'=>'时间']);
                    
        foreach($list as $k=>$v){
            array_push($arr, ['A'=>$v['id'], 'B'=>$v['odd'], 'C'=>$v['goods_name'], 'D'=>$v['true_name']?$v['true_name']:$v['user_name'], 'E'=>$v['phone'], 'F'=>$v['sale_amount'], 'G'=>$v['day'], 'H'=>($v['onsale']==0?'下架':($v['onsale']==1?'上架':'')), 'I'=>($v['status']==0?'待销售':($v['status']==1?'销售中':($v['status']==2?'已成单':''))), 'J'=>$v['bm_phone'], 'K'=>date('Y-m-d H:i:s', $v['addtime'])]);
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
              ->setCellValue('K'.$num, $val['K']);
        }
    
        $Excel->getActiveSheet()->setTitle('export');
    
        $Excel->setActiveSheetIndex(0);
    
        $name = '出售列表'.date('Y-m-d').'.xlsx';
    
        header('Content-Type: application/vnd.ms-excel');
    
        header('Content-Disposition: attachment; filename='.$name);
    
        header('Cache-Control: max-age=0');
    
        $ExcelWriter = \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007');
    
        $ExcelWriter->save('php://output');
    
        exit;
                
    }
    
    // public function paimai(){
    //     $post = input(); $where = []; $where1 = []; $where2 = []; $whereTime=[];
    //     $keyword = $post['keyword'];
        
    //     $wine_deal_area_id = $post['wine_deal_area_id'];
    //     if($wine_deal_area_id!=-1 && $wine_deal_area_id){
    //         $where['wos.wine_deal_area_id'] = $wine_deal_area_id;
    //     }
        
    //     // $status = -1;
    //     // if(isset($post['status'])){
    //     //     $status = $where2['wos.status'] = $post['status'];
    //     // }
        
    //     if($post['startDate']){
    //         $whereTime = [$post['startDate'], $post['endDate']];
    //     }
    //     else{
    //         $whereTime = ['2022-01-01 00:00:00', date('Y-m-d', strtotime('tomorrow')).' 00:00:00'];
    //     }
        
    //     $list = Db::name('wine_order_saler')->alias('wos')
    //             ->join('wine_goods wg', 'wg.id = wos.wine_goods_id', 'left')
    //             ->join('wine_deal_area wda', 'wda.id = wos.wine_deal_area_id', 'left')
    //             ->join('member m', 'm.id=wos.sale_id', 'inner')
    //             ->field('wos.*, wda.desc, m.phone, m.true_name, m.user_name')
    //             ->where('wos.status',  0)
    //             ->where(function ($query)use($keyword){
    //                 if(trim($keyword)!='')$query->where('m.id', $keyword)->whereOr('wos.odd', $keyword)->whereOr('m.phone', $keyword)->whereOr('m.true_name', $keyword);
    //             })
    //             // ->where(function($query) use($where, $where1) {
    //             //     $query->where($where)->whereOr($where1);
    //             // })
    //             ->where($where)
    //             ->where('wos.addtime', 'between time', $whereTime)
    //             // ->join('wine_order_buyer wob', 'wob.wine_order_saler_id=wos.id', 'left')
    //             // ->join('member bm', 'bm.id = wob.buy_id', 'left')
    //             ->where('wos.delete', 0)->order('wos.goods_name, id desc')->paginate(25, false, ['query'=>request()->param()]);
    //             // ->each(function ($item){
    //                 // $adopt = explode('-', $item['adopt']);
    //                 // $endtime = explode(':', $adopt[0]);
    //                 // $item['endtime'] = date('Y-m-d', $item['addtime']+86400).' '.$endtime[0].':'.$endtime[1].':00';
    //                 // return $item;
    //             // });
        
    //     $count = Db::name('wine_order_saler')->alias('wos')
    //             ->join('wine_goods wg', 'wg.id = wos.wine_goods_id', 'left')
    //             ->join('wine_deal_area wda', 'wda.id = wos.wine_deal_area_id', 'left')
    //             ->join('member m', 'm.id=wos.sale_id', 'inner')
    //             ->field('wos.*, wda.desc, m.phone, m.true_name, m.user_name')
    //             ->where(function ($query)use($keyword){
    //                 if(trim($keyword)!='')$query->where('m.id', $keyword)->whereOr('wos.odd', $keyword)->whereOr('m.phone', $keyword)->whereOr('m.true_name', $keyword);
    //             })
    //             // ->where(function($query) use($where, $where1) {
    //             //     $query->where($where)->whereOr($where1);
    //             // })
    //             ->where($where)
    //             ->where('wos.addtime', 'between time', $whereTime)
    //             // ->join('wine_order_buyer wob', 'wob.wine_order_saler_id=wos.id', 'left')
    //             // ->join('member bm', 'bm.id = wob.buy_id', 'left')
    //             ->where('wos.status', 0)->where('wos.delete', 0)->count();
                
    //             // echo Db::name('wine_order_saler')->getLastSql();exit;
    //     // $today_time = strtotime('today');        
    //     // $total_num = Db::name('wine_order_saler')->where('delete', 0)->count();
    //     // $today_num = Db::name('wine_order_saler')->where('delete', 0)->where('addtime', '>=', $today_time)->count();
    //     $page = $list->render();
        
    //     $wine_goods = Db::name('wine_goods')->where('onsale', 1)->select();
        
    //     $deal_area = Db::name('wine_deal_area')->column('id, desc');
    //     $deal_areass = Db::name('wine_deal_area')->where('id', '<>', 10)->column('id, desc');
        
    //     // $manager_member_list = Db::name('member')->where('frozen', 0)->where('checked', 1)->where('add_way', 1)->where('admin_id', '>', 0)->select();

    //     if (input('page')) {
    //         $pnum = input('page');
    //     } else {
    //         $pnum = 1;
    //     }

    //     $this->assign('keyword', $post['keyword']);
    //     $this->assign('wine_deal_area_id', $wine_deal_area_id);
    //     // $this->assign('status', $status);
    //     $this->assign('where_time', $whereTime);
    //     $this->assign('wine_goods', $wine_goods);
    //     $this->assign('deal_area', $deal_area);
    //     $this->assign('deal_areass', $deal_areass);
    //     // $this->assign('manager_member_list', $manager_member_list);
    //     $this->assign('list', $list);
    //     $this->assign('page', $page);
    //     $this->assign('pnum', $pnum);
    //     $this->assign('count', $count);
    //     // $this->assign('today_num', $today_num);
    //     // $this->assign('total_num', $total_num);
    //     if (request()->isAjax()) {
    //         return $this->fetch('paimai_ajaxpage');
    //     } else {
    //         return $this->fetch('paimai_lst');
    //     }
    // }
    
    // public function jinpai(){
    //     $post = input(); $where = []; $where1 = []; $where2 = []; $whereTime=[];
    //     $keyword = $post['keyword'];
    //     if($keyword){
    //         $where['wob.goods_name'] = $keyword;
    //     }
    //     $odd = $post['odd'];
    //     if($odd){
    //         $where['wob.odd'] = $odd;
    //     }
    //     $this->assign('odd', $odd);
        
    //     $mai_name = $post['mai_name'];
    //     $this->assign('mai_name', $mai_name);
        
    //     $buy_name = $post['buy_name'];
    //     $this->assign('buy_name', $buy_name);

    //     $pay_status = $post['pay_status'];
    //     if($pay_status==='0' || $pay_status==='1'){
    //         $where['wob.pay_status'] = $pay_status;
    //     }
    //     $this->assign('pay_status', $pay_status);
        
    //     if($post['startDate']){
    //         $whereTime = [$post['startDate'], $post['endDate']];
    //     }
    //     else{
    //         $whereTime = ['2022-01-01 00:00:00', date('Y-m-d', strtotime('tomorrow')).' 00:00:00'];
    //     }
        
    //     $list = Db::name('wine_order_buyer')->alias('wob')
    //             ->join('wine_goods wg', 'wg.id = wob.wine_goods_id', 'left')
    //             ->join('member m', 'm.id=wob.sale_id', 'left')
    //             ->join('member b', 'b.id=wob.buy_id', 'left')
    //             ->where('wob.addtime', 'between time', $whereTime)
    //             ->where($where)
    //             ->where(function($query)use($mai_name){
    //                 if($mai_name)$query->where('m.phone', $mai_name)->whereOr('m.user_name', $mai_name)->whereOr('m.true_name', $mai_name);
    //             })
    //             ->where(function($query)use($buy_name){
    //                 if($buy_name)$query->where('b.phone', $buy_name)->whereOr('b.user_name', $buy_name)->whereOr('b.true_name', $buy_name);
    //             })
    //             ->field('wob.*, b.true_name b_true_name, b.phone b_phone, m.true_name m_true_name, m.phone m_phone')
    //             ->where('wob.delete', 0)->where('wob.status',  'in', [1, 2])
    //             ->order('wob.goods_name, id desc')->paginate(25, false, ['query'=>request()->param()]);


    //     $count = Db::name('wine_order_buyer')->alias('wob')
    //             ->join('wine_goods wg', 'wg.id = wob.wine_goods_id', 'left')
    //             ->join('member m', 'm.id=wob.sale_id', 'left')
    //             ->join('member b', 'b.id=wob.buy_id', 'left')
    //             ->where('wob.addtime', 'between time', $whereTime)
    //             ->where($where)
    //             ->where(function($query)use($mai_name){
    //                 if($mai_name)$query->where('m.phone', $mai_name)->whereOr('m.user_name', $mai_name)->whereOr('m.true_name', $mai_name);
    //             })
    //             ->where(function($query)use($buy_name){
    //                 if($buy_name)$query->where('b.phone', $buy_name)->whereOr('b.user_name', $buy_name)->whereOr('b.true_name', $buy_name);
    //             })
    //             ->where('wob.delete', 0)->where('wob.status',  'in', [1, 2])
    //             ->count();
        
    //     $page = $list->render();
        
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
    //     $this->assign('count', $count);
    //     if (request()->isAjax()) {
    //         return $this->fetch('jinpai_ajaxpage');
    //     } else {
    //         return $this->fetch('jinpai_lst');
    //     }
    // }
    
    public function wenti(){
        $post = input(); $where = []; $where1 = []; $where2 = []; $whereTime=[];
        $keyword = $post['keyword'];
        if($keyword){
            $where['wob.goods_name'] = $keyword;
        }
        $odd = $post['odd'];
        if($odd){
            $where['wob.odd'] = $odd;
        }
        $where['wob.delete'] = 0;
        $this->assign('odd', $odd);
        
        $mai_name = $post['mai_name'];
        $this->assign('mai_name', $mai_name);
        
        $buy_name = $post['buy_name'];
        $this->assign('buy_name', $buy_name);

        $pay_status = $post['pay_status'];
        if($pay_status==='0' || $pay_status==='1'){
            $where['wob.pay_status'] = $pay_status;
        }
        $this->assign('pay_status', $pay_status);

        $attech_zeren = $post['attech_zeren'];
        if(in_array($attech_zeren, [1,2])){
            $where2['wob.attech_zeren'] = $attech_zeren;
            if($attech_zeren == 3){
                $where['wob.delete'] = 1;
            }
        }
        $this->assign('attech_zeren', $attech_zeren);
        
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01 00:00:00', date('Y-m-d', strtotime('tomorrow')).' 00:00:00'];
        }
        
        $list = Db::name('wine_order_buyer_contract')->alias('wob')
                ->join('wine_goods_contract wg', 'wg.id = wob.wine_goods_id', 'left')
                ->join('member m', 'm.id=wob.sale_id', 'left')
                ->join('member b', 'b.id=wob.buy_id', 'left')
                ->where('wob.addtime', 'between time', $whereTime)
                ->where($where)
                ->where(function($query)use($mai_name){
                    if($mai_name)$query->where('m.phone', $mai_name)->whereOr('m.user_name', $mai_name)->whereOr('m.true_name', $mai_name);
                })
                ->where(function($query)use($buy_name){
                    if($buy_name)$query->where('b.phone', $buy_name)->whereOr('b.user_name', $buy_name)->whereOr('b.true_name', $buy_name);
                })
                ->field('wob.*, b.true_name b_true_name, b.phone b_phone, m.true_name m_true_name, m.phone m_phone')
                ->where(function($query) use($where2){
                    if(empty($where2)){
                        $query->where('wob.status', 'in', [8, 9]);
                    }
                    else{
                        $query->where($where2);
                    }
                })
                ->order('wob.goods_name, id desc')->paginate(25, false, ['query'=>request()->param()]);
// var_dump($list);exit;

        $count = Db::name('wine_order_buyer_contract')->alias('wob')
                ->join('wine_goods_contract wg', 'wg.id = wob.wine_goods_id', 'left')
                ->join('member m', 'm.id=wob.sale_id', 'left')
                ->join('member b', 'b.id=wob.buy_id', 'left')
                ->where('wob.addtime', 'between time', $whereTime)
                ->where($where)
                ->where(function($query)use($mai_name){
                    if($mai_name)$query->where('m.phone', $mai_name)->whereOr('m.user_name', $mai_name)->whereOr('m.true_name', $mai_name);
                })
                ->where(function($query)use($buy_name){
                    if($buy_name)$query->where('b.phone', $buy_name)->whereOr('b.user_name', $buy_name)->whereOr('b.true_name', $buy_name);
                })
                ->where(function($query) use($where2){
                    if(empty($where2)){
                        $query->where('wob.status', 'in', [8, 9]);
                    }
                    else{
                        $query->where($where2);
                    }
                })
                ->count();
        
        $page = $list->render();
        
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
        $this->assign('count', $count);
        if (request()->isAjax()) {
            return $this->fetch('wenti_ajaxpage');
        } else {
            return $this->fetch('wenti_lst');
        }
    }
    
    public function wenti_export(){
        $post = input(); $where = []; $where1 = []; $where2 = []; $whereTime=[];
        $keyword = $post['keyword'];
        if($keyword){
            $where['wob.goods_name'] = $keyword;
        }
        $odd = $post['odd'];
        if($odd){
            $where['wob.odd'] = $odd;
        }
        $where['wob.delete'] = 0;
        $this->assign('odd', $odd);
        
        $mai_name = $post['mai_name'];
        $this->assign('mai_name', $mai_name);
        
        $buy_name = $post['buy_name'];
        $this->assign('buy_name', $buy_name);

        $pay_status = $post['pay_status'];
        if($pay_status==='0' || $pay_status==='1'){
            $where['wob.pay_status'] = $pay_status;
        }
        $this->assign('pay_status', $pay_status);

        $attech_zeren = $post['attech_zeren'];
        if(in_array($attech_zeren, [1,2,3,4])){
            $where2['wob.attech_zeren'] = $attech_zeren;
            if($attech_zeren == 3){
                $where['wob.delete'] = 1;
            }
        }
        $this->assign('attech_zeren', $attech_zeren);
        
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01 00:00:00', date('Y-m-d', strtotime('tomorrow')).' 00:00:00'];
        }
        
        $list = Db::name('wine_order_buyer_contract')->alias('wob')
                ->join('wine_goods_contract wg', 'wg.id = wob.wine_goods_id', 'left')
                ->join('member m', 'm.id=wob.sale_id', 'left')
                ->join('member b', 'b.id=wob.buy_id', 'left')
                ->where('wob.addtime', 'between time', $whereTime)
                ->where($where)
                ->where(function($query)use($mai_name){
                    if($mai_name)$query->where('m.phone', $mai_name)->whereOr('m.user_name', $mai_name)->whereOr('m.true_name', $mai_name);
                })
                ->where(function($query)use($buy_name){
                    if($buy_name)$query->where('b.phone', $buy_name)->whereOr('b.user_name', $buy_name)->whereOr('b.true_name', $buy_name);
                })
                ->field('wob.*, b.true_name b_true_name, b.phone b_phone, m.true_name m_true_name, m.phone m_phone')
                ->where(function($query) use($where2){
                    if(empty($where2)){
                        $query->where('wob.status', 'in', [8, 9]);
                    }
                    else{
                        $query->where($where2);
                    }
                })
                ->order('wob.goods_name, id desc')->select();
// var_dump($list);exit;
                    // echo Db::name('wine_order_buyer')->getLastSql();die();
                $arr = [];
        array_push($arr, ['A'=>'ID', 'B'=>'订单编号', 'C'=>'商品名称', 'D'=>'成交金额', 'E'=>'买家', 'F'=>'买家手机', 'G'=>'卖家', 'H'=>'卖家手机', 'I'=>'订单时间',
                    'J'=>'支付状态','K'=>'责任归属']);
        foreach($list as $k=>$v){
            $zeren_arr[0] = '暂未判定';
            $zeren_arr[1] = '更换买家';
            $zeren_arr[2] = '强制成交';
            array_push($arr, ['A'=>$v['id'], 'B'=>$v['odd'], 'C'=>$v['goods_name'], 'D'=>$v['buy_amount'].'元', 'E'=>$v['b_true_name']?$v['b_true_name']:$v['b_user_name'], 'F'=>$v['b_phone'], 'G'=>$v['m_true_name']?$v['m_true_name']:$v['m_user_name'], 'H'=>$v['m_phone'], 'I'=>date('Y-m-d H:i:s', $v['addtime']), 'J'=>($v['pay_status']==0?'未支付':($v['pay_status']==1?'已支付':'')), 'K'=>$zeren_arr[$v['attech_zeren']]]);
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
              ->setCellValue('K'.$num, $val['K']);
        }
    
        $Excel->getActiveSheet()->setTitle('export');
    
        $Excel->setActiveSheetIndex(0);
    
        $name = '问题订单'.date('Y-m-d').'.xlsx';
    
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
            $where['wos.sale_id'] = Db::name('member')->where('phone', $post['keyword'])->value('id');
        }
        if($post['keyword']){
            $where1['wos.odd'] = $post['keyword'];
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
        
        $list = Db::name('wine_order_saler')->alias('wos')
                ->where('wos.wine_goods_id', $wine_goods_id)
                ->join('bank_card bank', 'bank.user_id = wos.sale_id', 'left')
                ->join('wx_card wx', 'wx.user_id = wos.sale_id', 'left')
                ->join('zfb_card zfb', 'zfb.user_id = wos.sale_id', 'left')
                ->join('wine_goods wg', 'wg.id = wos.wine_goods_id', 'left')
                ->field('wos.*,m.user_name,m.emergency_phone,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,wg.day,
                        zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode,m.checked, w.total_stock, w.brand, wg.adopt, bm.phone bm_phone, bm.emergency_phone bm_emergency_phone')
                ->join('wallet w', 'w.user_id = wos.sale_id', 'left')
                ->where(function($query) use($where, $where1) {
                    $query->where($where)->whereOr($where1);
                })
                ->join('wine_order_buyer wob', 'wob.wine_order_saler_id=wos.id', 'left')
                ->join('member bm', 'bm.id = wob.buy_id', 'left')
                ->join('member m', 'm.id=wos.sale_id', 'inner')->where('wos.delete', 0)
                ->where('wos.addtime', 'between time', $whereTime)->order('wos.status')->paginate(25)->each(function ($item){
                    $adopt = explode('-', $item['adopt']);
                    $endtime = explode(':', $adopt[0]);
                    $item['endtime'] = date('Y-m-d', $item['addtime']+86400).' '.$endtime[0].':'.$endtime[1].':00';
                    return $item;
                });;
        $today_time = strtotime('today');        
        $total_num = Db::name('wine_order_saler')->alias('wos')
                ->where('wos.wine_goods_id', $wine_goods_id)
                ->join('bank_card bank', 'bank.user_id = wos.sale_id', 'left')
                ->join('wx_card wx', 'wx.user_id = wos.sale_id', 'left')
                ->join('zfb_card zfb', 'zfb.user_id = wos.sale_id', 'left')
                ->join('wine_goods wg', 'wg.id = wos.wine_goods_id', 'left')
                ->field('wos.*,m.user_name,m.emergency_phone,bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,wg.day,
                        zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode,m.checked, w.total_stock, w.brand, wg.adopt, bm.phone bm_phone, bm.emergency_phone bm_emergency_phone')
                ->join('wallet w', 'w.user_id = wos.sale_id', 'left')
                ->where(function($query) use($where, $where1) {
                    $query->where($where)->whereOr($where1);
                })
                ->join('wine_order_buyer wob', 'wob.wine_order_saler_id=wos.id', 'left')
                ->join('member bm', 'bm.id = wob.buy_id', 'left')
                ->join('member m', 'm.id=wos.sale_id', 'inner')->where('wos.delete', 0)
                ->where('wos.addtime', 'between time', $whereTime)->count()
                ;
        $today_num = Db::name('wine_order_saler')->where('wine_goods_id', $wine_goods_id)->where('delete', 0)->where('addtime', '>=', $today_time)->count();
        $page = $list->render();
        
        // $wine_goods = Db::name('wine_goods')->select();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('keyword', $post['keyword']);
        $this->assign('zfb', $post['zfb']);
        $this->assign('wx', $post['wx']);
        $this->assign('card_name', $post['card_name']);
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

    public function pipei(){
        $get = input();
        $wine_order_saler_id = $get['id'];
        $info = Db::name('wine_order_saler')->where('id', $wine_order_saler_id)->where('delete', 0)->find();
        if(is_null($info)){
            return $this->error('该出售信息不存在');
        }
        
        $where = [];
        if(isset($get['single']) && $get['single']==1){
            $where['wor.wine_goods_id'] = $info['wine_goods_id'];
        }
        
        if(isset($get['keyword']) && !empty($get['keyword'])){
            $user_id = Db::name('member')->where('phone', $get['keyword'])->value('id');
            
            if($user_id){
                $where['wor.buy_id'] = $user_id;
            }
        }
        
        $list = WineBespokeModel::with(['wineBespoke'=>function ($query){
                        $query->where('status', 0);
                    }, 'wineDistribution'=>function($query){
                        $query->where('delete', 0)->where('status', 1)->field('buy_id, goods_name');
                    }])->alias('wor')
                    ->join('bank_card bank', 'bank.user_id = wor.buy_id', 'left')
                    ->join('wx_card wx', 'wx.user_id = wor.buy_id', 'left')
                    ->join('zfb_card zfb', 'zfb.user_id = wor.buy_id', 'left')
                    ->join('wine_goods wg', 'wg.id = wor.wine_goods_id', 'left')
                    ->join('member m', 'm.id = wor.buy_id', 'left')
                    ->where($where)
                    ->join('wallet w', 'w.user_id = wor.buy_id', 'left')
                    ->where('wor.status', 0)
                    // ->order('wor.id desc')
                    ->where('wg.onsale', 1)
                    ->field('wor.*, wg.value, m.phone, m.checked, wg.adopt, bank.telephone bank_telephone, bank.card_number bank_card_number,bank.name bank_name,wx.telephone wx_telephone, wx.name wx_name, wx.qrcode wx_qrcode,zfb.name zfb_name, zfb.telephone zfb_telephone, zfb.qrcode zfb_qrcode, w.total_stock, w.brand, w.credit_value')->paginate(25);
        $page = $list->render();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('keyword', $get['keyword'] ?? '');
        $this->assign('get', $get);
        $this->assign('wine_order_saler_id', $wine_order_saler_id);
        return $this->fetch();
    }
    
    public function onekey_generate(){
        $post = input('post.');
        $post['num'] = 1;
        
        if(!$post['num'] || !$post['wine_goods_id'] || !$post['price'] || !$post['phone'] || !$post['wine_contract_day_id']){
            $value = array('status'=>400,'mess'=>'数据不能为空');
        }
        else{
            if($post['num'] <= 0 || $post['num']>100){
                $value = array('status'=>400,'mess'=>'生成数量不能小于0大于100');
            }
            else{
                if($post['price'] <= 0){
                    $value = array('status'=>400,'mess'=>'出售价不能小于0');
                }
                else{
                    $info = Db::name('wine_goods_contract')->where('id', $post['wine_goods_id'])->where('onsale', 1)->find();
                    $value = explode('-', $info['value']);
                    if($value[0]>$post['price'] || $post['price']>$value[1]){
                        $value = array('status'=>400,'mess'=>'商品价格不符合要求');
                        return json($value);
                    }
                    
                    $member_info = Db::name('member')->where('phone', $post['phone'])->where('frozen', 0)->where('checked', 1)->find();
                    if(is_null($member_info)){
                        $value = array('status'=>400,'mess'=>'未找到该出售者账号信息');
                        return json($value);
                    }
                    
                    if($info){
                        for ($i = 0; $i < $post['num']; $i++) {
                             // code...
                             
                            Db::startTrans();
                            try{
                                $data = [
                                    'goods_name'=>$info['goods_name'],
                                    'addtime'=>time(),
                                    'goods_rate'=>Db::name('wine_contract_day')->where('id', $post['wine_contract_day_id'])->value('day_rate'),
                                    'goods_thumb'=>$info['thumb_url'],
                                    'sale_amount'=>$post['price'],
                                    'pipei_amount'=>$post['price'],
                                    // 'sale_id'=>1,
                                    'sale_id'=>$member_info['id'],
                                    'odd'=>uniqid(),
                                    'sort'=>$i,
                                    'wine_contract_day_id'=>$post['wine_contract_day_id'],
                                    'wine_goods_id'=>$info['id'],
                                    // 'wine_deal_area_id' => $post['wine_deal_area_id']
                                    // 'wx_name'=>$post['wx_name'],
                                    // 'wx_telephone'=>$post['wx_telephone'],
                                    // 'wx_qrcode'=>$post['wx_qrcode'],
                                    // 'zfb_name'=>$post['zfb_name'],
                                    // 'zfb_telephone'=>$post['zfb_telephone'],
                                    // 'zfb_qrcode'=>$post['zfb_qrcode'],
                                    // 'bank_name'=>$post['bank_name'],
                                    // 'bank_telephone'=>$post['bank_telephone'],
                                    // 'bank_card_number'=>$post['bank_card_number'],
                                    // 'bank_card_name'=>$post['bank_card_name']
                                ];
                                $res = Db::name('wine_order_saler_contract')->insertGetId($data);
                                if(!$res)throw new Exception('失败');
                                
                                $res = Db::name('contract_record_wallet')->where('user_id', $member_info['id'])->setInc('total_assets', $post['price']);
                                if(!$res)throw new Exception('失败');
                                
                                Db::commit();
                            }
                            catch(Exception $e){
                                Db::rollback();
                            }
                        }
                        $value = array('status'=>200,'mess'=>'成功');
                    }
                    else{
                        $value = array('status'=>400,'mess'=>'商品信息不存在');
                    }
                }
            }
        }
        
        return json($value);
    }

    public function pppei(){
        $post = input('post.');

        $time = time();
        if(!$post['wine_order_saler_id'] || !$post['price'] || !$post['wine_order_record_id']){
            $value = array('status'=>400,'mess'=>'参数错误');
        }
        else{
            if (!is_numeric($post['wine_order_saler_id']) || !is_numeric($post['price']) || !is_numeric($post['wine_order_record_id'])){
                $value = array('status'=>400,'mess'=>'参数错误');
            }
            else{
                $wine_order_record = Db::name('wine_order_record')->where('id', $post['wine_order_record_id'])->find();
                $wine_order_saler = Db::name('wine_order_saler')->where('id', $post['wine_order_saler_id'])->find();
                
                $buyer = Db::name('member')->where('id', $wine_order_record['buy_id'])->find();
                $saler = Db::name('member')->where('id', $wine_order_saler['sale_id'])->find();
                if($buyer['checked'] == 0){
                    $value = array('status'=>400,'mess'=>'预定者已被冻结');
                    return json($value);
                }
                if($saler['checked'] == 0){
                    $value = array('status'=>400,'mess'=>'出售者已被冻结');
                    return json($value);
                }

                if (is_null($wine_order_record) || is_null($wine_order_saler)){
                    $value = array('status'=>400,'mess'=>'记录不存在或已被匹配');
                }
                else{
                    if ($wine_order_saler['sale_amount']-$wine_order_saler['pipei_amount'] < $post['price']){
                        $value = array('status'=>400,'mess'=>'剩余匹配金额不足');
                    }
                    else{
                        $best_low_price_goods = Db::name('wine_goods')->where('onsale', 1)->order('id asc')->find();
                        $best_low_price_goods_value = explode('-', $best_low_price_goods['value']);
                        if(($wine_order_saler['sale_amount']-$wine_order_saler['pipei_amount']-$post['price'])<$best_low_price_goods_value[0] 
                             && $wine_order_saler['sale_amount']-$wine_order_saler['pipei_amount']-$post['price'] != 0){
                            $value = array('status'=>400,'mess'=>'最后一单金额匹配异常');
                            return json($value);
                        }
                        
                        $value_info = Db::name('wine_goods')->where('id', $wine_order_record['wine_goods_id'])->where('onsale', 1)->find();
                        $scope = explode('-', $value_info['value']);

                        if ($scope[0]<=$post['price'] && $post['price']<=$scope[1]){
                            Db::startTrans();
                            try{
                                // $res = Db::name('wine_order_record')->where('id', $post['wine_order_record_id'])->where('status', 0)->update([
                                //     'buy_amount' => $post['price'],
                                //     'status' => 1
                                // ]);
                                // if (!$res){
                                //     throw new \Exception('订货记录失败');
                                // }
                                $res = Db::name('wine_order_record')->where('id', $post['wine_order_record_id'])->where('status', 0)->update([
                                    'buy_amount' => $post['price'],
                                    'status' => 4
                                ]);
                                if (!$res){
                                    throw new \Exception('订货记录失败');
                                }

                                $update['status'] = 4;
                                if ($wine_order_saler['sale_amount'] != $post['price']) {
                                    $update['separate_order'] = 1;
                                }
                                $res = Db::name('wine_order_saler')->where('id', $post['wine_order_saler_id'])->where('status', 'in', [0,1,4])
                                        ->inc('pipei_amount', $post['price'])->update($update);
                                if (!$res){
                                    throw new \Exception('销售更新失败');
                                }

                                $data = [
                                    'goods_name'=>$wine_order_record['goods_name'],
                                    'addtime'=>$time,
                                    'separate_order'=>isset($update['separate_order']) ? $update['separate_order'] : 0,
                                    'odd'=>uniqid(),
                                    'buy_amount'=>$post['price'],
                                    'goods_thumb'=>$wine_order_record['goods_thumb'],
                                    'buy_id'=>$wine_order_record['buy_id'],
                                    'sale_id'=>$wine_order_saler['sale_id'],
                                    'wine_goods_id'=>$wine_order_record['wine_goods_id'],
                                    'status'=>1,
                                    'wine_order_saler_id'=>$wine_order_saler['id'],
                                    'wine_order_record_id'=>$wine_order_record['id'],
                                    'sale_amount'=>$post['price']+$post['price']*$value_info['rate']/100
                                ];
                                // $res = Db::name('wine_order_buyer')->insert($data);
                                $res = Db::name('wine_order_buyer_advance_match')->insert($data);
                                if (!$res){
                                    throw new \Exception('数据增加失败3');
                                }
                                
                                
                                $value = array('status'=>200,'mess'=>'匹配成功');
                                Db::commit();
                            }
                            catch (\Exception $e){
                                Db::rollback();
                                $value = array('status'=>400,'mess'=>'匹配失败'.$e->getMessage());
                            }
                        }
                        else{
                            $value = array('status'=>400,'mess'=>'金额范围错误');
                        }
                    }
                }
            }

            return json($value);
        }
    }
    
    public function zerensplit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                if(!in_array($data['type'], [1, 2])){
                    $value = array('status'=>0,'mess'=>'请先选择归属责任方');
                    return json($value);
                }
                
                $info = Db::name('wine_order_buyer_contract')->where('id',$data['id'])->find();
                if($info){
                    Db::startTrans();
                    $time = time();
                    try{
                        if($data['type']==2){
                            // 强制成交
                            $res = Db::name('wine_order_buyer_contract')->where('id', $info['id'])->where('status', 'in', [8,9])->update([
                                'status'=>2,
                                'confirm_exchange' => $time,
                                'attech_zeren' => $data['type'],
                                'paytime'=>$time,
                                'pay_status'=>1,
                                'paywayindex'=>-1
                            ]);
                            if (!$res)throw new Exception('转让失败5');


                            $wine_order_saler_info = Db::name('wine_order_saler_contract')
                                                        ->where('sale_id', $info['sale_id'])
                                                        ->where('delete', 0)
                                                        ->where('status', 1)
                                                        ->where('id', $info['wine_order_saler_id'])->find();

                            if ($wine_order_saler_info['pipei_amount'] == $wine_order_saler_info['sale_amount']){
                                 $income_total_price = Db::name('wine_order_buyer_contract')->where('delete', 0)->where('sale_id', $info['sale_id'])->where('pay_status', 1)
                                        ->where('wine_order_saler_id', $wine_order_saler_info['id'])->sum('buy_amount');

                                 if($income_total_price == $wine_order_saler_info['sale_amount']){
                                     $res = Db::name('wine_order_saler_contract')->where('id', $wine_order_saler_info['id'])->update([
                                        'status'=>2,
                                        'confirm_exchange'=>$time,
                                        'attech_zeren'=>$data['type'],
                                        'pay_status'=>1
                                     ]);
                                     if (!$res)throw new Exception('转让失败4');
                                 }
                            }
                            
                            $res = Db::name('contract_record_wallet')->where('user_id', $info['buy_id'])->setInc('total_assets', $info['buy_amount']);
                            if (!$res)throw new Exception('转让失败1');
                            
                            $res = Db::name('contract_record_wallet')->where('user_id', $info['sale_id'])
                                ->dec('total_assets', $info['buy_amount'])
                                ->update();
                            if (!$res)throw new Exception('转让失败3');
                        }
                        elseif ($data['type']==1) {
                            // 更换买家
                            $buy_memberInfo = Db::name('member')->where('phone', $data['buy_phone'])->find();
                            if(is_null($buy_memberInfo)){
                                throw new Exception('用户不存在');
                            }
                            else{
                                $res = Db::name('wine_order_buyer_contract')->where('status', 'in', [8,9])->where('id', $data['id'])->update([
                                    'buy_id' => $buy_memberInfo['id'],
                                    'attech_zeren'=>$data['type'],
                                    'status' => 1,
                                    'pay_status' => 0,
                                    'paytime' => '',
                                    'proof_qrcode' => ''
                                ]);
                                if (!$res)throw new Exception('更新失败3');
                                
                                $value = array('status'=>1,'mess'=>'更新成功');
                            }
                        }
                        else{
                            throw new Exception('请先选择归属责任方');
                        }
                        
                        ys_admin_logs('归属责任','wine_order_buyer_contract',$data['id']);
                        Db::commit();
                        $value = array('status'=>1,'mess'=>'编辑成功');
                    }
                    catch(Exception $e){
                        Db::rollback();
                        $value = array('status'=>0,'mess'=>$e->getMessage());
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $info = Db::name('wine_order_buyer_contract')->alias('wob')
                        ->join('wine_goods_contract wg', 'wg.id = wob.wine_goods_id', 'left')
                        ->join('member m', 'm.id=wob.sale_id', 'left')
                        ->join('member b', 'b.id=wob.buy_id', 'left')
                        ->where('wob.id', input('id'))
                        ->field('wob.*, b.true_name b_true_name, b.phone b_phone, m.true_name m_true_name, m.phone m_phone')
                        ->where('wob.delete', 0)->where('wob.status', 'in', [8, 9])->where('wob.attech_zeren', 'in', [0])
                        ->find();
                        // var_dump($info);exit;
                if($info){
                    $this->assign('info', $info);
                    return $this->fetch('zeren_split');
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    public function editWineGoods(){
        $input = input();
        if(request()->isAjax()){
            // if(empty($input['id']) || empty($input['phone'])){
            if(empty($input['id'])){
                $value = array('status'=>400,'mess'=>'参数异常');
            }
            else{
                $data = [];
                if($input['sale_amount'] <= 0){
                    $value = array('status'=>400,'mess'=>'参数异常');
                }
                else{
                    $data['sale_amount'] = $input['sale_amount'];
                    
                    $wine_deal_area = Db::name('wine_contract_day')->where('id', $input['type'])->find();
                    if(is_null($wine_deal_area)){
                        $value = array('status'=>400,'mess'=>'参数异常');
                    }
                    else{
                        $data['wine_contract_day_id'] = $input['type'];
                        
                        if(!empty($input['phone'])){
                            $memberInfo = Db::name('member')->where('phone', $input['phone'])->find();
                            if(is_null($memberInfo)){
                                $value = array('status'=>400,'mess'=>'参数异常');
                                return json($value);
                            }
                            else{
                                $data['sale_id'] = $memberInfo['id'];
                            }
                        }
                        
                        if(!empty($input['buy_phone'])){
                            $buy_memberInfo = Db::name('member')->where('phone', $input['buy_phone'])->find();
                            if(is_null($buy_memberInfo)){
                                $value = array('status'=>400,'mess'=>'参数异常');
                                return json($value);
                            }
                            else{
                                $data['assign_buyer_id'] = $buy_memberInfo['id'];
                            }
                        }
                        else{
                            $data['assign_buyer_id'] = 0;
                        }
                        
                        $wine_order_saler_info = Db::name('wine_order_saler_contract')->where('id', $input['id'])->find();
                        if(empty($wine_order_saler_info)){
                            $value = array('status'=>400,'mess'=>'参数异常');
                        }
                        else{
                            $res = Db::name('wine_order_saler_contract')->where('id', $input['id'])->update($data);
                            
                            if($res){
                                ys_admin_logs('替换出售者', 'wine_order_saler_contract', $input['id']);
                                $value = array('status'=>1,'mess'=>'更新成功');
                            }
                            else{
                                $value = array('status'=>400,'mess'=>'更新失败');
                            }
                        }
                    }
                }
            }
            
            return json($value);
        }
        else{
            $deal_area = Db::name('wine_contract_day')->column('id, day');
            
            $info = Db::name('wine_order_saler_contract')->alias('wos')
                    ->join('wine_contract_day wda', 'wda.id = wos.wine_contract_day_id', 'left')
                    ->join('member m', 'm.id = wos.sale_id', 'left')
                    ->join('member bm', 'bm.id = wos.assign_buyer_id', 'left')
                    ->field('wos.*, m.true_name, m.user_name, m.phone, wda.day, bm.true_name bm_true_name, bm.user_name bm_user_name, bm.phone bm_phone')
                    ->where('wos.id', $input['id'])
                    ->find();
            
            $this->assign('info', $info);
            $this->assign('deal_area', $deal_area);
            return $this->fetch();
        }
    }
}