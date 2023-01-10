<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Cation as CationMx;

class Cation extends Common{
    
    public function lst(){
        $list = Db::name('cation')->order('sort asc')->select();
        $this->assign('list',$list);// 赋值数据集
        return $this->fetch();
    }
    
    public function checkCaname(){
        if(request()->isAjax()){
            $arr = Db::name('cation')->where('ca_name',input('post.ca_name'))->find();
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
            $result = $this->validate($data,'Cation');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $cation = new CationMx();
                $cation->data($data);
                $lastId = $cation->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('添加配置分类','cation',$cation->id);
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
                $result = $this->validate($data,'Cation');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $cationinfos = Db::name('cation')->where('id',$data['id'])->find();
                    if($cationinfos){
                        $cation = new CationMx();
                        $count = $cation->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            ys_admin_logs('编辑配置分类','cation',$data['id']);
                            $value = array('status'=>1,'mess'=>'编辑成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'编辑失败');
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
            if(input('id')){
                $cations = Db::name('cation')->find(input('id'));
                if($cations){
                    $this->assign('cations', $cations);
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
            $configs = Db::name('config')->where('ca_id',$id)->find();
            if(!empty($configs)){
                $value = array('status'=>0,'mess'=>'该配置分类下存在系统配置，删除失败');
            }else{
                $count = CationMx::destroy($id);
                if($count > 0){
                    ys_admin_logs('删除配置分类','cation',$id);
                    $value = array('status'=>1,'mess'=>'删除成功');
                }else{
                    $value = array('status'=>0,'mess'=>'编辑失败');
                }
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
                    Db::name('cation')->where('id',$v)->update(array('sort'=>$sort[$k]));
                }
            }
            $value = array('status'=>1,'mess'=>'排序成功');
            return json($value);
        }
    }
    
}