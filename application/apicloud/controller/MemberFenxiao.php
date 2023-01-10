<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;
use think\Paginator;

class MemberFenxiao extends Common{
    
    //获取用户分销信息接口
    public function index(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $members = Db::name('member')->where('id',$user_id)->field('user_name,headimgurl,one_level')->find();
                    if($members){
                        $webconfig = $this->webconfig;
                        
                        if($members['headimgurl']){
                            $members['headimgurl'] = $webconfig['weburl'].'/'.$members['headimgurl'];
                        }else{
                            $logo = Db::name('shops')->where('id',1)->value('logo');
                            $members['headimgurl'] = $webconfig['weburl'].'/'.$logo;
                        }
                        
                        if($members['one_level']){
                            $members['one_level_name'] = Db::name('member')->where('id',$members['one_level'])->value('user_name');
                        }else{
                            $members['one_level_name'] = '';
                        }
                        $profits = Db::name('wallet')->where('user_id',$user_id)->find();
                        $members['profit_wallet'] = $profits['price'];
                        // $profits = Db::name('profit')->where('user_id',$user_id)->find();
                        // $members['profit_wallet'] = $profits['price'];
						
                        // 只计算1级
                        // $members['fxorder_num'] = Db::name('order')->where(function ($query) use ($user_id){
                        //     $query->where('state',1)->where('fh_status',1)->where('order_status',1)->where('onefen_id',$user_id);
                        // })->whereOr(function ($query) use ($user_id){
                        //     $query->where('state',1)->where('fh_status',1)->where('order_status',1)->where('twofen_id',$user_id);
                        // })->count();
                        $members['fxorder_num'] = Db::name('order')->where(function ($query) use ($user_id){
                            $query->where('state',1)->where('fh_status',1)->where('order_status',1)->where('onefen_id',$user_id);
                        })->count();
                        // 团队人数只计算1级
                        $members['tuan_num'] = Db::name('member_friend')->where(['uid'=>$user_id,'level'=>1])->count();
						
						//起始时间
						$jsday = "30";
						//当前月份
						$data = date("Y-m");
						//上个月
						$last_month = date('Y-m',strtotime('last month'));
						
						$starttime = strtotime($last_month.'-'.$jsday);
						$endtime = strtotime($data.'-'.$jsday);
						
						//收益
						
						//推荐注册收益
						$reg = Db::name('profit')->where('user_id',$user_id)->find();
						$reg_price = $reg['price'];
						
						//预计收益：推荐注册奖+推荐订单奖励（非取消状态和退货状态的所有订单）thfw_id 1仅退款 2退货退款 3换货
						$orders1 = Db::query("select COALESCE(sum(onefen_price),0) AS price from v_fen_orders where onefen_id=$user_id and addtime>=$starttime and addtime <=$endtime");
						$order_price1 = $orders1[0]['price'];
						//echo $order_price1;
						$members['expect_money'] = $reg_price+$order_price1;//预计收益
						
						//流失收益：推荐订单奖励（已取消状态和已退货状态的所有订单）
						$orders2 = Db::query("select COALESCE(sum(onefen_price),0) AS price from v_fen_orders_tui where onefen_id=$user_id and addtime>=$starttime and addtime <=$endtime");
						$order_price2 = $orders2[0]['price'];
						//echo $order_price2;
						$members['loss_money'] = $order_price2;//流失收益
						
						//实得收益：推荐注册奖+推荐订单奖励（完成状态的所有订单）
						
						$members['actual_money'] = $reg_price+$order_price1 - $order_price2;//实得收益
						
						//已使用
						$members['use_money'] = Db::name('withdraw')->where('user_id',$user_id)->where('checked','in','1,2')->sum('price');//已使用,只计算提现checked为0：待审批  1：已审核
						
                        $value = array('status'=>200,'mess'=>'获取用户资料成功！','data'=>$members);
                    }else{
                        $value = array('status'=>400,'mess'=>'信息有误,获取失败','data'=>array('status'=>400));
                    }
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
    
    public function tgewm(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $qrcodeurl = Db::name('member')->where('id',$user_id)->value('qrcodeurl');
                    if($qrcodeurl){
                        $webconfig = $this->webconfig;
                        $qrcodeurl = $webconfig['weburl'].'/'.$qrcodeurl;
                        $value = array('status'=>200,'mess'=>'获取用户推广二维码成功！','data'=>array('qrcodeurl'=>$qrcodeurl));
                    }else{
                        $value = array('status'=>400,'mess'=>'信息有误,获取失败','data'=>array('status'=>400));
                    }
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
    
    public function tuandui(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $agent = [];
//                    input('post.filter') && in_array(input('post.filter'), array(1,2));
                    if(1){
                        if($user_id)
                        {
                            $agent['self_agent_type'] = Db::name('member')->where('id', $user_id)->value('agent_type');
                            // 查询出当前用户的直推、间推、个人代理、区县代理、市级代理
                            $agent['direct'] = Db::name('member')->where('one_level', $user_id)->count().'人';
                            $agent['indirect'] = Db::name('member')->where('two_level', $user_id)->count().'人';

                            // 代理等级0:游客  1:个人代理  2:区县代理  3:市级代理  4省级代理
                            $agent['people'] = Db::name('member')->where('team_id', ['like', '%,'.$user_id], ['like', '%,'.$user_id.',%'], 'or')->where(function ($query){
                                $query->where('agent_type', 1);
                            })->count().'人';
                            $agent['area'] = Db::name('member')->where('team_id', ['like', '%,'.$user_id], ['like', '%,'.$user_id.',%'], 'or')->where(function ($query){
                                $query->where('agent_type', 2);
                            })->count().'人';
                            $agent['city'] = Db::name('member')->where('team_id', ['like', '%,'.$user_id], ['like', '%,'.$user_id.',%'], 'or')->where(function ($query){
                                $query->where('agent_type', 3);
                            })->count().'人';
                            $agent['team_num'] = Db::name('member')->where('team_id', ['like', '%,'.$user_id], ['like', '%,'.$user_id.',%'], 'or')->count().'人';


                            $agent['vip'] = Db::name('member')->where('team_id', ['like', '%,'.$user_id], ['like', '%,'.$user_id.',%'], 'or')->where(function ($query){
                                    $query->where('is_vip', 1);
                                })->count().'人';
                        }

                        if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                            $filter = input('post.filter');
                            $pagenum = input('post.page');
                            $webconfig = $this->webconfig;
                            $perpage = $webconfig['app_goodlst_num'];
                            $offset = ($pagenum-1)*$perpage;
                            $size = input('param.size') ? input('param.size') : 10;

                            if(input('param.level_name') == 'direct')
                            {
                                // 直推
                                $friendres = Db::name('member')->order('regtime desc')->where('one_level', $user_id)->paginate($size);
                            }
                            elseif(input('param.level_name') == 'indirect')
                            {
                                // 间推
                                $friendres = Db::name('member')->order('regtime desc')->where('two_level', $user_id)->paginate($size);
                            }
                            elseif(input('param.level_name') == 'team_num')
                            {
                                // 团队
                                $friendres = Db::name('member')->order('regtime desc')->where('team_id', ['like', '%,'.$user_id], ['like', '%,'.$user_id.',%'], 'or')->paginate($size);
                            }
                            elseif(input('param.level_name') == 'vip')
                            {
                                // 经销商
                                $friendres = Db::name('member')->where('is_vip', 1)->order('regtime desc')->where('team_id', ['like', '%,'.$user_id], ['like', '%,'.$user_id.',%'], 'or')->paginate($size);
                            }
                            else{
                                $friendres = Db::name('member')->order('regtime desc')->where('team_id', ['like', '%,'.$user_id], ['like', '%,'.$user_id.',%'], 'or')->paginate($size);
                            }
                            $value = array('status'=>200,'mess'=>'获取团队信息成功','data'=>array('friendres'=>$friendres, 'agent'=>$agent));
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少页面参数','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少参数','data'=>array('status'=>400));
                    }
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

    public function getorder(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];

                    $pagenum = input('post.page');
                    $order_state = input('post.order_state');
                    $where = [];
                    if($order_state == 'today_deal')
                    {
                        $where['d.time'] = ['egt', strtotime('today')];
                    }
                    elseif($order_state == 'one_deal')
                    {
                        $where['o.onefen_id'] = $user_id;
                    }
                    elseif($order_state == 'two_deal')
                    {
                        $where['o.twofen_id'] = $user_id;
                    }
                    elseif($order_state == 'agent_deal')
                    {
                        $where['d.agent_type'] = ['gt', 0];
                    }

                    $agent = [];
                    if($user_id)
                    {
                        $agent['self_agent_type'] = Db::name('member')->where('id', $user_id)->value('agent_type');
                        // 查询出当前用户的直推、间推、个人代理、区县代理、市级代理
                        $agent['direct'] = Db::name('member')->where('one_level', $user_id)->count().'人';
                        $agent['indirect'] = Db::name('member')->where('two_level', $user_id)->count().'人';

                        // 代理等级0:游客  1:个人代理  2:区县代理  3:市级代理  4省级代理
                        $agent['people'] = Db::name('member')->where('team_id', ['like', '%,'.$user_id], ['like', '%,'.$user_id.',%'], 'or')->where(function ($query){
                                $query->where('agent_type', 1);
                            })->count().'人';
                        $agent['area'] = Db::name('member')->where('team_id', ['like', '%,'.$user_id], ['like', '%,'.$user_id.',%'], 'or')->where(function ($query){
                                $query->where('agent_type', 2);
                            })->count().'人';
                        $agent['city'] = Db::name('member')->where('team_id', ['like', '%,'.$user_id], ['like', '%,'.$user_id.',%'], 'or')->where(function ($query){
                                $query->where('agent_type', 3);
                            })->count().'人';
                        $agent['team_num'] = Db::name('member')->where('team_id', ['like', '%,'.$user_id], ['like', '%,'.$user_id.',%'], 'or')->count().'人';


                        $agent['vip'] = Db::name('member')->where('team_id', ['like', '%,'.$user_id], ['like', '%,'.$user_id.',%'], 'or')->where(function ($query){
                                $query->where('is_vip', 1);
                            })->count().'人';
                    }

                    $size = input('param.size') ? input('param.size') : 10;
                    $list = Db::name('detail')->alias('d')
                            ->where('d.user_id', $user_id)
                            ->where($where)
                            ->where('d.sr_type', 1)
                            ->order('d.time desc')
                            ->join('order o', 'd.order_id = o.id', 'left')
                            ->join('member m', 'm.id = o.user_id', 'left')
                            ->field('d.*, o.total_price, FROM_UNIXTIME(d.time) as time, m.user_name, o.ordernumber,m.headimgurl,
                             case when onefen_id = '.$user_id.' then "one" when twofen_id = '.$user_id.' then "two" end as type,
                             case when d.agent_type = 1 then "个人代理" when d.agent_type = 2 then "区县代理" when d.agent_type = 3 then "市级代理" when d.agent_type = 4 then "省级代理" end as agent_name')
                            ->paginate($size);

                    $value = array('status'=>200,'mess'=>'获取分销订单信息成功','data'=>array('orderes'=>$list, 'agent'=>$agent));
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

//    public function getorder(){
//        if(request()->isPost()){
//            if(input('post.token')){
//                $gongyong = new GongyongMx();
//                $result = $gongyong->apivalidate();
//                if($result['status'] == 200){
//                    $user_id = $result['user_id'];
//                    if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
//                        $pagenum = input('post.page');
//
//                        $webconfig = $this->webconfig;
//                        // dump($webconfig);die;
//                        $perpage = $webconfig['app_goodlst_num'];
//                        $offset = ($pagenum-1)*$perpage;
//                        $size = input('param.size') ? input('param.size') : 10;
//
//                        $orderes = Db::name('order')
//                            ->alias('a')
//                            ->field("a.state,a.fh_status,a.order_status,a.is_show,FROM_UNIXTIME(a.addtime) as addtime,a.id,a.ordernumber,case when onefen_id = $user_id then 'one' when twofen_id = $user_id then 'two' end as type, a.total_price,a.onefen_id,a.twofen_id,a.onefen_price,a.twofen_price,b.user_name,b.headimgurl")
//                            ->join('sp_member b','a.user_id = b.id','INNER')
//                            ->where("onefen_id = $user_id or twofen_id = $user_id")
//                            ->union("SELECT
//                                                a.state,
//                                                a.fh_status,
//                                                a.order_status,
//                                                a.is_show,
//                                                FROM_UNIXTIME(a.addtime) AS addtime,
//                                                a.id,
//                                                a.ordernumber,
//                                                CASE
//                                            WHEN onefen_id = $user_id THEN
//                                                'one'
//                                            WHEN twofen_id = $user_id THEN
//                                                'two'
//                                            END AS type,
//                                             a.total_price,
//                                             a.onefen_id,
//                                             a.twofen_id,
//                                             d.price as onefen_price,
//                                             a.twofen_price,
//                                             b.user_name,
//                                             b.headimgurl
//                                            FROM
//                                                `sp_order` `a`
//                                            INNER JOIN `sp_member` `b` ON `a`.`user_id` = `b`.`id`
//                                            RIGHT JOIN sp_detail d on a.id = d.order_id
//                                            WHERE
//                                            d.user_id = $user_id")->buildSql();
//                        $orderes = Db::table($orderes.'e')->paginate($size);
//
//                        $orderes->each(function ($item, $key) use ($user_id) {
//                                if($item['state'] == 0 && $item['fh_status'] == 0 && $item['order_status'] == 0 && $item['is_show'] == 1){
//                                    $item['order_zt'] = "待付款";
//                                }elseif($item['state'] == 1 && $item['fh_status'] == 0 && $item['order_status'] == 0 && $item['is_show'] == 1){
//                                    $item['order_zt'] = "待发货";
//                                }elseif($item['state'] == 1 && $item['fh_status'] == 1 && $item['order_status'] == 0 && $item['is_show'] == 1){
//                                    $item['order_zt'] = "待收货";
//                                }elseif($item['state'] == 1 && $item['fh_status'] == 1 && $item['order_status'] == 1 && $item['is_show'] == 1){
//                                    $item['order_zt'] = "已完成";
//                                }elseif($item['order_status'] == 2 && $item['is_show'] == 1){
//                                    $item['order_zt'] = "已关闭";
//
//                                }elseif($item['state'] == 1 && $item['order_status'] == 1 && $item['is_show'] == 1){
//                                    $item['order_zt'] = "已完成";
//                                }
//
//
//                                if ($item['type'] == 'one'){
//                                $commission_price = $item['onefen_price'];
//
//                                }elseif($item['type'] == 'two'){
//                                    $commission_price = $item['twofen_price'];
//                                }else{
//                                    $commission_price = $item['onefen_price'];
//                                }
//                                $item['commission_price'] = $commission_price;
//                                if($webconfig['cos_file'] = '开启'){
//                                    $domain = config('tengxunyun')['cos_domain'];
//                                }else{
//                                    $domian = $webconfig['weburl'];
//                                }
//
//                                if($item['headimgurl']){
//                                    if(strpos($item['headimgurl'],'http') !== false){
//                                        $members['headimgurl'] = $item['headimgurl'];
//                                    }else{
//                                        if(strpos($item['headimgurl'],'uploads/') !== false){
//                                            $item['headimgurl'] = $item['headimgurl'] ? $this->webconfig['weburl']."/".$item['headimgurl'] : "";
//                                        }else{
//                                            $domain = config('tengxunyun')['cos_domain'];
//                                            $item['headimgurl'] = $item['headimgurl'] ? $domain."/".$item['cover'] : "";
//                                        }
//                                    }
//                                }else{
//                                    $logo = Db::name('shops')->where('id',1)->value('logo');
//                                    $item['headimgurl'] = $this->webconfig['weburl'].'/'.$logo;
//                                }
//
//                                if($item['onefen_id'] == $user_id){
//                                    $item['level'] = 1;
//                                    unset($item['onefen_id']);
//                                    unset($item['twofen_id']);
//                                    unset($item['twofen_price']);
//                                }elseif($item['twofen_id'] == $user_id){
//                                    $item['level'] = 2;
//                                    unset($item['twofen_id']);
//                                    unset($item['onefen_id']);
//                                    unset($item['onefen_price']);
//                                }
//                                return $item;
//
//                        });
//
//                        $value = array('status'=>200,'mess'=>'获取分销订单信息成功','data'=>array('orderes'=>$orderes));
//                    }else{
//                        $value = array('status'=>400,'mess'=>'缺少页面参数','data'=>array('status'=>400));
//                    }
//                }else{
//                    $value = $result;
//                }
//            }else{
//                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
//            }
//        }else{
//            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
//        }
//        return json($value);
//    }
}