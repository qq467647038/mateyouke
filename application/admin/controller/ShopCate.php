<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\ShopCate as ShopCateMx;

class ShopCate extends Common{
    //栏目列表
    public function lst(){
        $shop_id = session('shop_id');
        $list = Db::name('shop_cate')->where('shop_id',$shop_id)->field('id,cate_name,pid,sort,is_show')->order('sort asc')->select();
        $this->assign('list', recursive($list));
        return $this->fetch();     
    }
    
    //修改状态
    public function gaibian(){
        $shop_id = session('shop_id');
        $id = input('post.id');
        $cates = Db::name('shop_cate')->where('id',$id)->where('shop_id',$shop_id)->field('id')->find();
        if($cates){
            $name = input('post.name');
            $value = input('post.value');
            $data[$name] = $value;
            $data['id'] = $id;
            $count = Db::name('shop_cate')->where('id',$data['id'])->where('shop_id',$shop_id)->update($data);
            if($count > 0){
                $result = 1;
            }else{
                $result = 0;
            }
        }else{
            $result = 0;
        }
        return $result;
    }
    
    //处理上传图片
    public function uploadify(){
//        $admin_id = session('admin_id');
        $file = request()->file('filedata');
        if($file){
            $info = aliyunOSS($_FILES);
//            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'shop_cate');
            if($info){
//                $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
//                if($zssjpics && $zssjpics['img_url']){
//                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
//                    if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
//                        @unlink('./'.$zssjpics['img_url']);
//                    }
//                }
//                $getSaveName = str_replace("\\","/",$info->getSaveName());
//                $original = 'uploads/shop_cate/'.$getSaveName;
                $original = $info['name'];
//                $image = \think\Image::open('./'.$original);
//                $image->thumb(350, 350)->save('./'.$original,null,90);
//                if($zssjpics){
//                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>$original));
//                    $zspic_id = $zssjpics['id'];
//                }else{
//                    $zspic_id = Db::name('huamu_zspic')->insertGetId(array('admin_id'=>$admin_id,'img_url'=>$original));
//                }
//                $picarr = array('img_url'=>$original,'pic_id'=>$zspic_id);
                $picarr = array('img_url'=>$original);
                $value = array('status'=>1,'path'=>$picarr);
            }else{
                $value = array('status'=>0,'msg'=>$file->getError());
            }
        }else{
            $value = array('status'=>0,'msg'=>'文件不存在');
        }
        return json($value);
    }
    
    //手动删除未保存的上传图片手机
    public function delfile(){
        if(input('post.zspic_id')){
            $admin_id = session('admin_id');
            $zspic_id = input('post.zspic_id');
            $pics = Db::name('huamu_zspic')->where('id',$zspic_id)->where('admin_id',$admin_id)->find();
            if($pics && $pics['img_url']){
                $count = Db::name('huamu_zspic')->where('id',$pics['id'])->update(array('img_url'=>''));
                if($count > 0){
                    if($pics['img_url'] && file_exists('./'.$pics['img_url'])){
                        @unlink('./'.$pics['img_url']);
                    }
                    $value = 1;
                }else{
                    $value = 0;
                }
            }else{
                $value = 0;
            }
        }else{
            $value = 0;
        }
        return json($value);
    }
    
    //添加分类
    public function add(){
        if(request()->isAjax()){
            $admin_id = session('admin_id');
            $shop_id = session('shop_id');
            $data = input('post.');
            $data['shop_id'] = $shop_id;
            $result = $this->validate($data,'ShopCate');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(!empty($data['pic_id'])){
//                    $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
//                    if($zssjpics && $zssjpics['img_url']){
//                        $data['cate_pic'] = $zssjpics['img_url'];
//                    }
                    $data['cate_pic'] = $data['pic_id'];
                }
                
                $cate = new ShopCateMx();
                $cate->data($data);
                $lastId = $cate->allowField(true)->save();
                if($lastId){
                    if(isset($zssjpics) && $zssjpics['img_url']){
                        Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    }
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            $admin_id = session('admin_id');
            $shop_id = session('shop_id');
//            $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
//            if($zssjpics && $zssjpics['img_url']){
//                Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
//                if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
//                    @unlink('./'.$zssjpics['img_url']);
//                }
//            }
            $cateres = Db::name('shop_cate')->where('shop_id',$shop_id)->field('id,cate_name,pid')->order('sort asc')->select();
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
                $admin_id = session('admin_id');
                $shop_id = session('shop_id');
                $data = input('post.');
                $data['shop_id'] = $shop_id;
                
                $result = $this->validate($data,'ShopCate');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $cateinfos = Db::name('shop_cate')->where('id',$data['id'])->where('shop_id',$shop_id)->find();
                    if($cateinfos){
                        if(!empty($data['pic_id'])){
//                            $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
//                            if($zssjpics && $zssjpics['img_url']){
//                                $data['cate_pic'] = $zssjpics['img_url'];
//                            }else{
//                                if(!empty($cateinfos['cate_pic'])){
//                                    $data['cate_pic'] = $cateinfos['cate_pic'];
//                                }
//                            }
                            $data['cate_pic'] = $data['pic_id'];
                        }else{
//                            if(!empty($cateinfos['cate_pic'])){
//                                $data['cate_pic'] = $cateinfos['cate_pic'];
//                            }
                        }
                        
                        $cate = new ShopCateMx();
                        $count = $cate->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            if(!empty($zssjpics) && $zssjpics['img_url']){
                                Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                                if($cateinfos['cate_pic'] && file_exists('./'.$cateinfos['cate_pic'])){
                                    @unlink('./'.$cateinfos['cate_pic']);
                                }
                            }
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
                $admin_id = session('admin_id');
                $shop_id = session('shop_id');
                $id = input('id');
                $cates = Db::name('shop_cate')->where('id',$id)->where('shop_id',$shop_id)->find();
                if($cates){
                    $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                            @unlink('./'.$zssjpics['img_url']);
                        }
                    }
                
                    $cateres = Db::name('shop_cate')->where('id','neq',$id)->where('shop_id',$shop_id)->field('id,cate_name,pid')->order('sort asc')->select();
                    $this->assign('cateres', recursive($cateres));
                    $this->assign('cates', $cates);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }    
    
    //处理删除分类
    public function delete(){
        $shop_id = session('shop_id');
        $id = input('id');
        if(!empty($id)){
            $cates = Db::name('shop_cate')->where('id',$id)->where('shop_id',$shop_id)->field('id')->find();
            if($cates){
                $child = Db::name('shop_cate')->where('pid',$id)->where('shop_id',$shop_id)->field('id')->find();
                if(!empty($child)){
                    $value = array('status'=>0,'mess'=>'该分类下存在子分类，删除失败');
                }else{
                    $goods = Db::name('goods')->where('shcate_id',$id)->where('shop_id',$shop_id)->where('is_recycle',0)->field('id')->find();
                    if(!empty($goods)){
                        $value = array('status'=>0,'mess'=>'该分类存在商品，删除失败');
                    }else{
                        $cate_pic = Db::name('shop_cate')->where('id',$id)->value('cate_pic');
                        $count = ShopCateMX::destroy($id);
                        if($count > 0){
                            if(!empty($cate_pic) && file_exists('./'.$cate_pic)){
                                @unlink('./'.$cate_pic);
                            }
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
        $shop_id = session('shop_id');
        $cate = new ShopCateMx();
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                $cateinfos = Db::name('shop_cate')->where('id',$data2['id'])->where('shop_id',$shop_id)->find();
                if($cateinfos){
                    $cate->save($data2,array('id'=>$data2['id']));
                }
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }
}