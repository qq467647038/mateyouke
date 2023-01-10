<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\PayType as PayTypeMx;

class PayType extends Common{
    
    public function lst(){
        $list = Db::name('pay_type')->where('is_show',1)->order('sort asc')->select();
        $this->assign('list',$list);// 赋值数据集
        return $this->fetch();
    }
    
    public function checkPayname(){
        if(request()->isAjax()){
            $arr = Db::name('pay_type')->where('pay_name',input('post.pay_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
    
    public function checkOnlyname(){
        if(request()->isAjax()){
            $arr = Db::name('pay_type')->where('only_name',input('post.only_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $count = Db::name('pay_type')->where('id',$id)->update($data);
        if($count > 0){
            if($value == 1){
                ys_admin_logs('开启支付方式','pay_type',$id);
            }elseif($value == 0){
                ys_admin_logs('关闭支付方式','pay_type',$id);
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
            $info = aliyunOSS($_FILES);
//            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'pay_type');
            if($info){
//                $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
//                if($zssjpics && $zssjpics['img_url']){
//                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
//                    if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
//                        @unlink('./'.$zssjpics['img_url']);
//                    }
//                }
//                $getSaveName = str_replace("\\","/",$info->getSaveName());
//                $original = 'uploads/pay_type/'.$getSaveName;
                $original = $info['name'];
//                $image = \think\Image::open('./'.$original);
//                $image->thumb(350, 350)->save('./'.$original);
//                if($zssjpics){
//                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>$original));
//                    $zspic_id = $zssjpics['id'];
//                }else{
//                    $zspic_id = Db::name('huamu_zspic')->insertGetId(array('admin_id'=>$admin_id,'img_url'=>$original));
//                }
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
    
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $admin_id = session('admin_id');
            $result = $this->validate($data,'PayType');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(!empty($data['pic_id'])){
                    $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        $data['pay_pic'] = $zssjpics['img_url'];
                        
                        $paytype = new PayTypeMx();
                        $paytype->data($data);
                        $lastId = $paytype->allowField(true)->save();
                        if($lastId){
                            if(isset($zssjpics) && $zssjpics['img_url']){
                                Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                            }
                            ys_admin_logs('新增支付方式','pay_type',$paytype->id);
                            $value = array('status'=>1,'mess'=>'增加成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'增加失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'请上传缩略图，增加失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'请上传缩略图，增加失败');
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
            return $this->fetch();
        }
    }
    
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                $admin_id = session('admin_id');
                $result = $this->validate($data,'PayType');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $types = Db::name('pay_type')->where('id',$data['id'])->find();
                    if($types){
                        if(!empty($data['pic_id'])){
                            $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                            if($zssjpics && $zssjpics['img_url']){
                                $data['pay_pic'] = $zssjpics['img_url'];
                            }else{
                                $data['pay_pic'] = $types['pay_pic'];
                            }
                        }else{
                            $data['pay_pic'] = $types['pay_pic'];
                        }
                        
                        $paytype = new PayTypeMx();
                        $count = $paytype->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            if(!empty($zssjpics) && $zssjpics['img_url']){
                                Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                                if($types['pay_pic'] && file_exists('./'.$types['pay_pic'])){
                                    @unlink('./'.$types['pay_pic']);
                                }
                            }
                            
                            ys_admin_logs('编辑支付方式','pay_type',$data['id']);
                            $value = array('status'=>1,'mess'=>'编辑成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'编辑失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $types = Db::name('pay_type')->find(input('id'));
                if($types){
                    $admin_id = session('admin_id');
                    $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                            @unlink('./'.$zssjpics['img_url']);
                        }
                    }
                    
                    $this->assign('types', $types);
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
        if(input('id') && !is_array(input('id'))){
            $id = input('id');
            $pay_pic = Db::name('pay_type')->where('id',$id)->value('pay_pic');
            $count = PayTypeMx::destroy($id);
            if($count > 0){
                if(!empty($pay_pic) && file_exists('./'.$pay_pic)){
                    @unlink('./'.$pay_pic);
                }
                ys_admin_logs('删除支付方式','pay_type',$id);
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'编辑失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }
    
    //排序
    public function order(){
        $thcate = new PayTypeMx();
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                $thcate->save($data2,array('id'=>$data2['id']));
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
            ys_admin_logs('更新支付方式排序','thcate',1);
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }    
    
}
