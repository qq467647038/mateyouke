<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Salesperson extends Common{
    //销售员列表
    public function lst(){
        $filter = input('filter');
        if(empty($filter)){
            $filter = 10;
        }
        
        $where = array();
        
        switch($filter){
            //全部
            case 10:
                $where = array();
                break;
            //开启
            case 1:
                $where['a.checked'] = 1;
                break;
            //关闭
            case 2:
                $where['a.checked'] = 0;
                break;
        }
        
        $where['a.leixing'] = 1;
        
        $list = Db::name('member')->alias('a')->field('a.id,a.user_name,a.headimgurl,a.phone,a.regtime,a.checked,b.position_name,c.price')->join('sp_position b','a.wz_id = b.id','LEFT')->join('sp_wallet c','a.id = c.user_id','LEFT')->where($where)->order('a.regtime desc')->paginate(25);
        $page = $list->render();
        
        $positionres = Db::name('position')->field('id,position_name')->order('sort asc')->select();
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum,
            'prores'=>$prores,
            'positionres'=>$positionres,
            'filter'=>$filter
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $count = Db::name('member')->where('id',$id)->where('leixing',1)->update($data);
        if($count > 0){
            if($value == 1){
                ys_admin_logs('开启销售员','member',$id);
            }elseif($value == 0){
                ys_admin_logs('关闭销售员','member',$id);
            }
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
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
    
    public function scpwd(){
        if(request()->isPost()){
           $password = createSMSCode(6);   
           if($password){
               $value = array('status'=>1,'mess'=>'生成密码成功','password'=>$password);
           }else{
               $value = array('status'=>0,'mess'=>'生成密码失败');
           }
           return json($value);
        }
    }
    
    
    //处理上传图片
    public function uploadify(){
        $admin_id = session('admin_id');
        $file = request()->file('filedata');
        if($file){
            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'salesperson');
            if($info){
                $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
                if($zssjpics && $zssjpics['img_url']){
                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                        @unlink('./'.$zssjpics['img_url']);
                    }
                }
                $date = date('Ymd',time());
                $original = 'public/uploads/salesperson/'.$info->getSaveName();
                $image = \think\Image::open('./'.$original);
                $image->thumb(200, 300)->save('./'.$original);
                if($zssjpics){
                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>$original));
                    $zspic_id = $zssjpics['id'];
                }else{
                    $zspic_id = Db::name('huamu_zspic')->insertGetId(array('admin_id'=>$admin_id,'img_url'=>$original));
                }
                $picarr = array('img_url'=>$original,'pic_id'=>$zspic_id);
                $value = array('status'=>1,'path'=>$picarr);
            }else{
                $value = array('status'=>0,'msg'=>$file->getError());
            }
        }else{
            $value = array('status'=>0,'msg'=>'文件不存在');
        }
        return json($value);
    }
    
    //手动删除未保存的上传图片手机
    public function delfile(){
        if(input('post.zspic_id')){
            $admin_id = session('admin_id');
            $zspic_id = input('post.zspic_id');
            $pics = Db::name('huamu_zspic')->where('id',$zspic_id)->where('admin_id',$admin_id)->find();
            if($pics && $pics['img_url']){
                $count = Db::name('huamu_zspic')->where('id',$pics['id'])->update(array('img_url'=>''));
                if($count > 0){
                    if($pics['img_url'] && file_exists('./'.$pics['img_url'])){
                        @unlink('./'.$pics['img_url']);
                    }
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
        return json($value);
    }
    
    public function checkPhone(){
        if(request()->isPost()){
            $arr = Db::name('member')->where('phone',input('post.phone'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
    
    public function add(){
        if(request()->isPost()){
            $data = input('post.');
            $data['email'] = input('post.email');
            $data['wxnum'] = input('post.wxnum');
            $data['qqnum'] = input('post.qqnum');
            $admin_id = session('admin_id');
            $result = $this->validate($data,'Member.saleadd');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $positions = Db::name('position')->where('id',$data['wz_id'])->find();
                if($positions){
                    if(!empty($data['pic_id'])){
                        $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                        if($zssjpics && $zssjpics['img_url']){
                            $data['headimgurl'] = $zssjpics['img_url'];
                            $data['password'] = md5($data['password']);
                            
                            $token = settoken();
                            $rxs = Db::name('rxin')->where('token',$token)->find();
                            
                            $appinfo_code = settoken();
                            $members = Db::name('member')->where('appinfo_code',$appinfo_code)->field('id')->find();
                            
                            if(!$rxs && !$members){
                                $datainfo = array();
                                $datainfo['user_name'] = $data['user_name'];
                                $datainfo['phone'] = $data['phone'];
                                $datainfo['password'] = $data['password'];
                                $datainfo['appinfo_code'] = $appinfo_code;
                                if($data['email']){
                                    $datainfo['email'] = $data['email'];
                                }
                                if($data['wxnum']){
                                    $datainfo['wxnum'] = $data['wxnum'];
                                }
                                if($data['qqnum']){
                                    $datainfo['qqnum'] = $data['qqnum'];
                                }
                                $datainfo['headimgurl'] = $data['headimgurl'];
                                $datainfo['leixing'] = 1;
                                $datainfo['checked'] = $data['checked'];
                                $datainfo['wz_id'] = $data['wz_id'];
                                $datainfo['regtime'] = time();
                                // 启动事务
                                Db::startTrans();
                                try{
                                    $user_id = Db::name('member')->insertGetId($datainfo);
                                    if($user_id){
                                        Db::name('rxin')->insert(array('token'=>$token,'user_id'=>$user_id));
                                        Db::name('wallet')->insert(array('price'=>0,'user_id'=>$user_id));
                                        Db::name('profit')->insert(array('price'=>0,'user_id'=>$user_id));
                                    }
                                    // 提交事务
                                    Db::commit();
                                    if($zssjpics && $zssjpics['img_url']){
                                        Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                                    }
                                    ys_admin_logs('添加销售员','member',$user_id);
                                    $value = array('status'=>1,'mess'=>'增加成功');
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>0,'mess'=>'增加失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'增加失败，请重试');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传头像');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'请上传头像');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'参数错误');
                }
            }
            return json($value);
        }else{
            $admin_id = session('admin_id');
            $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
            if($zssjpics && $zssjpics['img_url']){
                Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                    @unlink('./'.$zssjpics['img_url']);
                }
            }
            $positionres = Db::name('position')->field('id,position_name')->order('sort asc')->select();
            $this->assign('positionres',$positionres);
            return $this->fetch();
        }
    }
    
    public function edit(){
        if(request()->isPost()){
            if(input('post.id')){
                $admin_id = session('admin_id');
                $data = input('post.');
                $data['email'] = input('post.email');
                $data['wxnum'] = input('post.wxnum');
                $data['qqnum'] = input('post.qqnum');
                $result = $this->validate($data,'Member.saleedit');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $sales = Db::name('member')->where('id',$data['id'])->where('leixing',1)->find();
                    if($sales){
                        $phoneinfos = Db::name('member')->where('phone',$data['phone'])->where('id','neq',$data['id'])->find();
                        if(!$phoneinfos){
                            $positions = Db::name('position')->where('id',$data['wz_id'])->find();
                            if($positions){
                                if($sales['wz_id'] != $data['wz_id']){
                                    $quyu_level = Db::name('position')->where('id',$sales['wz_id'])->value('quyu_level');
                                    if($quyu_level != $positions['quyu_level']){
                                        $salequyu = Db::name('sale_quyu')->where('user_id',$data['id'])->find();
                                        if($salequyu){
                                            $value = array('status'=>0,'mess'=>'职位发生变化，请先删除该人员服务区域');
                                            return json($value);
                                        }
                                    }
                                }
                
                                if(!empty($data['pic_id'])){
                                    $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                                    if($zssjpics && $zssjpics['img_url']){
                                        $data['headimgurl'] = $zssjpics['img_url'];
                                    }else{
                                        $data['headimgurl'] = $sales['headimgurl'];
                                    }
                                }else{
                                    $data['headimgurl'] = $sales['headimgurl'];
                                }
                
                                if($data['password']){
                                    $data['password'] = md5($data['password']);
                                }else{
                                    $data['password'] = $sales['password'];
                                }
                
                                $count = Db::name('member')->update(array('id'=>$data['id'],'wz_id'=>$data['wz_id'],'user_name'=>$data['user_name'],'phone'=>$data['phone'],'password'=>$data['password'],'email'=>$data['email'],'wxnum'=>$data['wxnum'],'qqnum'=>$data['qqnum'],'headimgurl'=>$data['headimgurl'],'leixing'=>1,'checked'=>$data['checked']));
                                if($count !== false){
                                    if(!empty($zssjpics) && $zssjpics['img_url']){
                                        Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                                        if($sales['headimgurl'] && file_exists('./'.$sales['headimgurl'])){
                                            @unlink('./'.$sales['headimgurl']);
                                        }
                                    }
                                    ys_admin_logs('编辑销售员','member',$data['id']);
                                    $value = array('status'=>1,'mess'=>'编辑成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'编辑失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'参数错误，编辑失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'手机号已存在，编辑失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'信息错误，编辑失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $id = input('id');
                $admin_id = session('admin_id');
                $sales = Db::name('member')->where('id',$id)->where('leixing',1)->find();
                if($sales){
                    $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                            @unlink('./'.$zssjpics['img_url']);
                        }
                    }
                    
                    
                    $positionres = Db::name('position')->field('id,position_name')->order('sort asc')->select();
                    
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    $this->assign('pnum', input('page'));
                    $this->assign('filter',input('filter'));
                    $this->assign('sales',$sales);
                    $this->assign('positionres',$positionres);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    public function delete(){
        if(input('id') && !is_array(input('id'))){
            $id = input('id');
            $sale_quyus = Db::name('sale_quyu')->where('user_id',$id)->find();
            if(!$sale_quyus){
                $guanxis = Db::name('distr_guanxi')->where('uid',$id)->find();
                if(!$guanxis){
                    $details = Db::name('detail')->where('user_id',$id)->find();
                    if(!$details){
                        $por_intos = Db::name('por_into')->where('user_id',$id)->find();
                        if(!$por_intos){
                            // 启动事务
                            Db::startTrans();
                            try{
                                Db::name('member')->where('id',$id)->where('leixing',1)->delete();
                                Db::name('wallet')->where('user_id',$id)->delete();
                                Db::name('profit')->where('user_id',$id)->delete();
                                Db::name('rxin')->where('user_id',$id)->delete();
                                // 提交事务
                                Db::commit();
                                ys_admin_logs('删除销售人员','member',$id);
                                $value = array('status'=>1,'mess'=>'删除成功');
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status'=>0,'mess'=>'删除失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'该人员存在待分成信息，删除失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'该人员存在余额明细，删除失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'该人员存在推广经销商，删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'请先删除该人员的管理区域');
            }
        }else{
            $value = array('status'=>0,'mess'=>'参数错误，删除失败');
        }
        return json($value);
    }
    
    public function search(){
        if(input('post.keyword') != ''){
            cookie('sales_keyword',input('post.keyword'),7200);
        }else{
            cookie('sales_keyword',null);
        }
    
        if(input('post.pro_id') != ''){
            cookie("sales_pro_id", input('post.pro_id'), 7200);
        }
    
        if(input('post.city_id') != ''){
            cookie("sales_city_id", input('post.city_id'), 7200);
        }
    
        if(input('post.area_id') != ''){
            cookie("sales_area_id", input('post.area_id'), 7200);
        }
        
        if(input('post.wz_id') != ''){
            cookie('sales_wz_id',input('post.wz_id'),7200);
        }
    
        if(input('post.sales_zt') != ''){
            cookie('sales_zt',input('post.sales_zt'),7200);
        }
    
        if(input('post.sales_type') != ''){
            cookie('sales_type',input('post.sales_type'),7200);
        }
    
        $where = array();
        $where['a.leixing'] = 1;
        
        if(cookie('sales_pro_id') && cookie('sales_city_id') && cookie('sales_area_id')){
            $proid = (int)cookie('sales_pro_id');
            $cityid = (int)cookie('sales_city_id');
            $areaid = (int)cookie('sales_area_id');
            $sales_idres = Db::name('sale_quyu')->where('pro_id',$proid)->where('city_id',$cityid)->where('area_id',$areaid)->field('user_id')->select();
            if(!empty($sales_idres)){
                $salesres = array();
                foreach ($sales_idres as $v){
                    $salesres[] = $v['user_id'];
                }
                $where['a.id'] = array('in',$salesres);
            }        
        }elseif(cookie('sales_pro_id') && cookie('sales_city_id') && !cookie('sales_area_id')){
            $proid = (int)cookie('sales_pro_id');
            $cityid = (int)cookie('sales_city_id');
            $sales_idres = Db::name('sale_quyu')->where('pro_id',$proid)->where('city_id',$cityid)->field('user_id')->select();
            if(!empty($sales_idres)){
                $salesres = array();
                foreach ($sales_idres as $v){
                    $salesres[] = $v['user_id'];
                }
                $where['a.id'] = array('in',$salesres);
            }
        }elseif(cookie('sales_pro_id') && !cookie('sales_city_id') && !cookie('sales_area_id')){
            $proid = (int)cookie('sales_pro_id');
            $sales_idres = Db::name('sale_quyu')->where('pro_id',$proid)->field('user_id')->select();
            if(!empty($sales_idres)){
                $salesres = array();
                foreach ($sales_idres as $v){
                    $salesres[] = $v['user_id'];
                }
                $where['a.id'] = array('in',$salesres);
            }
        }
    
        if(cookie('sales_wz_id') != ''){
            $sales_wz_id = (int)cookie('sales_wz_id');
            if($sales_wz_id != 0){
                $where['a.wz_id'] = cookie('sales_wz_id');
            }
        }
    
        
        if(cookie('sales_zt') != ''){
            $sales_zt = (int)cookie('sales_zt');
            if($sales_zt != 0){
                switch ($sales_zt){
                    //开启
                    case 1:
                        $where['a.checked'] = 1;
                        break;
                    //关闭
                    case 2:
                        $where['a.checked'] = 0;
                        break;
                }
            }
        }
    
        if(cookie('sales_type') == 1 && cookie('sales_keyword')){
            $where['a.user_name'] = cookie('sales_keyword');
        }elseif(cookie('sales_type') == 2 && cookie('sales_keyword')){
            $where['a.phone'] = cookie('sales_keyword');
        }
    
        $list = Db::name('member')->alias('a')->field('a.id,a.user_name,a.headimgurl,a.phone,a.regtime,a.checked,b.position_name,c.price')->join('sp_position b','a.wz_id = b.id','LEFT')->join('sp_wallet c','a.id = c.user_id','LEFT')->where($where)->order('a.regtime desc')->paginate(25);
        
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $positionres = Db::name('position')->field('id,position_name')->order('sort asc')->select();
        
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
        if(cookie('sales_pro_id')){
            $cityres = Db::name('city')->where('pro_id',cookie('sales_pro_id'))->field('id,city_name,zm')->order('sort asc')->select();
        }
        if(cookie('sales_pro_id') && cookie('sales_city_id')){
            $areares = Db::name('area')->where('city_id',cookie('sales_city_id'))->field('id,area_name,zm')->select();
        }
    
        $search = 1;
        
        $filter = 10;
    
        if(!empty($cityres)){
            $this->assign('cityres',$cityres);
        }
        if(!empty($areares)){
            $this->assign('areares',$areares);
        }
    
        if(cookie('sales_pro_id') != ''){
            $this->assign('pro_id',cookie('sales_pro_id'));
        }
        if(cookie('sales_city_id') != ''){
            $this->assign('city_id',cookie('sales_city_id'));
        }
        if(cookie('sales_area_id') != ''){
            $this->assign('area_id',cookie('sales_area_id'));
        }
        
        if(cookie('sales_wz_id') != ''){
            $this->assign('wz_id',cookie('sales_wz_id'));
        }
    
        if(cookie('sales_keyword') != ''){
            $this->assign('keyword',cookie('sales_keyword'));
        }
    
        if(cookie('sales_zt') != ''){
            $this->assign('sales_zt',cookie('sales_zt'));
        }
    
        if(cookie('sales_type') != ''){
            $this->assign('sales_type',cookie('sales_type'));
        }
    
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('filter',$filter);
        $this->assign('prores',$prores);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('positionres',$positionres);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }

}

?>