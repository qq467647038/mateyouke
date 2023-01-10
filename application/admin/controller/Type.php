<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Type as TypeMx;

class Type extends Common{
    //类型列表
    public function lst(){
        $list = Db::name('type')->order('sort asc')->paginate(50);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign('page',$page);
        $this->assign('pnum',$pnum);
        $this->assign('list', $list);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }    
    }
    
    public function checkTypename(){
        if(request()->isPost()){
            $arr = Db::name('type')->where('type_name',input('post.type_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
    
    //添加类型
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'Type');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $type = new TypeMx();
                $type->data($data);
                $lastId = $type->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增类型','type',$type->id);
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }       
    
    /*
     * 编辑类型
     */
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'Type');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $typeinfos = Db::name('type')->where('id',$data['id'])->find();
                    if($typeinfos){
                        $type = new TypeMx();
                        $count = $type->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            ys_admin_logs('编辑类型','type',$data['id']);
                            $value = array('status'=>1,'mess'=>'编辑成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'编辑失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $id = input('id');
                $types = Db::name('type')->where('id',$id)->find();
                if($types){
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    $this->assign('pnum', input('page'));
                    $this->assign('types', $types);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }    
    
    //处理删除类型
    public function delete(){
        $id = input('id');
        if(!empty($id) && !is_array($id)){
            $types = Db::name('type')->where('id',$id)->find();
            if($types){
                $cates = Db::name('category')->where('type_id',$id)->find();
                if(!$cates){
                    $attrs = Db::name('attr')->where('type_id',$id)->field('id')->limit(1)->find();
                    if(!empty($attrs)){
                        $value = array('status'=>0,'mess'=>'该类型下存在属性，删除失败');
                    }else{
                        $count = TypeMX::destroy($id);
                        if($count > 0){
                            ys_admin_logs('删除类型','type',$id);
                            $value = array('status'=>1,'mess'=>'删除成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'删除失败');
                        }
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'有分类正在使用该类型，删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'类型信息错误');
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return json($value);
    }
    
    public function search(){
        if(input('post.keyword') != ''){
            cookie('type_keyword',input('post.keyword'),7200);
        }else{
            cookie('type_keyword',null);
        }
    
        $where = array();
        
        if(cookie('type_keyword')){
            $where['type_name'] = array('like','%'.cookie('type_keyword').'%');
        }
    
       $list = Db::name('type')->where($where)->order('sort asc')->paginate(50);
    
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $search = 1;
        
        if(cookie('type_keyword')){
            $this->assign('keyword',cookie('type_keyword'));
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
    
    public function paixu(){
        if(request()->isAjax()){
            if(input('post.ids') && input('post.sort')){
                $ids = input('post.ids');
                $sort = input('post.sort');
                $ids = explode(',', $ids);
                $sort = explode(',', $sort);
                foreach ($ids as $k => $v){
                    Db::name('type')->update(array('id'=>$v,'sort'=>$sort[$k]));
                }
            }
            $value = array('status'=>1,'mess'=>'排序成功');
            return json($value);
        }
    }
    
    
}