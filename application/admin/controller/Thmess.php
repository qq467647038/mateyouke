<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Thmess extends Common{
 
    public function lst(){
        if(input('cate_id')){
            $cate_id = input('cate_id');
            $thcates = Db::name('thcate')->where('id',$cate_id)->field('id,cate_name')->find();
            if($thcates){
                $list = Db::name('thmess')->alias('a')->field('a.id,a.leixing,a.mess,a.sort,b.cate_name')->join('sp_thcate b','a.cate_id = b.id','LEFT')->where('a.cate_id',$cate_id)->order('a.sort asc')->select();
                $this->assign(array(
                    'list'=>$list,
                    'cate_name'=>$thcates['cate_name'],
                    'cate_id'=>$cate_id
                ));
                return $this->fetch();
            }else{
                $this->error('退换种类不存在');
            }
        }else{
            $this->error('缺少退换种类参数');
        }
    }
    
    //添加
    public function add(){
        if(request()->isPost()){
            if(input('post.cate_id')){
                $cate_id = input('post.cate_id');
                $thcates = Db::name('thcate')->where('id',$cate_id)->field('id,cate_name')->find();
                if($thcates){
                    $data = input('post.');
                    $result = $this->validate($data,'Thmess');
                    if(true !== $result){
                        $value = array('status'=>0,'mess'=>$result);
                    }else{
                        $lastId = Db::name('thmess')->insertGetId(array('cate_id'=>$data['cate_id'],'leixing'=>$data['leixing'],'mess'=>$data['mess'],'sort'=>$data['sort']));
                        if($lastId){
                            ys_admin_logs('新增退换种类信息','thmess',$lastId);
                            $value = array('status'=>1,'mess'=>'添加成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'添加失败');
                        }
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'退换种类不存在');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少退换种类参数');
            }
            return json($value);
        }else{
            if(input('cate_id')){
                $thcates = Db::name('thcate')->where('id',input('cate_id'))->field('id,cate_name')->find();
                if($thcates){
                    $thcateres = Db::name('thcate')->field('id,cate_name')->order('sort asc')->select();
                    $this->assign('thcates',$thcates);
                    $this->assign('thcateres',$thcateres);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关退换种类');
                }
            }else{
                $this->error('缺少退换种类参数');
            }
        }
    }
    
    //编辑
    public function edit(){
        if(request()->isPost()){
            if(input('post.id')){
                $id = input('post.id');
                $thmessinfos = Db::name('thmess')->where('id',$id)->find();
                if($thmessinfos){
                    $data = input('post.');
                    $result = $this->validate($data,'Thmess');
                    if(true !== $result){
                        $value = array('status'=>0,'mess'=>$result);
                    }else{
                        $thcates = Db::name('thcate')->where('id',$data['cate_id'])->field('id,cate_name')->find();
                        if($thcates){
                            $count = Db::name('thmess')->update(array('cate_id'=>$data['cate_id'],'leixing'=>$data['leixing'],'mess'=>$data['mess'],'sort'=>$data['sort'],'id'=>$data['id']));
                            if($count !== false){
                                ys_admin_logs('编辑退换种类信息','thmess',$data['id']);
                                $value = array('status'=>1,'mess'=>'编辑成功');
                            }else{
                                $value = array('status'=>0,'mess'=>'编辑失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'退换种类不存在');
                        }
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'找不到相关信息');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少信息参数');
            }
            return json($value);
        }else{
            if(input('id')){
                $id = input('id');
                $thmess = Db::name('thmess')->where('id',$id)->find();
                if($thmess){
                    $thcateres = Db::name('thcate')->field('id,cate_name')->order('sort asc')->select();
                    $this->assign('thmess',$thmess);
                    $this->assign('thcateres',$thcateres);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数信息');
            }
        }
    }

    //删除
    public function delete(){
        if(input('id') && !is_array(input('id'))){
            $id = input('id');
            $thmess = Db::name('thmess')->where('id',$id)->find();
            if($thmess){
                $count = Db::name('thmess')->delete($id);
                if($count > 0){
                    $value = array('status'=>1,'mess'=>'删除成功');
                }else{
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'找不到相关信息，删除失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }
    
    
    //排序
    public function order(){
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                Db::name('thmess')->update(array('id'=>$data2['id'],'sort'=>$data2['sort']));
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
            ys_admin_logs('更新种类原因排序','thmess',1);
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }
}