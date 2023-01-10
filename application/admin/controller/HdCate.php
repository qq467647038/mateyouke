<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\HdCate as HdCateMx;

class HdCate extends Common{
    //活动分类列表
    public function lst(){
        $list = Db::name('hd_cate')->field('id,cate_name,pid,sort,is_show')->order('sort asc')->select();
        $this->assign('list', recursive($list));
        return $this->fetch();     
    }
    
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $hdcate = new HdCateMx();
        
        $count = $hdcate->save($data,array('id'=>$data['id']));
        if($count > 0){
            if($value == 1){
                ys_admin_logs('显示活动分类','hd_cate',$id);
            }elseif($value == 0){
                ys_admin_logs('隐藏活动分类','hd_cate',$id);
            }
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    public function checkCatename(){
        if(request()->isPost()){
            $arr = Db::name('hd_cate')->where('cate_name',input('post.cate_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
    
    //添加分类
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'HdCate');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $hdcate = new HdCateMx();
                $hdcate->data($data);
                $lastId = $hdcate->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增活动分类','hd_cate',$hdcate->id);
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            $cateres = Db::name('hd_cate')->field('id,cate_name,pid')->order('sort asc')->select();
            $this->assign('cateres', recursive($cateres));
            return $this->fetch();
        }
    }       
    
    /*
     * 编辑分类
     */
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'HdCate');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $cateinfos = Db::name('hd_cate')->where('id',$data['id'])->find();
                    if($cateinfos){
                        $hdcate = new HdCateMx();
                        $count = $hdcate->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            ys_admin_logs('编辑活动分类','hd_cate',$data['id']);
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
                $hdcates = Db::name('hd_cate')->where('id',$id)->find();
                if($hdcates){
                    $cateres = Db::name('hd_cate')->where('id','neq',$id)->field('id,cate_name,pid')->order('sort asc')->select();
                    $this->assign('cateres', recursive($cateres));
                    $this->assign('hdcates', $hdcates);
                    return $this->fetch();
                }else{
                    $this->error('找不到先关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }    
    
    //处理删除分类
    public function delete(){
        $id = input('id');
        if(!empty($id)){
            $hdcates = Db::name('hd_cate')->where('id',$id)->field('id')->find();
            if($hdcates){
                $child = Db::name('hd_cate')->where('pid',$id)->field('id')->limit(1)->find();
                if(!empty($child)){
                    $value = array('status'=>0,'mess'=>'该分类下存在子分类，删除失败');
                }else{
                    $activitys = Db::name('activity')->where('cate_id',$id)->limit(1)->field('id')->find();
                    if($activitys){
                        $value = array('status'=>0,'mess'=>'该分类存在活动信息，删除失败');
                    }else{
                        $count = HdCateMX::destroy($id);
                        if($count > 0){
                            ys_admin_logs('删除活动分类','hd_cate',$id);
                            $value = array('status'=>1,'mess'=>'删除成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'删除失败');
                        }
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'找不到相关信息');
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return json($value);
    }
    
    //处理排序
    public function order(){
        $hdcate = new HdCateMx();
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                $hdcate->save($data2,array('id'=>$data2['id']));
            }
            ys_admin_logs('更新活动分类排序','hd_cate',1);
            $value = array('status'=>1,'mess'=>'更新排序成功');
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }
}
