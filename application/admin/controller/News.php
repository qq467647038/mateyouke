<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\News as NewsMx;

class News extends Common{
    //文章列表
    public function lst(){
        $list = Db::name('news')->alias('a')->field('a.id,a.ar_title,a.is_rem,a.is_show,a.addtime,a.sort,b.cate_name,c.en_name')->join('sp_cate_new b','a.cate_id = b.id','LEFT')->join('sp_admin c','a.aid = c.id','LEFT')->order('a.sort asc')->paginate(25);
        $page = $list->render();
        $cateres = Db::name('cate_new')->field('id,cate_name,pid')->order('sort asc')->select();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
           'pnum'=>$pnum,
           'cateres'=> recursive($cateres),
           'list'=>$list,
           'page'=>$page
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch();
        }
    }
       
    //根据栏目分类获取文章列表
    public function catelist(){
        $id = input('cate_id');
        $cate_name = Db::name('cate_new')->where('id',$id)->value('cate_name');
        $cateres = Db::name('cate_new')->field('id,cate_name,pid')->order('sort asc')->select();
        $cateId = array();
        $cateId = get_all_child($cateres, $id);
        $cateId[] = $id;
        $cateId = implode(',', $cateId);
        $list = Db::name('news')->alias('a')->field('a.id,a.ar_title,a.is_rem,a.is_show,a.addtime,a.sort,b.cate_name,c.en_name')->join('sp_cate_new b','a.cate_id = b.id','LEFT')->join('sp_admin c','a.aid = c.id','LEFT')->where('a.cate_id','in',$cateId)->order('a.sort asc')->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
            'cate_id'=>$id,
            'cate_name'=>$cate_name,
            'pnum'=>$pnum,
            'cateres'=>recursive($cateres),
            'list'=>$list,
            'page'=>$page
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    //处理上传图片
    public function uploadify(){
        $admin_id = session('admin_id');
        $file = request()->file('filedata');
        if($file){
            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'new_pic');
            if($info){
                $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
                if($zssjpics && $zssjpics['img_url']){
                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                        @unlink('./'.$zssjpics['img_url']);
                    }
                }
                $getSaveName = str_replace("\\","/",$info->getSaveName());
                $original = 'uploads/new_pic/'.$getSaveName;
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
    
    //修改文章推荐
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $news = new NewsMx();
        $count = $news->save($data,array('id'=>$data['id']));
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    //添加文章视图
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $admin_id = session('admin_id');
            $result = $this->validate($data,'News');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(!empty($data['pic_id'])){
                    $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        $data['ar_pic'] = $zssjpics['img_url'];
                    }
                }
                
                if(!empty($data['addtime'])){
                    $data['addtime'] = strtotime($data['addtime']);
                }else{
                    $data['addtime'] = time();
                }
                
                $data['aid'] = session('admin_id');
                $news = new NewsMx();
                $news->data($data);
                $lastId = $news->allowField(true)->save();
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
            $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
            if($zssjpics && $zssjpics['img_url']){
                Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                    @unlink('./'.$zssjpics['img_url']);
                }
            }
            $cateres = Db::name('cate_new')->field('id,pid,cate_name')->order('sort asc')->select();
            $this->assign('cateres',recursive($cateres));
            if(input('cate_id')){
                $this->assign('cate_id',input('cate_id'));
            }
            return $this->fetch();
        }
    }
    
    //编辑文章视图
    public function edit(){
        if(request()->isAjax()){
            $data = input('post.');
            $admin_id = session('admin_id');
            $result = $this->validate($data,'News');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $ars = Db::name('news')->where('id',$data['id'])->find();
                if($ars){
                    if(!empty($data['pic_id'])){
                        $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                        if($zssjpics && $zssjpics['img_url']){
                            $data['ar_pic'] = $zssjpics['img_url'];
                        }else{
                            $data['ar_pic'] = $ars['ar_pic'];
                        }
                    }else{
                        $data['ar_pic'] = $ars['ar_pic'];
                    }
                   
                    if(!empty($data['addtime'])){
                        $data['addtime'] = strtotime($data['addtime']);
                    }else{
                        $data['addtime'] = time();
                    }
                    
                    $news = new NewsMx();
                    $count = $news->allowField(true)->save($data,array('id'=>$data['id']));
                    if($count !== false){
                        if(!empty($zssjpics) && $zssjpics['img_url']){
                            Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                            if($ars['ar_pic'] && file_exists('./'.$ars['ar_pic'])){
                                @unlink('./'.$ars['ar_pic']);
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
            return json($value);
        }else{
            $id = input('id');
            $admin_id = session('admin_id');
            $cateres = Db::name('cate_new')->field('id,pid,cate_name')->order('sort asc')->select();
            $ars = Db::name('news')->find($id);
            $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
            if($zssjpics && $zssjpics['img_url']){
                Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                    @unlink('./'.$zssjpics['img_url']);
                }
            }
            $this->assign('pnum', input('page'));
            if(input('s')){
                $this->assign('search', input('s'));
            }
            if(input('cate_id')){
                $this->assign('cate_id', input('cate_id'));
            }
            $this->assign('cateres',recursive($cateres));
            $this->assign('ars',$ars);
            return $this->fetch();
        }
    }

    public function delete(){
        if(input('post.id')){
            $id = array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        if(!empty($id)){
            if(is_array($id)){
                $delId = implode(',', $id);
                $pic = Db::name('news')->where('id','in',$delId)->field('ar_pic')->order('addtime desc')->select();
            }else{
                $pic =  Db::name('news')->where('id',$id)->value('ar_pic');
            }
            $count = Db::name('news')->delete($id);
            if($count > 0){
                if(is_array($id)){
                    if(!empty($pic)){
                        foreach ($pic as $v){
                            if(!empty($v['ar_pic']) && file_exists('./'.$v['ar_pic'])){
                                @unlink('./'.$v['ar_pic']);
                            }
                        }
                    }
                }else{
                    if(!empty($pic) && file_exists('./'.$pic)){
                        @unlink('./'.$pic);
                    }
                }
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'删除失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }
    
    //搜索
    public function search(){
        if(input('post.keyword') != ''){
            cookie('ar_title',input('post.keyword'),3600);
        }else{
            cookie('ar_title',null);
        }
        
        if(input('post.cate_id') != ''){
            cookie('ar_cate_id',input('post.cate_id'),3600);
        }
        
        $cateres = Db::name('cate_new')->field('id,cate_name,pid')->order('sort asc')->select();
        $where = array();
        
        if(cookie('ar_title')){
            $where['a.ar_title'] = array('like','%'.cookie('ar_title').'%');
        }

        if(cookie('ar_cate_id') != ''){
            $cate_id = (int)cookie('ar_cate_id');
            if($cate_id != 0){
                $cateId = array();
                $cateId = get_all_child($cateres, $cate_id);
                $cateId[] = $cate_id;
                $cateId = implode(',', $cateId);
                $where['a.cate_id'] = array('in',$cateId);
            }
        }
        $list = Db::name('news')->alias('a')->field('a.id,a.ar_title,a.is_rem,a.is_show,a.addtime,a.sort,b.cate_name,c.en_name')->join('sp_cate_new b','a.cate_id = b.id','LEFT')->join('sp_admin c','a.aid = c.id','LEFT')->where($where)->order('a.sort asc')->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('ar_title')){
            $this->assign('ar_title',cookie('ar_title'));
        }
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('cate_id', $cate_id);
        $this->assign('cateres',recursive($cateres));
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function paixu(){
       if(request()->isAjax()){
            if(input('post.ids') && input('post.sort')){
                $ids = input('post.ids');
                $sort = input('post.sort');
                $ids = explode(',', $ids);
                $sort = explode(',', $sort);
                foreach ($ids as $k => $v){
                    Db::name('news')->update(array('id'=>$v,'sort'=>$sort[$k]));
                }
            }
            $value = array('status'=>1,'mess'=>'排序成功');
            return json($value);
        }
    }
 
}