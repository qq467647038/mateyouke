<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;

class ManageCate extends Common{
    
    public function lst(){
        $shop_id = session('shopsh_id');
        $list = Db::name('manage_cate')->alias('a')->field('a.*,b.cate_name')->join('sp_category b','a.cate_id = b.id','LEFT')->where('a.shop_id',$shop_id)->order('a.apply_time desc')->select();
        $this->assign('list',$list);// 赋值数据集
        return $this->fetch();
    }
    
    public function add(){
        if(request()->isPost()){
            $shop_id = session('shopsh_id');
            $data = input('post.');
            $data['shop_id'] = $shop_id;
            $result = $this->validate($data,'ManageCate');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $categorys = Db::name('category')->where('id',$data['cate_id'])->find();
                if($categorys){
                    $manages = Db::name('manage_cate')->where('cate_id',$data['cate_id'])->where('shop_id',$data['shop_id'])->where('checked',1)->find();
                    if(!$manages){
                        $lastId = Db::name('manage_cate')->insert(array(
                            'shop_id'=>$data['shop_id'],
                            'cate_id'=>$data['cate_id'],
                            'checked'=>0,
                            'apply_time'=>time(),
                        ));
                        if($lastId){
                            $value = array('status'=>1,'mess'=>'新增成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'新增失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'已拥有该经营类目，新增失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'分类参数错误');
                }
            }
            return json($value);
        }else{
            $shop_id = session('shopsh_id');
            $manageres = Db::name('manage_cate')->where('shop_id',$shop_id)->where('checked',1)->field('cate_id')->select();
            $cateidres = array();
            foreach ($manageres as $v){
                $cateidres[] = $v['cate_id'];
            }
            $cateidres = implode(',',$cateidres);
            $cateres = Db::name('category')->where('id','not in',$cateidres)->where('pid',0)->field('id,cate_name')->order('sort asc')->select();
            $this->assign('cateres',$cateres);
            return $this->fetch();
        }
    }


    public function delete(){
        $id = input('id');
        $shop_id = session('shopsh_id');
        if(!empty($id) && !is_array($id)){
            $manages = Db::name('manage_cate')->where('id',$id)->where('shop_id',$shop_id)->find();
            if($manages){
                $goods = Db::name('goods')->where('cate_id',$manages['cate_id'])->where('shop_id',$shop_id)->where('is_recycle',0)->field('id')->find();
                if(!$goods){
                    $count = Db::name('manage_cate')->delete($id);
                    if($count > 0){
                        $value = array('status'=>1,'mess'=>'删除成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'编辑失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'该经营类目下存在商品，删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'找不到相关信息');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }
    
}
