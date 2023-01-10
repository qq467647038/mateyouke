<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Ad as AdMx;

class Ad extends Common{
    //广告列表
    public function lst(){
        $list = Db::name('ad')->alias('a')->field('a.id,a.ad_name,a.ad_type,a.is_on,b.pos_name')->join('sp_pos b','a.pos_id = b.id','LEFT')->order('a.id asc')->paginate(25);
        $page = $list->render();
        $posres = Db::name('pos')->field('id,pos_name,width,height')->order('id asc')->select();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign('pnum',$pnum);
        $this->assign('posres',$posres);
        $this->assign('list',$list);
        $this->assign('page',$page);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch();
        }
    }
    
    public function checkAdname(){
        if(request()->isAjax()){
            $ad_name = Db::name('ad')->where('ad_name',input('post.ad_name'))->find();
            if($ad_name){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }

    //根据广告位置获取广告列表
    public function poslist(){
        $id = input('pos_id');
        $pos_name = Db::name('pos')->where('id',$id)->value('pos_name');
        $posres = Db::name('pos')->field('id,pos_name,width,height')->order('id asc')->select();
        $list = Db::name('ad')->alias('a')->field('a.id,a.ad_name,a.ad_type,a.is_on,b.pos_name')->join('sp_pos b','a.pos_id = b.id','LEFT')->where('a.pos_id',$id)->order('a.id desc')->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
		
        $this->assign('pos_id',$id);
        $this->assign('pos_name',$pos_name);
        $this->assign('pnum',$pnum);
        $this->assign('posres',$posres);
        $this->assign('list',$list);
        $this->assign('page',$page);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    //修改广告状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $ads = Db::name('ad')->where('id',$data['id'])->find();
        if($ads){
            $count = Db::name('ad')->update($data);
            if($count > 0){
                if($value == 1){
                    // Db::name('ad')->where('pos_id',$ads['pos_id'])->where('id','neq',$ads['id'])->update(array('is_on'=>0));
                    ys_admin_logs('显示广告','ad',$id);
                }elseif($value == 0){
                    ys_admin_logs('隐藏广告','ad',$id);
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
    
    //处理上传图片
    public function uploadify(){
//        $admin_id = session('admin_id');
        $file = request()->file('filedata');
        if($file){
            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'ad_pic');
            if($info){
                $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
                if($zssjpics && $zssjpics['img_url']){
                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                        @unlink('./'.$zssjpics['img_url']);
                    }
                }
                $original = 'uploads/ad_pic/'.$info->getSaveName();
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
        if(input('post.ypic_id') && input('post.ad_id')){
            $pics = Db::name('ad_pic')->where('id',input('post.ypic_id'))->where('ad_id',input('post.ad_id'))->field('id,pic')->find();
            if($pics){
                $count = Db::name('ad_pic')->delete(input('post.ypic_id'));
                if($count > 0){
                    if(!empty($pics['pic']) && file_exists('./'.$pics['pic'])){
                        @unlink('./'.$pics['pic']);
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
    
    //添加广告
    public function add(){
        if(request()->isAjax()){
            $admin_id = session('admin_id');
            $data = input('post.');
            $result = $this->validate($data,'Ad');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if($data['ad_type'] == 1){
                    if(!empty($data['pic_id'])){
                        $zssjpics = Db::name('huamu_zsduopic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                        if(!empty($zssjpics) && !empty($zssjpics['img_url'])){
                            $data['ad_pic'] = $zssjpics['img_url'];
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传广告图片');
                            return json($value);
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'请上传广告图片');
                        return json($value);
                    }
                }
                
                // 启动事务
                Db::startTrans();
                try{
                    if($data['ad_type'] == 1){
                        $ad_id = Db::name('ad')->insertGetId(array('ad_name'=>$data['ad_name'],'ad_type'=>$data['ad_type'],'ad_pic'=>$data['ad_pic'],'ad_canshu'=>$data['ad_canshu'],'link_man'=>$data['link_man'],'link_phone'=>$data['link_phone'],'is_on'=>$data['is_on'],'pos_id'=>$data['pos_id']));
                    }elseif($data['ad_type'] == 2){
                        $ad_id = Db::name('ad')->insertGetId(array('ad_name'=>$data['ad_name'],'ad_type'=>$data['ad_type'],'link_man'=>$data['link_man'],'link_phone'=>$data['link_phone'],'is_on'=>$data['is_on'],'pos_id'=>$data['pos_id']));
                    }
                    if($ad_id && $data['ad_type'] == 2){
                        if(!empty($data['picres_id'])){
                            $canshu = $data['canshu'];
                            $sort2 = $data['sort2'];
                            foreach ($data['picres_id'] as $k => $v){
                                $img_url = Db::name('huamu_zsduopic')->where('id',$v)->where('admin_id',$admin_id)->value('img_url');
                                if($img_url){
                                    if(empty($sort2[$k])){
                                        $sort2[$k] = 0;
                                    }
                                    Db::name('ad_pic')->insert(array('pic'=>$img_url,'canshu'=>$canshu[$k],'sort'=>$sort2[$k],'ad_id'=>$ad_id));
                                }
                            }
                        }
                    }
                    
                    if($data['is_on'] == 1){
                        // Db::name('ad')->where('pos_id',$data['pos_id'])->where('id','neq',$ad_id)->update(array('is_on'=>0));
                    }
                
                    // 提交事务
                    Db::commit();
                    if($data['ad_type'] == 1){
                        if(!empty($zssjpics) && !empty($zssjpics['img_url'])){
                            Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        }
                    }
                    
                    if($data['ad_type'] == 2){
                        if(!empty($data['picres_id'])){
                            $zsinduspics = Db::name('huamu_zsduopic')->where('admin_id',$admin_id)->field('id,img_url')->select();
                            if($zsinduspics){
                                foreach ($zsinduspics as $v){
                                    Db::name('huamu_zsduopic')->delete($v['id']);
                                }
                            }
                        }
                    }
                    ys_admin_logs('新增广告','ad',$ad_id);
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
            
            $posres = Db::name('pos')->field('id,pos_name,width,height')->order('id asc')->select();
            $this->assign('posres',$posres);
            if(input('pos_id')){
                $this->assign('pos_id',input('pos_id'));
            }
            return $this->fetch();
        }
    }
    
    //编辑广告
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $admin_id = session('admin_id');
                $data = input('post.');
                $result = $this->validate($data,'Ad');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $ads = Db::name('ad')->where('id',$data['id'])->find();
                    if($ads){
                        if($ads['ad_type'] == $data['ad_type']){
                            if($ads['ad_type'] == 1){
                                if(!empty($data['pic_id'])){
                                    $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                                    if($zssjpics && $zssjpics['img_url']){
                                        $data['ad_pic'] = $zssjpics['img_url'];
                                    }else{
                                        $data['ad_pic'] = $ads['ad_pic'];
                                    }
                                }else{
                                    $data['ad_pic'] = $ads['ad_pic'];
                                }
                            }
                    
                            // 启动事务
                            Db::startTrans();
                            try{
                                if($data['ad_type'] == 1){
                                    Db::name('ad')->update(array('ad_name'=>$data['ad_name'],'ad_type'=>$data['ad_type'],'ad_pic'=>$data['ad_pic'],'ad_canshu'=>$data['ad_canshu'],'link_man'=>$data['link_man'],'link_phone'=>$data['link_phone'],'is_on'=>$data['is_on'],'pos_id'=>$data['pos_id'],'id'=>$data['id']));
                                }elseif($data['ad_type'] == 2){
                                    Db::name('ad')->update(array('ad_name'=>$data['ad_name'],'ad_type'=>$data['ad_type'],'link_man'=>$data['link_man'],'link_phone'=>$data['link_phone'],'is_on'=>$data['is_on'],'pos_id'=>$data['pos_id'],'id'=>$data['id']));
                                    if(!empty($data['ypic_id'])){
                                        $canshu = $data['canshu'];
                                        $sort2 = $data['sort2'];
                                        foreach ($data['ypic_id'] as $keypic => $valpic){
                                            if(empty($sort2[$keypic])){
                                                $sort2[$keypic] = 0;
                                            }
                                            $adpics = Db::name('ad_pic')->where('id',$valpic)->where('ad_id',$data['id'])->find();
                                            if($adpics){
                                                Db::name('ad_pic')->where('id',$valpic)->where('ad_id',$data['id'])->update(array('canshu'=>$canshu[$keypic],'sort'=>$sort2[$keypic]));
                                            }
                                        }
                                    }
                    
                                    if(!empty($data['picres_id'])){
                                        $duocanshu = $data['duocanshu'];
                                        $sort3 = $data['sort3'];
                                        foreach ($data['picres_id'] as $key => $val){
                                            $img_url = Db::name('huamu_zsduopic')->where('id',$val)->where('admin_id',$admin_id)->value('img_url');
                                            if($img_url){
                                                if(empty($sort3[$key])){
                                                    $sort3[$key] = 0;
                                                }
                                                Db::name('ad_pic')->insert(array('pic'=>$img_url,'canshu'=>$duocanshu[$key],'sort'=>$sort3[$key],'ad_id'=>$data['id']));
                                            }
                                        }
                                    }
                                }
                                
                                if($data['is_on'] == 1){
                                    // Db::name('ad')->where('pos_id',$data['pos_id'])->where('id','neq',$data['id'])->update(array('is_on'=>0));
                                }
                    
                                // 提交事务
                                Db::commit();
                                if($data['ad_type'] == 1){
                                    if(!empty($zssjpics) && !empty($zssjpics['img_url'])){
                                        Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                                        if($ads['ad_pic'] && file_exists('./'.$ads['ad_pic'])){
                                            @unlink('./'.$ads['ad_pic']);
                                        }
                                    }
                                }
                    
                                if($data['ad_type'] == 2){
                                    $zsinduspics = Db::name('huamu_zsduopic')->where('admin_id',$admin_id)->field('id,img_url')->select();
                                    if($zsinduspics){
                                        foreach ($zsinduspics as $v){
                                            Db::name('huamu_zsduopic')->delete($v['id']);
                                        }
                                    }
                                }
                    
                                ys_admin_logs('编辑广告','ad',$data['id']);
                                $value = array('status'=>1,'mess'=>'编辑成功');
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status'=>0,'mess'=>'编辑失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'广告类型错误');
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
                $ads = Db::name('ad')->find($id);
                if($ads){
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
                    
                    $posres = Db::name('pos')->field('id,pos_name,width,height')->order('id asc')->select();
                    $adpicres = Db::name('ad_pic')->where('ad_id',$id)->order('sort asc')->select();
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
    
    
    //删除广告
    public function delete(){
        if(input('post.id')){
            $id = array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        
        if(!empty($id)){
            if(is_array($id)){
                $delId = implode(',', $id);
                $arr = Db::name('ad')->where('id','in',$delId)->field('id,ad_type,ad_pic')->select();
                $count = AdMx::destroy($delId);
            }else{
                $arr2 = Db::name('ad')->where('id',$id)->field('id,ad_type,ad_pic')->find();
                $count = AdMx::destroy($id);
            }
            if($count > 0){
                if(is_array($id)){
                    if(!empty($arr)){
                        foreach ($arr as $v){
                            switch ($v['ad_type']){
                                case 0:
                                    if(!empty($v['ad_pic']) && file_exists('./'.$v['ad_pic'])){
                                        @unlink('./'.$v['ad_pic']);
                                    }
                                    break;
                                case 1:
                                    $picres = Db::name('ad_pic')->where('ad_id',$v['id'])->field('pic')->select();
                                    Db::name('ad_pic')->where('ad_id',$v['id'])->delete();
                                    if(!empty($picres)){
                                        foreach ($picres as $val){
                                            if(!empty($val['pic']) && file_exists('./'.$val['pic'])){
                                                @unlink('./'.$val['pic']);
                                            }
                                        }
                                    }
                                    break;
                            }
                        }
                    }
                    
                    foreach ($id as $v2){
                        ys_admin_logs('删除广告','ad',$v2);
                    }
                }else{
                    if(!empty($arr2)){
                        switch ($arr2['ad_type']){
                            case 0:
                                if(!empty($arr2['ad_pic']) && file_exists('./'.$arr2['ad_pic'])){
                                    @unlink('./'.$arr2['ad_pic']);
                                }
                                break;
                            case 1:
                                $picres =  Db::name('ad_pic')->where('ad_id',$arr2['id'])->field('pic')->select();
                                Db::name('ad_pic')->where('ad_id',$arr2['id'])->delete();
                                if(!empty($picres)){
                                    foreach ($picres as $val){
                                        if(!empty($val['pic']) && file_exists('./'.$val['pic'])){
                                            @unlink('./'.$val['pic']);
                                        }
                                    }
                                }
                                break;
                        }
                    }
                    
                    ys_admin_logs('删除广告','ad',$id);
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
    
    
    //搜索广告
    public function search(){
        if(input('post.keyword')){
            cookie('ad_name',input('post.keyword'),3600);
        }
        if(input('post.pos_id') != ''){
            cookie('pos_id',input('post.pos_id'),3600);
        }
        $posres = Db::name('pos')->field('id,pos_name,width,height')->order('id asc')->select();
        $where = array();
    
        if(cookie('ad_name')){
            $where['a.ad_name'] = array('like','%'.cookie('ad_name').'%');
        }
    
        if(cookie('pos_id') != ''){
            $pos_id = (int)cookie('pos_id');
            if($pos_id != 0){
                $where['a.pos_id'] = $pos_id;
            }
        }

        $list = Db::name('ad')->alias('a')->field('a.id,a.ad_name,a.ad_type,a.is_on,b.pos_name')->join('sp_pos b','a.pos_id = b.id','LEFT')->where($where)->order('a.id desc')->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('ad_name')){
            $this->assign('ad_name',cookie('ad_name'));
        }
        if(cookie('pos_id') != ''){
            $this->assign('pos_id',$pos_id);
        }
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('posres',$posres);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }    
}