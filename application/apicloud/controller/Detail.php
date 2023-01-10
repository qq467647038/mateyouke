<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class Detail extends Common{
    //信用值明细
    public function creditRecord(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    
                    $list = Db::name('detail')->where('user_id', $user_id)->where('sr_type', 11)->where('sr_type', 22)->field('de_type,price,time')->select();
                    foreach ($list as &$item){
                        switch ($item['sr_type']) {
                            case 11:
                                $item['remark'] = '进货付款提交凭证增加信用值';
                                break;
                            case 22:
                                $item['remark'] = '商城强制成交增加信用值';
                                break;
                        }
                        
                        $item['time'] = date('Y-m-d H:i:s', $item['time']);
                    }
                    
                    $value = array('status'=>200,'mess'=>'获取成功','data'=>$list);
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);        
    }
    
    //KLG明细
    public function klgRecord(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    
                    $list = Db::name('detail')->alias('d')->where('d.user_id', $user_id)->field('d.de_type,d.price,d.time,d.sr_type,d.zc_type,m.user_name m_user_name,t.user_name t_user_name, d.target_id')
                        ->join('member m', 'm.id = d.user_id', 'left')
                        ->join('member t', 't.id = d.target_id', 'left')
                        ->where(function ($query){
                            $query->where('sr_type', 'in', [25,26,66])->whereOr('zc_type', 'in', [25,60]);
                        })
                        ->order('d.id desc')
                        ->select();
                    foreach ($list as &$item){ 
                        switch ($item['sr_type']) {
                            case 25:
                                $item['remark'] = '后台添加KLG';
                                break;
                            case 26:
                                $item['remark'] = '兑换实物获得KLG';
                                break;
                            case 66:
                                $item['remark'] = '分割发货奖励KLG';
                                break;
                        }
                                
                        switch($item['zc_type']){
                            case 25:
                                $item['remark'] = 'KLG修改';
                                break;
                            case 60:
                                $item['remark'] = 'KLG转余额';
                                break;
                        }
                        $item['time'] = date('Y-m-d H:i:s', $item['time']);
                    }
                            
                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);        
    }
    
    public function pointRecord(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('index') == 0){
                        $time = strtotime('today');
                    }
                    else{
                        $time = strtotime('1970-01-01 00:00:00');
                    }
                    
                    $list = Db::name('detail')->alias('d')->where('d.user_id', $user_id)->field('m.phone m_phone,t.phone t_phone,d.de_type,d.price,d.time,d.sr_type,d.zc_type,m.true_name m_true_name,t.true_name t_true_name, d.target_id')
                        ->join('member m', 'm.id = d.user_id', 'left')
                        ->join('member t', 't.id = d.target_id', 'left')
                        ->where('d.time', '>=', $time)
                        ->where(function ($query){
                            $query->where('sr_type', 121)->whereOr('sr_type', 71)->whereOr('sr_type', 1111)->whereOr('sr_type', 25)->whereOr('sr_type', 500)->whereOr('sr_type', 108)
                                    ->whereOr('zc_type', 70)->whereOr('zc_type', 1000)->whereOr('zc_type', 1001)->whereOr('zc_type', 110)->whereOr('zc_type', 25)->whereOr('zc_type', 105);
                        })
                        ->order('d.id desc')
                        // ->field('d.*, m.user_name')
                        ->select();
                    foreach ($list as &$item){ 
                        switch ($item['sr_type']) {
                            case 121:
                                 $item['remark'] = '佣金提现积分';
                                 break;
                            case 25:
                                 $item['remark'] = '后台修改';
                                 break;
                            case 500:
                                 $item['remark'] = '购买积分商城商品';
                                 break;
                            case 71:
                                $wine_deal_area_id = Db::name('wine_order_record')->where('id', $item['target_id'])->value('wine_deal_area_id');
                                $desc = Db::name('wine_deal_area')->where('id', $wine_deal_area_id)->value('desc');
                                $item['remark'] = '普通竞拍'.$desc.'预约金返还';
                                 break;
                            case 1111:
                                $wine_contract_day_id = Db::name('wine_order_record_contract')->where('id', $item['target_id'])->value('wine_contract_day_id');
                                $day = Db::name('wine_contract_day')->where('id', $wine_contract_day_id)->value('day');
                                $item['remark'] = '合约竞拍【'.$day.'天】预约金返还';
                                break;
                            case 108:
                                $item['remark'] = '转账：'.($item['t_true_name'] ? $item['t_true_name'] : $item['t_user_name']).'【'.$item['t_phone'].'】 转 '.($item['m_true_name'] ? $item['m_true_name'] : $item['m_user_name']).'【'.$item['m_phone'].'】';
                                break;
                        }
                                
                        switch($item['zc_type']){
                            case 25:
                                 $item['remark'] = '后台修改';
                                 break;
                            case 70: 
                                $wine_deal_area_id = Db::name('wine_order_record')->where('id', $item['target_id'])->value('wine_deal_area_id');
                                $desc = Db::name('wine_deal_area')->where('id', $wine_deal_area_id)->value('desc');
                                $item['remark'] = '普通竞拍'.$desc.'预约金';
                                break;
                            case 1000:
                                $wine_contract_day_id = Db::name('wine_order_record_contract')->where('id', $item['target_id'])->value('wine_contract_day_id');
                                $day = Db::name('wine_contract_day')->where('id', $wine_contract_day_id)->value('day');
                                $item['remark'] = '合约竞拍【'.$day.'天】预约金';
                                break;
                            case 1001:
                                $item['remark'] = '合约购买';
                                break;
                            case 110:
                                $item['remark'] = '寄售服务费';
                                break;
                            case 105:
                                $item['remark'] = '转账：'.($item['m_true_name'] ? $item['m_true_name'] : $item['m_user_name']).'【'.$item['m_phone'].'】 转 '.($item['t_true_name'] ? $item['t_true_name'] : $item['t_user_name']).'【'.$item['t_phone'].'】';
                                break;
                        }
                        $item['time'] = date('Y-m-d H:i:s', $item['time']);
                    }
                            
                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    // 账户余额明细
    public function fuelRecord(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    
                    $list = Db::name('detail')->alias('d')->where('d.user_id', $user_id)->field('m.phone m_phone,t.phone t_phone,d.de_type,d.price,d.time,d.sr_type,d.zc_type,m.true_name m_true_name,t.true_name t_true_name, d.target_id')
                        ->join('member m', 'm.id = d.user_id', 'left')
                        ->join('member t', 't.id = d.target_id', 'left')
                        ->where(function ($query){
                            $query->whereOr('sr_type', 120)->whereOr('sr_type', 100)->whereOr('sr_type', 24)->whereOr('sr_type', 8)->whereOr('zc_type', 24)->whereOr('zc_type', 2)->whereOr('zc_type', 5)->whereOr('zc_type', 130);
                        })
                        ->order('d.id desc')
                        // ->field('d.*, m.user_name')
                        ->select();
                    foreach ($list as &$item){ 
                        switch ($item['sr_type']) {
                            case 120:
                                 $item['remark'] = '佣金转入';
                                 break;
                            case 8:
                                $item['remark'] = '转账：'.($item['t_true_name'] ? $item['t_true_name'] : $item['t_user_name']).'【'.$item['t_phone'].'】 转 '.($item['m_true_name'] ? $item['m_true_name'] : $item['m_user_name']).'【'.$item['m_phone'].'】';
                                break;
                            case 24:
                                 $item['remark'] = '后台余额修改';
                                 break;
                            case 100:
                                 $item['remark'] = 'USDT充值';
                                 break;
                        }
                                
                        switch($item['zc_type']){
                            case 5:
                                $item['remark'] = '转账：'.($item['m_true_name'] ? $item['m_true_name'] : $item['m_user_name']).'【'.$item['m_phone'].'】 转 '.($item['t_true_name'] ? $item['t_true_name'] : $item['t_user_name']).'【'.$item['t_phone'].'】';
                                break;
                            case 24:
                                 $item['remark'] = '后台余额修改';
                                 break;
                            case 2:
                                 $item['remark'] = '购买商品';
                                 break;
                            case 130:
                                 $item['remark'] = '余额提现';
                                 break;
                        }
                        $item['time'] = date('Y-m-d H:i:s', $item['time']);
                    }
                            
                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //品牌使用值明细
    public function brandRecord(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    
                    $list = Db::name('detail')->alias('d')->where('d.user_id', $user_id)
                        ->field('d.de_type,d.price,d.time,d.sr_type,d.zc_type, d.target_id, m.user_name m_user_name, m.true_name m_true_name, t.user_name t_user_name, t.true_name t_true_name, m.phone m_phone, t.phone t_phone')
                        ->join('member m', 'm.id = d.user_id', 'left')
                        ->join('member t', 't.id = d.target_id', 'left')
                        ->where(function ($query){
                            $query->where('sr_type', 80)->whereOr('sr_type', 64)->whereOr('sr_type', 65)->whereOr('zc_type', 120);
                        })
                        ->order('d.id desc')
                        ->select();
                    foreach ($list as &$item){
                        switch ($item['sr_type']) {
                            case 8:
                                $item['remark'] = '余额转账：'.$item['t_user_name'].' 转给姓名:'.($item['m_true_name']?$item['m_true_name']:$item['m_user_name']).' - 手机号:'.$item['m_phone'];
                                break;
                            case 24:
                                $item['remark'] = '后台修改';
                                break;
                            case 60:
                                $item['remark'] = 'KLG转余额';
                                break;
                            case 63:
                                $item['remark'] = '进货转出售';
                                break;
                            case 67:
                                $item['remark'] = '买家没有付款增加余额';
                                break;
                            case 71:
                                 $item['remark'] = '返还预定冻结余额';
                                 break;
                            case 72:
                                $item['remark'] = '抢购预定添加余额';
                                break;
                            case 74:
                                $item['remark'] = '购买成功解冻余额回到余额账户里';
                                break;
                            case 75:
                                $item['remark'] = '购买成功解冻余额回到余额账户里';
                                break;
                            case 76:
                                $item['remark'] = '购买成功解冻余额回到余额账户里';
                                break;
                            case 77:
                                $item['remark'] = '后台强制取消添加余额';
                                break;
                            case 64:
                                $item['remark'] = '分享奖励';
                                break;
                            case 65:
                                $item['remark'] = '市场分润';
                                break;
                            case 80:
                                $item['remark'] = '管理分润';
                                break;
                            case 1154:
                                $item['remark'] = '合约分享奖励';
                                break;
                            case 1155:
                                $item['remark'] = '合约市场分润';
                                break;
                            case 1180:
                                $item['remark'] = '合约管理分润';
                                break;
                        }
                                
                        switch($item['zc_type']){
                            case 5:
                                $item['remark'] = '余额转账：'.$item['m_user_name'].' 转给姓名:'.($item['t_true_name']?$item['t_true_name']:$item['t_user_name']).' - 手机号:'.$item['t_phone'];
                                break;
                            case 24:
                                $item['remark'] = '后台修改';
                                break;
                            case 61:
                                $item['remark'] = '平台寄售';
                                break;
                            case 120:
                                $item['remark'] = '佣金提现';
                                break;
                            case 1003:
                                $item['remark'] = '寄售服务费';
                                break;
                        }
                        $item['time'] = date('Y-m-d H:i:s', $item['time']);
                    }
                            
                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);        
    }
    
    //购物券明细
    public function buyTicketRecord(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    
                    
                    $list = Db::name('detail')->where('user_id', $user_id)->field('de_type,price,time,sr_type,zc_type')
                        ->where(function ($query){
                            $query->where('sr_type', 9)->whereOr('sr_type', 10)->whereOr('sr_type', 13)->whereOr('sr_type', 15);
                        })
                        ->order('id desc')
                        ->select();
                    foreach ($list as &$item){
                        switch($item['sr_type']){
                            case 9:
                                $item['remark'] = '品牌使用值兑换购物券';
                                break;
                            case 10:
                                $item['remark'] = '管理奖兑换购物券';
                                break;
                            case 13:
                                $item['remark'] = '直推购物券奖';
                                break;
                        }
                        $item['time'] = date('Y-m-d H:i:s', $item['time']);
                    }
                            
                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);        
    }
    
    //总库存明细
    public function totalStockRecord(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    
                    $list = Db::name('detail')->where('user_id', $user_id)->field('de_type,price,time,sr_type,zc_type')
                            ->where(function ($query){
                                $query->whereOr('sr_type', 21)->whereOr('sr_type', 23)->whereOr('zc_type', 14)->whereOr('zc_type', 16);
                            })
                            ->order('id desc')
                            ->select();
                    foreach ($list as &$item){
                        switch($item['sr_type']){
                            case 21:
                                $item['remark'] = '订货增加库存';
                                break;
                            case 23:
                                $item['remark'] = '商城强制成交订货增加库存';
                                break;
                        }
                        
                        switch($item['zc_type']){
                            case 14:
                                $item['remark'] = '进货转出售扣库存';
                                break;
                            case 16:
                                $item['remark'] = '兑换实物酒扣库存';
                                break;
                        }
                        $item['time'] = date('Y-m-d H:i:s', $item['time']);
                    }
                            
                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);        
    }
    
    //管理奖明细
    public function managerRewardRecord(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    
                    $list = Db::name('detail')->where('user_id', $user_id)->field('de_type,price,time,sr_type,zc_type')
                        ->where(function ($query){
                            $query->where('sr_type', 12)->whereOr('sr_type', 14)
                                  ->whereOr('zc_type', 4)->whereOr('zc_type', 7);
                        })
                        ->order('id desc')
                        ->select();
                    foreach ($list as &$item){
                        switch($item['sr_type']){
                            case 12:
                                $item['remark'] = '直推管理奖';
                                break;
                        }
                        
                        switch($item['zc_type']){
                            case 4:
                                $item['remark'] = '管理奖兑换扣管理奖';
                                break;
                            case 7:
                                $item['remark'] = '管理奖兑换购物券';
                                break;
                        }
                        $item['time'] = date('Y-m-d H:i:s', $item['time']);
                    }
                    
                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$list);
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);        
    }
}