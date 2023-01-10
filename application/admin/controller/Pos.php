<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Pos as PosMx;

class Pos extends Common{
    //广告位列表
    public function lst(){
        $list = Db::name('pos')->paginate(25);
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
            return $this->fetch('lst');
        }
    }

    public function checkPosname(){
        if(request()->isAjax()){
            $arr = Db::name('pos')->where('pos_name',input('post.pos_name'))->find();
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
            $result = $this->validate($data,'Pos');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $pos = new PosMx();
                $pos->data($data);
                $lastId = $pos->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增广告位','pos',$pos->id);
                    $value = array('status'=>1, 'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0, 'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }
    
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'Pos');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $posinfos = Db::name('pos')->where('id',$data['id'])->find();
                    if($posinfos){
                        $pos = new PosMx();
                        $count = $pos->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            ys_admin_logs('编辑广告位','pos',$data['id']);
                            $value = array('status'=>1, 'mess'=>'编辑成功');
                        }else{
                            $value = array('status'=>0, 'mess'=>'编辑失败');
                        }
                    }else{
                        $value = array('status'=>0, 'mess'=>'找不到相关信息');
                    }
                }
            }else{
                $value = array('status'=>0, 'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $id = input('id');
                $poss = Db::name('pos')->where('id',$id)->find();
                if($poss){
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    $this->assign('pnum', input('page'));
                    $this->assign('poss',$poss);
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
           $ad = Db::name('ad')->where('pos_id',$id)->field('id')->limit(1)->find();
           if(!empty($ad)){
               $value = array('status'=>0,'mess'=>'该广告位下存在广告，删除失败');
           }else{
               $count = PosMx::destroy($id);
               if($count > 0){
                   ys_admin_logs('删除广告位','pos',$id);
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

    public function search(){       
        if(input('post.keyword')){    
            cookie('pos_name',input('post.keyword'),3600);
        }
        $where = array();
        if(cookie('pos_name') != ''){
            $where['pos_name'] = array('like','%'.cookie('pos_name').'%');
        }
        $list = Db::name('pos')->where($where)->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('pos_name')){
            $this->assign('pos_name',cookie('pos_name'));
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