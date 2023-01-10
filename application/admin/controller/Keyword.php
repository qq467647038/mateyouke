<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Keyword as KeywordMx;

class Keyword extends Common{
    //关键字回复管理
    public function lst(){
        $list = Db::name('Keyword')->order('id desc')->paginate(10);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum
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
            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'keyword');
            if($info){
                $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
                if($zssjpics && $zssjpics['img_url']){
                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                        @unlink('./'.$zssjpics['img_url']);
                    }
                }
                $date = date('Ymd',time());
                $original = 'public/uploads/keyword/'.$info->getSaveName();
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
    
    //处理多图片上传
    public function uploadifys(){
        $admin_id = session('admin_id');
        
        $nupload = Db::name('huamu_zsduopic')->where('admin_id',$admin_id)->count();
        if(input('post.kid')){
            $ycount = Db::name('key_duonew')->where('kid',input('post.kid'))->count();
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
            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'keyword');
            if($info){
                $date = date('Ymd');
                $original = 'public/uploads/keyword/'.$info->getSaveName();
                $image = \think\Image::open('./'.$original);
                $image->thumb(640, 400)->save('./'.$original);
                $pic_id = Db::name('huamu_zsduopic')->insertGetId(array('img_url'=>$original,'admin_id'=>$admin_id));
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
    
    //手动删除批量上传未提交的图片
    public function deletefile(){
        if(input('post.pic_id')){
            $pic_id = input('post.pic_id');
            $admin_id = session('admin_id');
            $img_url = Db::name('huamu_zsduopic')->where('id',$pic_id)->where('admin_id',$admin_id)->value('img_url');
            if($img_url){
                $count = Db::name('huamu_zsduopic')->delete($pic_id);
                if($count > 0){
                    if($img_url && file_exists('./'.$img_url)){
                        if(unlink('./'.$img_url)){
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
            }else{
                $value = 0;
            }
        }else{
            $value = 0;
        }
        return json($value);
    }
    
    public function deleteone(){
        if(input('post.ypic_id') && input('post.kid')){
            $pics = Db::name('key_duonew')->where('id',input('post.ypic_id'))->where('kid',input('post.kid'))->field('id,picurl')->find();
            if($pics){
                $count = Db::name('key_duonew')->delete(input('post.ypic_id'));
                if($count > 0){
                    if(!empty($pics['picurl']) && file_exists('./'.$pics['picurl'])){
                        @unlink('./'.$pics['picurl']);
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
    
    public function checkKeywordname(){
        if(request()->isAjax()){
            $arr = Db::name('keyword')->where('keyword_name',input('post.keyword_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
    
    public function add(){
        if(request()->isPost()){
            $data = input('post.');
            $admin_id = session('admin_id');
            $result = $this->validate($data,'Keyword');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if($data['key_type'] == 2){
                    if(!empty($data['pic_id'])){
                        $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                        if(!empty($zssjpics) && !empty($zssjpics['img_url'])){
                            $data['picurl'] = $zssjpics['img_url'];
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传缩略图');
                            return json($value);
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'请上传缩略图');
                        return json($value);
                    }
                }
                
                // 启动事务
                Db::startTrans();
                try{
                    if($data['key_type'] == 1){
                        $kid = Db::name('keyword')->insertGetId(array('keyword_name'=>$data['keyword_name'],'message'=>$data['message'],'key_type'=>$data['key_type']));
                    }else{
                        $kid = Db::name('keyword')->insertGetId(array('keyword_name'=>$data['keyword_name'],'key_type'=>$data['key_type']));
                    }
                    
                    if($kid){
                        if($data['key_type'] == 2){
                            if(!empty($zssjpics) && !empty($zssjpics['img_url'])){
                                Db::name('key_new')->insert(array('title'=>$data['title'],'description'=>$data['description'],'picurl'=>$data['picurl'],'url'=>$data['url'],'kid'=>$kid));
                            }
                        }elseif($data['key_type'] == 3){
                            if(!empty($data['picres_id'])){
                                $ntitle = $data['ntitle'];
                                $ndescription = $data['ndescription'];
                                $nurl = $data['nurl'];
                                $sort2 = $data['sort2'];
                                foreach ($data['picres_id'] as $k => $v){
                                    $img_url = Db::name('huamu_zsduopic')->where('id',$v)->where('admin_id',$admin_id)->value('img_url');
                                    if($img_url){
                                        if(empty($sort2[$k])){
                                            $sort2[$k] = 0;
                                        }
                                        Db::name('key_duonew')->insert(array('title'=>$ntitle[$k],'description'=>$ndescription[$k],'picurl'=>$img_url,'url'=>$nurl[$k],'sort'=>$sort2[$k],'kid'=>$kid));
                                    }
                                }
                            }
                        }
                    }
                
                    // 提交事务
                    Db::commit();
                    if($data['key_type'] == 2){
                        if(!empty($zssjpics) && !empty($zssjpics['img_url'])){
                            Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        }
                    }
                    
                    if($data['key_type'] == 3){
                        if(!empty($data['picres_id'])){
                            $zsinduspics = Db::name('huamu_zsduopic')->where('admin_id',$admin_id)->field('id,img_url')->select();
                            if($zsinduspics){
                                foreach ($zsinduspics as $v){
                                    Db::name('huamu_zsduopic')->delete($v['id']);
                                }
                            }
                        }
                    }
                    ys_admin_logs('新增关键字','keyword',$kid);
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
            $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
            if($zssjpics && $zssjpics['img_url']){
                Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                    @unlink('./'.$zssjpics['img_url']);
                }
            }
            
            $zsinduspics = Db::name('huamu_zsduopic')->where('admin_id',$admin_id)->field('id,img_url')->select();
            if($zsinduspics){
                foreach ($zsinduspics as $v){
                    Db::name('huamu_zsduopic')->delete($v['id']);
                    if($v['img_url'] && file_exists('./'.$v['img_url'])){
                        @unlink('./'.$v['img_url']);
                    }
                }
            }
            return $this->fetch();
        }
    }
    
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $admin_id = session('admin_id');
                $data = input('post.');
                $result = $this->validate($data,'Keyword');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $keywords = Db::name('keyword')->where('id',$data['id'])->find();
                    if($keywords){
                        if($keywords['key_type'] == $data['key_type']){
                            if($data['key_type'] == 2){
                                $kns = Db::name('key_new')->where('kid',$data['id'])->field('id,picurl')->find();
                                if($kns){
                                    if(!empty($data['pic_id'])){
                                        $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                                        if($zssjpics && $zssjpics['img_url']){
                                            $data['picurl'] = $zssjpics['img_url'];
                                        }else{
                                            $data['picurl'] = $kns['picurl'];
                                        }
                                    }else{
                                        $data['picurl'] = $kns['picurl'];
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'信息错误');
                                    return json($value);
                                }
                            }
                    
                            // 启动事务
                            Db::startTrans();
                            try{
                                if($data['key_type'] == 1){
                                    Db::name('keyword')->update(array('keyword_name'=>$data['keyword_name'],'message'=>$data['message'],'key_type'=>$data['key_type'],'id'=>$data['id']));
                                }else{
                                    Db::name('keyword')->update(array('keyword_name'=>$data['keyword_name'],'key_type'=>$data['key_type'],'id'=>$data['id']));
                                    if($data['key_type'] == 2){
                                        Db::name('key_new')->update(array('title'=>$data['title'],'description'=>$data['description'],'picurl'=>$data['picurl'],'url'=>$data['url'],'id'=>$kns['id']));
                                    }elseif($data['key_type'] == 3){
                                        if(!empty($data['ypic_id'])){
                                            $ntitle = $data['ntitle'];
                                            $ndescription = $data['ndescription'];
                                            $nurl = $data['nurl'];
                                            $sort2 = $data['sort2'];
                                            foreach ($data['ypic_id'] as $keypic => $valpic){
                                                if(empty($sort2[$keypic])){
                                                    $sort2[$keypic] = 0;
                                                }
                                                $knpics = Db::name('key_duonew')->where('id',$valpic)->where('kid',$data['id'])->find();
                                                if($knpics){
                                                    Db::name('key_duonew')->where('id',$valpic)->where('kid',$data['id'])->update(array('title'=>$ntitle[$keypic],'description'=>$ndescription[$keypic],'url'=>$nurl[$keypic],'sort'=>$sort2[$keypic]));
                                                }
                                            }
                                        }
                    
                                        if(!empty($data['picres_id'])){
                                            $duotitle = $data['duotitle'];
                                            $duodescription = $data['duodescription'];
                                            $duourl = $data['duourl'];
                                            $sort3 = $data['sort3'];
                                            foreach ($data['picres_id'] as $key => $val){
                                                $img_url = Db::name('huamu_zsduopic')->where('id',$val)->where('admin_id',$admin_id)->value('img_url');
                                                if($img_url){
                                                    if(empty($sort3[$key])){
                                                        $sort3[$key] = 0;
                                                    }
                                                    Db::name('key_duonew')->insert(array('title'=>$duotitle[$key],'description'=>$duodescription[$key],'picurl'=>$img_url,'url'=>$duourl[$key],'sort'=>$sort3[$key],'kid'=>$data['id']));
                                                }
                                            }
                                        }
                                    }
                                }
                    
                                // 提交事务
                                Db::commit();
                                if($data['key_type'] == 2){
                                    if(!empty($zssjpics) && !empty($zssjpics['img_url'])){
                                        Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                                        if($kns['picurl'] && file_exists('./'.$kns['picurl'])){
                                            @unlink('./'.$kns['picurl']);
                                        }
                                    }
                                }
                    
                                if($data['key_type'] == 3){
                                    $zsinduspics = Db::name('huamu_zsduopic')->where('admin_id',$admin_id)->field('id,img_url')->select();
                                    if($zsinduspics){
                                        foreach ($zsinduspics as $v){
                                            Db::name('huamu_zsduopic')->delete($v['id']);
                                        }
                                    }
                                }
                                ys_admin_logs('编辑关键字','keyword',$data['id']);
                                $value = array('status'=>1,'mess'=>'编辑成功');
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status'=>0,'mess'=>'编辑失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'回复类型错误');
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
                $keys = Db::name('keyword')->where('id',input('id'))->find();
                if($keys){
                    $admin_id = session('admin_id');
                    $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                            @unlink('./'.$zssjpics['img_url']);
                        }
                    }
                    
                    $zsinduspics = Db::name('huamu_zsduopic')->where('admin_id',$admin_id)->field('id,img_url')->select();
                    if($zsinduspics){
                        foreach ($zsinduspics as $v){
                            Db::name('huamu_zsduopic')->delete($v['id']);
                            if($v['img_url'] && file_exists('./'.$v['img_url'])){
                                @unlink('./'.$v['img_url']);
                            }
                        }
                    }
                    
                    if($keys['key_type'] == 2){
                        $kns = Db::name('key_new')->where('kid',input('id'))->find();
                        if(!empty($kns)){
                            $this->assign('kns',$kns);
                        }
                    }elseif($keys['key_type'] == 3){
                        $knres = Db::name('key_duonew')->where('kid',input('id'))->order('sort asc')->select();
                        if(!empty($knres)){
                            $this->assign('knres',$knres);
                        }
                    }
                    if(input('s')){
                        $this->assign('search',input('s'));
                    }
                    $this->assign('pnum',input('page'));
                    $this->assign('keys',$keys);
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
        if(input('post.id')){
            $id = array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        
        if(!empty($id)){
            if(is_array($id)){
                $delId = implode(',', $id);
                $arr = Db::name('keyword')->where('id','in',$delId)->field('id,key_type')->select();
                $count = KeywordMx::destroy($delId);
            }else{
                $arr2 = Db::name('keyword')->where('id',$id)->field('id,key_type')->find();
                $count = KeywordMx::destroy($id);
            }
            
            if($count > 0){
                if(is_array($id)){
                    if(!empty($arr)){
                        foreach ($arr as $v){
                            if($v['key_type'] == 2){
                                $pic = Db::name('key_new')->where('kid',$v['id'])->field('picurl')->find();
                                Db::name('key_new')->where('kid',$v['id'])->delete();
                                if(!empty($pic['picurl']) && file_exists('./'.$pic['picurl'])){
                                    @unlink('./'.$pic['picurl']);
                                }
                            }elseif($v['key_type'] == 3){
                                $pic = Db::name('key_duonew')->where('kid',$v['id'])->field('picurl')->select();
                                Db::name('key_duonew')->where('kid',$v['id'])->delete();
                                if(!empty($pic)){
                                    foreach ($pic as $val){
                                        if(!empty($val['picurl']) && file_exists('./'.$val['picurl'])){
                                            @unlink('./'.$val['picurl']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    foreach ($id as $v2){
                        ys_admin_logs('删除关键字','keyword',$v2);
                    }
                }else{
                    if(!empty($arr2)){
                        if($arr2['key_type'] == 2){
                            $pic = Db::name('key_new')->where('kid',$arr2['id'])->field('picurl')->find();
                            Db::name('key_new')->where('kid',$arr2['id'])->delete();
                            if(!empty($pic['picurl']) && file_exists('./'.$pic['picurl'])){
                                @unlink('./'.$pic['picurl']);
                            }
                        }elseif($arr2['key_type'] == 3){
                            $pic = Db::name('key_duonew')->where('kid',$arr2['id'])->field('picurl')->select();
                            Db::name('key_duonew')->where('kid',$arr2['id'])->delete();
                            if(!empty($pic)){
                                foreach ($pic as $val){
                                    if(!empty($val['picurl']) && file_exists('./'.$val['picurl'])){
                                        @unlink('./'.$val['picurl']);
                                    }
                                }
                            }
                        }
                    }
                    
                    ys_admin_logs('删除关键字','keyword',$id);
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
    
    public function search(){
        if(input('post.keyword') != ''){
            cookie('keyword_name',input('post.keyword'),7200);
        }
        $where = array();
        if(cookie('keyword_name')){
            $where['keyword_name'] = array('like','%'.cookie('keyword_name').'%');
        }
        $list = Db::name('keyword')->where($where)->order('id desc')->paginate(2);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('keyword_name')){
            $this->assign('keyword_name',cookie('keyword_name'));
        }
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
}