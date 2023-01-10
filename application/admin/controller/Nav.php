<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Nav as NavMx;

class Nav extends Common{
    //自定义导航位
    public function lst(){
        $list = Db::name('nav')->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign('pnum',$pnum);
        $this->assign('page',$page);
        $this->assign('list',$list);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch();
        }
    }

    
    //修改新窗口打开
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $count = Db::name('nav')->update($data);
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    } 
    
    
    public function checkNavname(){
        if(request()->isAjax()){
            $arr = Db::name('nav')->where('nav_name',input('post.nav_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }else{
            $this->error('非法请求');
        }
    }

    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'Nav');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $nav = new NavMx();
                $nav->data($data);
                $lastId = $nav->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增自定义导航','nav',$nav->id);
                    $value = array('status'=>1, 'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0, 'mess'=>'增加失败');
                }
            }
            return $value;
        }else{
            return $this->fetch();
        }
    }
        
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'Nav');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $navinfos = Db::name('nav')->where('id',$data['id'])->find();
                    if($navinfos){
                        $nav = new NavMx();
                        $count = $nav->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            ys_admin_logs('编辑自定义导航','nav',$data['id']);
                            $value = array('status'=>1, 'mess'=>'编辑成功');
                        }else{
                            $value = array('status'=>0, 'mess'=>'编辑失败');
                        }
                    }else{
                        $value = array('status'=>0, 'mess'=>'找不到相关信息，编辑失败');
                    }
                }
            }else{
                $value = array('status'=>0, 'mess'=>'缺少参数，编辑失败');
            }
            return $value;
        }else{
            $id = input('id');
            if(!empty($id)){
                $navs = Db::name('nav')->where('id',$id)->find();
                if($navs){
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    $this->assign('pnum', input('page'));
                    $this->assign('navs',$navs);
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
        $id = input('id');
        if(!empty($id) && !is_array($id)){
            $menus = Db::name('nav_menu')->where('nav_id',$id)->field('id')->limit(1)->find();
            if(!empty($menus)){
                $value = array('status'=>0,'mess'=>'该导航位下存在导航菜单，删除失败');
            }else{
                $count = NavMx::destroy($id);
                if($count > 0){
                    ys_admin_logs('删除自定义导航','nav',$id);
                    $value = array('status'=>1,'mess'=>'删除成功');
                }else{
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return $value;
    }
    
    public function search(){
        if(input('post.keyword')){
            cookie('nav_name',input('post.keyword'),3600);
        }
        $where = array();
        if(cookie('nav_name') != ''){
            $where['nav_name'] = array('like','%'.cookie('nav_name').'%');
        }
        $list = Db::name('nav')->where($where)->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('nav_name')){
            $this->assign('keyword',cookie('nav_name'));
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