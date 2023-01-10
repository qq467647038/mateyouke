<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;

class Order extends Common{

    public function lst(){
        $shop_id = session('shopsh_id');
        
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,4,5,10))){
            $filter = 10;
        }
    
        switch ($filter){//0-待商家发货，1-商家已发货（等待平台确认），2平台确认（待平台发货），3平台已发货（等待用户收货），4-用户已收货（订单完成），5申请退款（平台），6申请退货（平台），6平台确认（待打款），7已打款（用户确认），8-用户已发货（平台确认收货），9平台已收货，10平台发货（商户确认收货）
            //待发货
            case 1:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.o_state'=>0,'a.order_status'=>0);
                break;
            //已发货
            case 2:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.o_state'=>1,'a.order_status'=>0);
                break;
            //已完成
            case 3:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.o_state'=>4,'a.order_status'=>1);
                break;
            //待支付
            case 4:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>0,'a.o_state'=>0,'a.order_status'=>0);
                break;
            //已关闭
            case 5:
                $where = array('a.shop_id'=>$shop_id,'a.order_status'=>2);
                break;
            //全部
            case 10:
                $where = array('a.shop_id'=>$shop_id);
            break;
        }
    
    
        $list = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.pro_name,d.city_name,u.area_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
        $this->assign('filter',$filter);
        $this->assign('prores',$prores);
        $this->assign('pnum',$pnum);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('list',$list);// 赋值数据集
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function getcitylist(){
        if(request()->isPost()){
            $pro_id = input('post.pro_id');
            if($pro_id){
                $cityres = Db::name('city')->where('pro_id',$pro_id)->field('id,city_name,zm')->order('sort asc')->select();
                if(empty($cityres)){
                    $cityres = 0;
                }
                return $cityres;
            }
        }
    }
    
    public function getarealist(){
        if(request()->isPost()){
            $city_id = input('post.city_id');
            if($city_id){
                $areares = Db::name('area')->where('city_id',$city_id)->field('id,area_name,zm')->order('sort asc')->select();
                if(empty($areares)){
                    $areares = 0;
                }
                return $areares;
            }
        }
    }
     
    //订单详情
    public function info(){
        if(input('order_id')){
            $shop_id = session('shopsh_id');
            $order_id = input('order_id');
            $orders = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.pro_name,d.city_name,p.area_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area p','a.area_id = p.id','LEFT')->where('a.id',$order_id)->where('a.shop_id',$shop_id)->find();
            if($orders){
                if($orders['state'] == 1 && $orders['fh_status'] == 0 && $orders['order_status'] == 0){
                    $orders['zhuangtai'] = 1;
                }elseif($orders['state'] == 1 && $orders['fh_status'] == 1 && $orders['order_status'] == 0){
                    $orders['zhuangtai'] = 2;
                }elseif($orders['state'] == 1 && $orders['fh_status'] == 1 && $orders['order_status'] == 1){
                    $orders['zhuangtai'] = 3;
                }elseif($orders['state'] == 0 && $orders['fh_status'] == 0 && $orders['order_status'] == 0){
                    $orders['zhuangtai'] = 4;
                }elseif($orders['order_status'] == 2){
                    $orders['zhuangtai'] = 5;
                }
                
                if($orders['order_type'] == 2){
                    $pintuans = Db::name('pintuan')->where('id',$orders['pin_id'])->field('id,pin_num,tuan_num,state,pin_status,timeout')->find();
                    $assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('order_id',$orders['id'])->find();
                }else{
                    $pintuans = array();
                    $assembles = array();
                }
                
                if($this->webconfig['cos_file'] == '开启'){
                    $domain = config('tengxunyun')['cos_domain'];
                }else{
                    $domain = $this->webconfig['weburl'];
                }
                $order_goodres = Db::name('order_goods')->where('order_id',$orders['id'])->select();
                foreach ($order_goodres as $k => $v){
                    $order_goodres[$k]['dan_price'] = sprintf("%.2f", $v['real_price']*$v['goods_num']);
                    $order_goodres[$k]['thumb_url'] = $domain.'/'.$order_goodres[$k]['thumb_url'];
                }
                
                $wulius = Db::name('order_wuliu')->where('order_id',$order_id)->find();
                
                $psres = Db::name('logistics')->where('is_show',1)->field('id,log_name')->order('sort asc')->select();
				
				//平台收货信息
				$configs = Db::name('config')->where('ca_id','in','16')->field('ename,value')->select();
				
				foreach ($configs as $v){
				    $ptinfo[$v['ename']] = $v['value'];
				}
				
                $this->assign('orders',$orders);
                $this->assign('pintuans',$pintuans);
                $this->assign('ptinfo',$ptinfo);
				$this->assign('assembles',$assembles);
                $this->assign('order_goodres',$order_goodres);
                $this->assign('wulius',$wulius);
                $this->assign('psres',$psres);
                return $this->fetch();
            }else{
                $this->error('订单信息错误');
            }
        }else{
            $this->error('缺少订单信息');
        }
    }
    
    //保存物流信息
    public function savewuliu(){
        if(request()->isPost()){
            if(input('post.ps_id') && input('post.psnum') && input('post.order_id')){
                $shop_id = session('shopsh_id');
                $ps_id = input('post.ps_id');
                $psnum = input('post.psnum');
                $order_id = input('post.order_id');
                $wuliu_infos = Db::name('order_wuliu')->where('psnum',$psnum)->find();
                if(!$wuliu_infos){
                    $logs = Db::name('logistics')->where('id',$ps_id)->find();
                    $orders = Db::name('order')->where('id',$order_id)->where('shop_id',$shop_id)->where('state',1)->where('order_status',0)->field('id,order_type,pin_id,pin_type')->find();
                    if($logs){
                        if($orders){
                            if($orders['order_type'] == 2){
                                $pintuans = Db::name('pintuan')->where('id',$orders['pin_id'])->where('state',1)->where('pin_status',1)->field('id')->find();
                                if(!$pintuans){
                                    $value = array('status'=>0,'mess'=>'拼团未完成，保存失败');
                                    return json($value);
                                }
                            }
                            
                            $wulius = Db::name('order_wuliu')->where('order_id',$order_id)->find();
                            if($wulius){
                                $count = Db::name('order_wuliu')->update(array('ps_id'=>$ps_id,'psnum'=>$psnum,'id'=>$wulius['id']));
                                if($count !== false){
                                    $value = array('status'=>1,'mess'=>'保存成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'保存失败');
                                }
                            }else{
                                $lastId = Db::name('order_wuliu')->insertGetId(array('ps_id'=>$ps_id,'psnum'=>$psnum,'order_id'=>$order_id));
                                if($lastId){
                                    $value = array('status'=>1,'mess'=>'保存成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'保存失败');
                                }
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'订单信息错误，保存失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'物流信息错误，保存失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'运单号已存在');
                }
            }else{
                $value = array('status'=>0,'mess'=>'请完善物流信息，保存失败');
            }
            return json($value);
        }
    }
    
    public function fachu(){
        if(request()->isPost()){
            if(input('post.order_id')){
                $shop_id = session('shopsh_id');
                $order_id = input('post.order_id');
                $orders = Db::name('order')->where('id',$order_id)->where('shop_id',$shop_id)->where('state',1)->where('fh_status',0)->where('order_status',0)->field('id,order_type,pin_id,pin_type,shouhou')->find();
                if($orders){
                    $ordouts = Db::name('order_timeout')->where('id',1)->find();
                    
                    if($orders['order_type'] == 2){
                        $pintuans = Db::name('pintuan')->where('id',$orders['pin_id'])->where('state',1)->where('pin_status',1)->field('id')->find();
                        if(!$pintuans){
                            $value = array('status'=>0,'mess'=>'拼团未完成，发货失败');
                            return json($value);
                        }
                    }
                    
                    if($orders['shouhou'] == 0){
                        $order_goodres = Db::name('order_goods')->where('order_id',$orders['id'])->field('th_status')->select();
                        if($order_goodres){
                            foreach ($order_goodres as $v){
                                if(in_array($v['th_status'], array(1,2))){
                                    $value = array('status'=>0,'mess'=>'订单存在商品在申请退款中，请处理后发货');
                                    return json($value);
                                }
                            }
                            $wulius = Db::name('order_wuliu')->where('order_id',$order_id)->find();
                            if($wulius){
                                $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                $count = Db::name('order')->update(array('fh_status'=>1,'fh_time'=>time(),'zdsh_time'=>$zdsh_time,'id'=>$order_id));
                                if($count > 0){
                                    $value = array('status'=>1,'mess'=>'发货成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'发货失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'请先保存物流信息，发货失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'订单异常，发货失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'订单存在商品在申请退款中，请处理后发货');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'找不到相关待发货订单，发货失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少订单信息，发货失败');
            }
            return json($value);
        }  
    }
    
    public function delete(){
        if(input('id') && !is_array(input('id'))){
            $shop_id = session('shopsh_id');
            $id = input('id');
            $orders = Db::name('order')->where('id',$id)->where('shop_id',$shop_id)->where('order_status',2)->field('id')->find();
            if($orders){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('order')->where('id',$id)->delete();
                    // 提交事务
                    Db::commit();
                    $value = array('status'=>1,'mess'=>'删除成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'未关闭订单不可删除');
            }
        }else{
            $value = array('status'=>0,'mess'=>'删除失败');
        }
        return json($value);
    }
    
    public function search(){
        $shop_id = session('shopsh_id');
        
        if(input('post.keyword') != ''){
            cookie('shopor_keyword',input('post.keyword'),7200);
        }else{
            cookie('shopor_keyword',null);
        }
    
        if(input('post.pro_id') != ''){
            cookie("shopor_pro_id", input('post.pro_id'), 7200);
        }
    
        if(input('post.city_id') != ''){
            cookie("shopor_city_id", input('post.city_id'), 7200);
        }
    
        if(input('post.area_id') != ''){
            cookie("shopor_area_id", input('post.area_id'), 7200);
        }
        
        if(input('post.order_type') != ''){
            cookie("shopor_order_type", input('post.order_type'), 7200);
        }
    
        if(input('post.order_zt') != ''){
            cookie("shopor_order_zt", input('post.order_zt'), 7200);
        }
    
        if(input('post.zf_type') != ''){
            cookie("shopor_zf_type", input('post.zf_type'), 7200);
        }
    
        if(input('post.starttime') != ''){
            $shoporstarttime = strtotime(input('post.starttime'));
            cookie('shoporstarttime',$shoporstarttime,7200);
        }
    
        if(input('post.endtime') != ''){
            $shoporendtime = strtotime(input('post.endtime'));
            cookie('shoporendtime',$shoporendtime,7200);
        }
    
        $where = array();
        $where['a.shop_id'] = $shop_id;
        
        if(cookie('shopor_keyword')){
            $where['a.ordernumber'] = cookie('shopor_keyword');
        }
        
        
        if(cookie('shopor_pro_id') != ''){
            $proid = (int)cookie('shopor_pro_id');
            if($proid != 0){
                $where['a.pro_id'] = $proid;
            }
        }
    
        if(cookie('shopor_city_id') != ''){
            $cityid = (int)cookie('shopor_city_id');
            if($cityid != 0){
                $where['a.city_id'] = $cityid;
            }
        }
    
        if(cookie('shopor_area_id') != ''){
            $areaid = (int)cookie('shopor_area_id');
            if($areaid != 0){
                $where['a.area_id'] = $areaid;
            }
        }
    
        $nowtime = time();
        
        if(cookie('shopor_order_type') != ''){
            $order_type = (int)cookie('shopor_order_type');
            if($order_type != 0){
                switch($order_type){
                    //普通订单
                    case 1:
                        $where['a.order_type'] = 1;
                        break;
                    //拼团订单
                    case 2:
                        $where['a.order_type'] = 2;
                        break;
                }
            }
        }
    
        if(cookie('shopor_order_zt') != ''){
            $order_zt = (int)cookie('shopor_order_zt');
    
            if($order_zt != 0){
                switch($order_zt){
                    //待发货
                    case 1:
                        $where['a.state'] = 1;
                        $where['a.fh_status'] = 0;
                        $where['a.order_status'] = 0;
                        break;
                    //已发货
                    case 2:
                        $where['a.state'] = 1;
                        $where['a.fh_status'] = 1;
                        $where['a.order_status'] = 0;
                        break;
                    //已完成
                    case 3:
                        $where['a.state'] = 1;
                        $where['a.fh_status'] = 1;
                        $where['a.order_status'] = 1;
                        break;
                    //待支付
                    case 4:
                        $where['a.state'] = 0;
                        $where['a.fh_status'] = 0;
                        $where['a.order_status'] = 0;
                        break;
                    //已关闭
                    case 5:
						$where['a.state'] = 0;
                        $where['a.order_status'] = 2;
                        break;
                }
            }
        }
    
        if(cookie('shopor_zf_type') != ''){
            $zf_type = (int)cookie('shopor_zf_type');
            if($zf_type != 0){
                switch($zf_type){
                    //支付宝支付
                    case 1:
                        $where['a.zf_type'] = 1;
                        break;
                        //微信支付
                    case 2:
                        $where['a.zf_type'] = 2;
                        break;
                        //余额支付
                    case 3:
                        $where['a.zf_type'] = 3;
                        break;                      
                }
            }
        }
    
        if(cookie('shoporendtime') && cookie('shoporstarttime')){
            $where['a.addtime'] = array(array('egt',cookie('shoporstarttime')), array('lt',cookie('shoporendtime')));
        }
    
        if(cookie('shoporstarttime') && !cookie('shoporendtime')){
            $where['a.addtime'] = array('egt',cookie('shoporstarttime'));
        }
    
        if(cookie('shoporendtime') && !cookie('shoporstarttime')){
            $where['a.addtime'] = array('lt',cookie('shoporendtime'));
        }
    
        $list = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.pro_name,d.city_name,u.area_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
    
        $page = $list->render();
    
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
    
        if(cookie('shopor_pro_id')){
            $cityres = Db::name('city')->where('pro_id',cookie('shopor_pro_id'))->field('id,city_name,zm')->order('sort asc')->select();
        }
    
        if(cookie('shopor_pro_id') && cookie('shopor_city_id')){
            $areares = Db::name('area')->where('city_id',cookie('shopor_city_id'))->field('id,area_name,zm')->select();
        }
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $search = 1;
    
        if(cookie('shopor_pro_id') != ''){
            $this->assign('pro_id',cookie('shopor_pro_id'));
        }
        if(cookie('shopor_city_id') != ''){
            $this->assign('city_id',cookie('shopor_city_id'));
        }
        if(cookie('shopor_area_id') != ''){
            $this->assign('area_id',cookie('shopor_area_id'));
        }
    
        if(cookie('shoporstarttime')){
            $this->assign('starttime',cookie('shoporstarttime'));
        }
    
        if(cookie('shoporendtime')){
            $this->assign('endtime',cookie('shoporendtime'));
        }
    
        if(!empty($cityres)){
            $this->assign('cityres',$cityres);
        }
    
        if(!empty($areares)){
            $this->assign('areares',$areares);
        }
    
        if(cookie('shopor_keyword')){
            $this->assign('keyword',cookie('shopor_keyword'));
        }
        
        if(cookie('shopor_order_type') != ''){
            $this->assign('order_type',cookie('shopor_order_type'));
        }
    
        if(cookie('shopor_order_zt') != ''){
            $this->assign('order_zt',cookie('shopor_order_zt'));
        }
    
        if(cookie('shopor_zf_type') != ''){
            $this->assign('zf_type',cookie('shopor_zf_type'));
        }
    
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('prores',$prores);
        $this->assign('filter',10);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }    
 
    
}
