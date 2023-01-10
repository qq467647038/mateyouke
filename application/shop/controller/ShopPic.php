<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;

class ShopPic extends Common{
    
    public function lst(){
        $shop_id = session('shopsh_id');
        $list = Db::name('shop_pic')->where('shop_id',$shop_id)->order('sort asc')->select();
        $this->assign('list',$list);// 赋值数据集
        return $this->fetch();
    }
    
    //处理上传图片
    public function uploadify(){
        $admin_id = session('shopadmin_id');
        $shop_id = session('shopsh_id');
        
        $count = Db::name('shop_pic')->where('shop_id',$shop_id)->count();
        if($count < 6){
            $file = request()->file('filedata');
            if($file){
                $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'shop_pic');
                if($info){
                    $zssjpics = Db::name('shopadmin_zspic')->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        Db::name('shopadmin_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                            @unlink('./'.$zssjpics['img_url']);
                        }
                    }
                    $getSaveName = str_replace("\\","/",$info->getSaveName());
                    $original = 'uploads/shop_pic/'.$getSaveName;
                    $image = \think\Image::open('./'.$original);
                    $image->thumb(640, 400)->save('./'.$original,null,90);
                    if($zssjpics){
                        Db::name('shopadmin_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>$original));
                        $zspic_id = $zssjpics['id'];
                    }else{
                        $zspic_id = Db::name('shopadmin_zspic')->insertGetId(array('img_url'=>$original,'admin_id'=>$admin_id));
                    }
                    $picarr = array('img_url'=>$original,'pic_id'=>$zspic_id);
                    $value = array('status'=>1,'path'=>$picarr);
                }else{
                    $value = array('status'=>0,'msg'=>$file->getError());
                }
            }else{
                $value = array('status'=>0,'msg'=>'文件不存在');
            }
        }else{
            $value = array('status'=>0,'msg'=>'商家最多允许上传6张banner图');
        }
        return json($value);
    }
    
    //手动删除未保存的上传图片手机
    public function delfile(){
        if(input('post.zspic_id')){
            $admin_id = session('shopadmin_id');
            $zspic_id = input('post.zspic_id');
            $pics = Db::name('shopadmin_zspic')->where('id',$zspic_id)->where('admin_id',$admin_id)->find();
            if($pics && $pics['img_url']){
                $count = Db::name('shopadmin_zspic')->where('id',$pics['id'])->update(array('img_url'=>''));
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

    public function add(){
        if(request()->isPost()){
            $admin_id = session('shopadmin_id');
            $shop_id = session('shopsh_id');
            $data = input('post.');
            $data['shop_id'] = $shop_id;
            $result = $this->validate($data,'ShopPic');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $count = Db::name('shop_pic')->where('shop_id',$shop_id)->count();
                if($count < 6){
                    if(!empty($data['pic_id'])){
                        $zssjpics = Db::name('shopadmin_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                        if($zssjpics && $zssjpics['img_url']){
                            $data['pic_url'] = $zssjpics['img_url'];
                            
                            $lastId = Db::name('shop_pic')->insert(array(
                                'pic_url'=>$data['pic_url'],
                                'url'=>$data['url'],
                                'sort'=>$data['sort'],
                                'shop_id'=>$data['shop_id'] 
                            ));
                            if($lastId){    
                                if($zssjpics && $zssjpics['img_url']){
                                    Db::name('shopadmin_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                                }
                                $value = array('status'=>1,'mess'=>'增加成功');
                            }else{
                                $value = array('status'=>0,'mess'=>'增加失败');
                            }                     
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传图片');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'请上传图片');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'最多允许上传6张banner图');
                }
            }
            return $value;
        }else{
            $admin_id = session('shopadmin_id');
            $zssjpics = Db::name('shopadmin_zspic')->where('admin_id',$admin_id)->find();
            if($zssjpics && $zssjpics['img_url']){
                Db::name('shopadmin_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                    @unlink('./'.$zssjpics['img_url']);
                }
            }
            return $this->fetch();
        }
    }

    public function edit(){
        if(request()->isPost()){
            if(input('post.id')){
                $admin_id = session('shopadmin_id');
                $shop_id = session('shopsh_id');
                $data = input('post.');
                $data['shop_id'] = $shop_id;
                $result = $this->validate($data,'ShopPic');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $picinfos = Db::name('shop_pic')->where('id',$data['id'])->where('shop_id',$shop_id)->find();
                    if($picinfos){
                        if(!empty($data['pic_id'])){
                            $zssjpics = Db::name('shopadmin_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                            if($zssjpics && $zssjpics['img_url']){
                                $data['pic_url'] = $zssjpics['img_url'];
                            }else{
                                $data['pic_url'] = $picinfos['pic_url'];
                            }
                        }else{
                            $data['pic_url'] = $picinfos['pic_url'];
                        }

                        $count = Db::name('shop_pic')->update(array(
                            'pic_url'=>$data['pic_url'],
                            'url'=>$data['url'],
                            'sort'=>$data['sort'],
                            'id'=>$data['id']
                        ));

                        if($count !== false){
                            if(!empty($zssjpics) && $zssjpics['img_url']){
                                Db::name('shopadmin_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                                if($picinfos['pic_url'] && file_exists('./'.$picinfos['pic_url'])){
                                    @unlink('./'.$picinfos['pic_url']);
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
                $admin_id = session('shopadmin_id');
                $shop_id = session('shopsh_id');
                $id = input('id');
                $shop_pics = Db::name('shop_pic')->where('id',$id)->where('shop_id',$shop_id)->find();
                if($shop_pics){
                    $zssjpics = Db::name('shopadmin_zspic')->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        Db::name('shopadmin_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                            @unlink('./'.$zssjpics['img_url']);
                        }
                    }
                    
                    $this->assign('shop_pics', $shop_pics);
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
        $shop_id = session('shopsh_id');
        $id = input('id');
        if(!empty($id) && !is_array($id)){
            $picinfos = Db::name('shop_pic')->where('id',$id)->where('shop_id',$shop_id)->find();
            if($picinfos){
                $count = Db::name('shop_pic')->delete($id);
                if($count > 0){
                    if(!empty($picinfos['pic_url']) && file_exists('./'.$picinfos['pic_url'])){
                        @unlink('./'.$picinfos['pic_url']);
                    }
                    $value = array('status'=>1,'mess'=>'删除成功');
                }else{
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'未选中任何数据');
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return json($value);
    }

    public function paixu(){
        if(request()->isAjax()){
            $shop_id = session('shopsh_id');
            if(input('post.ids') && input('post.sort')){
                $ids = input('post.ids');
                $sort = input('post.sort');
                $ids = explode(',', $ids);
                $sort = explode(',', $sort);
                foreach ($ids as $k => $v){
                    $shop_pics = Db::name('shop_pic')->where('id',$v)->where('shop_id',$shop_id)->find();
                    if($shop_pics){
                        Db::name('shop_pic')->where('id',$v)->update(array('sort'=>$sort[$k]));
                    }
                }
            }
            $value = array('status'=>1,'mess'=>'排序成功');
            return json($value);
        }
    }
}