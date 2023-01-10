<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\ShopAdmin as ShopAdminMx;

class ShopAdmin extends Common{
    //商家管理员列表
    public function lst(){
        $list = Db::name('shop_admin')->alias('a')->field('a.id,a.phone,a.open_status,a.addtime,a.login_time,a.login_ip,b.shop_name')->join('sp_shops b','a.shop_id = b.id','LEFT')->order('a.addtime desc,a.id desc')->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    //指定商家管理员列表
    public function shoplist(){
        if(input('shop_id')){
            $shop_id = input('shop_id');
            $list = Db::name('shop_admin')->alias('a')->field('a.id,a.phone,a.open_status,a.addtime,a.login_time,a.login_ip,b.shop_name')->join('sp_shops b','a.shop_id = b.id','LEFT')->order('a.addtime desc,a.id desc')->where('shop_id',$shop_id)->paginate(25);
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
                'shop_id'=>$shop_id
            ));
            if(request()->isAjax()){
                return $this->fetch('ajaxpage');
            }else{
                return $this->fetch('lst');
            }
        }else{
            $this->error('缺少参数');
        }
    }
   
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $count = Db::name('shop_admin')->update($data);
        if($count > 0){
            ys_admin_logs('改变商家管理员开启或关闭状态','shop_admin',$id);
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
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

    //编辑商家账号
    public function edit(){
        if(request()->isPost()){
            if(input('post.id')){
                $data = input('post.');
                $data['xieyi'] = 1;
                $result = $this->validate($data,'ShopAdmin');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $shopadmins = Db::name('shop_admin')->where('id',$data['id'])->field('id')->find();
                    if($shopadmins){
                        if(!empty($data['password'])){
                            $data['password'] = md5($data['password']);
                        }else{
                            unset($data['password']);
                        }
                        $shop_admin = new ShopAdminMx();
                        $count = $shop_admin->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            $log = '编辑商家管理员';
                            $tables = 'shop_admin';
                            $opid = $data['id'];
                            ys_admin_logs($log,$tables,$opid);
                            $value = array('status'=>1,'mess'=>'修改成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'修改失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            $id = input('id');
            if(!empty($id)){
                $shop_admins = Db::name('shop_admin')->field('id,phone,open_status')->find($id);
                if($shop_admins){
                    if(input('s')){
                        $this->assign('search',input('s'));
                    }
                    $this->assign('shop_admins',$shop_admins);
                    $this->assign('pnum',input('page'));
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }  
	  
	//编辑商家账号
	public function edit_account(){
	    if(request()->isPost()){
	        if(input('post.id')){
	            $data = input('post.');
	            //$result = $this->validate($data,'ShopAdmin');
	            //if(true !== $result){
	            //    $value = array('status'=>0,'mess'=>$result);
	            //}else{
	                $shopadmins = Db::name('shops')->where('id',$data['id'])->field('id')->find();
	                if($shopadmins){
	                    $count = Db::name('shops')->update($data);
	                    if($count !== false){
	                        $log = '编辑商家结算设置';
	                        $tables = 'shops';
	                        $opid = $data['id'];
	                        ys_admin_logs($log,$tables,$opid);
	                        $value = array('status'=>1,'mess'=>'修改成功');
	                    }else{
	                        $value = array('status'=>0,'mess'=>'修改失败');
	                    }
	                }else{
	                    $value = array('status'=>0,'mess'=>'找不到相关信息');
	                }
	            //}
	        }else{
	            $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
	        }
	        return json($value);
	    }else{
	        $id = input('id');
	        if(!empty($id)){
	            $shops = Db::name('shops')->field('id,settlement_date,service_rate')->find($id);
	            if($shops){
	                
	                $this->assign('shop',$shops);
	                return $this->fetch();
	            }else{
	                $this->error('找不到相关信息');
	            }
	        }else{
	            $this->error('缺少参数');
	        }
	    }
	}

    //搜索管理员
    public function search(){
        if(input('post.keyword') != ''){
            cookie('shopadmin_telephone',input('post.keyword'),7200);
        }
        $where = array();
        if(cookie('shopadmin_telephone')){
            $where['a.phone'] = cookie('shopadmin_telephone');
        }
        $list = Db::name('shop_admin')->alias('a')->field('a.id,a.phone,a.open_status,a.addtime,a.login_time,a.login_ip,b.shop_name')->join('sp_shops b','a.shop_id = b.id','LEFT')->where($where)->order('a.addtime desc,a.id desc')->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('shopadmin_telephone')){
            $this->assign('keyword',cookie('shopadmin_telephone'));
        }
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
}

?>