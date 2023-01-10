<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Attr as AttrMx;

class Attr extends Common{
    //属性列表
    public function lst(){
        if(input('typeid')){
            $typeId = input('typeid');
            $good_types = Db::name('type')->where('id',$typeId)->field('id,type_name')->find();
            if($good_types){
                $list = Db::name('attr')->alias('a')->field('a.*,b.type_name')->join('sp_type b','a.type_id = b.id','LEFT')->where('a.type_id',$typeId)->order('a.sort asc')->select();
                $typeres = Db::name('type')->order('id asc')->select();

                $this->assign('type_name',$good_types['type_name']);
                $this->assign('typeres',$typeres);
                $this->assign('list',$list);
                $this->assign('typeId',$typeId);
                return $this->fetch();
            }else{
                $this->error('参数错误');
            }
        }else{
            $this->error('缺少参数');
        }
    }
    
    public function getAttrLst(){
        if(request()->isPost()){
            if(input('post.typeid') && input('post.cate_id')){
                $typeId = input('post.typeid');
                $cate_id = input('post.cate_id');
                
                $cates = Db::name('category')->where('id',$cate_id)->find();
                if($cates){
                    if($cates['pid'] == 0){
                        $gdtypes = Db::name('type')->where('id',$typeId)->where('cate_id',$cate_id)->find();
                    }else{
                        $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
                        $cateIds = array();
                        $cateIds = get_all_parent($cateres, $cate_id);
                        $cid = end($cateIds);
                        $gdtypes = Db::name('type')->where('id',$typeId)->where('cate_id',$cid)->find();
                    }
                    
                    if($gdtypes){
                        $attrres = Db::name('attr')->where('type_id',$typeId)->order('sort asc')->select();
                    }else{
                        $attrres = '';
                    }
                }else{
                    $attrres = '';
                }
            }else{
                $attrres = '';
            }
            return json($attrres);
        }
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $attrs = Db::name('attr')->where('id',$data['id'])->find();
        if($attrs){
            if($value == 1 && !$attrs['attr_values']){
                $result = 0;
                return $result;
            }
            
            $attr = new AttrMx();
            $count = $attr->save($data,array('id'=>$data['id']));
            if($count > 0){
                if($value == 1){
                    ys_admin_logs('属性设为筛选条件','attr',$id);
                }elseif($value == 0){
                    ys_admin_logs('属性不设为筛选条件','attr',$id);
                }
                $result = 1;
            }else{
                $result = 0;
            }
        }else{
            $result = 0;
        }
        return $result;
    }
        
