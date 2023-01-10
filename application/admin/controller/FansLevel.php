<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\FansLevel as FansLevelMx;

class FansLevel extends Common{
    
    public function lst(){
        $list = Db::name('fans_level')->order('sort asc')->select();
        $this->assign('list',$list);// 赋值数据集
        return $this->fetch();
    }
    
    public function checkLevelname(){
        if(request()->isAjax()){
            $arr = Db::name('fans_level')->where('level_name',input('post.level_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
    
    public function checkSort(){
        if(request()->isAjax()){
            $arr = Db::name('fans_level')->where('sort',input('post.sort'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'FansLevel');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $level = new FansLevelMx();
                $level->data($data);
                $lastId = $level->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增粉丝等级','fans_level',$level->id);
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
    
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'FansLevel');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $levels = Db::name('fans_level')->where('id',$data['id'])->find();
                    if($levels){
                        $level = new FansLevelMx();
                        $count = $level->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            ys_admin_logs('编辑粉丝等级','fans_level',$data['id']);
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
                $levels = Db::name('fans_level')->find(input('id'));
                if($levels){
                    $this->assign('levels', $levels);
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
            $count = FansLevelMx::destroy($id);
            if($count > 0){
                ys_admin_logs('删除粉丝等级','fans_level',$id);
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'编辑失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }
    
    public function paixu(){
        if(request()->isAjax()){
            if(input('post.ids') && input('post.sort')){
                $ids = input('post.ids');
                $sort = input('post.sort');
                $ids = explode(',', $ids);
                $sort = explode(',', $sort);
                foreach ($ids as $k => $v){
                    Db::name('fans_level')->where('id',$v)->update(array('sort'=>$sort[$k]));
                }
            }
            $value = array('status'=>1,'mess'=>'排序成功');
            return json($value);
        }
    }  
    
}
