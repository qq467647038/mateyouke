<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Privilege as PrivilegeMx;

class Privilege extends Common{
    // 角色操作日志
    public function role_oper()
    {
        $admin_list = Db::name('admin')->column('id, username');

        $list = Db::name('admin_log')->alias('al')->field('al.*,a.username')->join('admin a', 'a.id = al.admin_id', 'left')->order('al.addtime desc')->paginate(25);
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
            return $this->fetch('ajaxpage_role');
        }else{
            return $this->fetch('lst_role');
        }
    }

    //权限列表
    public function lst(){
        $list = Db::name('privilege')->field('id,pri_name,mname,cname,aname,fwname,pid,sort,icon')->where('status',1)->order('sort asc')->select();
        $this->assign('list', recursive($list));
        return $this->fetch();
    }

    //添加权限视图   
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'Privilege');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $privilege = new PrivilegeMx();
                $privilege->data($data);
                $lastId = $privilege->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增权限','privilege',$privilege->id);
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            $prires = Db::name('privilege')->field('id,pri_name,pid')->where('status',1)->order('sort asc')->select();
            $this->assign('prires', recursive($prires));
            return $this->fetch();
        }
    }
    
    public function checkpriname(){    
        if(request()->isAjax()){
            $arr = Db::name('privilege')->where('pri_name',input('post.pri_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }

    /*
    *
    * 编辑权限视图
    */
    public function edit(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'Privilege');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $privilege = new PrivilegeMx();
                $count = $privilege->allowField(true)->save($data,array('id'=>$data['id']));
                if($count !== false){
                    ys_admin_logs('编辑权限','privilege',$data['id']);
                    $value = array('status'=>1,'mess'=>'编辑成功');
                }else{
                    $value = array('status'=>0,'mess'=>'编辑失败');
                }
            }
            return json($value);
        }else{
            $id = input('id');
            $prires = Db::name('privilege')->where('id','neq',$id)->field('id,pri_name,pid')->order('sort asc')->select();
            $pris = Db::name('privilege')->where('id',$id)->find();
            $this->assign('prires', recursive($prires));
            $this->assign('pris', $pris);
            return $this->fetch();
        }
    }

    //处理删除权限    
    public function delete(){
        $id = input('id');
        if(!empty($id) && !is_array($id)){
            $child = Db::name('privilege')->where('pid',$id)->limit(1)->find();
            if(!empty($child)){
                $value = array('status'=>0,'mess'=>'该权限下存在子权限，删除失败');
            }else{
                $count = PrivilegeMx::destroy($id);
                if($count > 0){
                    ys_admin_logs('删除权限','privilege',$id);
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
    
    //处理排序
    public function order(){
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                $privilege = new PrivilegeMx();
                $privilege->save($data2,array('id'=>$data2['id']));
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }
     
}