<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Follow as FollowMx;

class Follow extends Common{
    public function index(){
        if(request()->isAjax()){
            $admin_id = session('admin_id');
            $data = input('post.');
            if($data['fl_type'] == 2){
                $repic = Db::name('follow')->where('id',$data['id'])->value('picurl');
                if(!empty($data['pic_id'])){
                    $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        $data['picurl'] = $zssjpics['img_url'];
                    }else{
                        if(!empty($repic)){
                            $data['picurl'] = $repic;
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传缩略图');
                            return json($value);
                        }
                    }
                }else{
                    if(!empty($repic)){
                        $data['picurl'] = $repic;
                    }else{
                        $value = array('status'=>0,'mess'=>'请上传缩略图');
                        return json($value);
                    }
                }
            }            
            $follow = new FollowMx();
            $count = $follow->allowField(true)->save($data,array('id'=>$data['id']));
            if($count !== false){
                if($data['fl_type'] == 2 && !empty($data['pic_id'])){
                    if(!empty($zssjpics) && $zssjpics['img_url']){
                        Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        if(!empty($repic) && file_exists('./'.$repic)){
                            @unlink('./'.$repic);
                        }
                    }
                }
                ys_admin_logs('保存关注回复信息','follow',$data['id']);
                $value = array('status'=>1,'mess'=>'设置成功');
            }else{
                $value = array('status'=>0,'mess'=>'设置失败');
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
            $follows = Db::name('follow')->find(1);
            $this->assign('follows',$follows);
            return $this->fetch();
        }
    }
    
    //处理上传图片
    public function uploadify(){
        $admin_id = session('admin_id');
        $file = request()->file('filedata');
        if($file){
            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'follow');
            if($info){
                $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
                if($zssjpics && $zssjpics['img_url']){
                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                        @unlink('./'.$zssjpics['img_url']);
                    }
                }
                $date = date('Ymd',time());
                $original = 'public/uploads/follow/'.$info->getSaveName();
                $image = \think\Image::open('./'.$original);
                $image->thumb(640, 400)->save('./'.$original);
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
}