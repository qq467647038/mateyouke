<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;
use think\Loader;
use think\Validate;
use app\shop\model\Customer as CustomerMx;

class Customer extends Common{
    //栏目列表
    public function lst(){
        $shop_id = session('shopsh_id');
        $udata = Db::name('member')->where(['shop_id'=>$shop_id,'pid'=>0])->find();
        $list = Db::name('member')->where(['pid'=>$udata['id']])->order('id asc')->select();
        // $list = Db::name('chat_customer')->where(['is_delete'=>1])->order('id asc')->select();
        // foreach($list as $key=>&$value){
        //     $value['headimgurl'] = $this->webconfig['weburl'].'/'.$value['headimgurl'];
        // }
        $this->assign('list', $list);
        return $this->fetch();     
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        if($id){
            $customer_arr = db('member')->where(['id'=>$id])->find();
            if($customer_arr['checked'] == 1){
                $data['checked']=0;
            }else{
                $data['checked']=1;
            }
            $status = db('member')->where(['id'=>$id])->update($data);
            if($status){
                $result = 1;
            }else{
                $result = 0;
            }
        }else{
            $result = 0;
        }
        return $result;
    }


    //删除
    public function delete(){
        $id = input('get.id');
        $value = array('status'=>0,'mess'=>'不支持删除');
        // if($id){
        //     $is_delete = db('chat_customer')->where(['id'=>$id])->update(['is_delete'=>-1]);
        //     if($is_delete){
        //         $value = array('status'=>1,'mess'=>'删除成功');
        //     }else{
        //         $value = array('status'=>0,'mess'=>'系统错误');
        //     }
        // }else{
        //     $value = array('status'=>0,'mess'=>'系统错误');
        // }
        return json($value);
    }

    
    //处理上传图片
    public function uploadify(){
        $file = request()->file('filedata');
        if($file){
            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'customer');
            if($info){
                $getSaveName = str_replace("\\","/",$info->getSaveName());
                $original = 'uploads/customer/'.$getSaveName;
                $image = \think\Image::open('./'.$original);
                $image->thumb(350, 350)->save('./'.$original);
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



    //添加客服
    public function add(){
        if(request()->isAjax()){
            $shop_id = session('shopsh_id');
            $data = input('post.');
            $result = $this->validate($data,'Customer');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $data['password']=md5(md5($data['password']));
                $data['createtime']=time();
                $data['shop_id']=$shop_id;
                $result = db('chat_customer')->insertGetId($data);
                if($result){
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }




    //手动删除未保存的上传图片手机
    public function delfile(){
        $headimgurl = input('post.zspic_id');
        if($headimgurl){
            if($headimgurl && file_exists('./'.$headimgurl)){
                @unlink('./'.$headimgurl);
            }
            $value = 1;
        }else{
            $value = 0;
        }
        return json($value);
    }



    //编辑客服
    public function edit(){
        $id = input('param.id');
        if(request()->isAjax()){
            $data = input('post.');
            $validate = Loader::validate('Customer');
            $result = $validate->scene('edit')->check($data);
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$validate->getError());
            }else{
                if(!empty($data['password'])){
                    $data['password']=md5(md5($data['password']));
                }else{
                    unset($data['password']);
                }
                $result = db('chat_customer')->where(['id'=>$id])->update($data);
                if($result){
                    $value = array('status'=>1,'mess'=>'修改成功');
                }else{
                    $value = array('status'=>0,'mess'=>'没有做任何更改');
                }
            }
            return json($value);
        }else{
            $customer = db('chat_customer')->where(['id'=>$id])->find();
            $customer['wzheadimgurl']=$this->webconfig['weburl'].'/'.$customer['headimgurl'];
            $this->assign(
                [
                    'customer'=>$customer,
                ]
            );
            return $this->fetch();
        }
    }

}