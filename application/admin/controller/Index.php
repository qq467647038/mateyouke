<?php
/*
 * @Descripttion: 总后台框架控制器
 * @Copyright: ©版权所有
 * @Contact: QQ:2487937004
 * @Date: 2020-03-09 17:48:34
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2020-08-03 16:13:40
 */
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
// use think\Session;

class Index extends Common
{
    public function index(){
        $webconfig = $this->webconfig;
        $this->assign('webconfig',$webconfig);
        // dump(Session::get());
        return $this->fetch();
    }

    public function changepass(){
        $admin_id = session('admin_id');
        if(request()->isPost()){
            $password = input('password');
            
            Db::startTrans();
            try{
                $res = Db::name('admin')->where('id', $admin_id)->update(['password'=>md5($password)]);
                if(!$res){
                    throw new \Exception('修改密码失败');
                }
                
                $remark = '修改管理员密码';
                $res = ys_admin_logs('修改密码','admin',$admin_id,$remark);
                if($res == 'false'){
                    throw new \Exception('记录失败');
                }
                
                $bool = true;
                Db::commit();
            }
            catch(\Exception $e){
                $bool = false;
                Db::rollback();
            }
            

            if($bool){
                return $this->success('修改密码成功');
            }else{
                return $this->error('修改密码失败');
            }
        }


        $admin = Db::name('admin')->where('id', $admin_id)->find();

        $this->assign('admin', $admin);
        return $this->fetch();
    }

    public function index_v3(){
        $wallets = Db::name('pt_wallet')->where('id',1)->find();

        $nowtime = time();
        $year = date('Y',time());
        $year2 = $year+1;
        $month = date('m',time());
        $month2 = $month+1;

        $day = date('d',time());
        // 当月
        $nowmonth = strtotime($year.'-'.$month.'-01 00:00:00');
        // 下一个月
        $lastmonth = strtotime($year.'-'.$month2.'-01 00:00:00');
        $nowyear = strtotime($year.'-01-01 00:00:00');
        $lastyear = strtotime($year2.'-01-01 00:00:00');

        $order_num = Db::name('order')->where('state',1)->count();
        $month_order_num = Db::name('order')->where('state',1)->where('addtime','egt',$nowmonth)->count();
        $deal_num = Db::name('order')->where('addtime','egt',$nowmonth)->where('addtime','lt',$lastmonth)->where('order_status',1)->count();
        $dai_num = Db::name('order')->where('addtime','egt',$nowmonth)->where('addtime','lt',$lastmonth)->where('state',0)->count();
        $shou_num = Db::name('order')->where('addtime','egt',$nowmonth)->where('addtime','lt',$lastmonth)->where('shouhou',1)->count();

        if($month_order_num > 0){
            $deal_lv = sprintf("%.2f",$deal_num/$month_order_num)*100;
            $dai_lv = sprintf("%.2f",$dai_num/$month_order_num)*100;
            $shou_lv = sprintf("%.2f",$shou_num/$month_order_num)*100;
        }else{
            $deal_lv = 0;
            $dai_lv = 0;
            $shou_lv = 0;
        }

        $month_salenum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id=b.id','INNER')->where('b.state',1)->where('b.addtime','egt',$nowmonth)->where('b.addtime','lt',$lastmonth)->count();
        $month_tuinum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id = b.id','INNER')->where('a.th_status','in','1,2,3,4')->where('b.state',1)->where('b.addtime','egt',$nowmonth)->where('b.addtime','lt',$lastmonth)->count();
        $month_huannum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id = b.id','INNER')->where('a.th_status','in','5,6,7,8')->where('b.state',1)->where('b.addtime','egt',$nowmonth)->where('b.addtime','lt',$lastmonth)->count();

        $monthSalenumStr = '';
        $monthTuinumStr = '';
        $monthHuannumStr = '';
        // 全年各月销售量、退款量、换货量
        for ($i=1; $i <= 12; $i++) {
            $monthSalenum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id=b.id','INNER')->where('b.state',1)->where("FROM_UNIXTIME(b.addtime,'%m') = ".$i)->where('b.addtime','>',$nowyear)->count();
            $monthTuinum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id = b.id','INNER')->where('a.th_status','in','1,2,3,4')->where('b.state',1)->where("FROM_UNIXTIME(b.addtime,'%m') = ".$i)->where('b.addtime','>',$nowyear)->count();
            $monthHuannum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id = b.id','INNER')->where('a.th_status','in','5,6,7,8')->where('b.state',1)->where("FROM_UNIXTIME(b.addtime,'%m') = ".$i)->where('b.addtime','>',$nowyear)->count();
            $i < 12 ? $separator = ',' : $separator = '';
            $monthSalenumStr .= $monthSalenum.$separator;
            $monthTuinumStr .= $monthTuinum.$separator;
            $monthHuannumStr .= $monthHuannum.$separator;
        }

        // dump($monthSalenum1);die;

        // 总营业额
//        $totalTurnover = Db::name('order')->where('state','1')->where('can_time','null')->sum('goods_price');
        $totalTurnover = Db::name('order')->where('state','1')->where('can_time','null')->sum('total_price');
        // 总会员数
        $memberNum = Db::name('member')->count();
        // 总有效仓库
        $memberStock = Db::name('wallet')->where('total_stock', '>', 0)->count();
        // 总充值 - 收入|减少
        $recharge_income_price = Db::name('detail')->where('de_type', 1)->where('sr_type', 58)->sum('price');
        $recharge_reduce_price = Db::name('detail')->where('de_type', 2)->where('zc_type', 59)->sum('price');
        $recharge_brand = $recharge_income_price - $recharge_reduce_price;
        // 总扣除预定品牌使用费
        $bespokeBrand = Db::name('detail')->where('de_type', 2)->where('zc_type', 10)->sum('price');
        // 总扣除利润百分比费用
        $profitPercent = Db::name('detail')->where('de_type', 2)->where('zc_type', 9)->sum('price');
        // 总订单数
        $totalOrder = Db::name('wine_order_record')->where('status', 2)->count();



        $this->assign('memberStock', $memberStock);
        $this->assign('recharge_brand', $recharge_brand);
        $this->assign('bespokeBrand', $bespokeBrand);
        $this->assign('profitPercent', $profitPercent);
        $this->assign('totalOrder', $totalOrder);


        $this->assign('wallet_price',$wallets['price']);
        $this->assign('order_num',$order_num);
        $this->assign('month_order_num',$month_order_num);
        $this->assign('deal_lv',$deal_lv);
        $this->assign('dai_lv',$dai_lv);
        $this->assign('shou_lv',$shou_lv);
        $this->assign('month_salenum',$month_salenum);
        $this->assign('month_tuinum',$month_tuinum);
        $this->assign('month_huannum',$month_huannum);
        $this->assign('totalTurnover',$totalTurnover);
        $this->assign('memberNum',$memberNum);
        $this->assign('month',date('n',time()));
        $this->assign('year', $year);
        $this->assign('monthSalenumStr',$monthSalenumStr);
        $this->assign('monthTuinumStr',$monthTuinumStr);
        $this->assign('monthHuannumStr',$monthHuannumStr);
        return $this->fetch();
    }

}
