<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class ShopOrder extends Common{
    
    public function lst(){
        $shop_id = session('shop_id');
    
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,4,5,10))){
            $filter = 10;
        }
    
        switch ($filter){
            //待发货
            case 1:
                $where = array('a.shop_id'=>array('neq',$shop_id),'a.state'=>1,'a.o_state'=>1,'a.order_status'=>0);
                break;
                //已发货
            case 2:
                $where = array('a.shop_id'=>array('neq',$shop_id),'a.state'=>1,'a.o_state'=>3,'a.order_status'=>0);
			break;
            //已完成
            case 3:
                $where = array('a.shop_id'=>array('neq',$shop_id),'a.state'=>1,'a.o_state'=>4,'a.order_status'=>1);
                break;
                //待支付
            case 4:
                $where = array('a.shop_id'=>array('neq',$shop_id),'a.state'=>0,'a.o_state'=>0,'a.order_status'=>0);
                break;
                //已关闭
            case 5:
                $where = array('a.shop_id'=>array('neq',$shop_id),'a.order_status'=>2);
                break;
                //全部
			case 10:
                $where = array('a.shop_id'=>array('neq',$shop_id));
			break;
        }
    
    
        $list = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.shop_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $this->assign('filter',$filter);
        $this->assign('pnum',$pnum);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('list',$list);// 赋值数据集
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
     
    //订单详情
    public function info(){
        if(input('order_id')){
            $shop_id = session('shop_id');
            $order_id = input('order_id');
            $orders = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.shop_name,d.pro_name,f.city_name,g.area_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->join('sp_province d','a.pro_id = d.id','LEFT')->join('sp_city f','a.city_id = f.id','LEFT')->join('sp_area g','a.area_id = g.id','LEFT')->where('a.id',$order_id)->where('a.shop_id','neq',$shop_id)->find();
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
    
                $order_goodres = Db::name('order_goods')->where('order_id',$orders['id'])->select();
                foreach ($order_goodres as $k => $v){
                    $order_goodres[$k]['dan_price'] = sprintf("%.2f", $v['price']*$v['goods_num']);
                }
    
                $wulius = Db::name('order_wuliu')->where('order_id',$order_id)->find();
                
                if($wulius){
                    $log_name = Db::name('logistics')->where('id',$wulius['ps_id'])->value('log_name');
                }else{
                    $log_name = '';
                }

                $this->assign('orders',$orders);
                $this->assign('pintuans',$pintuans);
                $this->assign('assembles',$assembles);
                $this->assign('order_goodres',$order_goodres);
                $this->assign('wulius',$wulius);
                $this->assign('log_name',$log_name);
                return $this->fetch();
            }else{
                $this->error('订单信息错误');
            }
        }else{
            $this->error('缺少订单信息');
        }
    }
    
    public function search(){
        $shop_id = session('shop_id');
    
        if(input('post.keyword') != ''){
            cookie('shor_keyword',input('post.keyword'),7200);
        }else{
            cookie('shor_keyword',null);
        }

        if(input('post.shop_name') != ''){
            cookie('shor_shop_name',input('post.shop_name'),7200);
        }else{
            cookie('shor_shop_name',null);
        }
        

        if(input('post.order_type') != ''){
            cookie("shor_order_type", input('post.order_type'), 7200);
        }
    
        if(input('post.order_zt') != ''){
            cookie("shor_order_zt", input('post.order_zt'), 7200);
        }
    
        if(input('post.zf_type') != ''){
            cookie("shor_zf_type", input('post.zf_type'), 7200);
        }
    
        if(input('post.starttime') != ''){
            $shorstarttime = strtotime(input('post.starttime'));
            cookie('shorstarttime',$shorstarttime,7200);
        }
    
        if(input('post.endtime') != ''){
            $shorendtime = strtotime(input('post.endtime'));
            cookie('shorendtime',$shorendtime,7200);
        }
    
        $where = array();
        $where['a.shop_id'] = array('neq',$shop_id);
    
        if(cookie('shor_keyword')){
            $where['a.ordernumber'] = cookie('shor_keyword');
        }
        
        if(cookie('shor_shop_name')){
            $shops = Db::name('shops')->where('shop_name',cookie('shor_shop_name'))->where('id','neq',$shop_id)->field('id')->find();
            if($shops){
                $where['a.shop_id'] = $shops['id'];
            }else{
                $where['a.shop_id'] = 'aa';
            }
        }
        
        if(cookie('shor_order_type') != ''){
            $order_type = (int)cookie('shor_order_type');
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
    
        if(cookie('shor_order_zt') != ''){
            $order_zt = (int)cookie('shor_order_zt');
    
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
                        $where['a.order_status'] = 2;
                        break;
                }
            }
        }
    
        if(cookie('shor_zf_type') != ''){
            $zf_type = (int)cookie('shor_zf_type');
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
    
        if(cookie('shorendtime') && cookie('shorstarttime')){
            $where['a.addtime'] = array(array('egt',cookie('shorstarttime')), array('lt',cookie('shorendtime')));
        }
    
        if(cookie('shorstarttime') && !cookie('shorendtime')){
            $where['a.addtime'] = array('egt',cookie('shorstarttime'));
        }
    
        if(cookie('shorendtime') && !cookie('shorstarttime')){
            $where['a.addtime'] = array('lt',cookie('shorendtime'));
        }
    
         $list = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.shop_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
    
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $search = 1;
    
        if(cookie('shorstarttime')){
            $this->assign('starttime',cookie('shorstarttime'));
        }
    
        if(cookie('shorendtime')){
            $this->assign('endtime',cookie('shorendtime'));
        }

        if(cookie('shor_keyword')){
            $this->assign('keyword',cookie('shor_keyword'));
        }
        if(cookie('shor_shop_name')){
            $this->assign('shop_name',cookie('shor_shop_name'));
        }
        
        if(cookie('shor_order_type') != ''){
            $this->assign('order_type',cookie('shor_order_type'));
        }
    
        if(cookie('shor_order_zt') != ''){
            $this->assign('order_zt',cookie('shor_order_zt'));
        }
    
        if(cookie('shor_zf_type') != ''){
            $this->assign('zf_type',cookie('shor_zf_type'));
        }
    
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('filter',10);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
	
	public function checkorder(){
		
		if(input('id')){
		    $shop_id = session('shop_id');
		    $order_id = input('id');
		    $orders = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.shop_name,d.pro_name,f.city_name,g.area_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->join('sp_province d','a.pro_id = d.id','LEFT')->join('sp_city f','a.city_id = f.id','LEFT')->join('sp_area g','a.area_id = g.id','LEFT')->where('a.id',$order_id)->where('a.shop_id','neq',$shop_id)->find();
		    if($orders){
				
				$wulius = Db::name('order_wuliu')->where('order_id',$order_id)->find();
				
				$psres = Db::name('logistics')->where('is_show',1)->field('id,log_name')->order('sort asc')->select();
			}
			
			$this->assign('wulius',$wulius);
			$this->assign('psres',$psres);
			
			$this->assign('orders',$orders);
			
		}
		
		return $this->fetch();
		
	}
	
	//保存物流信息
	public function savewuliu(){
	    if(request()->isPost()){
	        if(input('post.ps_id') && input('post.psnum') && input('post.order_id')){
	            //$shop_id = session('shopsh_id');
	            $ps_id = input('post.ps_id');
	            $psnum = input('post.psnum');
	            $order_id = input('post.order_id');
	            $wuliu_infos = Db::name('order_wuliu')->where('psnum',$psnum)->find();
	            if(!$wuliu_infos){
	                $logs = Db::name('logistics')->where('id',$ps_id)->find();
	                $orders = Db::name('order')->where('id',$order_id)->where('state',1)->where('order_status',0)->field('id,order_type,pin_id,pin_type')->find();
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
	            //$shop_id = session('shopsh_id');
	            $order_id = input('post.order_id');
	            $orders = Db::name('order')->where('id',$order_id)->where('state',1)->where('fh_status',0)->where('order_status',0)->field('id,order_type,pin_id,pin_type,shouhou')->find();
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
	                            //$count = Db::name('order')->update(array('fh_status'=>1,'fh_time'=>time(),'zdsh_time'=>$zdsh_time,'id'=>$order_id));
								$count = Db::name('order')->update(array('o_state'=>3,'fh_status'=>1,'fh_time'=>time(),'zdsh_time'=>$zdsh_time,'id'=>$order_id));
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
    
}