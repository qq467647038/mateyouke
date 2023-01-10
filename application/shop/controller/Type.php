<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;
use app\shop\model\Type as TypeMx;

class Type extends Common{
    //类型列表
    public function lst(){
        $shop_id = session('shopsh_id');
        $list = Db::name('type')->where('shop_id',$shop_id)->paginate(25);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign('page',$page);
        $this->assign('pnum',$pnum);
        $this->assign('list', $list);
        return $this->fetch();     
    }
    
    public function checkTypename(){
        if(request()->isPost()){
            $shop_id = session('shopsh_id');
            $arr = Db::name('type')->where('shop_id',$shop_id)->where('type_name',input('post.type_name'))->find();
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
            $data['shop_id'] = session('shopsh_id');
            $result = $this->validate($data,'Type');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $leixing_infos = Db::name('type')->where('type_name',$data['type_name'])->where('shop_id',$data['shop_id'])->find();
                if(!$leixing_infos){
                    $type = new TypeMx();
                    $type->data($data);
                    $lastId = $type->allowField(true)->save();
                    if($lastId){
                        $value = array('status'=>1,'mess'=>'增加成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'增加失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'该类型已存在');
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
                $data['shop_id'] = session('shopsh_id');
                $result = $this->validate($data,'Type');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $typeinfos = Db::name('type')->where('id',$data['id'])->where('shop_id',$data['shop_id'])->find();
                    if($typeinfos){
                        $leixing_infos = Db::name('type')->where('id','neq',$data['id'])->where('type_name',$data['type_name'])->where('shop_id',$data['shop_id'])->find();
                        if(!$leixing_infos){
                            $type = new TypeMx();
                            $count = $type->allowField(true)->save($data,array('id'=>$data['id']));
                            if($count !== false){
                                $value = array('status'=>1,'mess'=>'编辑成功');
                            }else{
                                $value = array('status'=>0,'mess'=>'编辑失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'该类型已存在');
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
                $shop_id = session('shopsh_id');
                $id = input('id');
                $types = Db::name('type')->where('id',$id)->where('shop_id',$shop_id)->find();
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
        $shop_id = session('shopsh_id');
        $id = input('id');
        if(!empty($id) && !is_array($id)){
            $types = Db::name('type')->where('id',$id)->where('shop_id',$shop_id)->find();
            if($types){
                $attrs = Db::name('attr')->where('type_id',$id)->field('id')->limit(1)->find();
                if(!empty($attrs)){
                    $value = array('status'=>0,'mess'=>'该类型下存在属性，删除失败');
                }else{
                    $count = TypeMX::destroy($id);
                    if($count > 0){
                        $value = array('status'=>1,'mess'=>'删除成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'删除失败');
                    }
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
        $shop_id = session('shopsh_id');
        
        if(input('post.keyword') != ''){
            cookie('type_keyword',input('post.keyword'),7200);
        }else{
            cookie('type_keyword',null);
        }
    
        $where = array();
        $where['shop_id'] = $shop_id;
        
        if(cookie('type_keyword')){
            $where['type_name'] = array('like','%'.cookie('type_keyword').'%');
        }
    
        $list = Db::name('type')->where($where)->paginate(25);
    
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
    
    
}