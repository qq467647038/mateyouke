<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Admin as AdminMx;

class Admin extends Common{
    //管理员列表
    public function lst(){
        $list = Db::name('admin')->alias('a')->field('a.id,a.username,a.en_name,a.suo,a.addtime,a.login_time,a.login_ip,a.roleid,b.rolename')->join('sp_role b','a.roleid = b.id','LEFT')->order('a.addtime desc,a.id desc')->paginate(3);
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
    
    //验证管理员账号唯一性
    public function checkUsername(){
        if(request()->isAjax()){
            $username = Db::name('admin')->where(array('username' => input('post.username')))->find();
            if($username){
                echo 'false';
            }else{
                echo 'true';
            }
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
        $count = Db::name('admin')->update($data);
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    //添加管理员
    public function add(){
       if(request()->isAjax()){
           $data = input('post.');
           $result = $this->validate($data,'Admin');
           if(true !== $result){
               $value = array('status'=>0,'mess'=>$result);
           }else{
               if(!empty($data['password'])){
                   $data['password'] = md5($data['password']);
                   $data['addtime'] = time();
                   if(!empty($data['repwd'])){
                       unset($data['repwd']);
                   }
                   $admin = new AdminMx();
                   $admin->data($data);
                   $lastId = $admin->allowField(true)->save();
                   if($lastId){
                       ys_admin_logs('添加管理员','admin',$admin->id);
                       $value = array('status'=>1,'mess'=>'增加成功');
                   }else{
                       $value = array('status'=>0,'mess'=>'增加失败');
                   }
               }else{
                   $value = array('status'=>0,'mess'=>'密码不能为空');
               }
           }
           return json($value);
       }else{
           $list = Db::name('role')->field('id,rolename')->select();
           $this->assign('list',$list);
           return $this->fetch('add');
       }
    }

    //编辑管理员
    public function edit(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'Admin');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(!empty($data['password'])){
                    $data['password'] = md5($data['password']);
                }else{
                    unset($data['password']);
                }
                if(empty($data['repwd'])){
                    unset($data['repwd']);
                }
                $admin = new AdminMx();
                $count = $admin->allowField(true)->save($data,array('id'=>$data['id'])); 
                if($count !== false){
                    $log = '编辑管理员';
                    $tables = 'admin';
                    $opid = $data['id'];
                    ys_admin_logs($log,$tables,$opid);
                    $value = array('status'=>1,'mess'=>'修改成功');
                }else{
                    $value = array('status'=>0,'mess'=>'修改失败');
                }
            }
            return json($value);
        }else{
            $id = input('id');
            $rolelist = Db::name('role')->field('id,rolename')->select();
            $admins = Db::name('admin')->field('id,username,en_name,roleid,suo')->find($id);
            if(input('s')){
                $this->assign('search',input('s'));
            }
            $this->assign('admins',$admins);
            $this->assign('rolelist',$rolelist);
            $this->assign('pnum',input('page'));
            return $this->fetch();
        }
    }    

    //删除管理员
    public function delete(){
        if(input('post.id')){
            $id= array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        if(!empty($id)){
            $count = AdminMx::destroy($id);
            if($count > 0){
                if(is_array($id)){
                    foreach ($id as $v2){
                        ys_admin_logs('删除后台管理员','admin',$v2);
                    }
                }else{
                    ys_admin_logs('删除后台管理员','admin',$id);
                }
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'编辑失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }

    //搜索管理员
    public function search(){
        if(input('post.keyword') != ''){
            cookie('admin_name',input('post.keyword'),7200);
        }
        $where = array();
        if(cookie('admin_name')){
            $where['a.username'] = array('like','%'.cookie('admin_name').'%');
        }
        $list = Db::name('admin')->alias('a')->field('a.id,a.username,a.en_name,a.suo,a.addtime,a.login_time,a.login_ip,a.roleid,b.rolename')->join('sp_role b','a.roleid = b.id','LEFT')->where($where)->order('a.addtime desc,a.id desc')->paginate(2);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('admin_name')){
            $this->assign('admin_name',cookie('admin_name'));
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

    //退出登录
    public function loginOut(){
        session(null);
        if(isset($_COOKIE[session_name()])){
            setcookie(session_name(),'',time()-3600,'/');
        }
        session_destroy();
        $this->redirect('Index/index');
    }
}

?>