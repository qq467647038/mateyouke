<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Link as LinkMx;

class Link extends Common{
    
    public function lst(){
        $list = Db::name('link')->order('sort asc')->select();
        $this->assign('list',$list);// 赋值数据集
        return $this->fetch();
    }

    public function add(){
        if(request()->isPost()){
            $data = input('post.');
            $result = $this->validate($data,'Link');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $link = new LinkMx();
                $link->data($data);
                $lastId = $link->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增友情链接','link',$link->id);
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return $value;
        }else{
           return $this->fetch();
        }
    }

    public function edit(){
        if(request()->isPost()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'Link');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $linkinfos = Db::name('link')->where('id',$data['id'])->field('id')->find();
                    if($linkinfos){
                        $link = new LinkMx();
                        $count = $link->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            ys_admin_logs('编辑友情链接','link',$data['id']);
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
                $links = Db::name('link')->find($id);
                if($links){
                    $this->assign('links', $links);
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
        if(input('post.id')){
            $id= array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        if(!empty($id)){
            $count = LinkMx::destroy($id);
            if($count > 0){
                if(is_array($id)){
                    foreach ($id as $v2){
                        ys_admin_logs('删除友情链接','link',$v2);
                    }
                }else{
                    ys_admin_logs('删除友情链接','link',$id);
                }
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'编辑失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return $value;
    }

    public function paixu(){
        if(request()->isAjax()){
            if(input('post.ids') && input('post.sort')){
                $ids = input('post.ids');
                $sort = input('post.sort');
                $ids = explode(',', $ids);
                $sort = explode(',', $sort);
                foreach ($ids as $k => $v){
                    Db::name('link')->where('id',$v)->update(array('sort'=>$sort[$k]));
                }
            }
            $value = array('status'=>1,'mess'=>'排序成功');
            return json($value);
        }
    }
}