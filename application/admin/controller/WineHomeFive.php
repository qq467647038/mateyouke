<?php
namespace app\admin\controller;

use think\Db;

class WineHomeFive extends Common{
    //广告列表
    public function lst(){
        $list = Db::name('home_five')->order('id desc')->paginate(25);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign('pnum',$pnum);
        $this->assign('list',$list);
        $this->assign('page',$page);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch();
        }
    }
    
    //添加广告
    public function add(){
        if(request()->isAjax()){
            $admin_id = session('admin_id');
            $data = input('post.');
            $result = $this->validate($data,'WineHomeFive');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(!empty($data['pic_id'])){
                    $zssjpics = Db::name('huamu_zsduopic_five')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                    if(!empty($zssjpics) && !empty($zssjpics['img_url'])){
                        $data['ad_pic'] = $zssjpics['img_url'];
                    }else{
                        $value = array('status'=>0,'mess'=>'请上传图片');
                        return json($value);
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'请上传图片');
                    return json($value);
                }
                
                // 启动事务
                Db::startTrans();
                try{
                    // if(!empty($data['picres_id'])){
                    //     $canshu = $data['canshu'];
                    //     $sort2 = $data['sort2'];
                    //     foreach ($data['picres_id'] as $k => $v){
                    //         $img_url = Db::name('huamu_zsduopic')->where('id',$v)->where('admin_id',$admin_id)->value('img_url');
                    //         if($img_url){
                    //             if(empty($sort2[$k])){
                    //                 $sort2[$k] = 0;
                    //             }
                    //             Db::name('ad_pic')->insert(array('pic'=>$img_url,'canshu'=>$canshu[$k],'sort'=>$sort2[$k],'ad_id'=>$ad_id));
                    //         }
                    //     }
                    // }
                    $d = [
                        'name' => $data['name'],
                        'path' => $data['path'],
                        'img' => $data['ad_pic'],
                        'addtime'=>time()
                    ];
                    
                    Db::name('home_five')->insert($d);
                    // 提交事务
                    Db::commit();
                    // if(!empty($zssjpics) && !empty($zssjpics['img_url'])){
                    //     Db::name('huamu_zspic_five')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    // }
                    
                    // if($data['ad_type'] == 2){
                    //     if(!empty($data['picres_id'])){
                    //         $zsinduspics = Db::name('huamu_zsduopic')->where('admin_id',$admin_id)->field('id,img_url')->select();
                    //         if($zsinduspics){
                    //             foreach ($zsinduspics as $v){
                    //                 Db::name('huamu_zsduopic')->delete($v['id']);
                    //             }
                    //         }
                    //     }
                    // }
                    $value = array('status'=>1,'mess'=>'增加成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            $admin_id = session('admin_id');
            $zssjpics = Db::name('huamu_zspic_five')->where('admin_id',$admin_id)->find();
            if($zssjpics && $zssjpics['img_url']){
                Db::name('huamu_zspic_five')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                    @unlink('./'.$zssjpics['img_url']);
                }
            }
            
            $zsinduspics = Db::name('huamu_zsduopic_five')->where('admin_id',$admin_id)->field('id,img_url')->select();
            if($zsinduspics){
                foreach ($zsinduspics as $v){
                    Db::name('huamu_zsduopic_five')->delete($v['id']);
                    if($v['img_url'] && file_exists('./'.$v['img_url'])){
                        @unlink('./'.$v['img_url']);
                    }
                }
            }
            
            return $this->fetch();
        }
    }
    
    //处理多图片上传
    public function uploadifys(){
        $admin_id = session('admin_id');
    
        $nupload = Db::name('huamu_zsduopic_five')->where('admin_id',$admin_id)->count();
        if(input('post.ad_id')){
            $ycount = Db::name('ad_pic')->where('ad_id',input('post.ad_id'))->count();
            $uploadcount = $nupload+$ycount;
        }else{
            $uploadcount = $nupload;
        }
    
        if($uploadcount >= 6){
            $value = array('status'=>0,'msg'=>'图片最多上传6张');
            return json($value);
        }
    
        $file = request()->file('filedata');
        if($file){
            $info = aliyunOSS($_FILES);
//            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'ad_pic');
            if($info){
//                $original = 'uploads/ad_pic/'.$info->getSaveName();
                $original = $info['name'];
                $pic_id = Db::name('huamu_zsduopic_five')->insertGetId(array('img_url'=>$original,'admin_id'=>$admin_id));
                $picarr = array('pic_url'=>$original,'id'=>$pic_id);;
                $value = array('status'=>1, 'path'=>$picarr);
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
            $pics = Db::name('huamu_zsduopic_five')->where('id',$zspic_id)->where('admin_id',$admin_id)->find();
            if($pics && $pics['img_url']){
                $count = Db::name('huamu_zsduopic_five')->where('id',$pics['id'])->update(array('img_url'=>''));
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

    public function delete(){
        if(input('post.id')){
            $id = array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        
        if(!empty($id)){
            $count = Db::name('home_five')->where('id', 'in', $id)->delete();
            
            if($count){
                
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'删除失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }
    
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $admin_id = session('admin_id');
                $data = input('post.');
                $result = $this->validate($data,'WineHomeFive');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $ads = Db::name('home_five')->where('id',$data['id'])->find();
 
                    if($ads){
                                if(!empty($data['pic_id'])){
                                    $zssjpics = Db::name('huamu_zsduopic_five')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                                    if($zssjpics && $zssjpics['img_url']){
                                        $data['img'] = $zssjpics['img_url'];
                                    }else{
                                        $data['img'] = $ads['img'];
                                    }
                                }else{
                                    $data['img'] = $ads['img'];
                                }
                    unset($data['pic_id']);
                            // 启动事务
                            Db::startTrans();
                            try{
                                Db::name('home_five')->update($data);
                                
                                // 提交事务
                                Db::commit();
                                $value = array('status'=>1,'mess'=>'编辑成功');
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status'=>0,'mess'=>'编辑失败');
                            }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数');
            }
            return json($value);
        }else{
            if(input('id')){
                $id = input('id');
                $ads = Db::name('home_five')->find($id);
                if($ads){
                    $admin_id = session('admin_id');
                    $zssjpics = Db::name('huamu_zspic_five')->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        Db::name('huamu_zspic_five')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                            @unlink('./'.$zssjpics['img_url']);
                        }
                    }
                    
                    $zsinduspics = Db::name('huamu_zsduopic_five')->where('admin_id',$admin_id)->field('id,img_url')->select();
                    if($zsinduspics){
                        foreach ($zsinduspics as $v){
                            Db::name('huamu_zsduopic_five')->delete($v['id']);
                            if($v['img_url'] && file_exists('./'.$v['img_url'])){
                                @unlink('./'.$v['img_url']);
                            }
                        }
                    }
                    
                    // $posres = Db::name('pos')->field('id,pos_name,width,height')->order('id asc')->select();
                    // $adpicres = Db::name('ad_pic')->where('ad_id',$id)->order('sort asc')->select();
                    $this->assign('pnum', input('page'));
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    if(input('pos_id')){
                        $this->assign('pos_id', input('pos_id'));
                    }
                    $this->assign('posres',$posres);
                    $this->assign('adpicres',$adpicres);
                    $this->assign('ads',$ads);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
}