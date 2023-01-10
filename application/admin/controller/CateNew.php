<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\CateNew as CateNewMx;

class CateNew extends Common{
    //栏目列表
    public function lst(){
        $list = Db::name('cate_new')->field('id,cate_name,pid,sort,is_show,show_in_recommend')->order('sort asc')->select();
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
        $cate = new CateNewMx();
        $count = $cate->save($data,array('id'=>$data['id']));
        if($count > 0){
            if($value == 1){
                ys_admin_logs('显示文章分类','cate_new',$id);
            }elseif($value == 0){
                ys_admin_logs('隐藏文章分类','cate_new',$id);
            }
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    //处理上传图片
    public function uploadify(){
        $admin_id = session('admin_id');
        $file = request()->file('filedata');
        if($file){
            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'cate_new');
            if($info){
                $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
                if($zssjpics && $zssjpics['img_url']){
                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                        @unlink('./'.$zssjpics['img_url']);
                    }
                }
                $getSaveName = str_replace("\\","/",$info->getSaveName());
                $original = 'uploads/cate_new/'.$getSaveName;
                $image = \think\Image::open('./'.$original);
                $image->thumb(640, 400)->save('./'.$original,null,90);
                if($zssjpics){
                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>$original));
                    $zspic_id = $zssjpics['id'];
                }else{
                    $zspic_id = Db::name('huamu_zspic')->insertGetId(array('admin_id'=>$admin_id,'img_url'=>$original));
                }
                $picarr = array('img_url'=>$original,'pic_id'=>$zspic_id);
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
            $data = input('post.');
            $admin_id = session('admin_id');
            $result = $this->validate($data,'CateNew');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(!empty($data['pic_id'])){
                    $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        $data['cate_pic'] = $zssjpics['img_url'];
                    }
                }
                $cate = new CateNewMx();
                $cate->data($data);
                $lastId = $cate->allowField(true)->save();
                if($lastId){
                    if(isset($zssjpics) && $zssjpics['img_url']){
                        Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    }
                    ys_admin_logs('新增咨讯分类','cate_new',$cate->id);
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            $admin_id = session('admin_id');
            $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
            if($zssjpics && $zssjpics['img_url']){
                Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                    @unlink('./'.$zssjpics['img_url']);
                }
            }
            $cateres = Db::name('cate_new')->field('id,cate_name,pid')->order('sort asc')->select();
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
                $data = input('post.');
                $result = $this->validate($data,'CateNew');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $cateinfos = Db::name('cate_new')->where('id',$data['id'])->find();
                    if($cateinfos){
                        if(!empty($data['pic_id'])){
                            $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                            if($zssjpics && $zssjpics['img_url']){
                                $data['cate_pic'] = $zssjpics['img_url'];
                            }else{
                                $data['cate_pic'] = $cateinfos['cate_pic'];
                            }
                        }else{
                            $data['cate_pic'] = $cateinfos['cate_pic'];
                        }
                
                        $cate = new CateNewMx();
                        $count = $cate->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            if(!empty($zssjpics) && $zssjpics['img_url']){
                                Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                                if($cateinfos['cate_pic'] && file_exists('./'.$cateinfos['cate_pic'])){
                                    @unlink('./'.$cateinfos['cate_pic']);
                                }
                            }
                            ys_admin_logs('编辑咨讯分类','cate_new',$data['id']);
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
                $cates = Db::name('cate_new')->where('id',$id)->find();
                if($cates){
                    $admin_id = session('admin_id');
                    $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                            @unlink('./'.$zssjpics['img_url']);
                        }
                    }
                    
                    $cateres = Db::name('cate_new')->where('id','neq',$id)->field('id,cate_name,pid')->order('sort asc')->select();
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
        $id = input('id');
        if(!empty($id)){
            $child = Db::name('cate_new')->where('pid',$id)->field('id')->limit(1)->find();
            if(!empty($child)){
                $value = array('status'=>0,'mess'=>'该分类下存在子分类，删除失败');
            }else{
                $ga = Db::name('news')->where('cate_id',$id)->limit(1)->field('id')->find();
                if(!empty($ga)){
                    $value = array('status'=>0,'mess'=>'该分类存在文章，删除失败');
                }else{
                    $cate_pic = Db::name('cate_new')->where('id',$id)->value('cate_pic');
                    $count = CateNewMX::destroy($id);
                    if($count > 0){
                        if(!empty($cate_pic) && file_exists('./'.$cate_pic)){
                            @unlink('./'.$cate_pic);
                        }
                        ys_admin_logs('删除咨讯分类','cate_new',$id);
                        $value = array('status'=>1,'mess'=>'删除成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'删除失败');
                    }
                }
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return json($value);
    }
    
    //处理排序
    public function order(){
        $cate = new CateNewMx();
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                $cate->save($data2,array('id'=>$data2['id']));
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }
}