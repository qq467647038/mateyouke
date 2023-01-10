<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Caiwu extends Common{
    public function trade_detail(){
        $input = input();
        $list = Db::name('detail')->alias('d')
                    ->join('member m', 'm.id = d.user_id', 'inner')
                    ->join('member t', 't.id = d.target_id', 'left')
                    ->where('d.user_id', $input['id'])
                    ->where(function($query){
                        $query->where('sr_type', 'in', [8,24,60,63,67,64,65,72,74,75,76,77,71,80,90,100])->whereOr('zc_type', 'in', [5,24,17,61,70]);
                    })
                    ->field('d.*, m.user_name m_user_name, t.user_name t_user_name')
                    ->order('d.id desc')->paginate(50)->each(function($item){
                        switch($item['sr_type']){
                            // case 1:
                            //     $item['remark'] = '订单分成';
                            //     break;
                            // case 2:
                            //     $item['remark'] = '订单退款';
                            //     break;
                            // case 3:
                            //     $item['remark'] = '邀请注册';
                            //     break;
                            // case 4:
                            //     $item['remark'] = '礼物分成';
                            //     break;
                            // case 5:
                            //     $item['remark'] = '充值';
                            //     break;
                            case 8:
                                $item['remark'] = '乐购分转账：'.$item['t_user_name'].'转'.$item['m_user_name'];
                                break;
                            case 24:
                                $item['remark'] = '后台余额充值';
                                break;
                            case 60:
                                $item['remark'] = 'KLG转乐购分';
                                break;
                            case 63:
                                $item['remark'] = '进货转出售增加乐购分';
                                break;
                            case 25:
                                $item['remark'] = '后台添加KLG';
                                break;
                            case 26:
                                $item['remark'] = '兑换实物获得KLG';
                                break;
                            case 17:
                                $item['remark'] = '抢购增加冻结乐购分';
                                break;
                            case 61:
                                $item['remark'] = '平台寄售增加冻结乐购分';
                                break;
                            case 64:
                                $item['remark'] = '分享奖励';
                                break;
                            case 65:
                                $item['remark'] = '市场分润';
                                break;
                            case 66:
                                $item['remark'] = '分割发货奖励KLG';
                                break;
                            case 67:
                                $item['remark'] = '买家没有付款增加乐购分';
                                break;
                            case 70:
                                $item['remark'] = '预约增加冻结乐购分';
                                break;
                            case 71:
                                 $item['remark'] = '抢购失败解冻乐购分';
                                 break;
                            case 72:
                                $item['remark'] = '抢购预定添加乐购分';
                                break;
                            case 74:
                                $item['remark'] = '购买成功解冻乐购分回到余额账户里';
                                break;
                            case 75:
                                $item['remark'] = '购买成功解冻乐购分回到余额账户里';
                                break;
                            case 76:
                                $item['remark'] = '购买成功解冻乐购分回到余额账户里';
                                break;
                            case 77:
                                $item['remark'] = '后台强制取消添加乐购分';
                                break;
                            case 80:
                                $item['remark'] = '管理分润';
                                break;
                            case 90:
                                $item['remark'] = '注册保证金';
                                break;
                            case 100:
                                $item['remark'] = 'usdt充值';
                                break;
                            // case 9:
                        }
                        
                        switch ($item['zc_type']) {
                            // case 2:
                            //     $item['remark'] = '支付订单';
                            //     break;
                            // case 4:
                            //     $item['remark'] = '管理奖兑换扣管理奖';
                            //     break;
                            case 5:
                                $item['remark'] = '乐购分转账：'.$item['m_user_name'].'转'.$item['t_user_name'];
                                break;
                            case 24:
                                $item['remark'] = '后台余额充值';
                                break;
                            case 61:
                                $item['remark'] = '平台寄售乐购分';
                                break;
                            case 25:
                                $item['remark'] = 'KLG充值';
                                break;
                            case 60:
                                $item['remark'] = 'KLG转乐购分';
                                break;
                            case 25:
                                $item['remark'] = 'KLG充值';
                                break;
                            case 63:
                                $item['remark'] = '进货转出售扣除冻结乐购分';
                                break;
                            case 67:
                                $item['remark'] = '未付款扣除冻结乐购分';
                                break;
                            case 70:
                                $wine_deal_area_id = Db::name('wine_order_record')->where('id', $item['target_id'])->value('wine_deal_area_id');
                                $desc = Db::name('wine_deal_area')->where('id', $wine_deal_area_id)->value('desc');
                                $item['remark'] = '普通竞拍'.$desc.'预约金';
                                break;
                            case 1000:
                                $wine_deal_area_id = Db::name('wine_order_record_contract')->where('id', $item['target_id'])->value('wine_deal_area_id');
                                $desc = Db::name('wine_deal_area_contract')->where('id', $wine_deal_area_id)->value('desc');
                                $item['remark'] = '合约竞拍'.$desc.'预约金';
                                break;
                            case 72:
                                $item['remark'] = '抢购预定扣除冻结乐购分';
                                break;
                            case 74:
                                $item['remark'] = '确认转让扣除冻结乐购分';
                                break;
                            case 75:
                                $item['remark'] = '自动确认转让扣除冻结乐购分';
                                break;
                            case 76:
                                $item['remark'] = '后台确认转让扣除冻结乐购分';
                                break;
                            case 77:
                                $item['remark'] = '后台强制取消扣除冻结乐购分';
                                break;
                            // case 6:
                            //     $item['remark'] = '品牌使用值兑换购物券';
                            //     break;
                            // case 7:
                            //     $item['remark'] = '管理奖兑换购物券';
                            //     break;
                            // case 8:
                            //     $item['remark'] = '管理奖兑换扣品牌使用值';
                            //     break;
                            // case 9:
                            //     $item['remark'] = '成功交易扣除出售者相应获得利润的百分比品牌使用值';
                            //     break;
                            // case 10:
                            //     $item['remark'] = '预定扣除品牌使用值';
                            //     break;
                            // case 11:
                            //     $item['remark'] = '后台匹配成功扣买家品牌使用值';
                            //     break;
                            // case 59:
                            //     $item['remark'] = '后台充值品牌使用值';
                            //     break;
                            // case 12:
                            //     $item['remark'] = '违规罚款';
                            //     break;
                            // case 13:
                            //     $item['remark'] = '下级违规罚款';
                            //     break;
                            // case 14:
                            //     $item['remark'] = '进货转出售扣库存';
                            //     break;
                            // case 15:
                            //     $item['remark'] = '后台强制成交扣品牌使用费';
                            //     break;
                            // case 16:
                            //     $item['remark'] = '兑换实物酒扣库存';
                            //     break;
                        }
                        
                        return $item;
                    });
        $count = Db::name('detail')->alias('d')
                    ->join('member m', 'm.id = d.user_id', 'inner')
                    ->where('d.user_id', $input['id'])->count();
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
            return $this->fetch('trade_detail_ajaxpage');
        }else{
            return $this->fetch('trade_detail_lst');
        }
    }
}