    public function add(){
        if(request()->isPost()){
            $data = input('post.');
            $result = $this->validate($data,'Attr');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $good_types = Db::name('type')->where('id',$data['type_id'])->field('id')->find();
                if($good_types){
                    $shuxings = Db::name('attr')->where('attr_name',$data['attr_name'])->where('type_id',$data['type_id'])->find();
                    if(!$shuxings){
                        if($data['attr_type'] == 1){
                            if(input('post.attr_values')){
                                $data['attr_values'] = str_replace('，', ',', input('post.attr_values'));
                            }else{
                                $value = array('status'=>0,'mess'=>'单选属性属性可选值不能为空');
                                return json($value);
                            }
                        }elseif($data['attr_type'] == 0){
                            if(input('post.attr_values')){
                                $data['attr_values'] = str_replace('，', ',', input('post.attr_values'));
                            }
                        }
                        
                        if($data['is_sear'] == 1){
                            if(!input('post.attr_values')){
                                $value = array('status'=>0,'mess'=>'拥有属性可选值的属性方可设为筛选条件');
                            }
                        }
                        
                        if($data['attr_name'] == '颜色分类'){
                            $data['is_upload'] = 1;
                        }else{
                            $data['is_upload'] = 0;
                        }
                        
                        $attr = new AttrMx();
                        $attr->data($data);
                        $lastId = $attr->allowField(true)->save();
                        if($lastId){
                            ys_admin_logs('新增属性','attr',$attr->id);
                            $value = array('status'=>1,'mess'=>'增加成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'增加失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'该类型下已存在该属性，增加失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'类型错误，增加失败');
                }
            }
            return json($value);
        }else{
            if(input('typeid')){
                $good_types = Db::name('type')->where('id',input('typeid'))->field('id')->find();
                if($good_types){
                    $typeres = Db::name('type')->order('id asc')->select();
                    $this->assign('typeres',$typeres);
                    $this->assign('typeId',input('typeid'));
                    return $this->fetch();
                }else{
                    $this->error('参数错误');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    public function edit(){
        if(request()->isPost()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'Attr');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $good_types = Db::name('type')->where('id',$data['type_id'])->field('id')->find();
                    if($good_types){
                        $attrinfos = Db::name('attr')->where('id',$data['id'])->where('type_id',$data['type_id'])->find();
                        if($attrinfos){
                            $shuxings = Db::name('attr')->where('id','neq',$data['id'])->where('attr_name',$data['attr_name'])->where('type_id',$data['type_id'])->find();
                            if(!$shuxings){
                                if($data['attr_type'] == 1){
                                    if(input('post.attr_values')){
                                        $data['attr_values'] = str_replace('，', ',', input('post.attr_values'));
                                    }else{
                                        $value = array('status'=>0,'mess'=>'单选属性属性可选值不能为空');
                                        return json($value);
                                    }
                                }elseif($data['attr_type'] == 0){
                                    if(input('post.attr_values')){
                                        $data['attr_values'] = str_replace('，', ',', input('post.attr_values'));
                                    }
                                }
                                
                                if($data['is_sear'] == 1){
                                    if(!input('post.attr_values')){
                                        $value = array('status'=>0,'mess'=>'拥有属性可选值的属性方可设为筛选条件');
                                        return json($value);
                                    }
                                }
                                
                                if($data['attr_name'] == '颜色分类'){
                                    $data['is_upload'] = 1;
                                }else{
                                    $data['is_upload'] = 0;
                                }
                                
                                $attr = new AttrMx();
                                $count = $attr->allowField(true)->save($data,array('id'=>$data['id']));
                                if($count !== false){
                                    ys_admin_logs('编辑属性','attr',$data['id']);
                                    $value = array('status'=>1,'mess'=>'编辑成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'编辑失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'该类型下已存在该属性，编辑失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'类型信息错误，编辑失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id') && input('typeid')){
                $good_types = Db::name('type')->where('id',input('typeid'))->field('id')->find();
                if($good_types){
                    $attrs = Db::name('attr')->where('id',input('id'))->where('type_id',input('typeid'))->find();
                    if($attrs){
                        $typeres = Db::name('type')->order('id asc')->select();
                        $this->assign('typeres',$typeres);
                        $this->assign('attrs',$attrs);
                        $this->assign('typeId',input('typeid'));
                        return $this->fetch();
                    }else{
                        $this->error('参数错误');
                    }
                }else{
                    $this->error('类型参数错误');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    public function delete(){
        if(input('id') && !is_array(input('id'))){
            $id = input('id');
            $type_id = Db::name('attr')->where('id',$id)->value('type_id');
            if($type_id){
                $good_types = Db::name('type')->where('id',$type_id)->field('id')->find();
                if($good_types){
                    $ga = Db::name('goods_attr')->where('attr_id',$id)->field('id')->limit(1)->find();
                    if($ga){
                        $value = array('status'=>0,'mess'=>'有商品正在使用该属性，删除失败');
                    }else{
                        $count = AttrMX::destroy($id);
                        if($count > 0){
                            ys_admin_logs('删除属性','attr',$id);
                            $value = array('status'=>1,'mess'=>'删除成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'删除失败');
                        }
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'类型信息错误，删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'找不到相关信息，删除失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
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
                    $attrs = Db::name('attr')->where('id',$v)->find();
                    if($attrs){
                        Db::name('attr')->where('id',$v)->update(array('sort'=>$sort[$k]));
                    }
                }
            }
            $value = array('status'=>1,'mess'=>'排序成功');
            return json($value);
        }
    }
      
}
?>