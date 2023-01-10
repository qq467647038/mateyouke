<?php

namespace app\admin\controller;

use app\admin\controller\Common;

use think\Db;

use app\admin\model\Link as LinkMx;



class Version extends Common{

    

    public function lst(){

        $list = Db::name('app_versions')->order('create_time DESC')->select();

        $this->assign('list',$list);// 赋值数据集

        return $this->fetch();

    }



    public function add(){

        if(request()->isPost()) {

            $data = input('post.');

            if(empty($data['versions'])){

                $versions = array('status'=>0,'mess'=>'请上传版本号');

                return json($versions);

            }

            if(empty($data['urls'])){

                $urls = array('status'=>0,'mess'=>'请上传安装包');

                return json($urls);

            }

            unset($data['fileselect']);

            $data['create_time']=time();

            $result = db('app_versions')->insert($data);

            if($result){

                $value = array('status'=>1,'mess'=>'增加成功');

            }else{

                $value = array('status'=>0,'mess'=>'增加失败');

            }

            return json($value);

        }else{

            return $this->fetch();

        }

    }





    public function delete(){

        $id = input('id');

        if(!empty($id)){

            $result = db('app_versions')->delete($id);

            if($result){

                $value = array('status'=>1,'mess'=>'删除成功');

            }else{

                $value = array('status'=>0,'mess'=>'删除失败');

            }

        }

        return $value;

    }











    //上传apk的包

//处理上传图片

    public function uploadify(){

        $admin_id = session('admin_id');

        $file = request()->file('filedata');

        if($file){

            $info = $file->validate(['size'=>52428800,'ext'=>'apk,png'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apkversion');

            if($info){

                $getSaveName = str_replace("\\","/",$info->getSaveName());

                $original = 'public/uploads/apkversion/'.$getSaveName;

                $value = array('status'=>1,'path'=>$this->webconfig['weburl'].'/'.'uploads/android.jpg','filepath'=>$original);

            }else{

                $value = array('status'=>0,'msg'=>$file->getError());

            }

        }else{

            $value = array('status'=>0,'msg'=>'文件不存在');

        }

        return json($value);

    }

    // 删除文件
    public function delFile(){
        $path = input('post.urls');
        if(!$path){
            return array('status'=>0,'msg'=>'文件路径不存在，删除失败！');
        }
        $filePath = ROOT_PATH . $path;

        if (file_exists($filePath)) {
            $res = unlink($filePath);//删除文件
            if($res){
                return array('status'=>1,'msg'=>'删除成功！');
            }else{
                return array('status'=>1,'msg'=>'删除失败！');
            }
        }else{
            return array('status'=>0,'msg'=>'文件路径错误，删除失败！');
        }

    }







}