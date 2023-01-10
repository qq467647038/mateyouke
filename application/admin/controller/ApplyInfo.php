<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class ApplyInfo extends Common{ 
    public function lst(){
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,4,5,6,10))){
            $filter = 10;
        }
        
        $where = array();
        
        switch ($filter){
            case 1:
                //待审核
                $where = array('a.checked'=>0,'a.qht'=>0,'a.state'=>0,'a.complete'=>0);
                break;
            case 2:
                //待签合同
                $where = array('a.checked'=>1,'a.qht'=>0,'a.state'=>0,'a.complete'=>0);
                break;
            case 3:
                //待支付保证金
                $where = array('a.checked'=>1,'a.qht'=>1,'a.state'=>0,'a.complete'=>0);
                break;
            case 4:
                //待开通
                $where = array('a.checked'=>1,'a.qht'=>1,'a.state'=>1,'a.complete'=>0);
                break;        
            case 5:
                //已开通
                $where = array('a.checked'=>1,'a.qht'=>1,'a.state'=>1,'a.complete'=>1);
                break; 
            case 6:
                //已拒绝
                $where = array('a.checked'=>2,'a.qht'=>0,'a.state'=>0,'a.complete'=>0);
                break;
        }
        
        $list = Db::name('apply_info')->alias('a')->field('a.id,a.shop_name,a.contacts,a.telephone,a.checked,a.qht,a.state,a.complete,a.apply_type,a.apply_time,b.industry_name,c.pro_name,d.city_name,u.area_name')->join('sp_industry b','a.indus_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where($where)->order('a.apply_time desc')->paginate(25);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
        
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum,
            'prores'=>$prores,
            'filter'=>$filter
        ));
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
    
    public function checked(){
        if(request()->isPost()){
            if(input('post.id')){
                if(input('post.checked') && in_array(input('post.checked'), array(1,2))){
                    $id = input('post.id');
                    $checked = input('post.checked');
                    $applys = Db::name('apply_info')->where('id',$id)->where('checked',0)->where('qht',0)->where('state',0)->where('complete',0)->find();
                    if($applys){
                        if($checked == 1){
                            $count = Db::name('apply_info')->update(array('checked'=>$checked,'checked_time'=>time(),'id'=>$id));
                            if($count > 0){
                                ys_admin_logs('审核通过商家申请','apply_info',$id);
                                $value = array('status'=>1,'mess'=>'设置成功');
                            }else{
                                $value = array('status'=>0,'mess'=>'设置失败');
                            }
                        }elseif($checked == 2){
                            if(input('post.remarks')){
                                $remarks = input('post.remarks');
                                $count = Db::name('apply_info')->update(array('checked'=>$checked,'remarks'=>$remarks,'checked_time'=>time(),'id'=>$id));
                                if($count > 0){
                                    ys_admin_logs('拒绝商家申请','apply_info',$id);
                                    $value = array('status'=>1,'mess'=>'设置成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'设置失败');
                                }
                            }else{
                                $value = array('status'=>0, 'mess'=>'请填写失败原因');
                            }
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'参数错误，设置失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'参数错误，设置失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'参数错误，设置失败');
            }
            return json($value);
        }else{
            if(input('apply_id') && input('filter')){
                if(in_array(input('filter'), array(1,2,3,4,5,6,10))){
                    $id = input('apply_id');
                    $applys = Db::name('apply_info')->alias('a')->field('a.*,b.industry_name,c.pro_name,d.city_name,u.area_name')->join('sp_industry b','a.indus_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where('a.id',$id)->where('a.checked',0)->where('a.qht',0)->where('a.state',0)->where('a.complete',0)->find();
                    if($applys){
                        $manageres = Db::name('manage_apply')->where('apply_id',$applys['id'])->field('cate_id')->select();
                        $managearr = array();
                        foreach ($manageres as $v){
                            $managearr[] = $v['cate_id'];
                        }
                        $managearr = implode(',', $managearr);
                        $cateres = Db::name('category')->where('id','in',$managearr)->where('pid',0)->field('id,cate_name')->order('sort asc')->select();
                        $ziliaopicres = Db::name('apply_ziliaopic')->where('apply_id',$applys['id'])->select();
                        if(input('s')){
                            $this->assign('search',input('s'));
                        }
                        $this->assign('pnum',input('page'));
                        $this->assign('filter',input('filter'));
                        $this->assign('applys',$applys);
                        $this->assign('cateres',$cateres);
                        $this->assign('ziliaopicres',$ziliaopicres);
                        return $this->fetch();
                    }else{
                        $this->error('参数错误');
                    }
                }else{
                    $this->error('参数错误');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    public function qht(){
        if(request()->isPost()){
            if(input('post.id')){
                if(in_array(input('post.qht'), array(0,1))){
                    $id = input('post.id');
                    $qht = input('post.qht');
                    $applys = Db::name('apply_info')->where('id',$id)->where('checked',1)->where('qht',0)->where('state',0)->where('complete',0)->find();
                    if($applys){
                        if($qht == 1){
                            $count = Db::name('apply_info')->update(array('qht'=>$qht,'qht_time'=>time(),'id'=>$id));
                        }else{
                            $count = Db::name('apply_info')->update(array('qht'=>$qht,'id'=>$id));
                        }

                        if($count !== false){
                            if($qht == 1){
                                ys_admin_logs('编辑商家已签合同','apply_info',$id);
                            }else{
                                ys_admin_logs('编辑商家未签合同','apply_info',$id);
                            }
                            $value = array('status'=>1,'mess'=>'设置成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'设置失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'参数错误，设置失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'参数错误，设置失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'参数错误，设置失败');
            }
            return json($value);
        }else{
            if(input('apply_id') && input('filter')){
                if(in_array(input('filter'), array(1,2,3,4,5,6,10))){
                    $id = input('apply_id');
                    $applys = Db::name('apply_info')->alias('a')->field('a.*,b.industry_name,c.pro_name,d.city_name,u.area_name')->join('sp_industry b','a.indus_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where('a.id',$id)->where('a.checked',1)->where('a.qht',0)->where('a.state',0)->where('a.complete',0)->find();
                    if($applys){
                        $manageres = Db::name('manage_apply')->where('apply_id',$applys['id'])->field('cate_id')->select();
                        $managearr = array();
                        foreach ($manageres as $v){
                            $managearr[] = $v['cate_id'];
                        }
                        $managearr = implode(',', $managearr);
                        $cateres = Db::name('category')->where('id','in',$managearr)->where('pid',0)->field('id,cate_name')->order('sort asc')->select();
                        $ziliaopicres = Db::name('apply_ziliaopic')->where('apply_id',$applys['id'])->select();
                        if(input('s')){
                            $this->assign('search',input('s'));
                        }
                        $this->assign('pnum',input('page'));
                        $this->assign('filter',input('filter'));
                        $this->assign('applys',$applys);
                        $this->assign('cateres',$cateres);
                        $this->assign('ziliaopicres',$ziliaopicres);
                        return $this->fetch();
                    }else{
                        $this->error('参数错误');
                    }
                }else{
                    $this->error('参数错误');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    public function complete(){
        if(request()->isPost()){
            if(input('post.id')){
                $user_id = (int) input('post.user_id');
                if(empty($user_id)){
                    $value = array('status'=>0,'mess'=>'请填写APP用户ID');
                    return json($value);
                }
                $user = db('member')->find($user_id);
                if(empty($user)){
                    $value = array('status'=>0,'mess'=>'不存在ID为'.$user_id.'的用户');
                    return json($value);
                }
                if($user['shop_id']){
                    $value = array('status'=>0,'mess'=>'ID为'.$user_id.'的用户已开通过店铺');
                    return json($value);
                }
                if(in_array(input('post.complete'), array(0,1))){
                    $id = input('post.id');
                    $complete = input('post.complete');
                    $applys = Db::name('apply_info')->where('id',$id)->where('checked',1)->where('qht',1)->where('complete',0)->find();
                    if($applys){
                        // $rzorders = Db::name('rz_order')->where('apply_id',$applys['id'])->where('user_id',$applys['user_id'])->field('id,state')->find();
                        // if($rzorders && $rzorders['state'] == 1){
                            $manageres = Db::name('manage_apply')->where('apply_id',$applys['id'])->field('cate_id,apply_time')->select();
                            if($manageres){
                                
                                $members = Db::name('member')->where('id',$user['id'])->where('checked',1)->field('id,phone,password')->find();
                                if($members){
                                    if($complete == 1){
                                        $recode = settoken();
                                        $shops = Db::name('shops')->where('recode',$recode)->field('id')->find();
                                        if(!$shops){
                                            // 启动事务
                                            Db::startTrans();
                                            try{
                                                $shop_id = Db::name('shops')->insertGetId(array(
                                                    'shop_name'=>$applys['shop_name'],
                                                    'indus_id'=>$applys['indus_id'],
                                                    'recode'=>$recode,
                                                    'contacts'=>$applys['contacts'],
                                                    'telephone'=>$applys['telephone'],
                                                    'shop_desc'=>$applys['shop_desc'],
                                                    'pro_id'=>$applys['pro_id'],
                                                    'city_id'=>$applys['city_id'],
                                                    'area_id'=>$applys['area_id'],
                                                    'shengshiqu'=>$applys['shengshiqu'],
                                                    'address'=>$applys['address'],
													'settlement_date'=>$applys['settlement_date'],
													'service_rate'=>$applys['service_rate'],
                                                    'open_status'=>1,
                                                    'addtime'=>time()
                                                ));
                                
                                                if($shop_id){
                                                    foreach($manageres as $v){
                                                        Db::name('manage_cate')->insert(array('shop_id'=>$shop_id,'cate_id'=>$v['cate_id'],'checked'=>1,'apply_time'=>$v['apply_time'],'checked_time'=>time()));
                                                    }
                                                    Db::name('shop_wallet')->insert(array('price'=>0,'shop_id'=>$shop_id));
                                                    Db::name('shop_admin')->insert(array(
                                                        'phone'=>$members['phone'],
                                                        'password'=>$members['password'],
                                                        'xieyi'=>1,
                                                        'addtime'=>time(),
                                                        'open_status'=>1,
                                                        'shop_id'=>$shop_id
                                                    ));
                                                    // 把商家id更新到关联的用户表里面
                                                    Db::name('member')->update(array('shop_id'=>$shop_id,'id'=>$members['id']));
                                                    // Db::name('rz_order')->update(array('shop_id'=>$shop_id,'id'=>$rzorders['id']));
                                                    Db::name('apply_info')->update(array('complete'=>$complete,'complete_time'=>time(),'shop_id'=>$shop_id,'id'=>$id));
                                                    //添加客服账号
                                                    Db::name('chat_customer')->insert([
                                                        'shop_id'   =>$shop_id,
                                                        'username' => $applys['shop_name'],
                                                        'headimgurl'=> $applys['logo'],
                                                        'createtime'=> time(),
                                                        'phone' => $applys['telephone'],
                                                        'status'=> 1,
                                                        'is_delete' => '-1'
                                                    ]);
                                                    Db::name('customer_service')->insert([
                                                        'user_id' =>$user_id,
                                                        'create_time' => time(),
                                                        'shop_id'   =>$shop_id,
                                                        'status'    =>1
                                                    ]);
                                                }
                                                // 提交事务
                                                Db::commit();
                                                ys_admin_logs('编辑商家已开通','apply_info',$id);
                                                $value = array('status'=>1,'mess'=>'设置成功');
                                            } catch (\Exception $e) {
                                                // 回滚事务
                                                Db::rollback();
                                                $value = array('status'=>0,'mess'=>'设置失败，错误信息：'.$e->getMessage());
                                            }
                                        }else{
                                            $this->error('信息错误','login/loginout');
                                        }
                                    }else{
                                        $count = Db::name('apply_info')->update(array('complete'=>$complete,'id'=>$id));
                                        if($count !== false){
                                            ys_admin_logs('编辑商家未开通','apply_info',$id);
                                            $value = array('status'=>1,'mess'=>'设置成功');
                                        }else{
                                            $value = array('status'=>0,'mess'=>'设置失败');
                                        }
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'参数错误，设置失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'参数错误，设置失败');
                            }
                        // }else{
                        //     $value = array('status'=>0,'mess'=>'参数错误，设置失败3');
                        // }
                    }else{
                        $value = array('status'=>0,'mess'=>'参数错误，设置失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'参数错误，设置失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'参数错误，设置失败');
            }
            return json($value);
        }else{
            if(input('apply_id') && input('filter')){
                if(in_array(input('filter'), array(1,2,3,4,5,6,10))){
                    $id = input('apply_id');
                    $applys = Db::name('apply_info')->alias('a')->field('a.*,b.industry_name,c.pro_name,d.city_name,u.area_name')->join('sp_industry b','a.indus_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where('a.id',$id)->where('a.checked',1)->where('a.qht',1)->where('a.complete',0)->find();
                    if($applys){
                        $manageres = Db::name('manage_apply')->where('apply_id',$applys['id'])->field('cate_id')->select();
                        $managearr = array();
                        foreach ($manageres as $v){
                            $managearr[] = $v['cate_id'];
                        }
                        $managearr = implode(',', $managearr);
                        $cateres = Db::name('category')->where('id','in',$managearr)->where('pid',0)->field('id,cate_name')->order('sort asc')->select();
                        $ziliaopicres = Db::name('apply_ziliaopic')->where('apply_id',$applys['id'])->select();
                        if(input('s')){
                            $this->assign('search',input('s'));
                        }
                        $this->assign('pnum',input('page'));
                        $this->assign('filter',input('filter'));
                        $this->assign('applys',$applys);
                        $this->assign('cateres',$cateres);
                        $this->assign('ziliaopicres',$ziliaopicres);
                        return $this->fetch();
                    }else{
                        $this->error('参数错误');
                    }
                }else{
                    $this->error('参数错误');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    
    public function info(){
        if(input('apply_id')){
            $id = input('apply_id');
            $applys = Db::name('apply_info')->alias('a')->field('a.*,b.industry_name,c.pro_name,d.city_name,u.area_name')->join('sp_industry b','a.indus_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where('a.id',$id)->find();
            if($applys){
                $manageres = Db::name('manage_apply')->where('apply_id',$applys['id'])->field('cate_id')->select();
                $managearr = array();
                foreach ($manageres as $v){
                    $managearr[] = $v['cate_id'];
                }
                $managearr = implode(',', $managearr);
                $cateres = Db::name('category')->where('id','in',$managearr)->where('pid',0)->field('id,cate_name')->order('sort asc')->select();
                $ziliaopicres = Db::name('apply_ziliaopic')->where('apply_id',$applys['id'])->select();
                $this->assign('applys',$applys);
                $this->assign('cateres',$cateres);
                $this->assign('ziliaopicres',$ziliaopicres);
                return $this->fetch();
            }else{
                $this->error('参数错误');
            }
        }else{
            $this->error('缺少参数');
        }
    }
    
    public function search(){
        if(input('post.keyword') != ''){
            cookie('apply_keyword',input('post.keyword'),7200);
        }else{
            cookie('apply_keyword',null);
        }
        
        if(input('post.search_type') != ''){
            cookie('apply_type',input('post.search_type'),7200);
        }
        
        if(input('post.apply_zt') != ''){
            cookie("apply_zt", input('post.apply_zt'), 7200);
        }
        
        if(input('post.starttime') != ''){
            $applystarttime = strtotime(input('post.starttime'));
            cookie('applystarttime',$applystarttime,3600);
        }
        
        if(input('post.endtime') != ''){
            $applyendtime = strtotime(input('post.endtime'));
            cookie('applyendtime',$applyendtime,3600);
        }
        
        if(input('post.pro_id') != ''){
            cookie("apply_pro_id", input('post.pro_id'), 7200);
        }
        
        if(input('post.city_id') != ''){
            cookie("apply_city_id", input('post.city_id'), 7200);
        }
        
        if(input('post.area_id') != ''){
            cookie("apply_area_id", input('post.area_id'), 7200);
        }
        
        $where = array();
        
        if(cookie('apply_pro_id') != ''){
            $proid = (int)cookie('apply_pro_id');
            if($proid != 0){
                $where['a.pro_id'] = $proid;
            }
        }
        
        if(cookie('apply_city_id') != ''){
            $cityid = (int)cookie('apply_city_id');
            if($cityid != 0){
                $where['a.city_id'] = $cityid;
            }
        }
        
        if(cookie('apply_area_id') != ''){
            $areaid = (int)cookie('apply_area_id');
            if($areaid != 0){
                $where['a.area_id'] = $areaid;
            }
        }

        if(cookie('apply_zt') != ''){
            $apply_zt = (int)cookie('apply_zt');
            if($apply_zt != 0){
                switch($apply_zt){
                    //待审核
                    case 1:
                        $where['a.checked'] = 0;
                        $where['a.qht'] = 0;
                        $where['a.state'] = 0;
                        $where['a.complete'] = 0;
                        break;
                    //待签合同
                    case 2:
                        $where['a.checked'] = 1;
                        $where['a.qht'] = 0;
                        $where['a.state'] = 0;
                        $where['a.complete'] = 0;
                        break;
                    //待支付保证金
                    case 3:
                        $where['a.checked'] = 1;
                        $where['a.qht'] = 1;
                        $where['a.state'] = 0;
                        $where['a.complete'] = 0;
                        break;
                    //待开通
                    case 4:
                        $where['a.checked'] = 1;
                        $where['a.qht'] = 1;
                        $where['a.state'] = 1;
                        $where['a.complete'] = 0;
                        break;
                    //已开通
                    case 5:
                        $where['a.checked'] = 1;
                        $where['a.qht'] = 1;
                        $where['a.state'] = 1;
                        $where['a.complete'] = 1;
                        break; 
                    //已拒绝
                    case 6:
                        $where['a.checked'] = 2;
                        $where['a.qht'] = 0;
                        $where['a.state'] = 0;
                        $where['a.complete'] = 0;
                        break;
                }
            }
        }
        
        if(cookie('apply_type')){
            if(cookie('apply_type') == 1 && cookie('apply_keyword')){
                $where['a.shop_name'] = cookie('apply_keyword');
            }elseif(cookie('apply_type') == 2 && cookie('apply_keyword')){
                $where['a.telephone'] = cookie('apply_keyword');
            }
        }
        
        if(cookie('applyendtime') && cookie('applystarttime')){
            $where['a.apply_time'] = array(array('egt',cookie('applystarttime')), array('lt',cookie('applyendtime')));
        }
        
        if(cookie('applystarttime') && !cookie('applyendtime')){
            $where['a.apply_time'] = array('egt',cookie('applystarttime'));
        }
        
        if(cookie('applyendtime') && !cookie('applystarttime')){
            $where['a.apply_time'] = array('lt',cookie('applyendtime'));
        }

        
        $list =  Db::name('apply_info')->alias('a')->field('a.id,a.shop_name,a.contacts,a.telephone,a.checked,a.qht,a.state,a.complete,a.apply_type,a.apply_time,b.industry_name,c.pro_name,d.city_name,u.area_name')->join('sp_industry b','a.indus_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where($where)->order('a.apply_time desc')->paginate(25);
        $page = $list->render();
        
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
        
        if(cookie('apply_pro_id')){
            $cityres = Db::name('city')->where('pro_id',cookie('apply_pro_id'))->field('id,city_name,zm')->order('sort asc')->select();
        }
        
        if(cookie('apply_pro_id') && cookie('apply_city_id')){
            $areares = Db::name('area')->where('city_id',cookie('apply_city_id'))->field('id,area_name,zm')->select();
        }
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        
        if(cookie('apply_keyword') != ''){
            $this->assign('keyword',cookie('apply_keyword'));
        }
        
        if(cookie('apply_type') != ''){
            $this->assign('search_type',cookie('apply_type'));
        }
        
        if(cookie('apply_zt') != ''){
            $this->assign('apply_zt',cookie('apply_zt'));
        }
        
        if(cookie('applystarttime') != ''){
            $this->assign('starttime',cookie('applystarttime'));
        }
        
        if(cookie('applyendtime') != ''){
            $this->assign('endtime',cookie('applyendtime'));
        }
        
        if(cookie('apply_pro_id') != ''){
            $this->assign('pro_id',cookie('apply_pro_id'));
        }
        if(cookie('apply_city_id') != ''){
            $this->assign('city_id',cookie('apply_city_id'));
        }
        if(cookie('apply_area_id') != ''){
            $this->assign('area_id',cookie('apply_area_id'));
        }
        
        if(!empty($cityres)){
            $this->assign('cityres',$cityres);
        }
        
        if(!empty($areares)){
            $this->assign('areares',$areares);
        }
        
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('filter',10);
        $this->assign('prores',$prores);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }      
    }

    public function ceshi(){
        return $this->fetch();
    }

    // 添加企业商家
    public function comapply(){
        // echo 1;die;
        if(request()->isPost()){
           
               
                    $data = input('post.');
    
                
                    $result = $this->validate($data,'ComapplyInfo');
                    if(true !== $result){
                        $value = array('status'=>0,'mess'=>$result);
                    }else{
                        if(!empty($data['indus_id'])){
                            $industrys = Db::name('industry')->where('id',$data['indus_id'])->where('is_show',1)->field('id')->find();
                            if(!$industrys){
                                $value = array('status'=>0,'mess'=>'请选择行业');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请选择行业');
                            return json($value);
                        }
                        
                        if(!empty($data['goods_id']) && is_array($data['goods_id'])){
                            $goodids = array_unique($data['goods_id']);
                            $info_id = implode(',', $goodids);
                            
                            foreach ($goodids as $v){
                                $cates = Db::name('category')->where('id',$v)->where('pid',0)->where('is_show',1)->field('id')->find();
                                if(!$cates){
                                    $value = array('status'=>0,'mess'=>'经营类目信息有误，申请失败');
                                    return json($value);
                                }
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请选择经营类目');
                            return json($value);
                        }
    
                        if(!empty($data['sfzz_id'])){
                            $sfzz_pics = Db::name('apply_zspic')->where('id',$data['sfzz_id'])->find();
                            if($sfzz_pics && $sfzz_pics['img_url']){
                                $data['sfzz_pic'] = $sfzz_pics['img_url'];
                            }else{
                                $value = array('status'=>0,'mess'=>'请上传身份证正面照片');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传身份证正面照片');
                            return json($value);
                        }
    
                        if(!empty($data['sfzb_id'])){
                            $sfzb_pics = Db::name('apply_zspic')->where('id',$data['sfzb_id'])->find();
                            if($sfzb_pics && $sfzb_pics['img_url']){
                                $data['sfzb_pic'] = $sfzb_pics['img_url'];
                            }else{
                                $value = array('status'=>0,'mess'=>'请上传身份证背面照片');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传身份证背面照片');
                            return json($value);
                        }
    
                        if(!empty($data['frsfz_id'])){
                            $frsfz_pics = Db::name('apply_zspic')->where('id',$data['frsfz_id'])->find();
                            if($frsfz_pics && $frsfz_pics['img_url']){
                                $data['frsfz_pic'] = $frsfz_pics['img_url'];
                            }else{
                                $value = array('status'=>0,'mess'=>'请上传手持身份证照片');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传手持身份证照片');
                            return json($value);
                        }
    
                        if(!empty($data['zhizhao_id'])){
                            $zhizhao_pics = Db::name('apply_zspic')->where('id',$data['zhizhao_id'])->find();
                            if($zhizhao_pics && $zhizhao_pics['img_url']){
                                $data['zhizhao'] = $zhizhao_pics['img_url'];
                            }else{
                                $value = array('status'=>0,'mess'=>'请上传营业执照照片');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传营业执照照片');
                            return json($value);
                        }
    
                        $pro_id = $data['pro_id'];
                        $city_id = $data['city_id'];
                        $area_id = $data['area_id'];
                        $pros = Db::name('province')->where('id',$pro_id)->where('checked',1)->where('pro_zs',1)->field('id,pro_name')->find();
                        if($pros){
                            $citys = Db::name('city')->where('id',$city_id)->where('pro_id',$pros['id'])->where('checked',1)->where('city_zs',1)->field('id,city_name')->find();
                            if($citys){
                                $areas = Db::name('area')->where('id',$area_id)->where('city_id',$citys['id'])->where('checked',1)->field('id,area_name')->find();
                                if($areas){
                                    $data['com_shengshiqu'] = $data['com_province'].$data['com_city'].$data['com_area'];
                                    $data['latlon'] = str_replace('，', ',', $data['latlon']);
                                    
                                    if(strpos($data['latlon'],',') !== false){
                                        // 启动事务
                                        Db::startTrans();
                                        try{
                                            $apply_id = Db::name('apply_info')->insertGetId(array(
                                                'com_name'=>$data['com_name'],
                                                'nature'=>$data['nature'],
                                                'com_shengshiqu'=>$data['com_shengshiqu'],
                                                'com_address'=>$data['com_address'],
                                                'fixed_phone'=>$data['fixed_phone'],
                                                'com_email'=>$data['com_email'],
                                                'zczj'=>$data['zczj'],
                                                'tyxydm'=>$data['tyxydm'],
                                                'faren_name'=>$data['faren_name'],
                                                'zzstart_time'=>$data['zzstart_time'],
                                                'zzend_time'=>$data['zzend_time'],
                                                'jyfw'=>$data['jyfw'],
                                                'shop_name'=>$data['shop_name'],
                                                'shop_desc'=>$data['shop_desc'],
                                                'indus_id'=>$data['indus_id'],
                                                'contacts'=>$data['contacts'],
                                                'telephone'=>$data['telephone'],
                                                'email'=>$data['email'],
                                                'sfz_num'=>$data['sfz_num'],
                                                'sfzz_pic'=>$data['sfzz_pic'],
                                                'sfzb_pic'=>$data['sfzb_pic'],
                                                'frsfz_pic'=>$data['frsfz_pic'],
                                                'zhizhao'=>$data['zhizhao'],
                                                'pro_id'=>$data['pro_id'],
                                                'city_id'=>$data['city_id'],
                                                'area_id'=>$data['area_id'],
                                                'shengshiqu'=>$pros['pro_name'].$citys['city_name'].$areas['area_name'],
                                                'address'=>$data['address'],
                                                'latlon'=>$data['latlon'],
												'settlement_date'=>$data['settlement_date'],
												'service_rate'=>$data['service_rate'],
                                                'apply_type'=>2,
                                                'apply_time'=>time(),
                                            ));
                                            
                                            if($apply_id){
                                                foreach ($goodids as $val){
                                                    Db::name('manage_apply')->insert(array('cate_id'=>$val,'apply_id'=>$apply_id,'apply_time'=>time()));
                                                }
                                                
                                                if($sfzz_pics && $sfzz_pics['img_url']){
                                                    Db::name('apply_zspic')->where('id',$sfzz_pics['id'])->delete();
                                                }
                                                
                                                if($sfzb_pics && $sfzb_pics['img_url']){
                                                    Db::name('apply_zspic')->where('id',$sfzb_pics['id'])->delete();
                                                }
                                                
                                                if($frsfz_pics && $frsfz_pics['img_url']){
                                                    Db::name('apply_zspic')->where('id',$frsfz_pics['id'])->delete();
                                                }
                                                
                                                if($zhizhao_pics && $zhizhao_pics['img_url']){
                                                    Db::name('apply_zspic')->where('id',$zhizhao_pics['id'])->delete();
                                                }
                                            }
                                            // 提交事务
                                            Db::commit();
                                            $value = array('status'=>1,'mess'=>'提交资料成功，请待审核');
                                        } catch (\Exception $e) {
                                            // 回滚事务
                                            Db::rollback();
                                            $value = array('status'=>0,'mess'=>'提交资料失败');
                                        }
                                    }else{
                                        $value = array('status'=>0,'mess'=>'地址坐标参数错误');
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'请选择区域，操作失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'请选择区域，操作失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请选择区域，操作失败');
                        }
                    }
                 
        
            return json($value);
        }else{
            
                // $user_id = session('user_id');
                // $zsinduspics = Db::name('apply_zspic')->where('user_id',$user_id)->field('id,img_url')->select();
                // if($zsinduspics){
                //     foreach ($zsinduspics as $v){
                //         Db::name('apply_zspic')->delete($v['id']);
                //         if($v['img_url'] && file_exists('./'.$v['img_url'])){
                //             @unlink('./'.$v['img_url']);
                //         }
                //     }
                // }
    
                // $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
                // if(!$applyinfos){
                    $prores = Db::name('province')->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                    $industryres = Db::name('industry')->where('is_show',1)->field('id,industry_name')->order('sort asc')->select();
                    $this->assign('industryres',$industryres);
                    $this->assign('prores',$prores);
                    return $this->fetch();
                // }else{
                //     if($applyinfos['checked'] == 2 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                //         $prores = Db::name('province')->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                //         $industryres = Db::name('industry')->where('is_show',1)->field('id,industry_name')->order('sort asc')->select();
                //         $this->assign('industryres',$industryres);
                //         $this->assign('prores',$prores);
                //         return $this->fetch();
                //     }elseif($applyinfos['checked'] == 0 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                //         $this->redirect('apply_info/waitchecked');
                //     }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                //         $this->redirect('apply_info/waitqht');
                //     }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                //         $this->redirect('apply_info/waitpaybzj');
                //     }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 1 && $applyinfos['complete'] == 0){
                //         $this->redirect('apply_info/waitcomplete');
                //     }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 1 && $applyinfos['complete'] == 1){
                //         $this->redirect('apply_info/complete');
                //     }else{
                //         $this->error('信息错误');
                //     }
                // }
             
        }
    }


    public function uploadifys(){
        //  echo 1;die;
            // $user_id = session('user_id');
            $file = request()->file('filedata');
            $webconfig = $this->webconfig;
            
            // $uploadnum = Db::name('upload_num')->where('user_id',$user_id)->find();
            // if($uploadnum){
            //     $jtime = time();
            //     if($jtime < $uploadnum['time_out']){
            //         if($uploadnum['num'] >= $webconfig['user_upload_max']){
            //             $value = array('status'=>0,'msg'=>'今天已超出最大上传次数');
            //             return json($value);
            //         }
            //     }
            // }
            
            if($file){
                $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                if($info){
                    $original = 'uploads/apply_pic/'.$info->getSaveName();
                    $image = \think\Image::open('./'.$original);
                    $image->thumb(800, 800)->save('./'.$original);
                    // 启动事务
                    Db::startTrans();
                    try{
                        $zspic_id = Db::name('apply_zspic')->insertGetId(array('img_url'=>$original));
                        if($uploadnum){
                            if($jtime < $uploadnum['time_out']){
                                $cishu = $uploadnum['num']+1;
                                // Db::name('upload_num')->update(array('num'=>$cishu,'user_id'=>$user_id,'id'=>$uploadnum['id']));
                            }else{
                                $time_out = strtotime(date('Y-m-d',time()))+3600*24;
                                $cishu = 1;
                                // Db::name('upload_num')->update(array('time_out'=>$time_out,'num'=>$cishu,'user_id'=>$user_id,'id'=>$uploadnum['id']));
                            }
                        }else{
                            $time_out = strtotime(date('Y-m-d',time()))+3600*24;
                            // Db::name('upload_num')->insert(array('time_out'=>$time_out,'num'=>1,'user_id'=>$user_id));
                        }
                        // 提交事务
                        Db::commit();
                        $picarr = array('img_url'=>$original,'pic_id'=>$zspic_id);;
                        $value = array('status'=>1, 'path'=>$picarr);
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $value = array('status'=>0, 'msg'=>'上传失败');
                    }
                }else{
                    $value = array('status'=>0,'msg'=>$file->getError());
                }
            }else{
                $value = array('status'=>0,'msg'=>'文件不存在');
            }
        
        return json($value);
    }
    
    public function delfile(){
        if(session('user_id')){
            if(input('post.zspic_id')){
                $user_id = session('user_id');
                $zspic_id = input('post.zspic_id');
                $img_url = Db::name('apply_zspic')->where('id',$zspic_id)->where('user_id',$user_id)->value('img_url');
                if($img_url){
                    $count = Db::name('apply_zspic')->delete($zspic_id);
                    if($count > 0){
                        if($img_url && file_exists('./'.$img_url)){
                            if(unlink('./'.$img_url)){
                                $value = 1;
                            }else{
                                $value = 0;
                            }
                        }else{
                            $value = 0;
                        }
                    }else{
                        $value = 0;
                    }
                }else{
                    $value = 0;
                }
            }else{
                $value = 0;
            }
        }else{
            $value = 0;
        }
        return json($value);
    }
    
    //验证商家名称唯一性
    public function checkShopname(){
        if(request()->isAjax()){
            if(input('post.shop_name')){
                $shop_name = Db::name('shops')->where(array('shop_name' => input('post.shop_name')))->find();
                if($shop_name){
                    echo 'false';
                }else{
                    echo 'true';
                }
            }else{
                echo 'false';
            }
        }
    }
    
    public function waitchecked(){
        if(session('user_id')){
            $user_id = session('user_id');
            $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
            if($applyinfos && $applyinfos['checked'] == 0 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                return $this->fetch();
            }else{
                $this->redirect('index/index');
            }
        }else{
            $this->redirect('login/index');
        }
    }
    
    public function jujue(){
        if(session('user_id')){
            $user_id = session('user_id');
            $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete,remarks')->order('apply_time desc')->find();
            if($applyinfos && $applyinfos['checked'] == 2 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                $this->assign('remarks',$applyinfos['remarks']);
                return $this->fetch();
            }else{
                $this->redirect('index/index');
            }
        }else{
            $this->redirect('login/index');
        }
    }
    
    public function waitqht(){
        if(session('user_id')){
            $user_id = session('user_id');
            $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete,remarks')->order('apply_time desc')->find();
            if($applyinfos && $applyinfos['checked'] == 1 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                $this->assign('remarks',$applyinfos['remarks']);
                return $this->fetch();
            }else{
                $this->redirect('index/index');
            }
        }else{
            $this->redirect('login/index');
        }
    }
    
    
    public function waitpaybzj(){
        if(session('user_id')){
            $user_id = session('user_id');
            $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,indus_id,checked,qht,state,complete')->order('apply_time desc')->find();
            if($applyinfos && $applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                $rzorders = Db::name('rz_order')->where('user_id',$user_id)->where('apply_id',$applyinfos['id'])->field('id,state')->find();
                if(!$rzorders || $rzorders['state'] == 0){
                    $industrys = Db::name('industry')->where('id',$applyinfos['indus_id'])->where('is_show',1)->field('id,industry_name,ser_price,remind')->find();
                    if($industrys){
                        $this->assign('industrys',$industrys);
                        return $this->fetch();
                    }else{
                        $this->redirect('index/index');
                    }
                }else{
                    $this->redirect('index/index');
                }
            }else{
                $this->redirect('index/index');
            }
        }else{
            $this->redirect('login/index');
        }
    }
    
    public function waitcomplete(){
        if(session('user_id')){
            $user_id = session('user_id');
            $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
            if($applyinfos && $applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 1 && $applyinfos['complete'] == 0){
                return $this->fetch();
            }else{
                $this->redirect('index/index');
            }          
        }else{
            $this->redirect('login/index');
        }
    }
    
  
     
     
    
    public function addorder(){
        if(request()->isPost()){
            if(session('user_id')){
                $user_id = session('user_id');
                $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->order('apply_time desc')->find();
                if($applyinfos){
                    if($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                        $rzorders = Db::name('rz_order')->where('user_id',$user_id)->where('apply_id',$applyinfos['id'])->field('id,state')->find();
                        if($rzorders){
                            if($rzorders['state'] == 0){
                                $value = array('status'=>1,'mess'=>'成功');
                            }elseif($rzorders['state'] == 1){
                                $value = array('status'=>0,'mess'=>'信息错误，提交订单失败');
                            }
                        }else{
                            $industrys = Db::name('industry')->where('id',$applyinfos['indus_id'])->where('is_show',1)->field('id,ser_price')->find();
                            if($industrys){
                                $ordernumber = 'R'.date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                $dingdan = Db::name('rz_order')->where('ordernumber',$ordernumber)->find();
                                if(!$dingdan){
                                    $lastId = Db::name('rz_order')->insert(array(
                                        'ordernumber'=>$ordernumber,
                                        'contacts'=>$applyinfos['contacts'],
                                        'telephone'=>$applyinfos['telephone'],
                                        'shop_name'=>$applyinfos['shop_name'],
                                        'total_price'=>$industrys['ser_price'],
                                        'pro_id'=>$applyinfos['pro_id'],
                                        'city_id'=>$applyinfos['city_id'],
                                        'area_id'=>$applyinfos['area_id'],
                                        'state'=>0,
                                        'user_id'=>$user_id,
                                        'apply_id'=>$applyinfos['id'],
                                        'indus_id'=>$industrys['id'],
                                        'addtime'=>time()
                                    ));
                                    if($lastId){
                                        $value = array('status'=>1,'mess'=>'提交订单成功');
                                    }else{
                                        $value = array('status'=>0,'mess'=>'提交订单失败');
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'提交订单失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'提交订单失败');
                            }
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'资料审核尚未通过');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'请先提交申请资料');
                }
            }else{
                $value = array('status'=>2,'mess'=>'身份已过期，请重新登录');
            }
            return json($value);
        }
    }

}
?>

