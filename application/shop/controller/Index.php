<?php
/*
 * @Descripttion:
 * @Copyright: ©版权所有
 * @Contact: QQ:2487937004
 * @Date: 2020-03-10 02:15:07
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2020-08-03 16:14:50
 */
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;

class Index extends Common
{
    public function index(){
        $webconfig = $this->webconfig;
        $this->assign('webconfig',$webconfig);
        return $this->fetch();
    }

    public function index_v3(){
        $shop_id = session('shopsh_id');
        $wallets = Db::name('shop_wallet')->where('shop_id',$shop_id)->find();
        $ordercount = Db::name('order')->where('shop_id',$shop_id)->count();

        $nowtime = time();
        $year = date('Y',time());
        $year2 = $year+1;
        $month = date('m',time());
        $month2 = $month+1;

        $day = date('d',time());
        $nowmonth = strtotime($year.'-'.$month.'-01 00:00:00');
        $lastmonth = strtotime($year.'-'.$month2.'-01 00:00:00');
        $nowyear = strtotime($year.'-01-01 00:00:00');
        $lastyear = strtotime($year2.'-01-01 00:00:00');

        $order_num = Db::name('order')->where('shop_id',$shop_id)->where('state',1)->count();
        $month_order_num = Db::name('order')->where('shop_id',$shop_id)->where('state',1)->where('addtime','egt',$nowmonth)->where('addtime','lt',$lastmonth)->count();
        $deal_num = Db::name('order')->where('shop_id',$shop_id)->where('addtime','egt',$nowmonth)->where('addtime','lt',$lastmonth)->where('order_status',1)->count();
        $dai_num = Db::name('order')->where('shop_id',$shop_id)->where('addtime','egt',$nowmonth)->where('addtime','lt',$lastmonth)->where('state',0)->count();
        $shou_num = Db::name('order')->where('shop_id',$shop_id)->where('addtime','egt',$nowmonth)->where('addtime','lt',$lastmonth)->where('shouhou',1)->count();

        if($month_order_num > 0){
            $deal_lv = sprintf("%.2f",$deal_num/$month_order_num)*100;
            $dai_lv = sprintf("%.2f",$dai_num/$month_order_num)*100;
            $shou_lv = sprintf("%.2f",$shou_num/$month_order_num)*100;
        }else{
            $deal_lv = 0;
            $dai_lv = 0;
            $shou_lv = 0;
        }

        $month_salenum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id=b.id','INNER')->where('b.shop_id',$shop_id)->where('b.state',1)->where('b.addtime','egt',$nowmonth)->where('b.addtime','lt',$lastmonth)->count();
        $month_tuinum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id = b.id','INNER')->where('b.shop_id',$shop_id)->where('a.th_status','in','1,2,3,4')->where('b.state',1)->where('b.addtime','egt',$nowmonth)->where('b.addtime','lt',$lastmonth)->count();
        $month_huannum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id = b.id','INNER')->where('b.shop_id',$shop_id)->where('a.th_status','in','5,6,7,8')->where('b.state',1)->where('b.addtime','egt',$nowmonth)->where('b.addtime','lt',$lastmonth)->count();

        $monthSalenumStr = '';
        $monthTuinumStr = '';
        $monthHuannumStr = '';
        // 全年各月销售量、退款量、换货量
        for ($i=1; $i <= 12; $i++) {
            $monthSalenum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id=b.id','INNER')->where('b.shop_id',$shop_id)->where('b.state',1)->where("FROM_UNIXTIME(b.addtime,'%m') = ".$i)->where('b.addtime','>',$nowyear)->count();
            $monthTuinum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id = b.id','INNER')->where('b.shop_id',$shop_id)->where('a.th_status','in','1,2,3,4')->where('b.state',1)->where("FROM_UNIXTIME(b.addtime,'%m') = ".$i)->where('b.addtime','>',$nowyear)->count();
            $monthHuannum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id = b.id','INNER')->where('b.shop_id',$shop_id)->where('a.th_status','in','5,6,7,8')->where('b.state',1)->where("FROM_UNIXTIME(b.addtime,'%m') = ".$i)->where('b.addtime','>',$nowyear)->count();
            $i < 12 ? $separator = ',' : $separator = '';
            $monthSalenumStr .= $monthSalenum.$separator;
            $monthTuinumStr .= $monthTuinum.$separator;
            $monthHuannumStr .= $monthHuannum.$separator;
        }

        // 总营业额
        $totalTurnover = Db::name('order')->where('state','1')->where('shop_id',$shop_id)->where('can_time','null')->sum('goods_price');

        $this->assign('order_num',$order_num);
        $this->assign('month_order_num',$month_order_num);
        $this->assign('wallet_price',$wallets['price']);
        $this->assign('ordercount',$ordercount);
        $this->assign('deal_lv',$deal_lv);
        $this->assign('dai_lv',$dai_lv);
        $this->assign('shou_lv',$shou_lv);
        $this->assign('month_salenum',$month_salenum);
        $this->assign('month_tuinum',$month_tuinum);
        $this->assign('month_huannum',$month_huannum);
        $this->assign('totalTurnover',$totalTurnover);
        $this->assign('month',date('n',time()));
        $this->assign('year', $year);
        $this->assign('monthSalenumStr',$monthSalenumStr);
        $this->assign('monthTuinumStr',$monthTuinumStr);
        $this->assign('monthHuannumStr',$monthHuannumStr);
        return $this->fetch();
    }

}