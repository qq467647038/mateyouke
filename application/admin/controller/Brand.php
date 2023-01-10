<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Brand as BrandMx;

class Brand extends Common{
    //品牌列表
    public function lst(){
        $filter = input('filter');
        if(empty($filter)){
            $filter = 10;
        }
        
        $where = array();
        
        switch($filter){
            //全部
            case 10:
                $where = array();
                break;
                //开启
            case 1:
                $where['is_show'] = 1;
                break;
                //关闭
            case 2:
                $where['is_show'] = 0;
                break;
        }
        
        $list = Db::name('brand')->where($where)->paginate(25);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign('page',$page);
        $this->assign('pnum',$pnum);
        $this->assign('filter',$filter);
        $this->assign('list', $list);
        return $this->fetch();     
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $brand = new BrandMx();
        
        $count = $brand->save($data,array('id'=>$data['id']));
        if($count > 0){
            if($value == 1){
                ys_admin_logs('显示品牌','brand',$id);
            }elseif($value == 0){
                ys_admin_logs('隐藏品牌','brand',$id);
            }
            ys_admin_logs('','withdraw',input('post.id'));
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    public function checkBrandname(){
        if(request()->isPost()){
            $arr = Db::name('brand')->where('brand_name',input('post.brand_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
    
    //处理上传图片
    public function uploadify(){
        $admin_id = session('admin_id');
        $file = request()->file('filedata');
        if($file){
            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'brand');
            if($info){
                $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
                if($zssjpics && $zssjpics['img_url']){
                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                        @unlink('./'.$zssjpics['img_url']);
                    }
                }
                $date = date('Ymd',time());
                $original = 'uploads/brand/'.$info->getSaveName();
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
    
    
    //手动删除未保存的上传图片
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
    
    //添加品牌
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $admin_id = session('admin_id');
            $result = $this->validate($data,'Brand');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(empty($data['cate_id_list'])){
                    $value = array('status'=>0,'mess'=>'请添加分类选项');
                }else{
                    if(!empty($data['pic_id'])){
                        $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                        if($zssjpics && $zssjpics['img_url']){
                            $data['brand_logo'] = $zssjpics['img_url'];
                        }
                    }
                    
                    $data['cate_id_list'] = implode(',',$data['cate_id_list']);
                    $brand = new BrandMx();
                    $brand->data($data);
                    $lastId = $brand->allowField(true)->save();
                    if($lastId){
                        if(isset($zssjpics) && $zssjpics['img_url']){
                            Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        }
                        ys_admin_logs('新增品牌','brand',$brand->id);
                        $value = array('status'=>1,'mess'=>'增加成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'增加失败');
                    }
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
            $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
            $this->assign('cateres',recursive($cateres));
            return $this->fetch();
        }
    }       
    
    /*
     * 编辑品牌
     */
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                $admin_id = session('admin_id');
                $result = $this->validate($data,'Brand');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    if(empty($data['cate_id_list'])){
                        $value = array('status'=>0,'mess'=>'请添加分类选项');
                    }else{
                        $brandinfos = Db::name('brand')->where('id',$data['id'])->find();
                        if($brandinfos){
                            if(!empty($data['pic_id'])){
                                $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                                if($zssjpics && $zssjpics['img_url']){
                                    $data['brand_logo'] = $zssjpics['img_url'];
                                }else{
                                    $data['brand_logo'] = $brandinfos['brand_logo'];
                                }
                            }else{
                                $data['brand_logo'] = $brandinfos['brand_logo'];
                            }
                        
                            $data['cate_id_list'] = implode(',', $data['cate_id_list']);
                            $brand = new BrandMx();
                            $count = $brand->allowField(true)->save($data,array('id'=>$data['id']));
                            if($count !== false){
                                if(!empty($zssjpics) && $zssjpics['img_url']){
                                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                                    if($brandinfos['brand_logo'] && file_exists('./'.$brandinfos['brand_logo'])){
                                        @unlink('./'.$brandinfos['brand_logo']);
                                    }
                                }
                                ys_admin_logs('编辑品牌','brand',$data['id']);
                                $value = array('status'=>1,'mess'=>'编辑成功');
                            }else{
                                $value = array('status'=>0,'mess'=>'编辑失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                        }
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $id = input('id');
                $brands = Db::name('brand')->where('id',$id)->find();
                if($brands){
                    $admin_id = session('admin_id');
                    $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                            @unlink('./'.$zssjpics['img_url']);
                        }
                    }
                    
                    $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    $this->assign('pnum', input('page'));
                    $this->assign('filter',input('filter'));
                    $this->assign('brands', $brands);
                    $this->assign('cateres',recursive($cateres));
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }    
    
    //处理删除品牌
    public function delete(){
        $id = input('id');
        if(!empty($id)){
            $goods = Db::name('goods')->where('brand_id',$id)->limit(1)->field('id')->find();
            if(!empty($goods)){
                $value = array('status'=>0,'mess'=>'该品牌存在商品，删除失败');
            }else{
                $brand_logo = Db::name('brand')->where('id',$id)->value('brand_logo');
                $count = BrandMX::destroy($id);
                if($count > 0){
                    if(!empty($brand_logo) && file_exists('./'.$brand_logo)){
                        @unlink('./'.$brand_logo);
                    }
                    ys_admin_logs('删除品牌','brand',$id);
                    $value = array('status'=>1,'mess'=>'删除成功');
                }else{
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return json($value);
    }
    
    public function search(){
        if(input('post.keyword') != ''){
            cookie('brand_keyword',input('post.keyword'),7200);
        }else{
            cookie('brand_keyword',null);
        }
        
        if(input('post.brand_zt') != ''){
            cookie('brand_zt',input('post.brand_zt'),7200);
        }
    
        $where = array();

        if(cookie('brand_zt') != ''){
            $brand_zt = (int)cookie('brand_zt');
            if(!empty($brand_zt)){
                switch ($brand_zt){
                    //显示
                    case 1:
                        $where['is_show'] = 1;
                        break;
                    //隐藏
                    case 2:
                        $where['is_show'] = 0;
                        break;
                }
            }
        }
        
        if(cookie('brand_keyword')){
            $where['brand_name'] = array('like','%'.cookie('brand_keyword').'%');
        }
    
        $list = Db::name('brand')->where($where)->paginate(25);
    
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $search = 1;
        
        $filter = 10;
        
        if(!empty($brand_zt)){
            $this->assign('brand_zt',$brand_zt);
        }
        
        if(cookie('brand_keyword')){
            $this->assign('keyword',cookie('brand_keyword'));
        }
    
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('filter',$filter);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }

}