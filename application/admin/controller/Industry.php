<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Industry as IndustryMx;

class Industry extends Common{
    
    public function lst(){
        $list = Db::name('industry')->order('sort asc')->select();
        $this->assign('list',$list);// 赋值数据集
        return $this->fetch();
    }
    
    public function checkIndustryname(){
        if(request()->isAjax()){
            $arr = Db::name('industry')->where('industry_name',input('post.industry_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $industry = new IndustryMx();
    
        $count = $industry->save($data,array('id'=>$data['id']));
        if($count > 0){
            if($value == 1){
                ys_admin_logs('显示行业','industry',$id);
            }elseif($value == 0){
                ys_admin_logs('隐藏行业','industry',$id);
            }
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'Industry');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(!empty($data['goods_id'])){
                    $goods_id = implode(',', $data['goods_id']);
                    $goodids = explode(',', $goods_id);
                    $goodids = array_unique($goodids);
                    $info_id = implode(',', $goodids);
                    
                    foreach ($goodids as $v){
                        $cates = Db::name('category')->where('id',$v)->where('pid',0)->where('is_show',1)->field('id')->find();
                        if(!$cates){
                            $value = array('status'=>0,'mess'=>'关联分类信息有误，增加失败');
                            return json($value);
                        }
                    }

                    $lastId = Db::name('industry')->insertGetId(array(
                        'industry_name'=>$data['industry_name'],
                        'ser_price'=>$data['ser_price'],
                        'remind'=>$data['remind'],
                        'is_show'=>$data['is_show'],
                        'cate_id_list'=>$info_id,
                        'sort'=>$data['sort']
                    ));

                    if($lastId){
                        ys_admin_logs('新增行业','industry',$lastId);
                        $value = array('status'=>1,'mess'=>'增加成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'增加失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'请选择关联分类信息');
                }
            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }
    
    public function getindusinfo(){
        if(request()->isPost()){
            if(input('post.id')){
                $id = input('post.id');
                $industrys = Db::name('industry')->where('id',$id)->find();
                if($industrys){
                    $cominfo = Db::name('category')->where('id','in',$industrys['cate_id_list'])->where('pid',0)->where('is_show',1)->field('id,cate_name')->order('sort asc')->select();
                    $value = array('status'=>1,'info'=>$cominfo);
                }else{
                    $value = array('status'=>0,'mess'=>'参数错误');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数');
            }
            return json($value);
        }
    }
    
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'Industry');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    if(!empty($data['goods_id'])){
                        $goods_id = implode(',', $data['goods_id']);
                        $goodids = explode(',', $goods_id);
                        $goodids = array_unique($goodids);
                        $info_id = implode(',', $goodids);
                        
                        $industry_infos = Db::name('industry')->where('id',$data['id'])->find();
                        if($industry_infos){
                            foreach ($goodids as $v){
                                $cates = Db::name('category')->where('id',$v)->where('pid',0)->where('is_show',1)->field('id')->find();
                                if(!$cates){
                                    $value = array('status'=>0,'mess'=>'关联分类信息有误，编辑失败');
                                    return json($value);
                                }
                            }
                            
                            $industry = new IndustryMx();
                            $count = Db::name('industry')->update(array(
                                'industry_name'=>$data['industry_name'],
                                'ser_price'=>$data['ser_price'],
                                'remind'=>$data['remind'],
                                'is_show'=>$data['is_show'],
                                'cate_id_list'=>$info_id,
                                'sort'=>$data['sort'],
                                'id'=>$data['id']
                            ));
                            if($count !== false){
                                ys_admin_logs('编辑行业','position',$data['id']);
                                $value = array('status'=>1,'mess'=>'编辑成功');
                            }else{
                                $value = array('status'=>0,'mess'=>'编辑失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'请选择推荐信息');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $industrys = Db::name('industry')->find(input('id'));
                if($industrys){
                    $cominfo = Db::name('category')->where('id','in',$industrys['cate_id_list'])->where('pid',0)->where('is_show',1)->field('id,cate_name')->order('sort asc')->select();
                    $this->assign('industrys', $industrys);
                    $this->assign('cominfo',$cominfo);
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
            $industrys = Db::name('industry')->find($id);
            if($industrys){
                $applys = Db::name('apply_info')->where('indus_id',$id)->field('id')->find();
                if($applys){
                    $value = array('status'=>0,'mess'=>'已有商家申请选择了该行业，删除失败');
                }else{
                    $count = Db::name('industry')->delete($id);
                    if($count > 0){
                        ys_admin_logs('删除职位','industry',$id);
                        $value = array('status'=>1,'mess'=>'删除成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'删除失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'找不到相关信息');
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
                    Db::name('industry')->where('id',$v)->update(array('sort'=>$sort[$k]));
                }
            }
            $value = array('status'=>1,'mess'=>'排序成功');
            return json($value);
        }
    }  
    
}
