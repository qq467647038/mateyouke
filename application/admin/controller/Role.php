<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Role as RoleMx;

class Role extends Common{
    public function lst(){    
        $list = Db::name('role')->field('a.*,GROUP_CONCAT(b.pri_name) pri_name')->alias('a')->join('sp_privilege b','FIND_IN_SET(b.id,a.pri_id_list)','LEFT')->group('a.id')->select();
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function add(){    
        if(request()->isAjax()){ 
            $data = input('post.');
            $result = $this->validate($data,'Role');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(empty($data['pri_id_list'])){
                    $value = array('status'=>0,'mess'=>'请添加权限选项');
                }else{
                    $data['pri_id_list'] = implode(',',$data['pri_id_list']);
                    $role = new RoleMx();
                    $role->data($data);
                    $lastId = $role->allowField(true)->save();
                    if($lastId){
                        ys_admin_logs('新增角色','role',$role->id);
                        $value = array('status'=>1,'mess'=>'增加成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'增加失败');
                    }
                }
            }
            return json($value);
        }else{
            $prilist = Db::name('privilege')->field('id,pri_name,pid')->select();
            $this->assign('prilist',recursive($prilist));
            return $this->fetch();
        }   
    }
  
    public function checkrolename(){
        if(request()->isAjax()){
            $arr = Db::name('role')->where('rolename',input('post.rolename'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }

    public function edit(){
        if(request()->isAjax()){ 
            $data = input('post.');
            $result = $this->validate($data,'Role');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(empty($data['pri_id_list'])){
                    $value = array('status'=>0,'mess'=>'请添加权限选项');
                }else{
                    $data['pri_id_list'] = implode(',', $data['pri_id_list']);
                    $role = new RoleMx();
                    $count = $role->allowField(true)->save($data,array('id'=>$data['id']));
                    if($count !== false){
                        ys_admin_logs('编辑角色','role',$data['id']);
                        $value = array('status'=>1,'mess'=>'编辑成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'编辑失败');
                    }
                }
            }
            return json($value);
        }else{   
            $id = input('id');
            $prilist = Db::name('privilege')->field('id,pri_name,pid')->select();    
            $roles = Db::name('role')->find($id);    
            $qx = $roles['pri_id_list'];    
            $qx = explode(',', $qx);   
            foreach($prilist as $key => $v){
                if(in_array($v['id'], $qx)){
                    $prilist[$key]['xz'] = 1;
                }
            }
            $this->assign('roles', $roles);
            $this->assign('prilist', recursive($prilist));
            return $this->fetch();
        }
    }

    public function delete(){
        $id = input('id');
        if(!empty($id) && !is_array($id)){
            $child = Db::name('admin')->where('roleid',$id)->limit(1)->find();
            if(!empty($child)){
                $value = array('status'=>0,'mess'=>'该角色下存在管理员，删除失败');
            }else{
                $count = RoleMx::destroy($id);
                if($count > 0){
                    ys_admin_logs('删除角色','role',$id);
                    $value = array('status'=>1,'mess'=>'删除成功');
                }else{
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return json($value);   
    }    
    
}
